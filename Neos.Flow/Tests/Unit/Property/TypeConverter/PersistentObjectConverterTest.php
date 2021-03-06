<?php
namespace Neos\Flow\Tests\Unit\Property\TypeConverter;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Fixtures\ClassWithSetters;
use Neos\Flow\Fixtures\ClassWithSettersAndConstructor;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence;
use Neos\Flow\Property\Exception\DuplicateObjectException;
use Neos\Flow\Property\Exception\InvalidPropertyMappingConfigurationException;
use Neos\Flow\Property\Exception\InvalidSourceException;
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\Error\TargetNotFoundError;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Reflection\ClassSchema;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Annotations as Flow;

require_once(__DIR__ . '/../../Fixtures/ClassWithSetters.php');
require_once(__DIR__ . '/../../Fixtures/ClassWithSettersAndConstructor.php');

/**
 * Testcase for the PersistentObjectConverter
 */
class PersistentObjectConverterTest extends UnitTestCase
{
    /**
     * @var TypeConverterInterface
     */
    protected $converter;

    /**
     * @var ReflectionService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockReflectionService;

    /**
     * @var Persistence\PersistenceManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    protected function setUp(): void
    {
        $this->converter = new PersistentObjectConverter();
        $this->mockReflectionService = $this->createMock(ReflectionService::class);
        $this->inject($this->converter, 'reflectionService', $this->mockReflectionService);

        $this->mockPersistenceManager = $this->createMock(Persistence\PersistenceManagerInterface::class);
        $this->inject($this->converter, 'persistenceManager', $this->mockPersistenceManager);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->inject($this->converter, 'objectManager', $this->mockObjectManager);
    }

    /**
     * @test
     */
    public function checkMetadata()
    {
        self::assertEquals(['string', 'array'], $this->converter->getSupportedSourceTypes(), 'Source types do not match');
        self::assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
        self::assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
    }

    /**
     * @return array
     */
    public function dataProviderForCanConvert()
    {
        return [
            [true, false, true], // is entity => can convert
            [false, true, true], // is valueobject => can convert
            [false, false, false] // is no entity and no value object => can not convert
        ];
    }

    /**
     * @test
     * @param boolean $isEntity
     * @param boolean $isValueObject
     * @param boolean $expected
     * @dataProvider dataProviderForCanConvert
     */
    public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($isEntity, $isValueObject, $expected)
    {
        if ($isEntity) {
            $this->mockReflectionService->expects(self::once())->method('isClassAnnotatedWith')->with('TheTargetType', Flow\Entity::class)->will(self::returnValue($isEntity));
        } else {
            $this->mockReflectionService->expects(self::atLeast(2))->method('isClassAnnotatedWith')->withConsecutive(['TheTargetType', Flow\Entity::class], ['TheTargetType', Flow\ValueObject::class])->willReturnOnConsecutiveCalls($isEntity, $isValueObject);
        }

        self::assertEquals($expected, $this->converter->canConvertFrom('myInputData', 'TheTargetType'));
    }

