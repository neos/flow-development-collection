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

abstract class AbstractClassWithFactoryMethod
{
    /**
     * This is a weird example: how can this abstract class assume that there are two arguments to the constructor of the concrete class?
     * However, this exists in Flow, see for example Neos\Flow\Security\Authentication\Provider\AbstractProvider.
     */
    public static function createInAbstractClass(string $constructorArgument, PrototypeClassA $anotherDependency): static
    {
        return new static($constructorArgument, $anotherDependency);
    }
}
