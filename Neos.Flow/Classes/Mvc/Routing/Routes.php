<?php

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Traversable;

/**
 * @Flow\Proxy(false)
 * @implements \IteratorAggregate<int, Route>
 */
final class Routes implements \IteratorAggregate
{
    /**
     * @var array<int, Route>
     */
    private array $routes;

    public function __construct(
        Route ...$routes
    ) {
        $this->routes = $routes;
    }

    public static function fromConfiguration(array $configuration): self
    {
        $routes = [];
        $routesWithHttpMethodConstraints = [];
        foreach ($configuration as $routeConfiguration) {
            $route = Route::fromConfiguration($routeConfiguration);

            $uriPattern = $route->getUriPattern();

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
            $routes[] = $route;
        }
        return new self(...$routes);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function withPrependedRoute(Route $route): self
    {
        return new self($route, ...$this->routes);
    }

    public function withAppendedRoute(Route $route): self
    {
        return new self(...[...$this->routes, $route]);
    }

    /**
     * @return \ArrayIterator<int, Route>
     */
    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->routes);
    }
}
