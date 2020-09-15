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

/**
 * Functional tests for Object serialization.
 *
 */
class ObjectSerializationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function serializingAnObjectAndUnserializingWillReinjectProperties()
    {
        $object = $this->objectManager->get(Fixtures\ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $object->prototypeB->setSomeProperty('This is not a coffee machine.');

        $serializedObject = serialize($object);
        $object = unserialize($serializedObject);

        $this->assertInstanceOf(Fixtures\ClassToBeSerialized::class, $object);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);
        $this->assertInstanceOf(Fixtures\SingletonClassC::class, $object->eagerC);

        $this->assertEquals(null, $object->prototypeB->getSomeProperty(), 'An injected prototype instance will be overwritten with a fresh instance on unserialize.');
    }

    /**
     * @test
     */
    public function flowObjectPropertiesToSerializeContainsOnlyPropertiesThatCannotBeReinjected()
    {
        $object = $this->objectManager->get(Fixtures\ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $propertiesToBeSerialized = $object->__sleep();

        // Note that the privateProperty is not serialized as it was declared in the parent class of the proxy.
        $this->assertCount(2, $propertiesToBeSerialized);
        $this->assertContains('someProperty', $propertiesToBeSerialized);
        $this->assertContains('protectedProperty', $propertiesToBeSerialized);
    }
}
