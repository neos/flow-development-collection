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

/**
 * @Flow\Proxy(false)
 */
final class Parameters implements CacheAwareInterface
{

    /**
     * @var array in the format ['<namespace>' => ['<param-name>' => <Parameter>, ...], ...]
     */
    private $parameters = [];

    /**
     * @param array
     */
    private function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public static function create(): self
    {
        return new static([]);
    }

    public function withParameter(string $namespace, Parameter $parameter): self
    {
        $newParameters = $this->parameters;
        $newParameters[$namespace][$parameter->getName()] = $parameter;
        return new static($newParameters);
    }

    public function has(string $namespace, string $parameterName)
    {
        return isset($this->parameters[$namespace][$parameterName]);
    }

    /**
     * @param string $namespace
     * @param string $parameterName
     * @return mixed|null
     */
    public function getValue(string $namespace, string $parameterName)
    {
        return $this->has($namespace, $parameterName) ? $this->parameters[$namespace][$parameterName]->getValue() : null;
    }

    public function getCacheEntryIdentifier(): string
    {
        $cacheIdentifierParts = [];
        /** @var Parameter[] $namespaceParameters */
        foreach($this->parameters as $namespace => $namespaceParameters) {
            foreach($namespaceParameters as $parameter) {
                $cacheIdentifierParts[] = $namespace . ':' . $parameter->getCacheEntryIdentifier();
            }
        }
        return md5(implode('|', $cacheIdentifierParts));
    }

}
