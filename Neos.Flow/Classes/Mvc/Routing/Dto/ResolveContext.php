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
 * @Flow\Proxy(false)
 */
final class ResolveContext
{

    /**
     * @var UriInterface
     */
    private $requestUri;

    /**
     * @var array
     */
    private $routeValues;

    /**
     * @var bool
     */
    private $forceAbsoluteUri;

    /**
     * @var string
     */
    private $uriPathPrefix;

    /**
     * @param UriInterface $requestUri
     * @param array $routeValues
     * @param bool $forceAbsoluteUri
     * @param string $uriPathPrefix
     */
    public function __construct(UriInterface $requestUri, array $routeValues, bool $forceAbsoluteUri, string $uriPathPrefix = '')
    {
        $this->requestUri = $requestUri;
        $this->routeValues = $routeValues;
        $this->forceAbsoluteUri = $forceAbsoluteUri;
        $this->uriPathPrefix = $uriPathPrefix;
    }

    public function getRequestUri(): UriInterface
    {
        return $this->requestUri;
    }

    public function getRouteValues(): array
    {
        return $this->routeValues;
    }

    public function isForceAbsoluteUri(): bool
    {
        return $this->forceAbsoluteUri;
    }

    public function getUriPathPrefix(): string
    {
        return $this->uriPathPrefix;
    }
}
