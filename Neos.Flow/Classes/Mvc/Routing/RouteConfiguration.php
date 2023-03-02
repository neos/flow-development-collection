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
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;

/**
 * The default router configuration
 *
 * @Flow\Scope("singleton")
 */
final class RouteConfiguration
{
    /**
     * Array of routes to match against
     *
     * @var Routes
     */
    private Routes $routes;

    /**
     * true if route object have been created, otherwise false
     *
     * @var boolean
     */
    private bool $routesCreated = false;

    public function __construct(
        private readonly ConfigurationManager $configurationManager
    ) {
    }

    /**
     * Returns a list of configured routes
     *
     * @return Routes
     */
    public function getRoutes(): Routes
    {
        if ($this->routesCreated === false) {
            $this->createRoutesFromConfiguration();
        }
        return $this->routes;
    }

    /**
     * Creates \Neos\Flow\Mvc\Routing\Route objects from the injected routes
     * configuration.
     *
     * @return void
     * @throws InvalidRouteSetupException
     */
    protected function createRoutesFromConfiguration(): void
    {
        $routesConfiguration = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_ROUTES);
        $routes = [];
        $routesWithHttpMethodConstraints = [];
        foreach ($routesConfiguration as $routeConfiguration) {
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
            $routes = $route;
        }
        $this->routes = Routes::fromArray($routes);

        $this->emitRoutesCreated($this->routes);

        $this->routesCreated = true;
    }


    /**
     * Signals that all Routes.yaml routes have been configured
     *
     * @Flow\Signal
     * @param Routes $routes
     * @return void
     */
    protected function emitRoutesCreated(Routes $routes): void
    {
    }
}
