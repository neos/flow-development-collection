<?php

declare(strict_types=1);

namespace Neos\Utility;

/*
 * This file is part of the Neos.Utility.Arrays package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Type safe accessing of values from nested arrays without type casting
 *
 * ```php
 * $intValue = ValueAccessor::forValue($someMixedValue)->int();
 * ```
 *
 * Or in combination with {@see Arrays::getAccessorByPath()} to access values inside an array
 *
 * ```php
 * $intValue = Arrays::getAccessorByPath($mixedArray, 'foo.myIntOption')->int();
 * ```
 *
 * @internal experimental feature, not stable API
 */
final readonly class ValueAccessor
{
    private function __construct(
        public mixed $value,
        private ?string $additionalErrorMessage
    ) {
    }

    /**
     * @internal experimental feature, not stable API
     */
    public static function forValue(mixed $value): self
    {
        return new self($value, null);
    }

    /**
     * @internal You should use {@see ValueAccessor::forValue} instead
     */
    public static function forValueInPath(mixed $value, array|string $path): self
    {
        $pathinfo = is_array($path) ? implode('.', $path) : $path;
        return new self($value, 'in path ' . $pathinfo);
    }

    public function int(): int
    {
        if (is_int($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not an integer");
    }

    public function float(): float
    {
        if (is_float($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a float");
    }

    public function number(): int|float
    {
        if (is_int($this->value) || is_float($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a number");
    }

    public function string(): string
    {
        if (is_string($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a string");
    }

    /**
     * @return class-string
     */
    public function classString(): string
    {
        if (is_string($this->value) && class_exists($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a class-string");
    }

    public function array(): array
    {
        if (is_array($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not an array");
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T
     */
    public function instanceOf(string $className): object
    {
        if ($this->value instanceof $className) {
            return $this->value;
        }
        throw $this->createTypeError(sprintf('is not an instance of %s', $className));
    }

    public function intOrNull(): ?int
    {
        if (is_int($this->value) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not an integer or null");
    }

    public function floatOrNull(): ?float
    {
        if (is_float($this->value) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a float or null");
    }

    public function numberOrNull(): null|int|float
    {
        if (is_int($this->value) || is_float($this->value) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a number or null");
    }

    public function stringOrNull(): ?string
    {
        if (is_string($this->value) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a string or null");
    }

    /**
     * @return class-string|null
     */
    public function classStringOrNull(): ?string
    {
        if ((is_string($this->value) && class_exists($this->value)) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not a class-string");
    }

    public function arrayOrNull(): ?array
    {
        if (is_array($this->value) || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError("is not an array or null");
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return T|null
     */
    public function instanceOfOrNull(string $className): ?object
    {
        if ($this->value instanceof $className || is_null($this->value)) {
            return $this->value;
        }
        throw $this->createTypeError(sprintf('is not an instance of %s or null', $className));
    }

    private function createTypeError($message): \UnexpectedValueException
    {
        return new \UnexpectedValueException(get_debug_type($this->value) . ' ' . $message . ($this->additionalErrorMessage ? ' ' . $this->additionalErrorMessage : ''));
    }
}
