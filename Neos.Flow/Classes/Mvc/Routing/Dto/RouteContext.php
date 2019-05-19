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

use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Request as HttpRequest;

/**
 * Simple DTO wrapping the values required for a Router::route() call
 *
 * @Flow\Proxy(false)
 */
final class RouteContext implements CacheAwareInterface
{

    /**
     * The current HTTP request
     *
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * Routing RouteParameters
     *
     * @var RouteParameters
     */
    private $parameters;

    /**
     * @var string
     */
    private $cacheEntryIdentifier;

    /**
     * @param HttpRequest $httpRequest The current HTTP request
     * @param RouteParameters $parameters Routing RouteParameters
     */
    public function __construct(HttpRequest $httpRequest, RouteParameters $parameters)
    {
        $this->httpRequest = $httpRequest;
        $this->parameters = $parameters;
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    /**
     * @return RouteParameters
     */
    public function getParameters(): RouteParameters
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        if ($this->cacheEntryIdentifier === null) {
            $this->cacheEntryIdentifier = md5(sprintf('host:%s|path:%s|method:%s|parameters:%s',
                $this->httpRequest->getUri()->getHost(),
                $this->httpRequest->getRelativePath(),
                $this->httpRequest->getMethod(),
                $this->parameters->getCacheEntryIdentifier()
            ));
        }
        return $this->cacheEntryIdentifier;
    }
}
