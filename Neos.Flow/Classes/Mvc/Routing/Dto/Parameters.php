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
use Neos\Utility\TypeHandling;

/**
 * @Flow\Proxy(false)
 */
final class Parameters implements CacheAwareInterface
{

    /**
     * @var string[]
     */
    private $parameters = [];

    /**
     * @param string[] $parameters
     */
    private function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public static function createEmpty(): self
    {
        return new static([]);
    }

    /**
     * @param string $parameterName
     * @param bool|float|int|string|CacheAwareInterface $parameterValue
     * @return Parameters
     */
    public function withParameter(string $parameterName, $parameterValue): self
    {
        if (!TypeHandling::isLiteral(gettype($parameterValue)) && (!$parameterValue instanceof CacheAwareInterface)) {
            throw new \InvalidArgumentException(sprintf('Parameter values must be literal types or implement the CacheAwareInterface, given: "%s"', is_object($parameterValue) ? get_class($parameterValue) : gettype($parameterValue)), 1511194273);
        }
        $newParameters = $this->parameters;
        $newParameters[$parameterName] = $parameterValue;
        return new static($newParameters);
    }

    public function has(string $parameterName)
    {
        return isset($this->parameters[$parameterName]);
    }

    /**
     * @param string $parameterName
     * @return mixed|null
     */
    public function getValue(string $parameterName)
    {
        return $this->parameters[$parameterName] ?? null;
    }

    public function getCacheEntryIdentifier(): string
    {
        $cacheIdentifierParts = [];
        foreach ($this->parameters as $parameterName => $parameterValue) {
            $cacheIdentifierParts[] = $parameterName . ':' . ($parameterValue instanceof CacheAwareInterface ? $parameterValue->getCacheEntryIdentifier() : (string)$parameterValue);
        }
        return md5(implode('|', $cacheIdentifierParts));
    }
}
