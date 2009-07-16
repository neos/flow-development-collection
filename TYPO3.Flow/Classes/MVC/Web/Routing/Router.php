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
 * The default web router
 *
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
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

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
	 * The current request. Will be set in route()
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object factory
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Injects the system logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
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
		$this->request = $request;
		$requestPath = $this->request->getRequestPath();
		$matchResults = $this->findMatchResults($requestPath);
		if ($matchResults !== NULL) {
			$this->setControllerKeysAndFormat($matchResults);
			foreach ($matchResults as $argumentName => $argumentValue) {
				if ($argumentName[0] !== '@') {
					$this->request->setArgument($argumentName, $argumentValue);
				}
			}
		}
		$this->setControllerKeysAndFormat($this->request->getArguments());
	}

	/**
	 * Sets package key, subpackage key, controller name, action name and format of the current request.
	 *
	 * @param array $arguments
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see \F3\FLOW3\MVC\Web\Request
	 * @api
	 */
	protected function setControllerKeysAndFormat(array $arguments) {
		foreach($arguments as $argumentName => $argumentValue) {
			switch ($argumentName) {
				case '@package' :
					$this->request->setControllerPackageKey($argumentValue);
				break;
				case '@subpackage' :
					$this->request->setControllerSubpackageKey($argumentValue);
				break;
				case '@controller' :
					$this->request->setControllerName($argumentValue);
				break;
				case '@action' :
					$this->request->setControllerActionName($argumentValue);
				break;
				case '@format' :
					$this->request->setFormat($argumentValue);
				break;
			}
		}
	}

	/**
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param string $requestPath The request path
	 * @return array results of the matching route
	 * @see route()
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function findMatchResults($requestPath) {
		$this->createRoutesFromConfiguration();

		foreach ($this->routes as $route) {
			if ($route->matches($requestPath)) {
				$matchResults = $route->getMatchResults();
				$this->systemLogger->log('Router route(): Route "' . $route->getName() . '" matched the request path "' . $requestPath . '".', LOG_DEBUG);
				return $matchResults;
			}
		}
		$this->systemLogger->log('Router route(): No route matched the request path "' . $requestPath . '".', LOG_NOTICE);
		return NULL;
	}

	/**
	 * Builds the corresponding uri (excluding protocol and host) by iterating
	 * through all configured routes and calling their respective resolves()
	 * method. If no matching route is found, an empty string is returned.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolve(array $routeValues) {
		$this->createRoutesFromConfiguration();

		foreach ($this->routes as $route) {
			if ($route->resolves($routeValues)) {
				return $route->getMatchingURI();
			}
		}
		return '';
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
			foreach ($this->routesConfiguration as $routeConfiguration) {
				$route = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\Route');
				if (isset($routeConfiguration['name'])) {
					$route->setName($routeConfiguration['name']);
				}
				$route->setUriPattern($routeConfiguration['uriPattern']);
				if (isset($routeConfiguration['defaults'])) $route->setDefaults($routeConfiguration['defaults']);
				if (isset($routeConfiguration['routeParts'])) $route->setRoutePartsConfiguration($routeConfiguration['routeParts']);
				$this->routes[] = $route;
			}
			$this->routesCreated = TRUE;
		}
	}
}
?>
