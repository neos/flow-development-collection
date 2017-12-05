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
    private $requestUri;

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
     * @param UriInterface $requestUri The currently requested URI, required to fill in parts of the result when resolving absolute URIs
     * @param array $routeValues Route values to build the URI, for example ['@action' => 'index', 'someArgument' => 'foo', ...]
     * @param bool $forceAbsoluteUri Whether or not an absolute URI is to be returned
     * @param string $uriPathPrefix A prefix to be prepended to any resolved URI
     */
    public function __construct(UriInterface $requestUri, array $routeValues, bool $forceAbsoluteUri, string $uriPathPrefix = '')
    {
        $this->requestUri = $requestUri;
        $this->routeValues = $routeValues;
        $this->forceAbsoluteUri = $forceAbsoluteUri;
        $this->uriPathPrefix = $uriPathPrefix;
    }

    /**
     * @return UriInterface
     */
    public function getRequestUri(): UriInterface
    {
        return $this->requestUri;
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
}
