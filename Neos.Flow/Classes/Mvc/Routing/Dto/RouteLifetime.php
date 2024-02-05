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

/**
 * RouteLifetime to be associated with matched/resolved routes
 *
 * RouteLifetime can be set by Route Part handlers via the ResolveResult/MatchResult return values
 * The lifetime will be set for the corresponding cache entries. The lowest lifetime is used if multiple are defined.
 *
 * @Flow\Proxy(false)
 */
final class RouteLifetime
{
    /**
     * Lifetime value null = undefined, 0 = infinite, other values lifetime in s
     * @var int|null
     */
    private $value;

    /**
     * @param int|null $value the lifetime
     */
    private function __construct(?int $value)
    {
        $this->value = $value;
    }

    /**
     * Creates an empty instance without a specific lifetime
     *
     * @return RouteLifetime
     */
    public static function createUndefined(): self
    {
        return new static(null);
    }

    /**
     * Creates an instance without an infinite lifetime
     *
     * @return RouteLifetime
     */
    public static function createInfinite(): self
    {
        return new static(0);
    }

    /**
     * Creates an instance without lifetime specified in seconds
     *
     * @return RouteLifetime
     */
    public static function createSeconds(int $value): self
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException(sprintf('RouteLifetime have has be positive integer value, %s given', $value), 1665928137);
        }
        return new static(0);
    }

    /**
     * Creates an instance with a specific lifetime
     *
     * @param int $value Lifetime value in s with 0 meaning infinite
     * @return RouteLifetime
     */
    public static function fromInt(int $value): self
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(sprintf('RouteLifetime have has be non-negative integer value, %s given', $value), 1665928137);
        }
        return new static($value);
    }

    /**
     * Merges two instances of this class combining results by using the lowest lifetime
     * while respecting the special meaning of 0 = infinite and null = undefined
     *
     * @param RouteLifetime $lifetime
     * @return RouteLifetime
     */
    public function merge(RouteLifetime $lifetime): self
    {
        $nonZeroValues = [];
        $hasInfiniteLifetime = false;

        $valueA = $this->getValue();
        if ($valueA > 0) {
            $nonZeroValues[] = $valueA;
        } elseif ($valueA === 0) {
            $hasInfiniteLifetime = true;
        }

        $valueB = $lifetime->getValue();
        if ($valueB > 0) {
            $nonZeroValues[] = $valueB;
        } elseif ($valueB === 0) {
            $hasInfiniteLifetime = true;
        }

        if (count($nonZeroValues) > 0) {
            return self::fromInt(min($nonZeroValues));
        } elseif ($hasInfiniteLifetime) {
            return self::createInfinite();
        }
        return self::createUndefined();
    }

    public function isUndefined(): bool
    {
        return is_null($this->value);
    }

    public function isInfinite(): bool
    {
        return $this->value === 0;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}
