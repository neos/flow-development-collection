<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Property;

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

require_once (__DIR__ . '/../Fixtures/F3_FLOW3_Fixture_Validation_ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @package     FLOW3
 * @version     $Id$
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class MapperTest extends F3::Testing::BaseTestCase {

	/**
	 * Just makes sure that it's prototype
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function test_checkIfItsPrototype() {
		$mapper1 = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper2 = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$this->assertNotSame($mapper1, $mapper2, 'The Property Mapper instances are not unique - seem to be singleton.');
	}

	/**
	 * Checks if non-objects as a target trigger an exception
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapperOnlyAcceptsObjectsAsTarget() {
		try {
			$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
			$mapper->setTarget(array());
			$this->fail('The Property Mapper accepted a non-object as a target.');
		} catch(F3::FLOW3::Property::Exception::InvalidTargetObject $exception) {

		}
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithArrayObjectTargetBasicallyWorks() {
		$target = new ::ArrayObject();
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.'
			)
		);
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1', 'key2'));
		$mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithNestedArrayObjectWorks() {
		$target = new ::ArrayObject();
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => new ::ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1', 'key2', 'key3', 'key4'));
		$mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if mapping to a non-array target object via setter methods works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithSetterAccesBasicallyWorks() {
		$target = new F3::FLOW3::Fixture::Validation::ClassWithSetters();
		$source = new ::ArrayObject (
			array(
				'property1' => 'value1',
				'property2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'property4' => new ::ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'property3' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('property1', 'property2', 'property3', 'property4'));
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
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1', 'key3'));
		$expectedTarget = new ::ArrayObject(
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
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key.*'));
		$mapper->map($source);
		$this->assertEquals(array_keys($source->getArrayCopy()), array_keys((array)$target), 'The target object contains not the expected properties after allowing key.*');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function noPropertyIsMappedIfNoPropertiesAreAllowed() {
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array());
		$expectedTarget = new ::ArrayObject;
		$mapper->map($source);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing no property at all.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeredPropertyEditorsAreCalledForTheRightProperties() {
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$propertyEditor = $this->getMock('F3::FLOW3::Property::EditorInterface');
		$propertyEditor->expects($this->once())->method('setProperty')->with($this->equalTo('value1'));

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1'));
		$mapper->registerPropertyEditor($propertyEditor);

		$mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeredFiltersAreCalledForTheRightProperties() {
		$source = new ::ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$filter = $this->getMock('F3::FLOW3::Validation::FilterInterface');
		$filter->expects($this->once())->method('filter')->with($this->equalTo('value1'));

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key1'));
		$mapper->registerFilter($filter);

		$mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingAnAllowedPropertyAddsAWarningIfItIsNotAccessibleInTheTargetObject() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new F3::FLOW3::Fixture::Validation::ClassWithSetters();

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$mapper->map($source);
		$mappingResults = $mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasWarnings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingARequiredPropertyAddsAnErrorIfItIsNotAccessibleInTheTargetObject() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new F3::FLOW3::Fixture::Validation::ClassWithSetters();

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$mapper->setRequiredProperties(array('key'));
		$mapper->map($source);
		$mappingResults = $mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingANotDefinedPropertyAddsAnWarning() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new F3::FLOW3::Fixture::Validation::ClassWithSetters();

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3'));
		$mapper->map($source);
		$mappingResults = $mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasWarnings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function onlyWriteOnNoErrorsModeBasicallyWorks() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new F3::FLOW3::Fixture::Validation::ClassWithSetters();
		$originalTargetCopy = clone $target;

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$mapper->setRequiredProperties(array('notExistantKey'));
		$mapper->setOnlyWriteOnNoErrors(TRUE);
		$mapper->map($source);

		$this->assertEquals($originalTargetCopy, $target);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorIsInvokedCorrectly() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$validator = $this->getMock('F3::FLOW3::Validation::ObjectValidatorInterface');

		$validator->expects($this->once())->method('validate');
		$validator->expects($this->atLeastOnce())->method('isValidProperty');

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$mapper->registerValidator($validator);
		$mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function propertyIsNotMappedIfValidatorReturnsFalse() {
		$source = new ::ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new ::ArrayObject();
		$validator = $this->getMock('F3::FLOW3::Validation::ObjectValidatorInterface');

		$validator->expects($this->once())->method('validate')->will($this->returnValue(FALSE));
		$validator->expects($this->atLeastOnce())->method('isValidProperty')->will($this->returnValue(FALSE));

		$mapper = $this->objectManager->getObject('F3::FLOW3::Property::Mapper');
		$mapper->setTarget($target);
		$mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$mapper->registerValidator($validator);
		$mapper->map($source);

		$this->assertEquals($target, new ::ArrayObject());
	}
}
?>