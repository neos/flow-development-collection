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

use Neos\Utility\SchemaValidator;
use Neos\Error\Messages as Error;

/**
 * Testcase for the configuration validator
 */
class SchemaValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SchemaValidator
     */
    protected $configurationValidator;

    protected function setUp(): void
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
    protected function assertError(Error\Result $result, bool $expectError = true)
    {
        if ($expectError === true) {
            self::assertTrue($result->hasErrors());
        } else {
            self::assertFalse($result->hasErrors());
        }
    }

    /**
     * Handle the assertion that the given result object has no errors
     *
     * @param Error\Result $result
     * @param boolean $expectSuccess
     * @return void
     */
    protected function assertSuccess(Error\Result $result, bool $expectSuccess = true)
    {
        if ($expectSuccess === true) {
            self::assertFalse($result->hasErrors());
        } else {
            self::assertTrue($result->hasErrors());
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
    public function validateHandlesRequiredProperty(array $value, bool $expectSuccess)
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
    public function validateHandlesDisallowProperty($value, bool $expectSuccess)
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
    public function validateHandlesEnumProperty($value, bool $expectSuccess)
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
        self::assertTrue(array_key_exists('foo.bar.baz', $allErrors));

        $pathErrors = $result->forProperty('foo.bar.baz')->getErrors();
        $firstPathError = $pathErrors[0];
        self::assertEquals($firstPathError->getCode(), 1328557141);
        self::assertEquals($firstPathError->getArguments(), ['type=number', 'type=string']);
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
    public function validateHandlesMultipleTypes($value, bool $expectSuccess)
    {
        $schema = ['dictionary', 'string'];

        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypesInSchemaType($value, bool $expectSuccess)
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
    public function validateHandlesMultipleTypesInSubProperty($value, bool $expectSuccess)
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
    public function validateHandlesIntegerTypeProperty($value, bool $expectSuccess)
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
    public function validateHandlesNumberTypeProperty($value, bool $expectSuccess)
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
    public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraint($value, bool $expectSuccess)
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
    public function validateHandlesNumberTypePropertyWithNonExclusiveMinimumAndMaximumConstraint($value, bool $expectSuccess)
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
    public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraint($value, bool $expectSuccess)
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
    public function validateHandlesNumberTypePropertyWithDivisibleByConstraint($value, bool $expectSuccess)
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
    public function validateHandlesStringTypeProperty($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithPatternConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'pattern' => '/^[123ab]{3}$/'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithDateTimeConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'date-time'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatDateConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'date'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatTimeConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'time'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatUriPConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'uri'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatHostnameConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'host-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatIpv4Constraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ipv4'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatIpv6Constraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ipv6'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithFormatIpAddressConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'ip-address'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider()
    {
        return [
            [SchemaValidator::class, true],
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
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'class-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider()
    {
        return [
            [\Iterator::class, true],
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
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'format' => 'interface-name'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithMinLengthConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'minLength' => 4
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesStringTypePropertyWithMaxLengthConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'string',
            'maxLength' => 4
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesBooleanType($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'boolean',
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesArrayTypeProperty($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'array'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesArrayTypePropertyWithItemsConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'array',
            'items' => 'integer'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesArrayTypePropertyWithItemsSchemaConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'array',
            'items' => [
                'type' => 'integer'
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesArrayTypePropertyWithItemsArrayConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'array',
            'items' => [
                ['type' => 'integer'],
                'string'
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesArrayUniqueItemsConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'array',
            'uniqueItems' => true
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryType($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryTypeWithPropertiesConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => 'integer',
                'bar' => ['type' => 'string']
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryTypeWithPatternPropertiesConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'patternProperties' => [
                '/^[123ab]{3}$/' => 'string'
            ],
            'additionalProperties' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryTypeWithFormatPropertiesConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'formatProperties' => [
                'ip-address' => 'string'
            ],
            'additionalProperties' => false
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraint($value, bool $expectSuccess)
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
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraint($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'foo' => 'integer',
                'bar' => ['type' => 'string']
            ],
            'additionalProperties' => 'integer'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesNullType($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'null'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateHandlesUnknownType($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'unknown'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
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
    public function validateAnyTypeResultHasNoErrorsInAnyCase($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'any'
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /// CUSTOM ///

    /**
     * @return array
     */
    public function validateCustomTypeResultDataProvider()
    {
        return [
            [ ['property' => ['integer_property' => 1, 'string_property' => 'string' ] ], true ],
            [ ['property' => ['integer_property' => 'no_integer', 'string_property' => 123 ] ], false ],
            [ ['property' => 'some_value' ], false ],
            [ ['other_property' => ['integer_property' => 1, 'string_property' => 'string' ] ], false ],
            [ ['other_property' => 'some_value' ], false ]
        ];
    }

    /**
     * @test
     * @dataProvider validateCustomTypeResultDataProvider
     */
    public function validateCustomTypeResult($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'property' => '@customType'
            ],
            'additionalProperties' => false,
            '@customType' => [
                'type' => 'dictionary',
                'properties' => [
                    'integer_property' => 'integer',
                    'string_property' => 'string'
                ]
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateCustomTypeWithSuperTypesDataProvider()
    {
        return [
            [ ['property' => ['supertype_property' => 1, 'type_property' => 'string' ] ], true ],
            [ ['property' => ['supertype_property' => 'no_integer', 'type_property' => 123 ] ], false ],
            [ ['property' => 'some_value' ], false ],
            [ ['other_property' => ['supertype_property' => 1, 'type_property' => 'string' ] ], false ],
            [ ['other_property' => 'some_value' ], false ]
        ];
    }

    /**
     * @test
     * @dataProvider validateCustomTypeWithSuperTypesDataProvider
     */
    public function validateCustomTypeWithSuperTypes($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'property' => '@customType'
            ],
            'additionalProperties' => false,
            '@customSuperType' => [
                'type' => 'dictionary',
                'properties' => [
                    'supertype_property' => 'integer'
                ]
            ],
            '@customType' => [
                'superTypes' => ['@customSuperType'],
                'type' => 'dictionary',
                'properties' => [
                    'type_property' => 'string'
                ]
            ]
        ];
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateCustomTypeArrayDataProvider()
    {
        return [
            [ ['property' => ['custom_type_a_property' => 1]], true ],
            [ ['property' => ['custom_type_b_property' => 'string' ] ], true ],
            [ ['property' => ['custom_type_a_property' => 1, 'custom_type_b_property' => 'string' ] ], false ],

            [ ['property' => ['custom_type_a_property' => 'no_integer' ] ], false ],
            [ ['property' => ['custom_type_b_property' => 12324 ] ], false ],
        ];
    }

    /**
     * @test
     * @dataProvider validateCustomTypeArrayDataProvider
     */
    public function validateCustomTypeArray($value, bool $expectSuccess)
    {
        $schema = [
            'type' => 'dictionary',
            'properties' => [
                'property' => ['@customTypeA','@customTypeB'],
            ],
            'additionalProperties' => false,
            '@customTypeA' => [
                'type' => 'dictionary',
                'properties' => [
                    'custom_type_a_property' => 'integer'
                ],
                'additionalProperties' => false,
            ],
            '@customTypeB' => [
                'type' => 'dictionary',
                'properties' => [
                    'custom_type_b_property' => 'string'
                ],
                'additionalProperties' => false,
            ]
        ];

        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }
}
