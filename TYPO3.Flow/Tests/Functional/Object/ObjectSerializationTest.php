<?php
namespace TYPO3\Flow\Tests\Functional\Object;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Functional tests for Object serialization.
 *
 */
class ObjectSerializationTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function serializingAnObjectAndUnserializingWillReinjectProperties() {
		$object = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized');
		$object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA', $object->interfaceDeclaredSingletonButImplementationIsPrototype);

		$object->prototypeB->setSomeProperty('This is not a coffee machine.');

		$serializedObject = serialize($object);
		$object = unserialize($serializedObject);

		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized', $object);
		$object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA', $object->interfaceDeclaredSingletonButImplementationIsPrototype);
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC', $object->eagerC);

		$this->assertEquals(NULL, $object->prototypeB->getSomeProperty(), 'An injected prototype instance will be overwritten with a fresh instance on unserialize.');
	}

	/**
	 * @test
	 */
	public function flowObjectPropertiesToSerializeContainsOnlyPropertiesThatCannotBeReinjected() {
		$object = $this->objectManager->get('TYPO3\Flow\Tests\Functional\Object\Fixtures\ClassToBeSerialized');
		$object->interfaceDeclaredSingletonButImplementationIsPrototype->getSingletonA();
		$this->assertInstanceOf('TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassA', $object->interfaceDeclaredSingletonButImplementationIsPrototype);

		$propertiesToBeSerialized = $object->__sleep();

		// Note that the privateProperty is not serialized as it was declared in the parent class of the proxy.
		$this->assertCount(2, $propertiesToBeSerialized);
		$this->assertContains('someProperty', $propertiesToBeSerialized);
		$this->assertContains('protectedProperty', $propertiesToBeSerialized);
	}
}
