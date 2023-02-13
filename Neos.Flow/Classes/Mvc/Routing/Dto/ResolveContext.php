<?php
namespace Neos\Flow\Mvc\Routing\Dto;

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
use Psr\Http\Message\UriInterface;

/**
 * Simple DTO wrapping the values required for a Router::resolve() call
 *
 * @Flow\Proxy(false)
 */
final class ResolveContext
{

    /**
     * The currently requested URI, required to fill in parts of the result when resolving absolute URIs
     *
     * @var UriInterface
     */
    private $baseUri;

    /**
     * Route values to build the URI, for example ['@action' => 'index', 'someArgument' => 'foo', ...]
     *
     * @var array
     */
    private $routeValues;

    /**
     * Whether or not an absolute URI is to be returned
     *
     * @var bool
     */
    private $forceAbsoluteUri;

    /**
     * A prefix to be prepended to any resolved URI
     *
     * @var string
     */
    private $uriPathPrefix;

    /**
     * @var RouteParameters
     */
    private $parameters;

    /**
     * @param UriInterface $baseUri The base URI, retrieved from the current request URI or from configuration, if specified. Required to fill in parts of the result when resolving absolute URIs
     * @param array $routeValues Route values to build the URI, for example ['@action' => 'index', 'someArgument' => 'foo', ...]
     * @param bool $forceAbsoluteUri Whether or not an absolute URI is to be returned
     * @param string $uriPathPrefix A prefix to be prepended to any resolved URI. Not allowed to start with "/".
     * @param RouteParameters $parameters
     */
    public function __construct(UriInterface $baseUri, array $routeValues, bool $forceAbsoluteUri, string $uriPathPrefix, RouteParameters $parameters)
    {
        $this->baseUri = $baseUri;
        $this->routeValues = $routeValues;
        $this->forceAbsoluteUri = $forceAbsoluteUri;
        $this->uriPathPrefix = $uriPathPrefix;
        $this->parameters = $parameters;

        if (strpos($this->uriPathPrefix, '/') === 0) {
            throw new \InvalidArgumentException('UriPathPrefix "' . $uriPathPrefix . '" is not allowed to start with "/".', 1570187176);
        }
    }

    /**
     * @return UriInterface
     */
    public function getBaseUri(): UriInterface
    {
        return $this->baseUri;
    }

    /**
     * @return array
     */
    public function getRouteValues(): array
    {
        return $this->routeValues;
    }

    /**
     * @return bool
     */
    public function isForceAbsoluteUri(): bool
    {
        return $this->forceAbsoluteUri;
    }

    /**
     * @return string
     */
    public function getUriPathPrefix(): string
    {
        return $this->uriPathPrefix;
    }

    /**
     * @return RouteParameters
     */
    public function getParameters(): RouteParameters
    {
        return $this->parameters;
    }
}
