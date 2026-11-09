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
 * A fixture class providing methods with various signatures, used for testing
 * the ProxyMethodGenerator.
 */
class ClassWithVariousMethods
{
    /**
     * Some documentation for this method
     *
     * @param string $first The first argument
     * @return string
     */
    public function methodWithParameters(string $first, array $second, \ArrayObject $third, &$fourth, int $fifth = 42, bool $sixth = true): string
    {
        return $first;
    }

    public function methodWithoutReturnType($argument)
    {
        return $argument;
    }

    public function voidMethod(string $argument): void
    {
    }

    public function neverMethod(string $argument): never
    {
        throw new \RuntimeException('This method never returns', 1754212800);
    }

    public static function staticMethod(int $number): int
    {
        return $number;
    }

    protected function protectedMethod(): void
    {
    }

    #[ExampleMethodAttribute('some label')]
    public function methodWithAttribute(): void
    {
    }
}
