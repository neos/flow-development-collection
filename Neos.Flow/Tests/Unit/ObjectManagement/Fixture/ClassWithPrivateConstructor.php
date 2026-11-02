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
 * A fixture class with a private constructor, used for testing the ProxyConstructorGenerator.
 */
class ClassWithPrivateConstructor
{
    private function __construct(private readonly string $name)
    {
    }

    public static function create(string $name): self
    {
        return new self($name);
    }
}
