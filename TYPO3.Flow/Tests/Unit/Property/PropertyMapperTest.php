<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PropertyMapperTest extends \F3\Testing\BaseTestCase {

	protected $mockObjectManager;
	protected $mockReflectionService;
	protected $mappingResults;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mappingResults = new \F3\FLOW3\Property\MappingResults();
		$this->mockReflectionService = $this->getMock('F3\FLOW3\Reflection\ReflectionService');
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectRegistersAvailableObjectConverters() {
		$editor1 = $this->getMock('F3\FLOW3\Property\ObjectConverterInterface');
		$editor1->expects($this->once())->method('getSupportedTypes')->will($this->returnValue(array('F3\Foo\Bar')));

		$editor2 = $this->getMock('F3\FLOW3\Property\ObjectConverterInterface');
		$editor2->expects($this->once())->method('getSupportedTypes')->will($this->returnValue(array('F3\Baz\Quux', 'F3\Baz\Bong')));

		$expectedObjectConverters = array(
			'F3\Foo\Bar' => $editor1,
			'F3\Baz\Quux' => $editor2,
			'F3\Baz\Bong' => $editor2
		);

		$this->mockReflectionService->expects($this->once())->method('getAllImplementationClassNamesForInterface')
			->with('F3\FLOW3\Property\ObjectConverterInterface')
			->will($this->returnValue(array('F3\Editor1', 'F3\Editor2')));

		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\Editor1')->will($this->returnValue($editor1));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\Editor2')->will($this->returnValue($editor2));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('dummy'));
		$mapper->injectObjectManager($this->mockObjectManager);
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->initializeObject();

		$this->assertSame($expectedObjectConverters, $mapper->_get('objectConverters'));
	}

	/**
	 * Checks if one ArrayObject can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapCanCopyPropertiesOfOneArrayObjectToAnother() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

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

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectObjectManager($this->mockObjectManager);
		$mapper->injectReflectionService($this->mockReflectionService);
		$successful = $mapper->map(array('key1', 'key2', 'key3', 'key4'), $source, $target);
		$this->assertEquals($source, $target);
		$this->assertTrue($successful);
	}

	/**
	 * Checks if one array can be bound to another by using the default settings
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapCanCopyPropertiesOfOneArrayToAnother() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$target = array();
		$source = array(
			'key1' => 'value1',
			'key2' => 'Píca vailë yulda nár pé, cua téra engë centa oi.',
			'key3' => array(
				'key3-1' => 'トワク びつける アキテクチャ エム, クリック'
			),
			'key4' => array(
				'key4-1' => '@$ N0+ ||0t p@r+1cUL4r 7|24n5|473d'
			)
		);

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectObjectManager($this->mockObjectManager);
		$successful = $mapper->map(array('key1', 'key2', 'key3', 'key4'), $source, $target);
		$this->assertEquals($source, $target);
		$this->assertTrue($successful);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapAcceptsAnArrayAsSource() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$target = new \ArrayObject();
		$source = array(
			'key1' => 'value1',
			'key2' => 'value2'
		);

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);
		$successful = $mapper->map(array('key1', 'key2'), $source, $target);
		$this->assertEquals(new \ArrayObject($source), $target);
		$this->assertTrue($successful);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Property\Exception\InvalidTargetException
	 */
	public function mapExpectsTheTargetToBeAStringContainingClassNameOrAnObjectOrAnArray() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$target = '';
		$source = new \ArrayObject(array('key1' => 'value1'));

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectObjectManager($this->mockObjectManager);
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->map(array('key1'), $source, $target);
	}

	/**
	 * Checks if mapping to a non-array target object via setter methods works
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapCanCopyPropertiesFromAnArrayObjectToAnObjectWithSetters() {
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Error\Error')->will($this->returnValue(new \F3\FLOW3\Error\Error('Error1', 1)));

		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
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

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array('property3' => array('type' => 'array'))));
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);
		$result = $mapper->map(array('property1', 'property2', 'property3', 'property4'), $source, $target);

		$this->assertEquals($source['property1'], $target->property1, 'Property 1 has not the expected value.');
		$this->assertEquals(NULL, $target->getProperty2(), 'Property 2 is set although it should not, as there is no public setter and no public variable.');
		$this->assertEquals($source['property3'], $target->property3, 'Property 3 has not the expected value.');
		$this->assertEquals($source['property4'], $target->property4, 'Property 4 has not the expected value.');

		$this->assertFalse($result);

		$errors = $this->mappingResults->getErrors();
		$this->assertSame('Error1', $errors['property2']->getMessage());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlySpecifiedPropertiesAreMapped() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

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

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);
		$mapper->map(array('key1', 'key3'), $source, $target);
		$this->assertEquals($expectedTarget, $target, 'The target object has not the expected content after allowing key1 and key3.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function noPropertyIsMappedIfNoPropertiesWereSpecified() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

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

		$expectedTarget = new \ArrayObject;

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);
		$mapper->map(array(), $source, $target);
		$this->assertEquals($expectedTarget, $target);
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function anObjectCanBeMappedToAnotherObject() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$source = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$source->property1 = 'Hallo';
		$source->property3 = 'It is already late in the evening and I am curious which special characters my mac keyboard can do. «∑€®†Ω¨⁄øπ•±å‚∂ƒ©ªº∆@œæ¥≈ç√∫~µ∞…––çµ∫≤∞. Amazing :-) ';

		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectObjectManager($this->mockObjectManager);
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->will($this->returnValue(array('property3' => array('type' => 'string'))));
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->map(array('property1', 'property3'), $source, $target);
		$this->assertEquals($source, $target);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function ifAPropertyNameWasSpecifiedAndIsNotOptionalButDoesntExistInTheSourceTheMappingFails() {
		$this->mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));
		$this->mockObjectManager->expects($this->at(1))->method('getObject')->with('F3\FLOW3\Error\Error')->will($this->returnValue(new \F3\FLOW3\Error\Error('Error1', 1)));

		$target = new \ArrayObject();
		$source = new \ArrayObject(
			array(
				'key1' => 'value1',
				'key2' => 'value2'
			)
		);

		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);
		$successful = $mapper->map(array('key1', 'key2', 'key3', 'key4'), $source, $target, array('key4'));
		$this->assertFalse($successful);
		$errors = $this->mappingResults->getErrors();
		$this->assertSame('Error1', $errors['key3']->getMessage());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mapAndValidateMapsTheGivenProperties() {
		$propertyNames = array('foo', 'bar');
		$source = array('foo' => 'fooValue', 'bar' => 'barValue');
		$target = array();
		$optionalPropertyNames = array();

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ObjectValidatorInterface');
		$mockValidator->expects($this->once())->method('isValid')->will($this->returnValue(TRUE));

		$mockMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockMappingResults->expects($this->any())->method('hasErrors')->will($this->returnValue(FALSE));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('map'), array(), '', FALSE);
		$mapper->_set('mappingResults', $mockMappingResults);
		$mapper->injectObjectManager($this->mockObjectManager);

		$mapper->expects($this->at(0))->method('map')->with($propertyNames, $source, array(), $optionalPropertyNames);
		$mapper->expects($this->at(1))->method('map')->with($propertyNames, $source, $target, $optionalPropertyNames);

		$result = $mapper->mapAndValidate($propertyNames, $source, $target, $optionalPropertyNames, $mockValidator);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function mapAndValidateUsesTheSpecifiedValidatorsToValidateTheMappedProperties() {
		$propertyNames = array('foo', 'bar');
		$source = array('foo' => 'fooValue', 'bar' => 'barValue');
		$target = array();
		$optionalPropertyNames = array();

		$mockValidator = $this->getMock('F3\FLOW3\Validation\Validator\ObjectValidatorInterface');
		$mockValidator->expects($this->any())->method('isValid')->will($this->returnValue(FALSE));
		$mockValidator->expects($this->any())->method('getErrors')->will($this->returnValue(array('Some error message')));

		$mockMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockMappingResults->expects($this->at(0))->method('hasErrors')->will($this->returnValue(FALSE));
		$mockMappingResults->expects($this->at(1))->method('hasErrors')->will($this->returnValue(FALSE));
		$mockMappingResults->expects($this->at(2))->method('hasErrors')->will($this->returnValue(TRUE));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('map', 'addErrorsFromObjectValidator'), array(), '', FALSE);
		$mapper->expects($this->once())->method('addErrorsFromObjectValidator')->with(array('Some error message'));

		$mapper->_set('mappingResults', $mockMappingResults);
		$mapper->injectObjectManager($this->mockObjectManager);

		$mapper->expects($this->at(0))->method('map')->with($propertyNames, $source, array(), $optionalPropertyNames);
		$mapper->expects($this->at(1))->method('map')->with($propertyNames, $source, $target, $optionalPropertyNames);

		$result = $mapper->mapAndValidate($propertyNames, $source, $target, $optionalPropertyNames, $mockValidator);
		$this->assertFalse($result);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function addErrorsFromObjectValidatorAddsErrorsForIndividualPropertiesFromPropertyErrors() {
		$mockError = $this->getMock('F3\FLOW3\Validation\PropertyError', array('dummy'), array('foo'));

		$errors = array('foo' => $mockError);

		$mockMappingResults = $this->getMock('F3\FLOW3\Property\MappingResults', array(), array(), '', FALSE);
		$mockMappingResults->expects($this->once())->method('addError')->with($mockError, 'foo');

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('dummy'), array(), '', FALSE);
		$mapper->_set('mappingResults', $mockMappingResults);
		$mapper->_call('addErrorsFromObjectValidator', $errors);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapConvertsArraysWithUUIDsInSourceToObjectsIfTargetPropertyIsSplObjectStorageAndTyped() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$UUID = '740dea52-1bfd-436f-bef6-d7b39ac2f12f';
		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$source = array(
			'property1' => array($UUID)
		);
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\FLOW3\Fixture\Validation\ClassWithSetters');
		$classSchema->addProperty('property1', 'SplObjectStorage<\stdClass>');

		$existingObject = new \stdClass();
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));
		$mapper = $this->getMock('F3\FLOW3\Property\PropertyMapper', array('transformToObject'));
		$mapper->expects($this->once())->method('transformToObject')->with($source['property1'][0], '\stdClass', 'property1')->will($this->returnValue($existingObject));
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);

		$successful = $mapper->map(array('property1'), $source, $target);
		$this->assertType('SplObjectStorage', $target->property1);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapConvertsArraysWithUUIDsInSourceToObjectsIfTargetPropertyIsArrayObjectAndTyped() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$UUID = '740dea52-1bfd-436f-bef6-d7b39ac2f12f';
		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$source = array(
			'property1' => array($UUID)
		);
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\FLOW3\Fixture\Validation\ClassWithSetters');
		$classSchema->addProperty('property1', 'ArrayObject<\stdClass>');

		$existingObject = new \stdClass();
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));
		$mapper = new \F3\FLOW3\Property\PropertyMapper();
		$mapper = $this->getMock('F3\FLOW3\Property\PropertyMapper', array('transformToObject'));
		$mapper->expects($this->once())->method('transformToObject')->with($source['property1'][0], '\stdClass', 'property1')->will($this->returnValue($existingObject));
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);

		$successful = $mapper->map(array('property1'), $source, $target);
		$this->assertType('ArrayObject', $target->property1);
		$this->assertSame($existingObject, $target->property1[0]);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapConvertsArraysWithUUIDsInSourceToObjectsIfTargetPropertyIsArrayAndTyped() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$UUID = '740dea52-1bfd-436f-bef6-d7b39ac2f12f';
		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$source = array(
			'property1' => array($UUID)
		);
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\FLOW3\Fixture\Validation\ClassWithSetters');
		$classSchema->addProperty('property1', 'array<\stdClass>');

		$existingObject = new \stdClass();
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));
		$mapper = $this->getMock('F3\FLOW3\Property\PropertyMapper', array('transformToObject'));
		$mapper->expects($this->once())->method('transformToObject')->with($source['property1'][0], '\stdClass', 'property1')->will($this->returnValue($existingObject));
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);

		$mapper->map(array('property1'), $source, $target);
		$this->assertTrue(is_array($target->property1));
		$this->assertSame($existingObject, $target->property1[0]);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapConvertsArraysInSourceToObjectsIfTargetPropertyIsObject() {
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\Property\MappingResults')->will($this->returnValue($this->mappingResults));

		$target = new \F3\FLOW3\Fixtures\ClassWithSetters();
		$source = array(
			'property1' => array('foo' => 'bar')
		);
		$classSchema = new \F3\FLOW3\Reflection\ClassSchema('F3\FLOW3\Fixture\Validation\ClassWithSetters');
		$classSchema->addProperty('property1', '\F3\Foo\Bar');

		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->will($this->returnValue($classSchema));
		$mapper = $this->getMock('F3\FLOW3\Property\PropertyMapper', array('transformToObject'));
		$mapper->expects($this->once())->method('transformToObject')->with($source['property1'], 'F3\Foo\Bar', 'property1');
		$mapper->injectReflectionService($this->mockReflectionService);
		$mapper->injectObjectManager($this->mockObjectManager);

		$mapper->map(array('property1'), $source, $target);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function transformToObjectConvertsAnUuidStringToAnObject() {
		$UUID = 'e104e469-9030-4b98-babf-3990f07dd3f1';
		$existingObject = new \stdClass();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($UUID)->will($this->returnValue($existingObject));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('dummy'));
		$mapper->injectPersistenceManager($mockPersistenceManager);
		$mapper->_call('transformToObject', $UUID, 'F3\Foo\Bar', 'someProp');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function transformToObjectConvertsAnIdentityArrayContainingAnUUIDToAnObject() {
		$UUID = 'e104e469-9030-4b98-babf-3990f07dd3f1';
		$existingObject = new \stdClass();
		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($UUID)->will($this->returnValue($existingObject));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('dummy'));
		$mapper->injectPersistenceManager($mockPersistenceManager);
		$mapper->_call('transformToObject', array('__identity' => $UUID), 'F3\Foo\Bar', 'someProp');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function transformToObjectCallsFindObjectByIdentityPropertiesToConvertAnIdentityArrayContainingIdentityPropertiesIntoTheRealObject() {
		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('findObjectByIdentityProperties'));
		$mapper->expects($this->once())->method('findObjectByIdentityProperties')->with(array('key1' => 'value1', 'key2' => 'value2'));
		$mapper->_call('transformToObject', array('__identity' => array('key1' => 'value1', 'key2' => 'value2')), 'F3\Foo\Bar', 'someProp');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function transformToObjectCallsObjectConverterIfOneSupportsTheCurrentTargetType() {
		$propertyValue = array('foo' => 'bar');
		$expectedObject = new \stdClass;

		$mockObjectConverter = $this->getMock('F3\FLOW3\Property\ObjectConverterInterface');
		$mockObjectConverter->expects($this->once())->method('convertFrom')->with($propertyValue)->will($this->returnValue($expectedObject));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('dummy'));
		$mapper->_set('objectConverters', array('F3\Foo\Bar\Type' => $mockObjectConverter));
		$result = $mapper->_call('transformToObject', $propertyValue, 'F3\Foo\Bar\Type', 'propertyName');

		$this->assertSame($expectedObject, $result);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function transformToObjectConvertsAnArrayIntoAFreshObjectWithThePropertiesSetToTheArrayValuesIfDataTypeIsAClassAndNoIdentityInformationIsFoundInTheValue() {
		$theValue = array('property1' => 'value1', 'property2' => 'value2');
		$theObject = new \stdClass();

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('getObject')->with('F3\Foo\Bar')->will($this->returnValue($theObject));

		$mapper = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Property\PropertyMapper'), array('map'));
		$mapper->injectObjectManager($mockObjectManager);
		$mapper->expects($this->once())->method('map')->with(array('property1', 'property2'), $theValue, $theObject)->will($this->returnValue(TRUE));
		$mapper->_call('transformToObject', $theValue, 'F3\Foo\Bar', 'someProp');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function mapCallsTransformToObjectIfTargetIsAStringContainingAClassName() {
		$source = array();
		$target = '\F3\Foo\Bar';
		$mapper = $this->getMock('F3\FLOW3\Property\PropertyMapper', array('transformToObject'));
		$mapper->expects($this->once())->method('transformToObject')->with(array(), '\F3\Foo\Bar');

		$mapper->map(array(), $source, $target);
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function transformToObjectCallsMapIfSourcePropertiesRemainAfterObjectWasFound() {
		$this->markTestIncomplete('No test yet!');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findObjectByIdentityPropertiesDispatchesTheExpectedQuery() {
		$this->markTestIncomplete('No test yet!');
	}

}
?>