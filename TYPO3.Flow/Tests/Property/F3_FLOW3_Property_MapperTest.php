<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

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
class MapperTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$this->mockObjectFactory->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'createCallback')));
		$this->mockValidatorResolver = $this->getMock('F3\FLOW3\Validation\ValidatorResolver', array(), array(), '', FALSE);
		$this->mapper = new \F3\FLOW3\Property\Mapper($this->mockObjectFactory);
		$this->mapper->injectValidatorResolver($this->mockValidatorResolver);
	}

	/**
	 * Callback for the mocked object factory defined in setUp()
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createCallback() {
		switch (func_get_arg(0)) {
			case 'F3\FLOW3\Property\MappingResults':
				return new \F3\FLOW3\Property\MappingResults();
			break;
			case 'F3\FLOW3\Property\MappingWarning':
				$message = func_get_arg(1);
				$code = func_get_arg(2);
				return new \F3\FLOW3\Property\MappingWarning($message, $code);
			break;
			case 'F3\FLOW3\Validation\Errors':
				return new \F3\FLOW3\Validation\Errors();
			break;
			case 'F3\FLOW3\Property\MappingError':
				$message = func_get_arg(1);
				$code = func_get_arg(2);
				return new \F3\FLOW3\Property\MappingError($message, $code);
			break;
		}
	}

	/**
	 * Checks if non-objects as a target trigger an exception
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidTargetObject
	 */
	public function mapperOnlyAcceptsObjectsAsTarget() {
		$this->mapper->setTarget(array());
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithArrayObjectTargetBasicallyWorks() {
		$target = new \ArrayObject();
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.'
			)
		);

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1', 'key2'));
		$this->mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithNestedArrayObjectWorks() {
		$target = new \ArrayObject();
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => new \ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1', 'key2', 'key3', 'key4'));
		$this->mapper->map($source);
		$this->assertEquals($source, $target, 'The two ArrayObjects are not equal after mapping them together.');
	}

	/**
	 * Checks if mapping to a non-array target object via setter methods works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mappingWithSetterAccessBasicallyWorks() {
		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();
		$source = new \ArrayObject (
			array(
				'property1' => 'value1',
				'property2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'property4' => new \ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'property3' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$this->mapper->setTarget($target);
		$this->mapper->map($source);

		$this->assertEquals($source['property1'], $target->property1, 'Property 1 has not the expected value.');
		$this->assertEquals(NULL, $target->getProperty2(), 'Property 2 is set although it should not, as there is no public setter and no public variable.');
		$this->assertEquals($source['property3'], $target->property3, 'Property 3 has not the expected value.');
		$this->assertEquals($source['property4'], $target->property4, 'Property 4 has not the expected value.');
	}

	/**
	 * Checks if mapping to a non-array target object via setter methods works if the shorthand syntax is used
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function mappingWithSetterAccessBasicallyWorksWithShortSyntax() {
		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();
		$source = new \ArrayObject (
			array(
				'property1' => 'value1',
				'property2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'property4' => new \ArrayObject(
					array(
						'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
					)
				),
				'property3' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$this->mapper->map($source, $target);

		$this->assertEquals($source['property1'], $target->property1, 'Property 1 has not the expected value.');
		$this->assertEquals(NULL, $target->getProperty2(), 'Property 2 is set although it should not, as there is no public setter and no public variable.');
		$this->assertEquals($source['property3'], $target->property3, 'Property 3 has not the expected value.');
		$this->assertEquals($source['property4'], $target->property4, 'Property 4 has not the expected value.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlyCertainAllowedPropertiesAreMapped() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1', 'key3'));
		$expectedTarget = new \ArrayObject(
			array(
				'key1' => $source['key1'],
				'key3' => $source['key3']
			)
		);
		$this->mapper->map($source);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing key1 and key3.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function onlyCertainAllowedPropertiesAreMappedWithAlternativeSyntax() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();

		$expectedTarget = new \ArrayObject(
			array(
				'key1' => $source['key1'],
				'key3' => $source['key3']
			)
		);
		$this->mapper->map($source, $target, array('key1', 'key3'));
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing key1 and key3.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function wildCardWorksForSpecifyingAllowedProperties() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key.*'));
		$this->mapper->map($source);
		$this->assertEquals(array_keys($source->getArrayCopy()), array_keys((array)$target), 'The target object contains not the expected properties after allowing key.*');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function noPropertyIsMappedIfNoPropertiesAreAllowed() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array());
		$expectedTarget = new \ArrayObject;
		$this->mapper->map($source);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing no property at all.');
	}

	/**
	 * Needed for multiple invocations of $this->propertyMapper->map(..., ...);
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function allowedPropertiesAreResetIfThirdParameterOfMapIsNotSet() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$this->mapper->setAllowedProperties(array());

		$expectedTarget = $source;
		$this->mapper->map($source, $target);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content. Thus, the allowed properties have not been reset.');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectCanBeMappedToOtherObject() {
		$source = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();
		$source->setProperty1('Hallo');
		$source->setProperty3('It is already late in the evening and I am curious which special characters my mac keyboard can do. «∑€®†Ω¨⁄øπ•±å‚∂ƒ©ªº∆@œæ¥≈ç√∫~µ∞…––çµ∫≤∞. Amazing :-) ');

		$destination = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();
		$this->mapper->map($source, $destination);
		$this->assertEquals($source, $destination, 'Complex objects cannot be mapped to each other.');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aPropertyEditorRegisteredForAllPropertiesIsCalledCorrectly() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$propertyEditor = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$propertyEditor->expects($this->once())->method('setAsFormat')->with($this->equalTo('default'), $this->equalTo('value1'));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1'));
		$this->mapper->registerPropertyEditor($propertyEditor);

		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aPropertyEditorRegisteredForAllPropertiesWithASpecificFormatIsCalledCorrectly() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$propertyEditor = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$propertyEditor->expects($this->once())->method('setAsFormat')->with($this->equalTo('customFormat'), $this->equalTo('value1'));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1'));
		$this->mapper->registerPropertyEditor($propertyEditor, 'all', 'customFormat');

		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aPropertyEditorRegisteredForASinglePropertyIsCalledCorrectly() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$propertyEditor = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$propertyEditor->expects($this->once())->method('setAsFormat')->with($this->equalTo('default'), $this->equalTo('value3'));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1', 'key2', 'key3', 'key4'));
		$this->mapper->registerPropertyEditor($propertyEditor, 'key3');

		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function aPropertyEditorRegisteredForASinglePropertyWithASpecificFormatIsCalledCorrectly() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$propertyEditor = $this->getMock('F3\FLOW3\Property\EditorInterface');
		$propertyEditor->expects($this->once())->method('setAsFormat')->with($this->equalTo('customFormat'), $this->equalTo('value3'));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1', 'key2', 'key3', 'key4'));
		$this->mapper->registerPropertyEditor($propertyEditor, 'key3', 'customFormat');

		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function registeredFiltersAreCalledForTheRightProperties() {
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$filter = $this->getMock('F3\FLOW3\Validation\FilterInterface');
		$filter->expects($this->once())->method('filter')->with($this->equalTo('value1'));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key1'));
		$this->mapper->registerFilter($filter);

		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingAnAllowedPropertyAddsAWarningIfItIsNotAccessibleInTheTargetObject() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$this->mapper->map($source);
		$mappingResults = $this->mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasWarnings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingARequiredPropertyAddsAnErrorIfItIsNotAccessibleInTheTargetObject() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$this->mapper->setRequiredProperties(array('key'));
		$this->mapper->map($source);
		$mappingResults = $this->mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function mappingANotDefinedPropertyAddsAnWarning() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3'));
		$this->mapper->map($source);
		$mappingResults = $this->mapper->getMappingResults();

		$this->assertTrue($mappingResults->hasWarnings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function onlyWriteOnNoErrorsModeBasicallyWorks() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \F3\FLOW3\Fixture\Validation\ClassWithSetters();
		$originalTargetCopy = clone $target;

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$this->mapper->setRequiredProperties(array('notExistantKey'));
		$this->mapper->setOnlyWriteOnNoErrors(TRUE);
		$this->mapper->map($source);

		$this->assertEquals($originalTargetCopy, $target);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function validatorIsInvokedCorrectly() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$validator = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');

		$validator->expects($this->once())->method('validate');
		$validator->expects($this->atLeastOnce())->method('isValidProperty');

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$this->mapper->registerValidator($validator);
		$this->mapper->map($source);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function propertyIsNotMappedIfValidatorReturnsFalse() {
		$source = new \ArrayObject(
			array(
				'key' => 'value1',
				'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
				'key3' => 'value3',
				'key4' => array(
					'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
				)
			)
		);

		$target = new \ArrayObject();
		$validator = $this->getMock('F3\FLOW3\Validation\ObjectValidatorInterface');

		$validator->expects($this->once())->method('validate')->will($this->returnValue(FALSE));
		$validator->expects($this->atLeastOnce())->method('isValidProperty')->will($this->returnValue(FALSE));

		$this->mapper->setTarget($target);
		$this->mapper->setAllowedProperties(array('key', 'key2', 'key3', 'key4'));
		$this->mapper->registerValidator($validator);
		$this->mapper->map($source);

		$this->assertEquals($target, new \ArrayObject());
	}
}
?>