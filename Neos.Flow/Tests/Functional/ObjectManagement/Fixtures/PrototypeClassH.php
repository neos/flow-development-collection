<?php
namespace Neos\Flow\Tests\Functional\ObjectManagement\Fixtures;

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
 * A class of scope prototype in the style of a read model
 *
 * NOTE: Autowiring for the constructor is disabled via the setting "excludeClassesFromConstructorAutowiring".
 */
readonly class PrototypeClassH
{
    public function __construct(
        public ValueObjectClassA $classA,
        public ValueObjectClassB $classB,
    ) {
    }
}