    /**
     * @test
     */
    public function getSourceChildPropertiesToBeConvertedReturnsAllPropertiesExceptTheIdentityProperty()
    {
        $source = [
            'k1' => 'v1',
            '__identity' => 'someIdentity',
            'k2' => 'v2'
        ];
        $expected = [
            'k1' => 'v1',
            'k2' => 'v2'
        ];
        self::assertEquals($expected, $this->converter->getSourceChildPropertiesToBeConverted($source));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType()
    {
        $mockSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->expects(self::any())->method('getClassSchema')->with('TheTargetType')->will(self::returnValue($mockSchema));

        $mockSchema->expects(self::any())->method('hasProperty')->with('thePropertyName')->will(self::returnValue(true));
        $mockSchema->expects(self::any())->method('getProperty')->with('thePropertyName')->will(self::returnValue([
            'type' => 'TheTypeOfSubObject',
            'elementType' => null
        ]));
        $configuration = $this->buildConfiguration([]);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldUseConfiguredTypeIfItWasSet()
    {
        $this->mockReflectionService->expects(self::never())->method('getClassSchema');

        $configuration = $this->buildConfiguration([]);
        $configuration->forProperty('thePropertyName')->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, 'Foo\Bar');
        self::assertEquals('Foo\Bar', $this->converter->getTypeOfChildProperty('foo', 'thePropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldConsiderSetters()
    {
        $mockSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->expects(self::any())->method('getClassSchema')->with('TheTargetType')->will(self::returnValue($mockSchema));

        $mockSchema->expects(self::any())->method('hasProperty')->with('virtualPropertyName')->will(self::returnValue(false));

        $this->mockReflectionService->expects(self::any())->method('hasMethod')->with('TheTargetType', 'setVirtualPropertyName')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::any())->method('getMethodParameters')->will($this->returnValueMap([
            ['TheTargetType', '__construct', []],
            ['TheTargetType', 'setVirtualPropertyName', [['type' => 'TheTypeOfSubObject']]]
        ]));

        $this->mockReflectionService->expects(self::any())->method('hasMethod')->with('TheTargetType', 'setVirtualPropertyName')->will(self::returnValue(true));
        $this->mockReflectionService
            ->expects(self::exactly(2))
            ->method('getMethodParameters')
            ->withConsecutive(
                [self::equalTo('TheTargetType'), self::equalTo('__construct')],
                [self::equalTo('TheTargetType'), self::equalTo('setVirtualPropertyName')]
            )
            ->will(self::returnValue([
                ['type' => 'TheTypeOfSubObject']
            ]));
        $configuration = $this->buildConfiguration([]);
        self::assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'virtualPropertyName', $configuration));
    }

    /**
     * @test
     */
    public function getTypeOfChildPropertyShouldConsiderConstructors()
    {
        $mockSchema = $this->getMockBuilder(ClassSchema::class)->disableOriginalConstructor()->getMock();
        $this->mockReflectionService->expects(self::any())->method('getClassSchema')->with('TheTargetType')->will(self::returnValue($mockSchema));
        $this->mockReflectionService
            ->expects(self::exactly(1))
            ->method('getMethodParameters')
            ->with('TheTargetType', '__construct')
            ->will(self::returnValue([
                'anotherProperty' => ['type' => 'string']
            ]));

        $configuration = $this->buildConfiguration([]);
        self::assertEquals('string', $this->converter->getTypeOfChildProperty('TheTargetType', 'anotherProperty', $configuration));
    }


    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfUuidStringIsGiven()
    {
        $identifier = '550e8400-e29b-11d4-a716-446655440000';
        $object = new \stdClass();

        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier)->will(self::returnValue($object));
        self::assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
    }

    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfNonUuidStringIsGiven()
    {
        $identifier = 'someIdentifier';
        $object = new \stdClass();

        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier)->will(self::returnValue($object));
        self::assertSame($object, $this->converter->convertFrom($identifier, 'MySpecialType'));
    }

    /**
     * @test
     */
    public function convertFromShouldFetchObjectFromPersistenceIfOnlyIdentityArrayGiven()
    {
        $identifier = '550e8400-e29b-11d4-a716-446655440000';
        $object = new \stdClass();

        $source = [
            '__identity' => $identifier
        ];
        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier)->will(self::returnValue($object));
        self::assertSame($object, $this->converter->convertFrom($source, 'MySpecialType'));
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfObjectNeedsToBeModifiedButConfigurationIsNotSet()
    {
        $this->expectException(InvalidPropertyMappingConfigurationException::class);
        $identifier = '550e8400-e29b-11d4-a716-446655440000';
        $object = new \stdClass();
        $object->someProperty = 'asdf';

        $source = [
            '__identity' => $identifier,
            'foo' => 'bar'
        ];
        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier)->will(self::returnValue($object));
        $this->converter->convertFrom($source, 'MySpecialType', ['foo' => 'bar']);
    }

    /**
     * @test
     */
    public function convertFromReturnsTargetNotFoundErrorIfHandleArrayDataFails()
    {
        $identifier = '550e8400-e29b-11d4-a716-446655440000';
        $object = new \stdClass();
        $object->someProperty = 'asdf';

        $source = [
            '__identity' => $identifier,
            'foo' => 'bar'
        ];
        $this->mockPersistenceManager->expects(self::once())->method('getObjectByIdentifier')->with($identifier)->will(self::returnValue(null));
        $actualResult = $this->converter->convertFrom($source, 'MySpecialType', ['foo' => 'bar']);

        self::assertInstanceOf(TargetNotFoundError::class, $actualResult);
    }

    /**
     * @param array $typeConverterOptions
     * @return PropertyMappingConfiguration
     */
    protected function buildConfiguration($typeConverterOptions)
    {
        $configuration = new PropertyMappingConfiguration();
        $configuration->setTypeConverterOptions(PersistentObjectConverter::class, $typeConverterOptions);
        return $configuration;
    }

    /**
     * @param integer $numberOfResults
     * @param \PHPUnit_Framework_MockObject_Matcher_Invocation $howOftenIsGetFirstCalled
     * @return \stdClass
     */
    protected function setUpMockQuery($numberOfResults, $howOftenIsGetFirstCalled)
    {
        $mockClassSchema = $this->createMock(ClassSchema::class, [], ['Dummy']);
        $mockClassSchema->expects(self::once())->method('getIdentityProperties')->will(self::returnValue(['key1' => 'someType']));
        $this->mockReflectionService->expects(self::once())->method('getClassSchema')->with('SomeType')->will(self::returnValue($mockClassSchema));

        $mockConstraint = $this->getMockBuilder(Persistence\Generic\Qom\Comparison::class)->disableOriginalConstructor()->getMock();

        $mockObject = new \stdClass();
        $mockQuery = $this->createMock(Persistence\QueryInterface::class);
        $mockQueryResult = $this->createMock(Persistence\QueryResultInterface::class);
        $mockQueryResult->expects(self::once())->method('count')->will(self::returnValue($numberOfResults));
        $mockQueryResult->expects($howOftenIsGetFirstCalled)->method('getFirst')->will(self::returnValue($mockObject));
        $mockQuery->expects(self::once())->method('equals')->with('key1', 'value1')->will(self::returnValue($mockConstraint));
        $mockQuery->expects(self::once())->method('matching')->with($mockConstraint)->will(self::returnValue($mockQuery));
        $mockQuery->expects(self::once())->method('execute')->will(self::returnValue($mockQueryResult));

        $this->mockPersistenceManager->expects(self::once())->method('createQueryForType')->with('SomeType')->will(self::returnValue($mockQuery));

        return $mockObject;
    }

    /**
     * @test
     */
    public function convertFromShouldReturnFirstMatchingObjectIfMultipleIdentityPropertiesExist()
    {
        $mockObject = $this->setupMockQuery(1, self::once());

        $source = [
            '__identity' => ['key1' => 'value1', 'key2' => 'value2']
        ];
        $actual = $this->converter->convertFrom($source, 'SomeType');
        self::assertSame($mockObject, $actual);
    }

    /**
     * @test
     */
    public function convertFromShouldReturnTargetNotFoundErrorIfNoMatchingObjectWasFound()
    {
        $this->setupMockQuery(0, self::never());

        $source = [
            '__identity' => ['key1' => 'value1', 'key2' => 'value2']
        ];
        $actual = $this->converter->convertFrom($source, 'SomeType');
        self::assertInstanceOf(TargetNotFoundError::class, $actual);
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfIdentityIsOfInvalidType()
    {
        $this->expectException(InvalidSourceException::class);
        $source = [
            '__identity' => new \stdClass(),
        ];
        $this->converter->convertFrom($source, 'SomeType');
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfMoreThanOneObjectWasFound()
    {
        $this->expectException(DuplicateObjectException::class);
        $this->setupMockQuery(2, self::never());

        $source = [
            '__identity' => ['key1' => 'value1', 'key2' => 'value2']
        ];
        $this->converter->convertFrom($source, 'SomeType');
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfObjectNeedsToBeCreatedButConfigurationIsNotSet()
    {
        $this->expectException(InvalidPropertyMappingConfigurationException::class);
        $source = [
            'foo' => 'bar'
        ];
        $this->converter->convertFrom($source, 'MySpecialType');
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObject()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $convertedChildProperties = [
            'property1' => 'bar'
        ];
        $expectedObject = new ClassWithSetters();
        $expectedObject->property1 = 'bar';

        $this->mockReflectionService->expects(self::once())->method('hasMethod')->with(ClassWithSetters::class, '__construct')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with(ClassWithSetters::class)->will(self::returnValue(ClassWithSetters::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, ClassWithSetters::class, $convertedChildProperties, $configuration);
        self::assertEquals($expectedObject, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfPropertyOnTargetObjectCouldNotBeSet()
    {
        $this->expectException(InvalidTargetException::class);
        $source = [
            'propertyX' => 'bar'
        ];
        $object = new ClassWithSetters();
        $convertedChildProperties = [
            'propertyNotExisting' => 'bar'
        ];

        $this->mockReflectionService->expects(self::once())->method('hasMethod')->with(ClassWithSetters::class, '__construct')->will(self::returnValue(false));
        $this->mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with(ClassWithSetters::class)->will(self::returnValue(ClassWithSetters::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, ClassWithSetters::class, $convertedChildProperties, $configuration);
        self::assertSame($object, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObjectWhenThereAreConstructorParameters()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $convertedChildProperties = [
            'property1' => 'param1',
            'property2' => 'bar'
        ];
        $expectedObject = new ClassWithSettersAndConstructor('param1');
        $expectedObject->setProperty2('bar');

        $this->mockReflectionService->expects(self::once())->method('hasMethod')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::once())->method('getMethodParameters')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue([
            'property1' => ['optional' => false]
        ]));
        $this->mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with(ClassWithSettersAndConstructor::class)->will(self::returnValue(ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, ClassWithSettersAndConstructor::class, $convertedChildProperties, $configuration);
        self::assertEquals($expectedObject, $result);
        self::assertEquals('bar', $expectedObject->getProperty2());
    }

    /**
     * @test
     */
    public function convertFromShouldCreateObjectWhenThereAreOptionalConstructorParameters()
    {
        $source = [
            'propertyX' => 'bar'
        ];
        $expectedObject = new ClassWithSettersAndConstructor('thisIsTheDefaultValue');

        $this->mockReflectionService->expects(self::once())->method('hasMethod')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::once())->method('getMethodParameters')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue([
            'property1' => ['optional' => true, 'defaultValue' => 'thisIsTheDefaultValue']
        ]));
        $this->mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with(ClassWithSettersAndConstructor::class)->will(self::returnValue(ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, ClassWithSettersAndConstructor::class, [], $configuration);
        self::assertEquals($expectedObject, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldThrowExceptionIfRequiredConstructorParameterWasNotFound()
    {
        $this->expectException(InvalidTargetException::class);
        $source = [
            'propertyX' => 'bar'
        ];
        $object = new ClassWithSettersAndConstructor('param1');
        $convertedChildProperties = [
            'property2' => 'bar'
        ];

        $this->mockReflectionService->expects(self::once())->method('hasMethod')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue(true));
        $this->mockReflectionService->expects(self::once())->method('getMethodParameters')->with(ClassWithSettersAndConstructor::class, '__construct')->will(self::returnValue([
            'property1' => ['optional' => false, 'type' => null]
        ]));
        $this->mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with(ClassWithSettersAndConstructor::class)->will(self::returnValue(ClassWithSettersAndConstructor::class));
        $configuration = $this->buildConfiguration([PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED => true]);
        $result = $this->converter->convertFrom($source, ClassWithSettersAndConstructor::class, $convertedChildProperties, $configuration);
        self::assertSame($object, $result);
    }

    /**
     * @test
     */
    public function convertFromShouldReturnNullForEmptyString()
    {
        $source = '';
        $result = $this->converter->convertFrom($source, ClassWithSettersAndConstructor::class);
        self::assertNull($result);
    }
}
