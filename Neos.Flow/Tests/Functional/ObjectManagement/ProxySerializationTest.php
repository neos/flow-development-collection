<?php

namespace Neos\Flow\Tests\Functional\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Test for Issue #3493: Classes with entity properties should get serialization code
 */
class ProxySerializationTest extends FunctionalTestCase
{
    protected static $testablePersistenceEnabled = true;

    #[Test]
    public function classWithEntityPropertyCanBeSerialized(): void
    {
        $entity = new Fixtures\SimpleEntity('Test Entity');
        $object = new Fixtures\ClassWithEntityProperty($entity, 'some value');

        // This should not fail - the proxy should have __sleep() method
        // Before the fix, this would fail because entity references would not be stripped
        $serialized = serialize($object);
        $unserialized = unserialize($serialized);

        self::assertInstanceOf(Fixtures\ClassWithEntityProperty::class, $unserialized);
        self::assertEquals('some value', $unserialized->someValue);
    }

    #[Test]
    public function classWithForcedSerializationCodeSerializesPersistedEntitiesInArraysAsReferences(): void
    {
        $entity = new Fixtures\SimpleEntity('Persisted Entity');
        $this->persistenceManager->add($entity);
        $this->persistenceManager->persistAll();
        $identifier = $this->persistenceManager->getIdentifierByObject($entity);

        $object = new Fixtures\ClassWithEntitiesInArray(['entity' => $entity, 'name' => 'some value']);
        $unserialized = unserialize(serialize($object));

        self::assertInstanceOf(Fixtures\ClassWithEntitiesInArray::class, $unserialized);
        self::assertSame('some value', $unserialized->values['name']);
        self::assertInstanceOf(Fixtures\SimpleEntity::class, $unserialized->values['entity']);
        self::assertSame($identifier, $this->persistenceManager->getIdentifierByObject($unserialized->values['entity']));
    }
}
