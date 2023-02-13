<?php
namespace Neos\Flow\Tests\Unit\Reflection\Fixture;

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
 * Dummy class for the Reflection tests, with a method that has a builtin type method argument
 */
class ClassWithBuiltinTypes
{
    public function doCoolStuffWithObject(object $firstArgument)
    {
    }

    public function doCoolStuffWithIterable(iterable $firstArgument)
    {
    }

    public function doCoolStuffWithClass(\stdClass $firstArgument)
    {
    }

    public function doCoolStuffWithString(string $firstArgument)
    {
    }

    public function doCoolStuffWithNullableString(?string $firstArgument)
    {
    }
}
