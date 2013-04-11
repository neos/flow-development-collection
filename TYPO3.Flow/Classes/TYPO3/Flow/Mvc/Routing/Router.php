<?php
namespace TYPO3\Flow\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;
use TYPO3\Flow\Utility\Arrays;

/**
 * The default web router
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Router implements RouterInterface {

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 * @Flow\Inject
	 */
	protected $systemLogger;

	/**
	 * @var string
	 */
	protected $controllerObjectNamePattern = '@package\@subpackage\Controller\@controllerController';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 */
	protected $objectManager;

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
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $actionRequest;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\Route
	 */
	protected $lastMatchedRoute;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\Route
	 */
	protected $lastResolvedRoute;

	/**
	 * Sets the routes configuration.
	 *
	 * @param array $routesConfiguration The routes configuration
	 * @return void
	 */
	public function setRoutesConfiguration(array $routesConfiguration) {
		$this->routesConfiguration = $routesConfiguration;
		$this->routesCreated = FALSE;
	}

	/**
	 * Routes the specified web request by setting the controller name, action and possible
	 * parameters. If the request could not be routed, it will be left untouched.
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest The web request to be analyzed. Will be modified by the router.
	 * @return \TYPO3\Flow\Mvc\ActionRequest
	 */
	public function route(Request $httpRequest) {
		$this->actionRequest = $httpRequest->createActionRequest();

		$matchResults = $this->findMatchResults($httpRequest);
		if ($matchResults !== NULL) {
			$requestArguments = $this->actionRequest->getArguments();
			$mergedArguments = Arrays::arrayMergeRecursiveOverrule($requestArguments, $matchResults);
			$this->actionRequest->setArguments($mergedArguments);
		}
		$this->setDefaultControllerAndActionNameIfNoneSpecified();
		return $this->actionRequest;
	}

	/**
	 * Returns the route that has been matched with the last route() call.
	 * Returns NULL if no route matched or route() has not been called yet
	 *
	 * @return Route
	 */
	public function getLastMatchedRoute() {
		return $this->lastMatchedRoute;
	}

	/**
	 * Returns a list of configured routes
	 *
	 * @return array
	 */
	public function getRoutes() {
		$this->createRoutesFromConfiguration();
		return $this->routes;
	}

	/**
	 * Manually adds a route to the beginning of the configured routes
	 *
	 * @param \TYPO3\Flow\Mvc\Routing\Route $route
	 * @return void
	 */
	public function addRoute(Route $route) {
		$this->createRoutesFromConfiguration();
		array_unshift($this->routes, $route);
	}

	/**
	 * Set the default controller and action names if none has been specified.
	 *
	 * @return void
	 */
	protected function setDefaultControllerAndActionNameIfNoneSpecified() {
		if ($this->actionRequest->getControllerName() === NULL) {
			$this->actionRequest->setControllerName('Standard');
		}
		if ($this->actionRequest->getControllerActionName() === NULL) {
			$this->actionRequest->setControllerActionName('index');
		}
	}

	/**
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param \TYPO3\Flow\Http\Request $httpRequest
	 * @return array results of the matching route
	 * @see route()
	 */
	protected function findMatchResults(Request $httpRequest) {
		$this->lastMatchedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->matches($httpRequest) === TRUE) {
				$this->lastMatchedRoute = $route;
				return $route->getMatchResults();
			}
		}
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
	 * @throws \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
	 */
	public function resolve(array $routeValues) {
		$this->lastResolvedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->resolves($routeValues)) {
				$this->lastResolvedRoute = $route;
				return $route->getMatchingUri();
			}
		}
		$this->systemLogger->log('Router resolve(): Could not resolve a route for building an URI for the given route values.', LOG_WARNING, $routeValues);
		throw new NoMatchingRouteException('Could not resolve a route and its corresponding URI for the given parameters. This may be due to referring to a not existing package / controller / action while building a link or URI. Refer to log and check the backtrace for more details.', 1301610453);
	}

	/**
	 * Returns the route that has been resolved with the last resolve() call.
	 * Returns NULL if no route was found or resolve() has not been called yet
	 *
	 * @return Route
	 */
	public function getLastResolvedRoute() {
		return $this->lastResolvedRoute;
	}

	/**
	 * Creates TYPO3\Flow\Mvc\Routing\Route objects from the injected routes
	 * configuration.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException
	 */
	protected function createRoutesFromConfiguration() {
		if ($this->routesCreated === FALSE) {
			$this->routes = array();
			$routesWithHttpMethodConstraints = array();
			foreach ($this->routesConfiguration as $routeConfiguration) {
				$route = new Route();
				if (isset($routeConfiguration['name'])) {
					$route->setName($routeConfiguration['name']);
				}
				$uriPattern = $routeConfiguration['uriPattern'];
				$route->setUriPattern($uriPattern);
				if (isset($routeConfiguration['defaults'])) {
					$route->setDefaults($routeConfiguration['defaults']);
				}
				if (isset($routeConfiguration['routeParts'])) {
					$route->setRoutePartsConfiguration($routeConfiguration['routeParts']);
				}
				if (isset($routeConfiguration['toLowerCase'])) {
					$route->setLowerCase($routeConfiguration['toLowerCase']);
				}
				if (isset($routeConfiguration['appendExceedingArguments'])) {
					$route->setAppendExceedingArguments($routeConfiguration['appendExceedingArguments']);
				}
				if (isset($routeConfiguration['httpMethods'])) {
					if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === FALSE) {
						throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678427);
					}
					$routesWithHttpMethodConstraints[$uriPattern] = TRUE;
					$route->setHttpMethods($routeConfiguration['httpMethods']);
				} else {
					if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === TRUE) {
						throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678432);
					}
					$routesWithHttpMethodConstraints[$uriPattern] = FALSE;
				}
				$this->routes[] = $route;
			}
			$this->routesCreated = TRUE;
		}
	}

	/**
	 * Returns the object name of the controller defined by the package, subpackage key and
	 * controller name
	 *
	 * @param string $packageKey the package key of the controller
	 * @param string $subPackageKey the subpackage key of the controller
	 * @param string $controllerName the controller name excluding the "Controller" suffix
	 * @return string The controller's Object Name or NULL if the controller does not exist
	 * @api
	 */
	public function getControllerObjectName($packageKey, $subPackageKey, $controllerName) {
		$possibleObjectName = $this->controllerObjectNamePattern;
		$possibleObjectName = str_replace('@package', str_replace('.', '\\', $packageKey), $possibleObjectName);
		$possibleObjectName = str_replace('@subpackage', $subPackageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $controllerName, $possibleObjectName);
		$possibleObjectName = str_replace('\\\\', '\\', $possibleObjectName);

		$controllerObjectName = $this->objectManager->getCaseSensitiveObjectName($possibleObjectName);
		return ($controllerObjectName !== FALSE) ? $controllerObjectName : NULL;
	}
}
?>
