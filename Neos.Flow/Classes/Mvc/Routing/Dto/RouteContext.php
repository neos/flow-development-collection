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
use Neos\Flow\Http\Request as HttpRequest;

/**
 * @Flow\Proxy(false)
 */
final class RouteContext
{

    /**
     * @var HttpRequest
     */
    private $httpRequest;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param HttpRequest $httpRequest
     * @param array $parameters
     */
    public function __construct(HttpRequest $httpRequest, array $parameters)
    {
        $this->httpRequest = $httpRequest;
        $this->parameters = $parameters;
    }

    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    /**
     * @param string $namespace
     * @return Parameter[]
     */
    public function getParameters(string $namespace): array
    {
        return $this->parameters[$namespace] ?? [];
    }

    /**
     * @param string $namespace
     * @param Parameter $parameter
     * @return self
     */
    public function withParameter(string $namespace, Parameter $parameter): self
    {
        $newParameters = $this->parameters;
        $newParameters[$namespace][$parameter->getName()] = $parameter;
        return new static($this->httpRequest, $newParameters);
    }

}
