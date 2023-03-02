<?php

namespace Neos\Flow\Mvc\Routing;

use Neos\Flow\Annotations as Flow;
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

    public static function fromArray($routes): self
    {
        return new self(...$routes);
    }

    public static function empty(): self
    {
        return new self(...[]);
    }

    public function prepend(Route $route): self
    {
        $routes = $this->routes;
        array_unshift($routes, $route);
        return self::fromArray($routes);
    }

    public function append(Route $route): self
    {
        $routes = $this->routes;
        $routes[] = $route;
        return self::fromArray($routes);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->routes);
    }

}
