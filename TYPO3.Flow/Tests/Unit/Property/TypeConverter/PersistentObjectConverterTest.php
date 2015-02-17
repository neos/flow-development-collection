<?php
namespace TYPO3\Flow\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Fixtures\ClassWithSetters;
use TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Property\PropertyMappingConfiguration;
use TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter;
use TYPO3\Flow\Property\TypeConverterInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Tests\UnitTestCase;

require_once (__DIR__ . '/../../Fixtures/ClassWithSetters.php');
require_once (__DIR__ . '/../../Fixtures/ClassWithSettersAndConstructor.php');

/**
 * Testcase for the PersistentObjectConverter
 */
class PersistentObjectConverterTest extends UnitTestCase {

	/**
	 * @var TypeConverterInterface
	 */
	protected $converter;

	/**
	 * @var ReflectionService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockReflectionService;

	/**
	 * @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPersistenceManager;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	public function setUp() {
		$this->converter = new PersistentObjectConverter();
		$this->mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

		$this->mockPersistenceManager = $this->getMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->inject($this->converter, 'objectManager', $this->mockObjectManager);
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('string', 'array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @return array
	 */
	public function dataProviderForCanConvert() {
		return array(
			array(TRUE, FALSE, TRUE), // is entity => can convert
			array(FALSE, TRUE, TRUE), // is valueobject => can convert
			array(FALSE, FALSE, FALSE) // is no entity and no value object => can not convert
		);
	}

