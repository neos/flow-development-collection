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
final class ResolveResult
{

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var array
     */
    private $parameters;

    public function __construct(UriInterface $uri, array $parameters)
    {
        $this->uri = $uri;
        $this->parameters = $parameters;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * @param string $namespace
     * @return Parameter[]
     */
    public function getParameters(string $namespace): array
    {
        return $this->parameters[$namespace] ?? [];
    }


    public function withUri(UriInterface $uri): self {
        return new static($uri, $this->parameters);
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
        return new static($this->uri, $newParameters);
    }
}
