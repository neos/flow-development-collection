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
    ){
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

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->routes);
    }

}
