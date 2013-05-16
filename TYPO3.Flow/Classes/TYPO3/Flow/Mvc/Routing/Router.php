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
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException;
use TYPO3\Flow\Mvc\Exception\NoMatchingRouteException;

/**
 * The default web router
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Router implements RouterInterface {

	/**
	 * @Flow\Inject
	 * @var SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @Flow\Inject
	 * @var RouterCachingService
	 */
	protected $routerCachingService;

	/**
	 * Array containing the configuration for all routes
	 *
	 * @var array
	 */
	protected $routesConfiguration = array();

	/**
	 * Array of routes to match against
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * TRUE if route object have been created, otherwise FALSE
	 *
	 * @var boolean
	 */
	protected $routesCreated = FALSE;

	/**
	 * @var Route
	 */
	protected $lastMatchedRoute;

	/**
	 * @var Route
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
	 * Iterates through all configured routes and calls matches() on them.
	 * Returns the matchResults of the matching route or NULL if no matching
	 * route could be found.
	 *
	 * @param Request $httpRequest The web request to be analyzed. Will be modified by the router.
	 * @return array The results of the matching route or NULL if no route matched
	 */
	public function route(Request $httpRequest) {
		$cachedMatchResults = $this->routerCachingService->getCachedMatchResults($httpRequest);
		if ($cachedMatchResults !== FALSE) {
			return $cachedMatchResults;
		}
		$this->lastMatchedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->matches($httpRequest) === TRUE) {
				$this->lastMatchedRoute = $route;
				$matchResults = $route->getMatchResults();
				if ($matchResults !== NULL) {
					$this->routerCachingService->storeMatchResults($httpRequest, $matchResults);
				}
				return $matchResults;
			}
		}
		return NULL;
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
	 * @param Route $route
	 * @return void
	 */
	public function addRoute(Route $route) {
		$this->createRoutesFromConfiguration();
		array_unshift($this->routes, $route);
	}

	/**
	 * Builds the corresponding uri (excluding protocol and host) by iterating
	 * through all configured routes and calling their respective resolves()
	 * method. If no matching route is found, an empty string is returned.
	 * Note: calls of this message are cached by RouterCachingAspect
	 *
	 * @param array $routeValues Key/value pairs to be resolved. E.g. array('@package' => 'MyPackage', '@controller' => 'MyController');
	 * @return string
	 * @throws NoMatchingRouteException
	 */
	public function resolve(array $routeValues) {
		$cachedResolvedUriPath = $this->routerCachingService->getCachedResolvedUriPath($routeValues);
		if ($cachedResolvedUriPath !== FALSE) {
			return $cachedResolvedUriPath;
		}

		$this->lastResolvedRoute = NULL;
		$this->createRoutesFromConfiguration();

		/** @var $route Route */
		foreach ($this->routes as $route) {
			if ($route->resolves($routeValues)) {
				$this->lastResolvedRoute = $route;
				$resolvedUriPath = $route->getResolvedUriPath();
				if ($resolvedUriPath !== NULL) {
					$this->routerCachingService->storeResolvedUriPath($resolvedUriPath, $routeValues);
				}
				return $resolvedUriPath;
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
	 * Creates \TYPO3\Flow\Mvc\Routing\Route objects from the injected routes
	 * configuration.
	 *
	 * @return void
	 * @throws InvalidRouteSetupException
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
}
