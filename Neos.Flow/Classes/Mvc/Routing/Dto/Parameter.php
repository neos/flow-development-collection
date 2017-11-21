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
final class Parameter implements CacheAwareInterface
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var array|string|float|int|bool|CacheAwareInterface
     */
    private $value;

    /**
     * @var string
     */
    private $cacheEntryIdentifier;

    /**
     * @param string $name
     * @param array|bool|float|int|CacheAwareInterface|string $value
     */
    public function __construct(string $name, $value)
    {
        $this->cacheEntryIdentifier = $this->buildCacheEntryIdentifier($name, $value);
        $this->name = $name;
        $this->value = $value;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    private function buildCacheEntryIdentifier(string $name, $value)
    {
        if (is_array($value)) {
            $convertedArray = '';
            foreach ($value as $key => $subValue) {
                $convertedArray .= '|' . $this->buildCacheEntryIdentifier($name . '.' . $key, $subValue);
            }
            return $convertedArray;
        }
        if ($value instanceof CacheAwareInterface) {
            return $value->getCacheEntryIdentifier();
        }
        if (TypeHandling::isSimpleType(gettype($value))) {
            return $name . ':' . (string)$value;
        }
        throw new \InvalidArgumentException(sprintf('Parameter values must be simple types or implement the CacheAwareInterface, given: "%s"', is_object($value) ? get_class($value) : gettype($value)), 1511194273);
    }

    public function getCacheEntryIdentifier(): string
    {
        return $this->cacheEntryIdentifier;
    }

}
