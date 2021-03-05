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
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\Helper\UriHelper;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * The default web router
 *
 * @Flow\Scope("singleton")
 * @api
 */
class Router implements RouterInterface
{
    /**
     * @Flow\Inject(name="Neos.Flow:SystemLogger")
     * @var LoggerInterface
     */
    protected $logger;

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
     * true if route object have been created, otherwise false
     *
     * @var boolean
     */
    protected $routesCreated = false;

    /**
     * @var Route
     */
    protected $lastMatchedRoute;

    /**
     * @var Route
     */
    protected $lastResolvedRoute;

    /**
     * Injects the (system) logger based on PSR-3.
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function injectLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

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

    /**
     * Iterates through all configured routes and calls matches() on them.
     * Returns the matchResults of the matching route or NULL if no matching
     * route could be found.
     *
     * @param RouteContext $routeContext The Route Context containing the current HTTP Request and, optional, Routing RouteParameters
     * @return array The results of the matching route or NULL if no route matched
     * @throws InvalidRouteSetupException
     * @throws NoMatchingRouteException if no route matched the given $routeContext
     * @throws InvalidRoutePartValueException
     */
    public function route(RouteContext $routeContext): array
    {
        $this->lastMatchedRoute = null;
        $cachedRouteResult = $this->routerCachingService->getCachedMatchResults($routeContext);
        if ($cachedRouteResult !== false) {
            return $cachedRouteResult;
        }
        $this->createRoutesFromConfiguration();
        $httpRequest = $routeContext->getHttpRequest();

        /** @var $route Route */
        foreach ($this->routes as $route) {
            if ($route->matches($routeContext) === true) {
                $this->lastMatchedRoute = $route;
                $matchResults = $route->getMatchResults();
                $this->routerCachingService->storeMatchResults($routeContext, $matchResults, $route->getMatchedTags());
                $this->logger->debug(sprintf('Router route(): Route "%s" matched the request "%s (%s)".', $route->getName(), $httpRequest->getUri(), $httpRequest->getMethod()));
                return $matchResults;
            }
        }
        $routePath = UriHelper::getRelativePath(RequestInformationHelper::generateBaseUri($httpRequest), $httpRequest->getUri());
        $this->logger->debug(sprintf('Router route(): No route matched the route path "%s".', $routePath));
        throw new NoMatchingRouteException('Could not match a route for the HTTP request.', 1510846308);
    }

    /**
     * Returns the route that has been matched with the last route() call.
     * Returns NULL if no route matched or route() has not been called yet
     *
     * @return Route
     */
    public function getLastMatchedRoute()
    {
        return $this->lastMatchedRoute;
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
     * @param ResolveContext $resolveContext The Resolve Context containing the route values, the request URI and some flags to be resolved
     * @return UriInterface The resolved URI
     * @throws NoMatchingRouteException if no route could resolve the given $resolveContext
     */
    public function resolve(ResolveContext $resolveContext): UriInterface
    {
        $this->lastResolvedRoute = null;
        $cachedResolvedUriConstraints = $this->routerCachingService->getCachedResolvedUriConstraints($resolveContext);
        if ($cachedResolvedUriConstraints !== false) {
            return $cachedResolvedUriConstraints->applyTo($resolveContext->getBaseUri(), $resolveContext->isForceAbsoluteUri());
        }

        $this->createRoutesFromConfiguration();

        /** @var $route Route */
        foreach ($this->routes as $route) {
            if ($route->resolves($resolveContext) === true) {
                $uriConstraints = $route->getResolvedUriConstraints()->withPathPrefix($resolveContext->getUriPathPrefix());
                $resolvedUri = $uriConstraints->applyTo($resolveContext->getBaseUri(), $resolveContext->isForceAbsoluteUri());
                $this->routerCachingService->storeResolvedUriConstraints($resolveContext, $uriConstraints, $route->getResolvedTags());
                $this->lastResolvedRoute = $route;
                return $resolvedUri;
            }
        }
        $this->logger->warning('Router resolve(): Could not resolve a route for building an URI for the given resolve context.', LogEnvironment::fromMethodName(__METHOD__) + ['routeValues' => $resolveContext->getRouteValues()]);
        throw new NoMatchingRouteException('Could not resolve a route and its corresponding URI for the given parameters. This may be due to referring to a not existing package / controller / action while building a link or URI. Refer to log and check the backtrace for more details.', 1301610453);
    }

    /**
     * Returns the route that has been resolved with the last resolve() call.
     * Returns NULL if no route was found or resolve() has not been called yet
     *
     * @return Route
     */
    public function getLastResolvedRoute()
    {
        return $this->lastResolvedRoute;
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
