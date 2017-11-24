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
 * @Flow\Proxy(false)
 */
final class RouteContext implements CacheAwareInterface
{

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var Parameters
     */
    private $parameters;

    /**
     * @var string
     */
    private $cacheEntryIdentifier;

    /**
     * @param HttpRequest $httpRequest
     * @param Parameters $parameters
     */
    public function __construct(HttpRequest $httpRequest, Parameters $parameters)
    {
        $this->httpRequest = $httpRequest;
        $this->parameters = $parameters;
        $this->cacheEntryIdentifier = md5(sprintf('host:%s|path:%s|method:%s|parameters:%s',
            $httpRequest->getUri()->getHost(),
            $httpRequest->getRelativePath(),
            $httpRequest->getMethod(),
            $parameters->getCacheEntryIdentifier()
        ));
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    /**
     * @return Parameters
     */
    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->cacheEntryIdentifier;
    }

}
