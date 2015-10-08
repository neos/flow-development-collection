<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Functional tests for Object serialization.
 *
 */
class ObjectSerializationTest extends \TYPO3\Flow\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function serializingAnObjectAndUnserializingWillReinjectProperties()
    {
        $object = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $object->prototypeB->setSomeProperty('This is not a coffee machine.');

        $serializedObject = serialize($object);
        $object = unserialize($serializedObject);

        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized::class, $object);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC::class, $object->eagerC);

        $this->assertEquals(null, $object->prototypeB->getSomeProperty(), 'An injected prototype instance will be overwritten with a fresh instance on unserialize.');
    }

    /**
     * @test
     */
    public function flowObjectPropertiesToSerializeContainsOnlyPropertiesThatCannotBeReinjected()
    {
        $object = $this->objectManager->get(\TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized::class);
        $object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
        $this->assertInstanceOf(\TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA::class, $object->interfaceDeclaredSingletonButImplementationIsPrototype);

        $propertiesToBeSerialized = $object->__sleep();

        // Note that the privateProperty is not serialized as it was declared in the parent class of the proxy.
        $this->assertCount(2, $propertiesToBeSerialized);
        $this->assertContains('someProperty', $propertiesToBeSerialized);
        $this->assertContains('protectedProperty', $propertiesToBeSerialized);
    }
}
