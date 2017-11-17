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

use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteResult;
use Psr\Http\Message\UriInterface;

/**
 * Contract for a Web Router
 */
interface RouterInterface
{
    /**
     * Iterates through all configured routes and calls matches() on them.
     *
     * @param RouteContext $routeContext
     * @return RouteResult
     */
    public function route(RouteContext $routeContext): RouteResult;

    /**
     * Walks through all configured routes and calls their respective resolves-method.
     *
     * @param ResolveContext $resolveContext
     * @return UriInterface
     */
    public function resolve(ResolveContext $resolveContext): UriInterface;
}
