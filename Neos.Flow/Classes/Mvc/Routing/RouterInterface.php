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

use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Psr\Http\Message\UriInterface;

/**
 * Contract for a Web Router
 */
interface RouterInterface
{
    /**
     * Iterates through all configured routes and calls matches() on them.
     * Returns the matchResults of the matching route.
     *
     * @param RouteContext $routeContext The Route Context containing the current HTTP Request and, optional, Routing RouteParameters
     * @return array The results of the matching route
     * @throws NoMatchingRouteException if no route matched the $routeContext
     */
    public function route(RouteContext $routeContext): array;

    /**
     * Walks through all configured routes and calls their respective resolves-method.
     * When a matching route is found, the corresponding URI is returned.
     *
     * @param ResolveContext $resolveContext The Resolve Context containing the route values, the request URI and some flags to be resolved
     * @return UriInterface The resolved Uri
     * @throws NoMatchingRouteException if no route could resolve the given $resolveContext
     */
    public function resolve(ResolveContext $resolveContext): UriInterface;
}
