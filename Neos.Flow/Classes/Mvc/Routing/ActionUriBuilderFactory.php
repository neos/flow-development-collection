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
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * @Flow\Scope("singleton")
 */
final class ActionUriBuilderFactory
{
    private array $instances = [];

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    private function createFromBaseUriAndRouteParameters(UriInterface $baseUri, RouteParameters $routeParameters): ActionUriBuilder
    {
        $runtimeCacheKey = md5((string)$baseUri) . '|' . $routeParameters->getCacheEntryIdentifier();
        if (!isset($this->instances[$runtimeCacheKey])) {
            $this->instances[$runtimeCacheKey] = ActionUriBuilder::fromRouterAndBaseUriAndRouteParameters($this->router, $baseUri, $routeParameters);
        }
        return $this->instances[$runtimeCacheKey];
    }

    public function createFromBaseUri(UriInterface $baseUri): ActionUriBuilder
    {
        return $this->createFromBaseUriAndRouteParameters($baseUri, RouteParameters::createEmpty());
    }

    public function createFromHttpRequest(ServerRequestInterface $request): ActionUriBuilder
    {
        return $this->createFromBaseUriAndRouteParameters(RequestInformationHelper::generateBaseUri($request), $request->getAttribute(ServerRequestAttributes::ROUTING_PARAMETERS) ?? RouteParameters::createEmpty());
    }

    public function createFromActionRequest(ActionRequest $actionRequest): ActionUriBuilder
    {
        return $this->createFromHttpRequest($actionRequest->getHttpRequest());
    }
}
