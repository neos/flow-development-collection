<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once (dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_Validation_ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @package     FLOW3
 * @version     $Id$
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Property_MapperTest extends T3_Testing_BaseTestCase {

	/**
	 * Just makes sure that it's prototype
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_checkIfItsPrototype() {
		$mapper1 = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper2 = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$this->assertNotSame($mapper1, $mapper2, 'The Property Mapper instances are not unique - seem to be singleton.');
	}

	/**
	 * Checks if non-objects as a target trigger an exception
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_mapperOnlyAcceptsObjectsAsTarget() {
		try {
			$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
			$mapper->setTarget(array());
			$this->fail('The Property Mapper accepted a non-object as a target.');
		} catch(T3_FLOW3_Property_Exception_InvalidTargetObject $exception) {

		}
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_mappingWithArrayObjectTargetBasicallyWorks() {
		$target = new ArrayObject();
		$source = new ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.'
			)
		);
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_mappingWithNestedArrayObjectWorks() {
		$target = new ArrayObject();
		$source = new ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => new ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if mapping to a non-array target object via setter methods works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_mappingWithSetterAccesBasicallyWorks() {
		$target = new T3_FLOW3_Fixture_Validation_ClassWithSetters;
		$source = new ArrayObject(
			array(
				'property1' => 'value1',
				'property2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'property4' => new ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'property3' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->map($source);

		$this->assertEquals($source['property1'], $target->property1, 'Property 1 has not the expected value.');
		$this->assertEquals(NULL, $target->property2, 'Property 2 has been set although no setter method exists.');
		$this->assertEquals($source['property3'], $target->property3, 'Property 3 has not the expected value.');
		$this->assertEquals(NULL, $target->property4, 'Property 4 has been set although the setter method is protected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlyCertainAllowedPropertiesAreMapped() {
		$source = new ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ArrayObject();
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1', 'key3'));
		$expectedTarget = new ArrayObject(
			array(
				'key1' => $source['key1'],
				'key3' => $source['key3']
			)
		);
		$mapper->map($source);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing key1 and key3.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function wildCardWorksForSpecifyingAllowedProperties() {
		$source = new ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ArrayObject();
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key.*'));
		$mapper->map($source);
		$this->assertEquals(array_keys($source->getArrayCopy()), array_keys((array)$target), 'The target object contains not the expected properties after allowing key.*');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function noPropertyIsMappedNoPropertiesAreAllowed() {
		$source = new ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ArrayObject();
		$mapper = $this->componentManager->getComponent('T3_FLOW3_Property_Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array());
		$expectedTarget = new ArrayObject;
		$mapper->map($source);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing no property at all.');
	}
}
?>