<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Property;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Property\Exception\DuplicateTypeConverterException;
use Neos\Flow\Property\Exception\InvalidSourceException;
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Security\Exception;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\TypeHandling;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

require_once(__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 */
final class PropertyMapperTest extends UnitTestCase
{
    protected $mockConfiguration;
    protected static ?\WeakMap $mockTypeConverterNames = null;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function validSourceTypes(): \Iterator
    {
        yield ['someString', ['string']];
        yield [42, ['integer']];
        yield [3.5, ['float']];
        yield [true, ['boolean']];
        yield [[], ['array']];
        yield [new \stdClass(), ['stdClass', 'object']];
    }

    #[DataProvider('validSourceTypes')]
    #[Test]
    public function sourceTypeCanBeCorrectlyDetermined($source, $sourceTypes): void
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        self::assertEquals($sourceTypes, $propertyMapper->_call('determineSourceTypes', $source));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function invalidSourceTypes(): \Iterator
    {
        yield [null];
    }

    #[DataProvider('invalidSourceTypes')]
    #[Test]
    public function sourceWhichIsNoSimpleTypeOrObjectThrowsException($source): void
    {
        $this->expectException(InvalidSourceException::class);
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_call('determineSourceTypes', $source);
    }

    /**
     * @param string $name
     * @param boolean $canConvertFrom
     * @param array $properties
     * @param string $typeOfSubObject
     * @return TypeConverterInterface|MockObject
     */
    protected function getMockTypeConverter($name = '', $canConvertFrom = true, array $properties = [], $typeOfSubObject = '')
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $mockTypeConverter->method('canConvertFrom')->willReturn(($canConvertFrom));
        $mockTypeConverter->method('convertFrom')->willReturn(($name));
        $mockTypeConverter->method('getSourceChildPropertiesToBeConverted')->willReturn(($properties));

        $mockTypeConverter->method('getTypeOfChildProperty')->willReturn(($typeOfSubObject));

        if (self::$mockTypeConverterNames === null) {
            self::$mockTypeConverterNames = new \WeakMap();
        }
        self::$mockTypeConverterNames[$mockTypeConverter] = $name;

        return $mockTypeConverter;
    }

    #[Test]
    public function findTypeConverterShouldReturnTypeConverterFromConfigurationIfItIsSet(): void
    {
        $mockTypeConverter = $this->getMockTypeConverter();
        $this->mockConfiguration->method('getTypeConverter')->willReturn(($mockTypeConverter));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        self::assertSame($mockTypeConverter, $propertyMapper->_call('findTypeConverter', 'someSource', 'someTargetType', $this->mockConfiguration));
    }

    /**
     * Simple type conversion
     * @return \Iterator<(int | string), mixed>
     */
    public static function dataProviderForFindTypeConverter(): \Iterator
    {
        // Mocks are materialized in the test method; leaves are mock-name strings.
        yield ['someStringSource', 'string', [
            'string' => [
                'string' => [
                    10 => 'string2string,prio10',
                    1 => 'string2string,prio1'
                ]
            ]], 'string2string,prio10'
        ];
        yield [['some' => 'array'], 'string', [
            'array' => [
                'string' => [
                    10 => 'array2string,prio10',
                    1 => 'array2string,prio1'
                ]
            ]], 'array2string,prio10'
        ];
        yield ['someStringSource', 'bool', [
            'string' => [
                'boolean' => [
                    10 => 'string2boolean,prio10',
                    1 => 'string2boolean,prio1'
                ]
            ]], 'string2boolean,prio10'
        ];
        yield ['someStringSource', 'int', [
            'string' => [
                'integer' => [
                    10 => 'string2integer,prio10',
                    1 => 'string2integer,prio1'
                ]
            ]], 'string2integer,prio10'
        ];
    }

    #[DataProvider('dataProviderForFindTypeConverter')]
    #[Test]
    public function findTypeConverterShouldReturnHighestPriorityTypeConverterForSimpleType($source, $targetType, $typeConverters, $expectedTypeConverter): void
    {
        foreach ($typeConverters as $sourceType => $targetTypes) {
            foreach ($targetTypes as $tType => $priorities) {
                foreach ($priorities as $prio => $name) {
                    $typeConverters[$sourceType][$tType][$prio] = $this->getMockTypeConverter($name);
                }
            }
        }
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', $typeConverters);
        $actualTypeConverter = $propertyMapper->_call('findTypeConverter', $source, $targetType, $this->mockConfiguration);
        self::assertSame($expectedTypeConverter, self::$mockTypeConverterNames[$actualTypeConverter]);
    }

    #[Test]
    public function findEligibleConverterWithHighestPrioritySkipsConvertersWithNegativePriorities(): void
    {
        $internalTypeConverter1 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter1->expects($this->atLeastOnce())->method('getPriority')->willReturn((-1));

        $internalTypeConverter2 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter2->expects($this->atLeastOnce())->method('getPriority')->willReturn((-2));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $mockTypeConverters = [
            $internalTypeConverter1,
            $internalTypeConverter2,
        ];
        self::assertNull($propertyMapper->_call('findEligibleConverterWithHighestPriority', $mockTypeConverters, 'foo', 'string'));
    }

    #[Test]
    public function findTypeConverterThrowsExceptionIfAllMatchingConvertersHaveNegativePriorities(): void
    {
        $this->expectException(TypeConverterException::class);
        $internalTypeConverter1 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter1->expects($this->atLeastOnce())->method('getPriority')->willReturn((-1));

        $internalTypeConverter2 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter2->expects($this->atLeastOnce())->method('getPriority')->willReturn((-2));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', [
            'string' => [
                'string' => [
                    -1 => $internalTypeConverter1,
                    -2 => $internalTypeConverter2
                ],
            ],
        ]);
        $propertyMapper->_call('findTypeConverter', 'foo', 'string', $this->mockConfiguration);
    }

    /**
     * @return array
     */
    public static function dataProviderForObjectTypeConverters(): array
    {
        $data = [];

        $className1 = uniqid('Neos_Flow_Testclass1_', false);
        $className2 = uniqid('Neos_Flow_Testclass2_', false);
        $className3 = uniqid('Neos_Flow_Testclass3_', false);

        $interfaceName1 = uniqid('Neos_Flow_TestInterface1_', false);
        $interfaceName2 = uniqid('Neos_Flow_TestInterface2_', false);
        $interfaceName3 = uniqid('Neos_Flow_TestInterface3_', false);

        eval('
			interface ' . $interfaceName2 . ' {}
			interface ' . $interfaceName1 . ' {}

			interface ' . $interfaceName3 . ' extends ' . $interfaceName2 . ' {}

			class ' . $className1 . ' implements ' . $interfaceName1 . ' {}
			class ' . $className2 . ' extends ' . $className1 . ' {}
			class ' . $className3 . ' extends ' . $className2 . ' implements ' . $interfaceName3 . ' {}
		');

        // Mocks are materialized in the test method; leaves are [name, canConvertFrom?] specs.
        // The most specific converter should win
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class3Converter',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter']],
                $className3 => [0 => ['Class3Converter']],

                $interfaceName1 => [0 => ['Interface1Converter']],
                $interfaceName2 => [0 => ['Interface2Converter']],
                $interfaceName3 => [0 => ['Interface3Converter']],
            ]
        ];

        // In case the most specific converter does not want to handle this conversion, the second one is taken.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter']],
                $className3 => [0 => ['Class3Converter', false]],

                $interfaceName1 => [0 => ['Interface1Converter']],
                $interfaceName2 => [0 => ['Interface2Converter']],
                $interfaceName3 => [0 => ['Interface3Converter']],
            ]
        ];

        // In case there is no most-specific-converter, we climb ub the type hierarchy
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter'], 10 => ['Class2Converter-HighPriority']]
            ]
        ];

        // If no parent class converter wants to handle it, we ask for all interface converters.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter', false], 10 => ['Class2Converter-HighPriority', false]],

                $interfaceName1 => [4 => ['Interface1Converter']],
                $interfaceName2 => [1 => ['Interface2Converter']],
                $interfaceName3 => [2 => ['Interface3Converter']],
            ]
        ];

        // If two interface converters have the same priority, an exception is thrown.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter', false], 10 => ['Class2Converter-HighPriority', false]],

                $interfaceName1 => [4 => ['Interface1Converter']],
                $interfaceName2 => [2 => ['Interface2Converter']],
                $interfaceName3 => [2 => ['Interface3Converter']],
            ],
            'shouldFailWithException' => DuplicateTypeConverterException::class
        ];

        // If no interface converter wants to handle it, a converter for "object" is looked up.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'GenericObjectConverter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => ['Class2Converter', false], 10 => ['Class2Converter-HighPriority', false]],

                $interfaceName1 => [4 => ['Interface1Converter', false]],
                $interfaceName2 => [3 => ['Interface2Converter', false]],
                $interfaceName3 => [2 => ['Interface3Converter', false]],
                'object' => [1 => ['GenericObjectConverter'], 10 => ['GenericObjectConverter-HighPriority']]
            ],
        ];

        // If the target is no valid class name and no simple type, an exception is thrown
        $data[] = [
            'target' => 'SomeNotExistingClassName',
            'expectedConverter' => 'GenericObjectConverter-HighPriority',
            'typeConverters' => [],
            'shouldFailWithException' => InvalidTargetException::class
        ];

        // if the type converter is not found, we expect an exception
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class3Converter',
            'typeConverters' => [],
            'shouldFailWithException' => TypeConverterException::class
        ];

        // If The target type is no string, we expect an exception.
        $data[] = [
            'target' => new \stdClass(),
            'expectedConverter' => '',
            'typeConverters' => [],
            'shouldFailWithException' => InvalidTargetException::class
        ];
        return $data;
    }

    #[DataProvider('dataProviderForObjectTypeConverters')]
    #[Test]
    public function findTypeConverterShouldReturnConverterForTargetObjectIfItExists($target, $expectedConverter, $typeConverters, $shouldFailWithException = false): void
    {
        foreach ($typeConverters as $key => $priorities) {
            foreach ($priorities as $prio => $spec) {
                $typeConverters[$key][$prio] = $this->getMockTypeConverter($spec[0], $spec[1] ?? true);
            }
        }
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', ['string' => $typeConverters]);
        try {
            $actualTypeConverter = $propertyMapper->_call('findTypeConverter', 'someSourceString', $target, $this->mockConfiguration);
            if ($shouldFailWithException) {
                $this->fail('Expected exception ' . $shouldFailWithException . ' which was not thrown.');
            }
            self::assertSame($expectedConverter, self::$mockTypeConverterNames[$actualTypeConverter]);
        } catch (\Exception $e) {
            if ($shouldFailWithException === false) {
                throw $e;
            }
            self::assertInstanceOf($shouldFailWithException, $e);
        }
    }

    #[Test]
    public function convertShouldAskConfigurationBuilderForDefaultConfiguration(): void
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);

        $converter = $this->getMockTypeConverter('string2string');
        $typeConverters = [
            'string' => [
                'string' => [10 => $converter]
            ]
        ];

        $propertyMapper->_set('typeConverters', $typeConverters);
        self::assertEquals('string2string', $propertyMapper->convert('source', 'string'));
    }

    #[Test]
    public function convertDoesNotCatchSecurityExceptions(): void
    {
        $this->expectException(Exception::class);
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['doMapping']);
        $propertyMapper->expects($this->once())->method('doMapping')->with('sourceType', 'targetType', $this->mockConfiguration)->willThrowException(new Exception());

        $propertyMapper->convert('sourceType', 'targetType', $this->mockConfiguration);
    }

    #[Test]
    public function findFirstEligibleTypeConverterInObjectHierarchyShouldReturnNullIfSourceTypeIsUnknown(): void
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        self::assertNull($propertyMapper->_call('findFirstEligibleTypeConverterInObjectHierarchy', 'source', 'unknownSourceType', Bootstrap::class));
    }

    #[Test]
    public function doMappingReturnsSourceUnchangedIfAlreadyConverted(): void
    {
        $source = new \ArrayObject();
        $targetType = 'ArrayObject';
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        self::assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    #[Test]
    public function doMappingReturnsSourceUnchangedIfAlreadyConvertedToCompositeType(): void
    {
        $source = new \ArrayObject();
        $targetType = 'ArrayObject<SomeEntity>';
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        self::assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function convertSkipsPropertiesIfConfiguredTo(): void
    {
        $source = ['firstProperty' => 1, 'secondProperty' => 2];
        $typeConverters = [
            'array' => [
                'stdClass' => [10 => $this->getMockTypeConverter('array2object', true, $source, 'integer')]
            ],
            'integer' => [
                'integer' => [10 => $this->getMockTypeConverter('integer2integer')]
            ]
        ];
        $configuration = new PropertyMappingConfiguration();

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipProperties('secondProperty'));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function convertSkipsUnknownPropertiesIfConfiguredTo(): void
    {
        $source = ['firstProperty' => 1, 'secondProperty' => 2];
        $typeConverters = [
            'array' => [
                'stdClass' => [10 => $this->getMockTypeConverter('array2object', true, $source, 'integer')]
            ],
            'integer' => [
                'integer' => [10 => $this->getMockTypeConverter('integer2integer')]
            ]
        ];
        $configuration = new PropertyMappingConfiguration();

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipUnknownProperties());
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function convertCallsCanConvertFromWithTheFullNormalizedTargetTypeDataProvider(): \Iterator
    {
        yield ['source' => 'foo', 'fullTargetType' => 'string'];
        yield ['source' => 'foo', 'fullTargetType' => 'array'];
        yield ['source' => 'foo', 'fullTargetType' => 'array<string>'];
        yield ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage'];
        yield ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage<Some\Element\Type>'];
    }

    #[DataProvider('convertCallsCanConvertFromWithTheFullNormalizedTargetTypeDataProvider')]
    #[Test]
    public function convertCallsCanConvertFromWithTheFullNormalizedTargetType($source, $fullTargetType): void
    {
        $mockTypeConverter = $this->getMockTypeConverter();
        $mockTypeConverter->expects($this->atLeastOnce())->method('canConvertFrom')->with($source, $fullTargetType);
        $truncatedTargetType = TypeHandling::truncateElementType($fullTargetType);
        $mockTypeConverters = [
            gettype($source) => [
                $truncatedTargetType => [1 => $mockTypeConverter]
            ],
        ];
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', $mockTypeConverters);

        $mockConfiguration = $this->createStub(PropertyMappingConfiguration::class);
        $propertyMapper->convert($source, $fullTargetType, $mockConfiguration);
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function convertCallsCanConvertFromWithNullableTargetTypeDataProvider(): \Iterator
    {
        yield ['source' => 'foo', 'fullTargetType' => 'string|null'];
        yield ['source' => 'foo', 'fullTargetType' => 'array|null'];
        yield ['source' => 'foo', 'fullTargetType' => 'array<string>|null'];
        yield ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage|null'];
        yield ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage<Some\Element\Type>|null'];
    }

    #[DataProvider('convertCallsCanConvertFromWithNullableTargetTypeDataProvider')]
    #[Test]
    public function convertCallsCanConvertFromWithNullableTargetType($source, $fullTargetType): void
    {
        $fullTargetTypeWithoutNull = TypeHandling::stripNullableType($fullTargetType);
        $mockTypeConverter = $this->getMockTypeConverter();
        $mockTypeConverter->expects($this->atLeastOnce())->method('canConvertFrom')->with($source, $fullTargetTypeWithoutNull);
        $truncatedTargetType = TypeHandling::truncateElementType($fullTargetTypeWithoutNull);
        $mockTypeConverters = [
            gettype($source) => [
                $truncatedTargetType => [1 => $mockTypeConverter]
            ],
        ];
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);
        $propertyMapper->_set('typeConverters', $mockTypeConverters);

        $mockConfiguration = $this->createStub(PropertyMappingConfiguration::class);
        $propertyMapper->convert($source, $fullTargetType, $mockConfiguration);
    }

    #[Test]
    public function convertCallsConvertToNullWithNullableTargetType(): void
    {
        $source = null;
        $fullTargetType = 'SplObjectStorage|null';

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, []);

        $mockConfiguration = $this->createStub(PropertyMappingConfiguration::class);
        self::assertEquals(null, $propertyMapper->convert($source, $fullTargetType, $mockConfiguration));
    }
}
