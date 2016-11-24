<?php
namespace Neos\Flow\Tests\Unit\Utility;

/*
 * This file is part of the Neos.Utility.Schema package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\PackageManager;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Utility\SchemaValidator;
use Neos\Error\Messages as Error;

/**
 * Testcase for the configuration validator
 */
class SchemaValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SchemaValidator
     */
    protected $configurationValidator;

    public function setUp()
    {
        $this->configurationValidator = $this->getMockBuilder(SchemaValidator::class)->setMethods(['getError'])->getMock();
    }

    /**
     * Handle the assertion that the given result object has errors
     *
     * @param Error\Result $result
     * @param boolean $expectError
     * @return void
     */
    protected function assertError(Error\Result $result, $expectError = true)
    {
        if ($expectError === true) {
            $this->assertTrue($result->hasErrors());
        } else {
            $this->assertFalse($result->hasErrors());
        }
    }

    /**
     * Handle the assertion that the given result object has no errors
     *
     * @param Error\Result $result
     * @param boolean $expectSuccess
     * @return void
     */
    protected function assertSuccess(Error\Result $result, $expectSuccess = true)
    {
        if ($expectSuccess === true) {
            $this->assertFalse($result->hasErrors());
        } else {
            $this->assertTrue($result->hasErrors());
        }
    }

    /**
     * @return array
     */
    public function validateHandlesRequiredPropertyDataProvider()
    {
        return [
            [['foo' => 'a string'], true],
            [['foo' => 'a string', 'bar' => 'a string'], true],
            [['foo' => 'a string', 'bar' => 123], false],
            [['foo' => 'a string', 'bar' => 'a string'], true],
            [['foo' => 123, 'bar' => 'a string'], false],
            [['foo' => null, 'bar' => 'a string'], false],
            [['bar' => 'string'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesRequiredPropertyDataProvider
     */
    public function validateHandlesRequiredProperty($value, $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => [
                    'type' => 'string',
                    'required' => true
                ],
                'bar' => 'string'
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesDisallowPropertyDataProvider()
    {
        return [
            ['string', true],
            [123, false],
            [[1,2,3], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDisallowPropertyDataProvider
     */
    public function validateHandlesDisallowProperty($value, $expectSuccess)
    {
        $schema = [
            'disallow' => ['integer','array']
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesEnumPropertyDataProvider()
    {
        return [
            [1, true],
            [2, true],
            [null, false],
            [4, false],
            [[1,2,3], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesEnumPropertyDataProvider
     */
    public function validateHandlesEnumProperty($value, $expectSuccess)
    {
        $schema = [
            'enum' => [1,2,3]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @test
     */
    public function validateReturnsErrorPath()
    {
        $value = [
            'foo' => [
                'bar' => [
                    'baz' => 'string'
                ]
            ]
        ];

        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => [
                    'type' => 'dictionary',
                    'properties' => [
                        'bar' => [
                            'type' => 'dictionary',
                            'properties' => [
                                'baz' => 'number'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertError($result);

        $allErrors = $result->getFlattenedErrors();
        $this->assertTrue(array_key_exists('foo.bar.baz', $allErrors));

        $pathErrors = $result->forProperty('foo.bar.baz')->getErrors();
        $firstPathError = $pathErrors[0];
        $this->assertEquals($firstPathError->getCode(), 1328557141);
        $this->assertEquals($firstPathError->getArguments(), ['type=number', 'type=string']);
    }

    /**
     * @return array
     */
    public function validateHandlesMultipleTypesDataProvider()
    {
        return [
            [['property' => 'value'], true],
            ['value', true],
            [false, false],
            [123, false],
            [[1,2,3], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypes($value, $expectSuccess)
    {
        $schema = ['dictionary', 'string'];

        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypesInSchemaType($value, $expectSuccess)
    {
        $schema = [
            'type' => ['dictionary', 'string']
        ];
        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypesInSubProperty($value, $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => [
                    'type' => ['dictionary', 'string']
                ]
            ]
        ];
        $result = $this->configurationValidator->validate(['foo' => $value], $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /// INTEGER ///

    /**
     * @return array
     */
    public function validateHandlesIntegerTypePropertyDataProvider()
    {
        return [
            [23, true],
            ['foo', false],
            [23.42, false],
            [[], false],
            [null, false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesIntegerTypePropertyDataProvider
     */
    public function validateHandlesIntegerTypeProperty($value, $expectSuccess)
    {
        $schema = [
            'type' => 'integer'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /// NUMBER ///

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyDataProvider()
    {
        return [
            [23.42, true],
            [42, true],
            ['foo', false],
            [null, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyDataProvider
     */
    public function validateHandlesNumberTypeProperty($value, $expectSuccess)
    {
        $schema = [
            'type' => 'number'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider()
    {
        return [
            [33, true],
            [99, false],
            [1, false],
            [23, true],
            [42, true]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = [
            'type' => 'number',
            'minimum' => 23,
            'maximum' => 42
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithNonExclusiveMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = [
            'type' => 'number',
            'minimum' => 23,
            'exclusiveMinimum' => false,
            'maximum' => 42,
            'exclusiveMaximum' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider()
    {
        return [
            [10, false],
            [22, false],
            [23, true],
            [42, true],
            [43, false],
            [99, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = [
            'type' => 'number',
            'minimum' => 22,
            'exclusiveMinimum' => true,
            'maximum' => 43,
            'exclusiveMaximum' => true
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider()
    {
        return [
            [4, true],
            [3, false],
            [-3, false],
            [-4, true],
            [0, true],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithDivisibleByConstraint($value, $expectSuccess)
    {
        $schema = [
            'type' => 'number',
            'divisibleBy' => 2
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /// STRING ///

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyDataProvider()
    {
        return [
            ['FooBar', true],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyDataProvider
     */
    public function validateHandlesStringTypeProperty($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithPatternConstraintDataProvider()
    {
        return [
            ['12a', true],
            ['1236', false],
            ['12c', false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithPatternConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithPatternConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'pattern' => '/^[123ab]{3}$/'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider()
    {
        return [
            ['01:25:00', false],
            ['1976-04-18', false],
            ['1976-04-18T01:25:00+00:00', true],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithDateTimeConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'date-time'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider()
    {
        return [
            ['01:25:00', false],
            ['1976-04-18', true],
            ['1976-04-18T01:25:00+00:00', false],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatDateConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'date'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider()
    {
        return [
            ['01:25:00', true],
            ['1976-04-18', false],
            ['1976-04-18T01:25:00+00:00', false],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatTimeConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'time'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider()
    {
        return [
            ['http://foo.bar.de', true],
            ['ftp://dasdas.de/foo/bar/?asds=123&dasdasd#dasdas', true],
            ['foo', false],
            [123, false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatUriPConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'uri'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider()
    {
        return [
            ['www.neos.io', true],
            ['this.is.an.invalid.hostname', false],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatHostnameConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'host-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider()
    {
        return [
            ['2001:0db8:85a3:08d3:1319:8a2e:0370:7344', false],
            ['123.132.123.132', true],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpv4Constraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ipv4'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider()
    {
        return [
            ['2001:0db8:85a3:08d3:1319:8a2e:0370:7344', true],
            ['123.132.123.132', false],
            ['foobar', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpv6Constraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ipv6'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider()
    {
        return [
            ['2001:0db8:85a3:08d3:1319:8a2e:0370:7344', true],
            ['123.132.123.132', true],
            ['foobar', false],
            ['ab1', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpAddressConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ip-address'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider()
    {
        return [
            [PackageManager::class, true],
            ['Neos\Flow\UnknownClass', false],
            ['foobar', false],
            ['foo bar', false],
            ['foo/bar', false],
            ['flow/welcome', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'class-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider()
    {
        return [
            [PackageManagerInterface::class, true],
            ['\Neos\Flow\UnknownClass', false],
            ['foobar', false],
            ['foo bar', false],
            ['foo/bar', false],
            ['flow/welcome', false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'format' => 'interface-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider()
    {
        return [
            ['12356', true],
            ['1235', true],
            ['123', false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithMinLengthConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'minLength' => 4
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider()
    {
        return [
            ['123', true],
            ['1234', true],
            ['12345', false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithMaxLengthConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'string',
            'maxLength' => 4
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }


    /// BOOLEAN ///

    /**
     * @return array
     */
    public function validateHandlesBooleanTypeDataProvider()
    {
        return [
            [true, true],
            [false, true],
            ['foo', false],
            [123, false],
            [12.34, false],
            [[1,2,3], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesBooleanTypeDataProvider
     */
    public function validateHandlesBooleanType($value, $expectedResult)
    {
        $schema = [
            'type' => 'boolean',
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /// ARRAY ///

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyDataProvider()
    {
        return [
            [[1, 2, 3], true],
            ['foo', false],
            [['foo' => 'bar'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyDataProvider
     */
    public function validateHandlesArrayTypeProperty($value, $expectedResult)
    {
        $schema = [
            'type' => 'array'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsConstraintDataProvider()
    {
        return [
            [[1, 2, 3], true],
            [[1, 2, 'test string'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'array',
            'items' => 'integer'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider()
    {
        return [
            [[1, 2, 3], true],
            [[1, 2, 'test string'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsSchemaConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'array',
            'items' => [
                'type' => 'integer'
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider()
    {
        return [
            [[1, 2, 'test string'], true],
            [[1, 2, 'test string', 1.56], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsArrayConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'array',
            'items' => [
                ['type' => 'integer'],
                'string'
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayUniqueItemsConstraintDataProvider()
    {
        return [
            [[1,2,3], true],
            [[1,2,1], false],
            [[[1,2], [1,3]], true],
            [[[1,2], [1,3], [1,2]], false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayUniqueItemsConstraintDataProvider
     */
    public function validateHandlesArrayUniqueItemsConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'array',
            'uniqueItems' => true
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /// DICTIONARY ///

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeDataProvider()
    {
        return [
            [['A' => 1, 'B' => 2, 'C' => 3], true],
            [[1, 2, 3], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeDataProvider
     */
    public function validateHandlesDictionaryType($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider()
    {
        return [
            [['foo' => 123, 'bar' => 'baz'], true],
            [['foo' => 'baz', 'bar' => 'baz'], false],
            [['foo' => 123, 'bar' => 123], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithPropertiesConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => 'integer',
                'bar' => ['type' => 'string']
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider()
    {
        return [
            [['ab1' => 'string'], true],
            [['bbb' => 123], false],
            [['ab' => 123], false],
            [['ad12' => 'string'], false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithPatternPropertiesConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary',
            'patternProperties' => [
                '/^[123ab]{3}$/' => 'string'
            ],
            'additionalProperties' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider()
    {
        return [
            [['127.0.0.1' => 'string'], true],
            [['string' => 123], false],
            [['127.0.0.1' => 123], false],
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithFormatPropertiesConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary',
            'formatProperties' => [
                'ip-address' => 'string'
            ],
            'additionalProperties' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider()
    {
        return [
            [['empty' => null], true],
            [['foo' => 123, 'bar' => 'baz'], true],
            [['foo' => 123, 'bar' => 'baz', 'baz' => 'blah'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'empty' => 'null',
                'foo' => 'integer',
                'bar' => ['type' => 'string']
            ],
            'additionalProperties' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider()
    {
        return [
            [['foo' => 123, 'bar' => 'baz'], true],
            [['foo' => 123, 'bar' => 'baz', 'baz' => 123], true],
            [['foo' => 123, 'bar' => 123, 'baz' => 'string'], false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraint($value, $expectedResult)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => 'integer',
                'bar' => ['type' => 'string']
            ],
            'additionalProperties' => 'integer'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @test
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyTrueSchemaConstraint()
    {
        $schema = [
            'type' => 'dictionary',
            'additionalProperties' => true
        ];
        $value = [
            'foo' => 42
        ];

        $this->assertSuccess($this->configurationValidator->validate($value, $schema), true);
    }

    /// NULL ///

    /**
     * @return array
     */
    public function validateHandlesNullTypeDataProvider()
    {
        return [
            [null, true],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesNullTypeDataProvider
     */
    public function validateHandlesNullType($value, $expectedResult)
    {
        $schema = [
            'type' => 'null'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesUnknownTypeDataProvider()
    {
        return [
            [null, false],
            [123, false]
        ];
    }

    /**
     * @test
     * @dataProvider validateHandlesUnknownTypeDataProvider
     */
    public function validateHandlesUnknownType($value, $expectedResult)
    {
        $schema = [
            'type' => 'unknown'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }


    /// ANY ///

    /**
     * @return array
     */
    public function validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider()
    {
        return [
            [23, true],
            [23.42, true],
            ['foo', true],
            [[1,2,3], true],
            [['A' => 1, 'B' => 2, 'C' => 3], true],
            [null, true],
        ];
    }

    /**
     * @test
     * @dataProvider validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider
     */
    public function validateAnyTypeResultHasNoErrorsInAnyCase($value, $expectedResult)
    {
        $schema = [
            'type' => 'any'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }
}
