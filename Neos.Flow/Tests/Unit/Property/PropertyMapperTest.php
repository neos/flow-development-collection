<?php
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
use Neos\Flow\Property\Exception\InvalidTargetException;
use Neos\Flow\Property\Exception\TypeConverterException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;
use Neos\Flow\Property\TypeConverterInterface;
use Neos\Flow\Security\Exception;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\TypeHandling;

require_once(__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 */
class PropertyMapperTest extends UnitTestCase
{
    protected $mockConfiguration;

    /**
     * Sets up this test case
     *
     * @return void
     */
    public function setUp()
    {
        $this->mockConfiguration = $this->createMock(PropertyMappingConfigurationInterface::class);
    }

    /**
     * @return array
     */
    public function validSourceTypes()
    {
        return [
            ['someString', ['string']],
            [42, ['integer']],
            [3.5, ['float']],
            [true, ['boolean']],
            [[], ['array']],
            [new \stdClass(), ['stdClass', 'object']]
        ];
    }

    /**
     * @test
     * @dataProvider validSourceTypes
     */
    public function sourceTypeCanBeCorrectlyDetermined($source, $sourceTypes)
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $this->assertEquals($sourceTypes, $propertyMapper->_call('determineSourceTypes', $source));
    }

    /**
     * @return array
     */
    public function invalidSourceTypes()
    {
        return [
            [null]
        ];
    }

    /**
     * @test
     * @dataProvider invalidSourceTypes
     * @expectedException \Neos\Flow\Property\Exception\InvalidSourceException
     */
    public function sourceWhichIsNoSimpleTypeOrObjectThrowsException($source)
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_call('determineSourceTypes', $source);
    }

    /**
     * @param string $name
     * @param boolean $canConvertFrom
     * @param array $properties
     * @param string $typeOfSubObject
     * @return TypeConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockTypeConverter($name = '', $canConvertFrom = true, array $properties = [], $typeOfSubObject = '')
    {
        $mockTypeConverter = $this->createMock(TypeConverterInterface::class);
        $mockTypeConverter->_name = $name;
        $mockTypeConverter->expects($this->any())->method('canConvertFrom')->will($this->returnValue($canConvertFrom));
        $mockTypeConverter->expects($this->any())->method('convertFrom')->will($this->returnValue($name));
        $mockTypeConverter->expects($this->any())->method('getSourceChildPropertiesToBeConverted')->will($this->returnValue($properties));

        $mockTypeConverter->expects($this->any())->method('getTypeOfChildProperty')->will($this->returnValue($typeOfSubObject));
        return $mockTypeConverter;
    }

    /**
     * @test
     */
    public function findTypeConverterShouldReturnTypeConverterFromConfigurationIfItIsSet()
    {
        $mockTypeConverter = $this->getMockTypeConverter();
        $this->mockConfiguration->expects($this->any())->method('getTypeConverter')->will($this->returnValue($mockTypeConverter));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $this->assertSame($mockTypeConverter, $propertyMapper->_call('findTypeConverter', 'someSource', 'someTargetType', $this->mockConfiguration));
    }

    /**
     * Simple type conversion
     * @return array
     */
    public function dataProviderForFindTypeConverter()
    {
        return [
            ['someStringSource', 'string', [
                'string' => [
                    'string' => [
                        10 => $this->getMockTypeConverter('string2string,prio10'),
                        1 => $this->getMockTypeConverter('string2string,prio1')
                    ]
                ]], 'string2string,prio10'
            ],
            [['some' => 'array'], 'string', [
                'array' => [
                    'string' => [
                        10 => $this->getMockTypeConverter('array2string,prio10'),
                        1 => $this->getMockTypeConverter('array2string,prio1')
                    ]
                ]], 'array2string,prio10'
            ],
            ['someStringSource', 'bool', [
                'string' => [
                    'boolean' => [
                        10 => $this->getMockTypeConverter('string2boolean,prio10'),
                        1 => $this->getMockTypeConverter('string2boolean,prio1')
                    ]
                ]], 'string2boolean,prio10'
            ],
            ['someStringSource', 'int', [
                'string' => [
                    'integer' => [
                        10 => $this->getMockTypeConverter('string2integer,prio10'),
                        1 => $this->getMockTypeConverter('string2integer,prio1')
                    ]
                ]], 'string2integer,prio10'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForFindTypeConverter
     */
    public function findTypeConverterShouldReturnHighestPriorityTypeConverterForSimpleType($source, $targetType, $typeConverters, $expectedTypeConverter)
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);
        $actualTypeConverter = $propertyMapper->_call('findTypeConverter', $source, $targetType, $this->mockConfiguration);
        $this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
    }

    /**
     * @test
     */
    public function findEligibleConverterWithHighestPrioritySkipsConvertersWithNegativePriorities()
    {
        $internalTypeConverter1 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter1->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(-1));

        $internalTypeConverter2 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter2->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(-2));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $mockTypeConverters = [
            $internalTypeConverter1,
            $internalTypeConverter2,
        ];
        $this->assertNull($propertyMapper->_call('findEligibleConverterWithHighestPriority', $mockTypeConverters, 'foo', 'string'));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Property\Exception\TypeConverterException
     */
    public function findTypeConverterThrowsExceptionIfAllMatchingConvertersHaveNegativePriorities()
    {
        $internalTypeConverter1 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter1->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(-1));

        $internalTypeConverter2 = $this->getMockTypeConverter('string2string,prio-1');
        $internalTypeConverter2->expects($this->atLeastOnce())->method('getPriority')->will($this->returnValue(-2));

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
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
    public function dataProviderForObjectTypeConverters()
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

        // The most specific converter should win
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class3Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter')],
                $className3 => [0 => $this->getMockTypeConverter('Class3Converter')],

                $interfaceName1 => [0 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [0 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [0 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // In case the most specific converter does not want to handle this conversion, the second one is taken.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter')],
                $className3 => [0 => $this->getMockTypeConverter('Class3Converter', false)],

                $interfaceName1 => [0 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [0 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [0 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // In case there is no most-specific-converter, we climb ub the type hierarchy
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter'), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority')]
            ]
        ];

        // If no parent class converter wants to handle it, we ask for all interface converters.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [1 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // If two interface converters have the same priority, an exception is thrown.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [2 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter')],
            ],
            'shouldFailWithException' => DuplicateTypeConverterException::class
        ];

        // If no interface converter wants to handle it, a converter for "object" is looked up.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'GenericObjectConverter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter', false)],
                $interfaceName2 => [3 => $this->getMockTypeConverter('Interface2Converter', false)],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter', false)],
                'object' => [1 => $this->getMockTypeConverter('GenericObjectConverter'), 10 => $this->getMockTypeConverter('GenericObjectConverter-HighPriority')]
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
            'target' => new \stdClass,
            'expectedConverter' => '',
            'typeConverters' => [],
            'shouldFailWithException' => InvalidTargetException::class
        ];
        return $data;
    }

    /**
     * @test
     * @dataProvider dataProviderForObjectTypeConverters
     */
    public function findTypeConverterShouldReturnConverterForTargetObjectIfItExists($targetClass, $expectedTypeConverter, $typeConverters, $shouldFailWithException = false)
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', ['string' => $typeConverters]);
        try {
            $actualTypeConverter = $propertyMapper->_call('findTypeConverter', 'someSourceString', $targetClass, $this->mockConfiguration);
            if ($shouldFailWithException) {
                $this->fail('Expected exception ' . $shouldFailWithException . ' which was not thrown.');
            }
            $this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
        } catch (\Exception $e) {
            if ($shouldFailWithException === false) {
                throw $e;
            }
            $this->assertInstanceOf($shouldFailWithException, $e);
        }
    }

    /**
     * @test
     */
    public function convertShouldAskConfigurationBuilderForDefaultConfiguration()
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);

        $converter = $this->getMockTypeConverter('string2string');
        $typeConverters = [
            'string' => [
                'string' => [10 => $converter]
            ]
        ];

        $propertyMapper->_set('typeConverters', $typeConverters);
        $this->assertEquals('string2string', $propertyMapper->convert('source', 'string'));
    }

    /**
     * @test
     * @expectedException Exception
     */
    public function convertDoesNotCatchSecurityExceptions()
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['doMapping']);
        $propertyMapper->expects($this->once())->method('doMapping')->with('sourceType', 'targetType', $this->mockConfiguration)->will($this->throwException(new Exception()));

        $propertyMapper->convert('sourceType', 'targetType', $this->mockConfiguration);
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyShouldReturnNullIfSourceTypeIsUnknown()
    {
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $this->assertNull($propertyMapper->_call('findFirstEligibleTypeConverterInObjectHierarchy', 'source', 'unknownSourceType', Bootstrap::class));
    }

    /**
     * @test
     */
    public function doMappingReturnsSourceUnchangedIfAlreadyConverted()
    {
        $source = new \ArrayObject();
        $targetType = 'ArrayObject';
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    /**
     * @test
     */
    public function doMappingReturnsSourceUnchangedIfAlreadyConvertedToCompositeType()
    {
        $source = new \ArrayObject();
        $targetType = 'ArrayObject<SomeEntity>';
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    /**
     * @test
     */
    public function convertSkipsPropertiesIfConfiguredTo()
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

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipProperties('secondProperty'));

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function convertSkipsUnknownPropertiesIfConfiguredTo()
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

        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipUnknownProperties());

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }

    /**
     * @return array
     */
    public function convertCallsCanConvertFromWithTheFullNormalizedTargetTypeDataProvider()
    {
        return [
            ['source' => 'foo', 'fullTargetType' => 'string'],
            ['source' => 'foo', 'fullTargetType' => 'array'],
            ['source' => 'foo', 'fullTargetType' => 'array<string>'],
            ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage'],
            ['source' => 'foo', 'fullTargetType' => 'SplObjectStorage<Some\Element\Type>'],
        ];
    }

    /**
     * @test
     * @dataProvider convertCallsCanConvertFromWithTheFullNormalizedTargetTypeDataProvider
     */
    public function convertCallsCanConvertFromWithTheFullNormalizedTargetType($source, $fullTargetType)
    {
        $mockTypeConverter = $this->getMockTypeConverter();
        $mockTypeConverter->expects($this->atLeastOnce())->method('canConvertFrom')->with($source, $fullTargetType);
        $truncatedTargetType = TypeHandling::truncateElementType($fullTargetType);
        $mockTypeConverters = [
            gettype($source) => [
                $truncatedTargetType => [1 => $mockTypeConverter]
            ],
        ];
        $propertyMapper = $this->getAccessibleMock(PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $mockTypeConverters);

        $mockConfiguration = $this->getMockBuilder(PropertyMappingConfiguration::class)->disableOriginalConstructor()->getMock();
        $propertyMapper->convert($source, $fullTargetType, $mockConfiguration);

        // dummy assertion to avoid PHPUnit warning
        $this->assertTrue(true);
    }
}
