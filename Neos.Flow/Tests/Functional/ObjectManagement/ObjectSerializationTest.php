<?php

declare(strict_types=1);

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

use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\ClassToBeSerialized;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\PrototypeClassA;
use Neos\Flow\Tests\Functional\ObjectManagement\Fixtures\SingletonClassC;
use Neos\Flow\Tests\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Functional tests for Object serialization.
 *
 */
final class ObjectSerializationTest extends FunctionalTestCase
{
    #[Test]
    public function serializingAnObjectAndUnserializingWillReinjectProperties()
    {
        $object = $this->objectManager->get(ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        self::assertInstanceOf(PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $object->prototypeB->setSomeProperty('This is not a coffee machine.');

        $serializedObject = serialize($object);
        $object = unserialize($serializedObject);

        self::assertInstanceOf(ClassToBeSerialized::class, $object);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        self::assertInstanceOf(PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);
        self::assertInstanceOf(SingletonClassC::class, $object->eagerC);

        self::assertEquals(null, $object->prototypeB->getSomeProperty(), 'An injected prototype instance will be overwritten with a fresh instance on unserialize.');
    }

    #[Test]
    public function flowObjectPropertiesToSerializeContainsOnlyPropertiesThatCannotBeReinjected()
    {
        $object = $this->objectManager->get(ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        self::assertInstanceOf(PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $propertiesToBeSerialized = $object->__sleep();

        // Note that the privateProperty is not serialized as it was declared in the parent class of the proxy.
        self::assertCount(3, $propertiesToBeSerialized);
        self::assertContains('Persistence_Object_Identifier', $propertiesToBeSerialized); # Introduced due to "Entity" annotation
        self::assertContains('someProperty', $propertiesToBeSerialized);
        self::assertContains('protectedProperty', $propertiesToBeSerialized);
    }

    #[Test]
    public function readonlyClassesSurviveASerializationRoundtrip()
    {
        $object = new Fixtures\ReadonlyClassWithSerializedState('a name', ['first tag', 'second tag'], 'a temporary value');
        self::assertInstanceOf(ProxyInterface::class, $object);

        $unserializedObject = unserialize(serialize($object));

        self::assertInstanceOf(Fixtures\ReadonlyClassWithSerializedState::class, $unserializedObject);
        self::assertSame('a name', $unserializedObject->name);
        self::assertSame(['first tag', 'second tag'], $unserializedObject->tags);
    }

    #[Test]
    public function transientPropertiesOfReadonlyClassesAreNotSerialized()
    {
        $object = new Fixtures\ReadonlyClassWithSerializedState('a name', ['first tag'], 'a temporary value');

        $propertiesToBeSerialized = $object->__sleep();

        self::assertSame(['name', 'tags'], $propertiesToBeSerialized);

        $unserializedObject = unserialize(serialize($object));
        self::assertFalse(isset($unserializedObject->temporaryValue));
    }
}
