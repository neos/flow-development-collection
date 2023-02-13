<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PHP8;

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
 * A class with PHP 8 constructor properties
 * @Flow\Scope("prototype")
 */
class ClassWithConstructorProperties
{
    public function __construct(
        public ?string $propertyA = 'Foo',
        public int|null $propertyB = 25,
        public \DateTime|null $propertyC = null
    ) {
    }
}
