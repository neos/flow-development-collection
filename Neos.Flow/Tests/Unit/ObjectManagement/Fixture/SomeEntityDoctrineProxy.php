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

use Doctrine\Persistence\Proxy as DoctrineProxy;

/**
 * A fixture class which acts like a Doctrine proxy of SomeEntity, used for testing the
 * ObjectSerializationTrait and the RelatedEntitiesContainer.
 */
class SomeEntityDoctrineProxy extends SomeEntity implements DoctrineProxy
{
    /**
     * Doctrine used to store the identifier of a not yet initialized proxy in this property.
     *
     * Note: This property must be public, because ObjectAccess::getProperty() resolves a Doctrine
     * proxy to its parent class – which does not declare this property – before it tries to access
     * it directly.
     */
    public array $_identifier = ['persistence_object_identifier' => 'identifier-from-doctrine-proxy'];

    public function __load(): void
    {
    }

    public function __isInitialized(): bool
    {
        return false;
    }
}
