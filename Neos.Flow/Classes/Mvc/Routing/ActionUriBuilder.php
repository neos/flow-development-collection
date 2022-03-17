<?php
declare(strict_types=1);
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
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\Action;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Psr\Http\Message\UriInterface;

/**
 * @Flow\Proxy(false)
 */
final class ActionUriBuilder
{
    private function __construct(
        private RouterInterface $router,
        private UriInterface $baseUri,
        private RouteParameters $routeParameters,
    ) {
    }

    public static function fromRouterAndBaseUriAndRouteParameters(RouterInterface $router, UriInterface $baseUri, RouteParameters $routeParameters): self
    {
        return new self($router, $baseUri, $routeParameters);
    }

    public function withAddedRouteParameters(RouteParameters $routeParameters): self
    {
        $newRouteParameters = $this->routeParameters;
        foreach ($routeParameters as $parameterName => $parameterValue) {
            $newRouteParameters = $newRouteParameters->withParameter($parameterName, $parameterValue);
        }
        return new self($this->router, $this->baseUri, $newRouteParameters);
    }

    /**
     * @throws NoMatchingRouteException
     */
    public function uriFor(Action $action): UriInterface
    {
        return $this->router->resolve(new ResolveContext($this->baseUri, $action->toRouteValues(), false, ltrim($this->baseUri->getPath(), '\/'), $this->routeParameters));
    }

    /**
     * @throws NoMatchingRouteException
     */
    public function absoluteUriFor(Action $action): UriInterface
    {
        return $this->router->resolve(new ResolveContext($this->baseUri, $action->toRouteValues(), true, ltrim($this->baseUri->getPath(), '\/'), $this->routeParameters));
    }
}
