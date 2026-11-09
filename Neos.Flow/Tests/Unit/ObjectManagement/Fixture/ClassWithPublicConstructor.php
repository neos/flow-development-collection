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
 * A fixture class with a public constructor, used for testing the ProxyConstructorGenerator.
 */
class ClassWithPublicConstructor
{
    /**
     * Some documentation for this constructor
     *
     * @param string $name The name
     */
    public function __construct(public readonly string $name, protected int $number = 42, ?\ArrayObject $options = null)
    {
    }
}
