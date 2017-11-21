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
final class RoutingContext implements CacheAwareInterface
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

    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    public function hasParameter(string $namespace, string $parameterName): bool
    {
        return $this->parameters->has($namespace, $parameterName);
    }

    /**
     * @param string $namespace
     * @param string $parameterName
     * @return array|string|float|int|bool|CacheAwareInterface|null
     */
    public function getParameterValue(string $namespace, string $parameterName)
    {
        return $this->parameters->getValue($namespace, $parameterName);
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->cacheEntryIdentifier;
    }
}
