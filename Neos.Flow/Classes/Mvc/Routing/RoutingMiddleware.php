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
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Package\PackageManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A routing HTTP component
 */
class RoutingMiddleware implements MiddlewareInterface
{
    /**
     * @Flow\Inject
     * @var RouterInterface
     */
    protected $router;

    /**
     * @Flow\Inject
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * Resolve a route for the request
     *
     * Stores the resolved route values in the HTTP request attributes to pass them
     * to other components. They can be accessed via ServerRequestInterface::getAttribute(ServerRequestAttributes::ROUTING_RESULTS);
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $parameters = $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS);
        if ($parameters === null) {
            $parameters = RouteParameters::createEmpty();
        }
        $routeContext = new RouteContext($request, $parameters);

        try {
            $matchResults = $this->router->route($routeContext);
        } catch (NoMatchingRouteException $exception) {
            $matchResults = null;
        }

        if (isset($matchResults['@package'])) {
            $matchResults['@package'] = $this->packageManager->getCaseSensitivePackageKey($matchResults['@package']);
        }

        return $next->handle($request->withAttribute(ServerRequestAttributes::ROUTING_RESULTS, $matchResults));
    }
}
