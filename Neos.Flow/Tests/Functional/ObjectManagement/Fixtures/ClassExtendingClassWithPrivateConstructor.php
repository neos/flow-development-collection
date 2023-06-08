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

class ClassExtendingClassWithPrivateConstructor extends ClassWithPrivateConstructor
{
    public static function createInSubClass(string $constructorArgument, PrototypeClassA $anotherDependency): static
    {
        return new static($constructorArgument, $anotherDependency);
    }
}
