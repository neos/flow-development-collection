<?php
namespace Neos\Flow\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Request;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RoutingContext;
use Psr\Http\Message\UriInterface;

/**
 * The default web router
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Router implements RouterInterface
{
    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

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
    protected $routesConfiguration = null;

    /**
     * Array of routes to match against
     *
     * @var array
     */
    protected $routes = [];

    /**
     * TRUE if route object have been created, otherwise FALSE
     *
     * @var boolean
     */
    protected $routesCreated = false;

    /**
     * @var RoutingContext
     */
    protected $context;

    /**
     * Sets the routes configuration.
     *
     * @param array $routesConfiguration The routes configuration or NULL if it should be fetched from configuration
     * @return void
     */
    public function setRoutesConfiguration(array $routesConfiguration = null)
    {
        $this->routesConfiguration = $routesConfiguration;
        $this->routesCreated = false;
    }

    public function setContext(RoutingContext $context)
    {
        $this->context = $context;
        $this->routesCreated = false;
    }

    /**
     * Iterates through all configured routes and calls matches() on them.
     * Returns the matchResults of the matching route or NULL if no matching
     * route could be found.
     *
     * @param Request $httpRequest The web request to be analyzed. Will be modified by the router.
     * @return array The results of the matching route or NULL if no route matched
     * @throws NoMatchingRouteException
     */
    public function route(Request $httpRequest): array
    {
        $cachedRouteResult = $this->routerCachingService->getCachedMatchResults($this->context);
        if ($cachedRouteResult !== false) {
            return $cachedRouteResult;
        }
        $this->createRoutesFromConfiguration();

        /** @var $route Route */
        foreach ($this->routes as $route) {
            if ($route->matches($httpRequest) === true) {
                $matchResults = $route->getMatchResults();
                if ($matchResults !== null) {
                    $this->routerCachingService->storeMatchResults($this->context, $matchResults);
                }
                $this->systemLogger->log(sprintf('Router route(): Route "%s" matched the request "%s (%s)".', $route->getName(), $httpRequest->getUri(), $httpRequest->getMethod()), LOG_DEBUG);
                return $route->getMatchResults();
            }
        }
        $this->systemLogger->log(sprintf('Router route(): No route matched the route path "%s".', $httpRequest->getRelativePath()), LOG_NOTICE);
        throw new NoMatchingRouteException('Could not match a route for the HTTP request.', 1510846308);
    }

    /**
     * Returns a list of configured routes
     *
     * @return array
     */
    public function getRoutes()
    {
        $this->createRoutesFromConfiguration();
        return $this->routes;
    }

    /**
     * Manually adds a route to the beginning of the configured routes
     *
     * @param Route $route
     * @return void
     */
    public function addRoute(Route $route)
    {
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
     * @return UriInterface
     * @throws NoMatchingRouteException
     */
    public function resolve(array $routeValues): UriInterface
    {
        $cachedResolvedUri = $this->routerCachingService->getCachedResolvedUri($this->context, $routeValues);
        if ($cachedResolvedUri !== false) {
            return $cachedResolvedUri;
        }

        $this->createRoutesFromConfiguration();

        /** @var $route Route */
        foreach ($this->routes as $route) {
            if ($route->resolves($routeValues) === true) {
                $resolveUri = $route->getResolvedUri();
                $this->routerCachingService->storeResolvedUri($this->context, $resolveUri, $routeValues);
                return $resolveUri;
            }
        }
        $this->systemLogger->log('Router resolve(): Could not resolve a route for building an URI for the given resolve context.', LOG_WARNING, $routeValues);
        throw new NoMatchingRouteException('Could not resolve a route and its corresponding URI for the given parameters. This may be due to referring to a not existing package / controller / action while building a link or URI. Refer to log and check the backtrace for more details.', 1301610453);
    }

    /**
     * Creates \Neos\Flow\Mvc\Routing\Route objects from the injected routes
     * configuration.
     *
     * @return void
     * @throws InvalidRouteSetupException
     */
    protected function createRoutesFromConfiguration()
    {
        if ($this->routesCreated === true) {
            return;
        }
        $this->initializeRoutesConfiguration();
        $this->routes = [];
        $routesWithHttpMethodConstraints = [];
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
                if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === false) {
                    throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678427);
                }
                $routesWithHttpMethodConstraints[$uriPattern] = true;
                $route->setHttpMethods($routeConfiguration['httpMethods']);
            } else {
                if (isset($routesWithHttpMethodConstraints[$uriPattern]) && $routesWithHttpMethodConstraints[$uriPattern] === true) {
                    throw new InvalidRouteSetupException(sprintf('There are multiple routes with the uriPattern "%s" and "httpMethods" option set. Please specify accepted HTTP methods for all of these, or adjust the uriPattern', $uriPattern), 1365678432);
                }
                $routesWithHttpMethodConstraints[$uriPattern] = false;
            }
            if ($this->context !== null) {
                $route->setContext($this->context);
            }
            $this->routes[] = $route;
        }
        $this->routesCreated = true;
    }

    /**
     * Checks if a routes configuration was set and otherwise loads the configuration from the configuration manager.
     *
     * @return void
     */
    protected function initializeRoutesConfiguration()
    {
        if ($this->routesConfiguration === null) {
            $this->routesConfiguration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
        }
    }

}
