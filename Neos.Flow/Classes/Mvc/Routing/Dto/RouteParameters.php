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
 * This class allows the whole routing behavior to be parametrized.
 * Route Part implementations can react to given parameters and adjust their matching behavior accordingly
 * if they implement ParameterAwareRoutePartInterface
 *
 * Routing RouteParameters are usually registered using HTTP components
 *
 * @Flow\Proxy(false)
 */
final class RouteParameters implements CacheAwareInterface
{

    /**
     * The parameters as simple key/value pair in the format ['<parameter1Key>' => <parameter1Value>, ...]
     *
     * @var array
     */
    private $parameters;

    /**
     * @param array $parameters simple key/value pair in the format ['<parameter1Key>' => <parameter1Value>, ...]
     */
    private function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Creates an empty instance of this class
     *
     * @return RouteParameters
     */
    public static function createEmpty(): self
    {
        return new static([]);
    }

    /**
     * Create a new instance adding the given parameter
     *
     * @param string $parameterName name of the parameter to add
     * @param bool|float|int|string|CacheAwareInterface $parameterValue value of the parameter, has to be a literal or an object implementing CacheAwareInterface
     * @return RouteParameters a new instance with the given parameters added
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

    /**
     * Checks whether a given parameter exists
     *
     * @param string $parameterName
     * @return bool
     */
    public function has(string $parameterName)
    {
        return isset($this->parameters[$parameterName]);
    }

    /**
     * Returns the value for a given $parameterName, or NULL if it doesn't exist
     *
     * @param string $parameterName
     * @return bool|float|int|string|CacheAwareInterface|null
     */
    public function getValue(string $parameterName)
    {
        return $this->parameters[$parameterName] ?? null;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->parameters === [];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->parameters;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        $cacheIdentifierParts = [];
        foreach ($this->parameters as $parameterName => $parameterValue) {
            $cacheIdentifierParts[] = $parameterName . ':' . ($parameterValue instanceof CacheAwareInterface ? $parameterValue->getCacheEntryIdentifier() : (string)$parameterValue);
        }
        return md5(implode('|', $cacheIdentifierParts));
    }
}
