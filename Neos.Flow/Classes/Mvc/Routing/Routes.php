<?php

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Traversable;

/**
 * @Flow\Proxy(false)
 */
final class Routes implements \IteratorAggregate
{
    /**
     * @var array<int, Route>
     */
    private readonly array $routes;

    private function __construct(
        Route ...$routes
    ) {
        $this->routes = $routes;
    }

    public static function fromConfiguration(array $configuration): self
    {
        $routes = self::empty();
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
            $routes->append($route);
        }
    }

    public static function empty(): self
    {
        return new self();
    }
    public function prepend(Route $route): self
    {
        return new self($route, ...$this->routes);
    }
    public function append(Route $route): self
    {
        return new self(...$this->routes, $route);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->routes);
    }
}
