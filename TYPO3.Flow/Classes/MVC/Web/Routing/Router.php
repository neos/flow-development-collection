<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * The default web router
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Router implements \F3\FLOW3\MVC\Web\Routing\RouterInterface {

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * The FLOW3 configuration
	 * @var \F3\FLOW3\Configuration\Container
	 */
	protected $configuration;

	/**
	 * Array containing the configuration for all routes.
	 * @var array
	 */
	protected $routesConfiguration = array();

	/**
	 * Array of routes to match against
	 * @var array
	 */
	protected $routes = array();

	/**
	 * TRUE if route object have been created, otherwise FALSE
	 * @var boolean
	 */
	protected $routesCreated = FALSE;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory A reference to the object factory
	 * @param \F3\FLOW3\Utility\Environment $environment A reference to the environment
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Object\ManagerInterface $objectManager, \F3\FLOW3\Object\FactoryInterface $objectFactory, \F3\FLOW3\Utility\Environment $environment) {
		$this->objectManager = $objectManager;
		$this->objectFactory = $objectFactory;
		$this->environment = $environment;
	}

	/**
	 * Sets the routes configuration.
	 *
	 * @param array $configuration The routes configuration
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRoutesConfiguration(array $routesConfiguration) {
		$this->routesConfiguration = $routesConfiguration;
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param \F3\FLOW3\MVC\Web\Request $request The web request to be analyzed. Will be modified by the router.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function route(\F3\FLOW3\MVC\Web\Request $request) {
		$requestPath = substr($request->getRequestURI()->getPath(), strlen((string)$request->getBaseURI()->getPath()));
		if (substr($requestPath, 0, 5) === 'index' && strpos($requestPath, '.php/')) {
			$requestPath = ltrim(strstr($requestPath, '/'), '/');
		}
		$matchResults = $this->findMatchResults($requestPath);
		if ($matchResults !== NULL) {
			foreach ($matchResults as $argumentName => $argumentValue) {
				if ($argumentName[0] == '@') {
					switch ($argumentName) {
						case '@package' :
							$request->setControllerPackageKey($argumentValue);
						break;
						case '@subpackage' :
							$request->setControllerSubpackageKey($argumentValue);
						break;
						case '@controller' :
							$request->setControllerName($argumentValue);
						break;
						case '@action' :
							$request->setControllerActionName($argumentValue);
						break;
						case '@format' :
							$request->setFormat($argumentValue);
						break;
					}
				} else {
					$request->setArgument($argumentName, $argumentValue);
				}
			}
		}
		$this->setArgumentsFromRawRequestData($request);
	}

	/**
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 *
	 * @param string $request The request path
	 * @return array results of the matching route
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findMatchResults($requestPath) {
		$this->createRoutesFromConfiguration();

		foreach (array_reverse($this->routes) as $route) {
			if ($route->matches($requestPath)) {
				$matchResults = $route->getMatchResults();
				$this->emitRouteMatched($route->getName(), $matchResults);
				return $matchResults;
			}
		}
		return NULL;
	}

	/**
	 * Builds the corresponding uri (excluding protocol and host) by iterating
	 * through all configured routes and calling their respective resolves()
	 * method. If no matching route is found, an empty string is returned.
	 *
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolve(array $routeValues) {
		$this->createRoutesFromConfiguration();

		foreach (array_reverse($this->routes) as $route) {
			if ($route->resolves($routeValues)) {
				return $route->getMatchingURI();
			}
		}
		return '';
	}

	/**
	 * Emits the signal that a route matched and was chosen
	 *
	 * @param string $routeName Name of the route which matched
	 * @param array $arguments an array with evaluated arguments
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	public function emitRouteMatched($routeName, array $arguments) {
	}

	/**
	 * Creates F3\FLOW3\MVC\Web\Routing\Route objects from the injected routes
	 * configuration.
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function createRoutesFromConfiguration() {
		if ($this->routesCreated === FALSE) {
			$this->routes = array();
			foreach ($this->routesConfiguration as $routeName => $routeConfiguration) {
				$route = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\Route');
				$route->setName($routeName);
				$route->setUriPattern($routeConfiguration['uriPattern']);
				if (isset($routeConfiguration['defaults'])) $route->setDefaults($routeConfiguration['defaults']);
				if (isset($routeConfiguration['routePartHandlers'])) $route->setRoutePartHandlers($routeConfiguration['routePartHandlers']);
				$this->routes[$routeName] = $route;
			}
			$this->routesCreated = TRUE;
		}
	}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @param \F3\FLOW3\MVC\Web\Request $request The web request which will contain the arguments
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setArgumentsFromRawRequestData(\F3\FLOW3\MVC\Web\Request $request) {
		foreach ($request->getRequestURI()->getArguments() as $argumentName => $argumentValue) {
			$request->setArgument($argumentName, $argumentValue);
		}
		switch ($request->getMethod()) {
			case 'POST' :
				foreach ($this->environment->getRawPOSTArguments() as $argumentName => $argumentValue) {
					$request->setArgument($argumentName, $argumentValue);
				}
			break;
			case 'PUT' :
				$putArguments = array();
				parse_str(file_get_contents("php://input"), $putArguments);
				foreach ($putArguments as $argumentName => $argumentValue) {
					$request->setArgument($argumentName, $argumentValue);
				}
			break;
		}
	}
}
?>
