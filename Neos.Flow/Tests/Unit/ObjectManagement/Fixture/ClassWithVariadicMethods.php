<?php

namespace Neos\Flow\Tests\Unit\ObjectManagement\Fixture;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Fixture class with methods using variadic parameters
 */
class ClassWithVariadicMethods
{
    public function sum(...$numbers)
    {
        return array_sum($numbers);
    }

    public function sumTyped(int ...$numbers): int
    {
        return array_sum($numbers);
    }

    public function concatenate(string $separator, string ...$parts): string
    {
        return implode($separator, $parts);
    }

    public function collect(array &$collection, string ...$items): void
    {
        $collection = array_merge($collection, $items);
    }

    public function noVariadics(string $first, int $second = 42): string
    {
        return $first . $second;
    }
}