	/**
	 * @test
	 * @param boolean $isEntity
	 * @param boolean $isValueObject
	 * @param boolean $expected
	 * @dataProvider dataProviderForCanConvert
	 */
	public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected) {
		if ($isEntity) {
			$this->mockReflectionService->expects($this->once())->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\Entity')->will($this->returnValue($isEntity));
		} else {
			$this->mockReflectionService->expects($this->at(0))->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\Entity')->will($this->returnValue($isEntity));
			$this->mockReflectionService->expects($this->at(1))->method('isClassAnnotatedWith')->with('TheTargetType', 'TYPO3\Flow\Annotations\ValueObject')->will($this->returnValue($isValueObject));
		}

		$this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
	}

	/**
	 * @test
	 */
	public function getSourceChildPropertiesToBeConvertedReturnsAllPropertiesExceptTheIdentityProperty() {
		$source = array(
			'k1' => 'v1',
			'__identity' => 'someIdentity',
			'k2' => 'v2'
		);
		$expected = array(
			'k1' => 'v1',
			'k2' => 'v2'
		);
		$this->assertEquals($expected, $this->converter->getSourceChildPropertiesToBeConverted($source));
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType() {
		$mockSchema = $this->getMockBuilder('TYPO3\Flow\Reflection\ClassSchema')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('TheTargetType')->will($this->returnValue($mockSchema));

		$mockSchema->expects($this->any())->method('hasProperty')->with('thePropertyName')->will($this->returnValue(TRUE));
		$mockSchema->expects($this->any())->method('getProperty')->with('thePropertyName')->will($this->returnValue(array(
			'type' => 'TheTypeOfSubObject',
			'elementType' => NULL
		)));
		$configuration = $this->buildConfiguration(array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyShouldUseConfiguredTypeIfItWasSet() {
		$this->mockReflectionService->expects($this->never())->method('getClassSchema');

		$configuration = $this->buildConfiguration(array());
		$configuration->forProperty('thePropertyName')->setTypeConverterOption('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, 'Foo\Bar');
		$this->assertEquals('Foo\Bar', $this->converter->getTypeOfChildProperty('foo', 'thePropertyName', $configuration));
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyShouldConsiderSetters() {
		$mockSchema = $this->getMockBuilder('TYPO3\Flow\Reflection\ClassSchema')->disableOriginalConstructor()->getMock();
		$this->mockReflectionService->expects($this->any())->method('getClassSchema')->with('TheTargetType')->will($this->returnValue($mockSchema));

		$mockSchema->expects($this->any())->method('hasProperty')->with('virtualPropertyName')->will($this->returnValue(FALSE));

		$this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setVirtualPropertyName')->will($this->returnValue(TRUE));
		$this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', 'setVirtualPropertyName')->will($this->returnValue(array(
			array('type' => 'TheTypeOfSubObject')
		)));

		$configuration = $this->buildConfiguration(array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'virtualPropertyName', $configuration));
	}

	/**
	 * @test
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfUuidStringIsGiven() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();


		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
	}

	/**
	 * @test
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfNonUuidStringIsGiven() {
		$identifier = 'someIdentifier';
		$object = new \stdClass();

		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
	}

	/**
	 * @test
	 */
	public function convertFromShouldFetchObjectFromPersistenceIfOnlyIdentityArrayGiven() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();

		$source = array(
			'__identity' => $identifier
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->assertSame($object, $this->converter->convertFrom($source, 'MySpecialType'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeModifiedButConfigurationIsNotSet() {
		$identifier = '550e8400-e29b-11d4-a716-446655440000';
		$object = new \stdClass();
		$object->someProperty = 'asdf';

		$source = array(
			'__identity' => $identifier,
			'foo' => 'bar'
		);
		$this->mockPersistenceManager->expects($this->once())->method('getObjectByIdentifier')->with($identifier)->will($this->returnValue($object));
		$this->converter->convertFrom($source, 'MySpecialType', array('foo' => 'bar'));
	}

	/**
	 * @param array $typeConverterOptions
	 * @return PropertyMappingConfiguration
	 */
	protected function buildConfiguration($typeConverterOptions) {
		$configuration = new PropertyMappingConfiguration();
		$configuration->setTypeConverterOptions('TYPO3\Flow\Property\TypeConverter\PersistentObjectConverter', $typeConverterOptions);
		return $configuration;
	}

	/**
	 * @param integer $numberOfResults
	 * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $howOftenIsGetFirstCalled
	 * @return \stdClass
	 */
	public function setupMockQuery($numberOfResults, $howOftenIsGetFirstCalled) {
		$mockClassSchema = $this->getMock('TYPO3\Flow\Reflection\ClassSchema', array(), array('Dummy'));
		$mockClassSchema->expects($this->once())->method('getIdentityProperties')->will($this->returnValue(array('key1' => 'someType')));
		$this->mockReflectionService->expects($this->once())->method('getClassSchema')->with('SomeType')->will($this->returnValue($mockClassSchema));

		$mockConstraint = $this->getMockBuilder('TYPO3\Flow\Persistence\Generic\Qom\Comparison')->disableOriginalConstructor()->getMock();

		$mockObject = new \stdClass();
		$mockQuery = $this->getMock('TYPO3\Flow\Persistence\QueryInterface');
		$mockQueryResult = $this->getMock('TYPO3\Flow\Persistence\QueryResultInterface');
		$mockQueryResult->expects($this->once())->method('count')->will($this->returnValue($numberOfResults));
		$mockQueryResult->expects($howOftenIsGetFirstCalled)->method('getFirst')->will($this->returnValue($mockObject));
		$mockQuery->expects($this->once())->method('equals')->with('key1', 'value1')->will($this->returnValue($mockConstraint));
		$mockQuery->expects($this->once())->method('matching')->with($mockConstraint)->will($this->returnValue($mockQuery));
		$mockQuery->expects($this->once())->method('execute')->will($this->returnValue($mockQueryResult));

		$this->mockPersistenceManager->expects($this->once())->method('createQueryForType')->with('SomeType')->will($this->returnValue($mockQuery));

		return $mockObject;
	}

	/**
	 * @test
	 */
	public function convertFromShouldReturnFirstMatchingObjectIfMultipleIdentityPropertiesExist() {
		$mockObject = $this->setupMockQuery(1, $this->once());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$actual = $this->converter->convertFrom($source, 'SomeType');
		$this->assertSame($mockObject, $actual);
	}

	/**
	 * @test
	 */
	public function convertFromShouldReturnTargetNotFoundErrorIfNoMatchingObjectWasFound() {
		$this->setupMockQuery(0, $this->never());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$actual = $this->converter->convertFrom($source, 'SomeType');
		$this->assertInstanceOf('TYPO3\Flow\Property\TypeConverter\Error\TargetNotFoundError', $actual);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidSourceException
	 */
	public function convertFromShouldThrowExceptionIfIdentityIsOfInvalidType() {
		$source = array(
			'__identity' => new \stdClass(),
		);
		$this->converter->convertFrom($source, 'SomeType');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\DuplicateObjectException
	 */
	public function convertFromShouldThrowExceptionIfMoreThanOneObjectWasFound() {
		$this->setupMockQuery(2, $this->never());

		$source = array(
			'__identity' => array('key1' => 'value1', 'key2' => 'value2')
		);
		$this->converter->convertFrom($source, 'SomeType');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertFromShouldThrowExceptionIfObjectNeedsToBeCreatedButConfigurationIsNotSet() {
		$source = array(
			'foo' => 'bar'
		);
		$this->converter->convertFrom($source, 'MySpecialType');
	}

	/**
	 * @test
	 */
	public function convertFromShouldCreateObject() {
		$source = array(
			'propertyX' => 'bar'
		);
		$convertedChildProperties = array(
			'property1' => 'bar'
		);
		$expectedObject = new ClassWithSetters();
		$expectedObject->property1 = 'bar';

		$this->mockReflectionService->expects($this->once())->method('hasMethod')->with('TYPO3\Flow\Fixtures\ClassWithSetters', '__construct')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with('TYPO3\Flow\Fixtures\ClassWithSetters')->will($this->returnValue('TYPO3\Flow\Fixtures\ClassWithSetters'));
		$configuration = $this->buildConfiguration(array(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertEquals($expectedObject, $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfPropertyOnTargetObjectCouldNotBeSet() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new ClassWithSetters();
		$convertedChildProperties = array(
			'propertyNotExisting' => 'bar'
		);

		$this->mockReflectionService->expects($this->once())->method('hasMethod')->with('TYPO3\Flow\Fixtures\ClassWithSetters', '__construct')->will($this->returnValue(FALSE));
		$this->mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with('TYPO3\Flow\Fixtures\ClassWithSetters')->will($this->returnValue('TYPO3\Flow\Fixtures\ClassWithSetters'));
		$configuration = $this->buildConfiguration(array(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSetters', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 */
	public function convertFromShouldCreateObjectWhenThereAreConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$convertedChildProperties = array(
			'property1' => 'param1',
			'property2' => 'bar'
		);
		$expectedObject = new ClassWithSettersAndConstructor('param1');
		$expectedObject->setProperty2('bar');

		$this->mockReflectionService->expects($this->once())->method('hasMethod')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(TRUE));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$this->mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor')->will($this->returnValue('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor'));
		$configuration = $this->buildConfiguration(array(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertEquals($expectedObject, $result);
		$this->assertEquals('bar', $expectedObject->getProperty2());
	}

	/**
	 * @test
	 */
	public function convertFromShouldCreateObjectWhenThereAreOptionalConstructorParameters() {
		$source = array(
			'propertyX' => 'bar'
		);
		$expectedObject = new ClassWithSettersAndConstructor('thisIsTheDefaultValue');

		$this->mockReflectionService->expects($this->once())->method('hasMethod')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(TRUE));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => TRUE, 'defaultValue' => 'thisIsTheDefaultValue')
		)));
		$this->mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor')->will($this->returnValue('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor'));
		$configuration = $this->buildConfiguration(array(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', array(), $configuration);
		$this->assertEquals($expectedObject, $result);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidTargetException
	 */
	public function convertFromShouldThrowExceptionIfRequiredConstructorParameterWasNotFound() {
		$source = array(
			'propertyX' => 'bar'
		);
		$object = new ClassWithSettersAndConstructor('param1');
		$convertedChildProperties = array(
			'property2' => 'bar'
		);

		$this->mockReflectionService->expects($this->once())->method('hasMethod')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(TRUE));
		$this->mockReflectionService->expects($this->once())->method('getMethodParameters')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', '__construct')->will($this->returnValue(array(
			'property1' => array('optional' => FALSE)
		)));
		$this->mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor')->will($this->returnValue('TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor'));
		$configuration = $this->buildConfiguration(array(PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => TRUE));
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor', $convertedChildProperties, $configuration);
		$this->assertSame($object, $result);
	}

	/**
	 * @test
	 */
	public function convertFromShouldReturnNullForEmptyString() {
		$source = '';
		$result = $this->converter->convertFrom($source, 'TYPO3\Flow\Fixtures\ClassWithSettersAndConstructor');
		$this->assertNull($result);
	}

}
