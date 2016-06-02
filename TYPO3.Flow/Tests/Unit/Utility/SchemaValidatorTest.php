<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the configuration validator
 *
 */
class SchemaValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Utility\SchemaValidator
     */
    protected $configurationValidator;

    public function setUp()
    {
        $this->configurationValidator = $this->getAccessibleMock('TYPO3\Flow\Utility\SchemaValidator', array('getError'));
    }

    /**
     * Handle the assertion that the given result object has errors
     *
     * @param \TYPO3\Flow\Error\Result $result
     * @param boolean $expectError
     * @return void
     */
    protected function assertError(\TYPO3\Flow\Error\Result $result, $expectError = true)
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
     * @param \TYPO3\Flow\Error\Result $result
     * @param boolean $expectSuccess
     * @return void
     */
    protected function assertSuccess(\TYPO3\Flow\Error\Result $result, $expectSuccess = true)
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
        return array(
            array(array('foo' => 'a string'), true),
            array(array('foo' => 'a string', 'bar' => 'a string'), true),
            array(array('foo' => 'a string', 'bar' => 123), false),
            array(array('foo' => 'a string', 'bar' => 'a string'), true),
            array(array('foo' => 123, 'bar' => 'a string'), false),
            array(array('foo' => null, 'bar' => 'a string'), false),
            array(array('bar' => 'string'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesRequiredPropertyDataProvider
     */
    public function validateHandlesRequiredProperty($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'foo' => array(
                    'type' => 'string',
                    'required' => true
                ),
                'bar' => 'string'
            )
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesDisallowPropertyDataProvider()
    {
        return array(
            array('string', true),
            array(123, false),
            array(array(1,2,3), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDisallowPropertyDataProvider
     */
    public function validateHandlesDisallowProperty($value, $expectSuccess)
    {
        $schema = array(
            'disallow' => array('integer','array')
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesEnumPropertyDataProvider()
    {
        return array(
            array(1, true),
            array(2, true),
            array(null, false),
            array(4, false),
            array(array(1,2,3), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesEnumPropertyDataProvider
     */
    public function validateHandlesEnumProperty($value, $expectSuccess)
    {
        $schema = array(
            'enum' => array(1,2,3)
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @test
     */
    public function validateReturnsErrorPath()
    {
        $value = array(
            'foo' => array(
                'bar' => array(
                    'baz' => 'string'
                )
            )
        );

        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'foo' => array(
                    'type' => 'dictionary',
                    'properties' => array(
                        'bar' => array(
                            'type' => 'dictionary',
                            'properties' => array(
                                'baz' => 'number'
                            )
                        )
                    )
                )
            )
        );

        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertError($result);

        $allErrors = $result->getFlattenedErrors();
        $this->assertTrue(array_key_exists('foo.bar.baz', $allErrors));

        $pathErrors = $result->forProperty('foo.bar.baz')->getErrors();
        $firstPathError = $pathErrors[0];
        $this->assertEquals($firstPathError->getCode(), 1328557141);
        $this->assertEquals($firstPathError->getArguments(), array('type=number', 'type=string'));
    }

    /**
     * @return array
     */
    public function validateHandlesMultipleTypesDataProvider()
    {
        return array(
            [['property' => 'value'], true],
            ['value', true],
            [false, false],
            [123, false],
            [array(1,2,3), false]
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypes($value, $expectSuccess)
    {
        $schema = array('dictionary', 'string');

        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypesInSchemaType($value, $expectSuccess)
    {
        $schema = array(
            'type' => array('dictionary', 'string')
        );
        $result = $this->configurationValidator->validate($value, $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesMultipleTypesDataProvider
     */
    public function validateHandlesMultipleTypesInSubProperty($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'foo' => array(
                    'type' => array('dictionary', 'string')
                )
            )
        );
        $result = $this->configurationValidator->validate(['foo' => $value], $schema);
        $this->assertSuccess($result, $expectSuccess);
    }

    /// INTEGER ///

    /**
     * @return array
     */
    public function validateHandlesIntegerTypePropertyDataProvider()
    {
        return array(
            array(23, true),
            array('foo', false),
            array(23.42, false),
            array(array(), false),
            array(null, false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesIntegerTypePropertyDataProvider
     */
    public function validateHandlesIntegerTypeProperty($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'integer'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /// NUMBER ///

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyDataProvider()
    {
        return array(
            array(23.42, true),
            array(42, true),
            array('foo', false),
            array(null, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyDataProvider
     */
    public function validateHandlesNumberTypeProperty($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'number'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider()
    {
        return array(
            array(33, true),
            array(99, false),
            array(1, false),
            array(23, true),
            array(42, true)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'number',
            'minimum' => 23,
            'maximum' => 42
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithNonExclusiveMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'number',
            'minimum' => 23,
            'exclusiveMinimum' => false,
            'maximum' => 42,
            'exclusiveMaximum' => false
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider()
    {
        return array(
            array(10, false),
            array(22, false),
            array(23, true),
            array(42, true),
            array(43, false),
            array(99, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraint($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'number',
            'minimum' => 22,
            'exclusiveMinimum' => true,
            'maximum' => 43,
            'exclusiveMaximum' => true
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /**
     * @return array
     */
    public function validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider()
    {
        return array(
            array(4, true),
            array(3, false),
            array(-3, false),
            array(-4, true),
            array(0, true),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider
     */
    public function validateHandlesNumberTypePropertyWithDivisibleByConstraint($value, $expectSuccess)
    {
        $schema = array(
            'type' => 'number',
            'divisibleBy' => 2
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
    }

    /// STRING ///

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyDataProvider()
    {
        return array(
            array('FooBar', true),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyDataProvider
     */
    public function validateHandlesStringTypeProperty($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithPatternConstraintDataProvider()
    {
        return array(
            array('12a', true),
            array('1236', false),
            array('12c', false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithPatternConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithPatternConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'pattern' => '/^[123ab]{3}$/'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider()
    {
        return array(
            array('01:25:00', false),
            array('1976-04-18', false),
            array('1976-04-18T01:25:00+00:00', true),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithDateTimeConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'date-time'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider()
    {
        return array(
            array('01:25:00', false),
            array('1976-04-18', true),
            array('1976-04-18T01:25:00+00:00', false),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatDateConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'date'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider()
    {
        return array(
            array('01:25:00', true),
            array('1976-04-18', false),
            array('1976-04-18T01:25:00+00:00', false),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatTimeConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'time'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider()
    {
        return array(
            array('http://foo.bar.de', true),
            array('ftp://dasdas.de/foo/bar/?asds=123&dasdasd#dasdas', true),
            array('foo', false),
            array(123, false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatUriPConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'uri'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider()
    {
        return array(
            array('www.typo3.org', true),
            array('this.is.an.invalid.hostname', false),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatHostnameConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'host-name'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider()
    {
        return array(
            array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', false),
            array('123.132.123.132', true),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpv4Constraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'ipv4'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider()
    {
        return array(
            array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', true),
            array('123.132.123.132', false),
            array('foobar', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpv6Constraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'ipv6'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider()
    {
        return array(
            array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', true),
            array('123.132.123.132', true),
            array('foobar', false),
            array('ab1', false),
            array(123, false)
        );
    }

    /**
    * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatIpAddressConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'ip-address'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider()
    {
        return array(
            array('\TYPO3\Flow\Package\PackageManager', true),
            array('\TYPO3\Flow\UnknownClass', false),
            array('foobar', false),
            array('foo bar', false),
            array('foo/bar', false),
            array('flow/welcome', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatClassNameConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'class-name'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider()
    {
        return array(
            array('\TYPO3\Flow\Package\PackageManagerInterface', true),
            array('\TYPO3\Flow\UnknownClass', false),
            array('foobar', false),
            array('foo bar', false),
            array('foo/bar', false),
            array('flow/welcome', false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'format' => 'interface-name'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider()
    {
        return array(
            array('12356', true),
            array('1235', true),
            array('123', false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithMinLengthConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'minLength' => 4
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider()
    {
        return array(
            array('123', true),
            array('1234', true),
            array('12345', false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider
     */
    public function validateHandlesStringTypePropertyWithMaxLengthConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'string',
            'maxLength' => 4
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }


    /// BOOLEAN ///

    /**
     * @return array
     */
    public function validateHandlesBooleanTypeDataProvider()
    {
        return array(
            array(true, true),
            array(false, true),
            array('foo', false),
            array(123, false),
            array(12.34, false),
            array(array(1,2,3), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesBooleanTypeDataProvider
     */
    public function validateHandlesBooleanType($value, $expectedResult)
    {
        $schema = array(
            'type' => 'boolean',
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /// ARRAY ///

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyDataProvider()
    {
        return array(
            array(array(1, 2, 3), true),
            array('foo', false),
            array(array('foo' => 'bar'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyDataProvider
     */
    public function validateHandlesArrayTypeProperty($value, $expectedResult)
    {
        $schema = array(
            'type' => 'array'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsConstraintDataProvider()
    {
        return array(
            array(array(1, 2, 3), true),
            array(array(1, 2, 'test string'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'array',
            'items' => 'integer'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider()
    {
        return array(
            array(array(1, 2, 3), true),
            array(array(1, 2, 'test string'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsSchemaConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'array',
            'items' => array(
                'type' => 'integer'
            )
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider()
    {
        return array(
            array(array(1, 2, 'test string'), true),
            array(array(1, 2, 'test string', 1.56), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider
     */
    public function validateHandlesArrayTypePropertyWithItemsArrayConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'array',
            'items' => array(
                array('type' => 'integer'),
                'string'
            )
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesArrayUniqueItemsConstraintDataProvider()
    {
        return array(
            array(array(1,2,3), true),
            array(array(1,2,1), false),
            array(array(array(1,2), array(1,3)), true),
            array(array(array(1,2), array(1,3), array(1,2)), false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesArrayUniqueItemsConstraintDataProvider
     */
    public function validateHandlesArrayUniqueItemsConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'array',
            'uniqueItems' => true
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /// DICTIONARY ///

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeDataProvider()
    {
        return array(
            array(array('A' => 1, 'B' => 2, 'C' => 3), true),
            array(array(1, 2, 3), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeDataProvider
     */
    public function validateHandlesDictionaryType($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider()
    {
        return array(
            array(array('foo' => 123, 'bar' => 'baz'), true),
            array(array('foo' => 'baz', 'bar' => 'baz'), false),
            array(array('foo' => 123, 'bar' => 123), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithPropertiesConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'foo' => 'integer',
                'bar' => array('type' => 'string')
            )
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider()
    {
        return array(
            array(array('ab1' => 'string'), true),
            array(array('bbb' => 123), false),
            array(array('ab' => 123), false),
            array(array('ad12' => 'string'), false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithPatternPropertiesConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary',
            'patternProperties' => array(
                '/^[123ab]{3}$/' => 'string'
            ),
            'additionalProperties' => false
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider()
    {
        return array(
            array(array('127.0.0.1' => 'string'), true),
            array(array('string' => 123), false),
            array(array('127.0.0.1' => 123), false),
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithFormatPropertiesConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary',
            'formatProperties' => array(
                'ip-address' => 'string'
            ),
            'additionalProperties' => false
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider()
    {
        return array(
            array(array('empty' => null), true),
            array(array('foo' => 123, 'bar' => 'baz'), true),
            array(array('foo' => 123, 'bar' => 'baz', 'baz' => 'blah'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'empty' => 'null',
                'foo' => 'integer',
                'bar' => array('type' => 'string')
            ),
            'additionalProperties' => false
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider()
    {
        return array(
            array(array('foo' => 123, 'bar' => 'baz'), true),
            array(array('foo' => 123, 'bar' => 'baz', 'baz' => 123), true),
            array(array('foo' => 123, 'bar' => 123, 'baz' => 'string'), false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraint($value, $expectedResult)
    {
        $schema = array(
            'type' => 'dictionary',
            'properties' => array(
                'foo' => 'integer',
                'bar' => array('type' => 'string')
            ),
            'additionalProperties' => 'integer'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @test
     */
    public function validateHandlesDictionaryTypeWithAdditionalPropertyTrueSchemaConstraint()
    {
        $schema = array(
            'type' => 'dictionary',
            'additionalProperties' => true
        );
        $value = array(
            'foo' => 42
        );

        $this->assertSuccess($this->configurationValidator->validate($value, $schema), true);
    }

    /// NULL ///

    /**
     * @return array
     */
    public function validateHandlesNullTypeDataProvider()
    {
        return array(
            array(null, true),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesNullTypeDataProvider
     */
    public function validateHandlesNullType($value, $expectedResult)
    {
        $schema = array(
            'type' => 'null'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }

    /**
     * @return array
     */
    public function validateHandlesUnknownTypeDataProvider()
    {
        return array(
            array(null, false),
            array(123, false)
        );
    }

    /**
     * @test
     * @dataProvider validateHandlesUnknownTypeDataProvider
     */
    public function validateHandlesUnknownType($value, $expectedResult)
    {
        $schema = array(
            'type' => 'unknown'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }


    /// ANY ///

    /**
     * @return array
     */
    public function validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider()
    {
        return array(
            array(23, true),
            array(23.42, true),
            array('foo', true),
            array(array(1,2,3), true),
            array(array('A' => 1, 'B' => 2, 'C' => 3), true),
            array(null, true),
        );
    }

    /**
     * @test
     * @dataProvider validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider
     */
    public function validateAnyTypeResultHasNoErrorsInAnyCase($value, $expectedResult)
    {
        $schema = array(
            'type' => 'any'
        );
        $this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
    }
}
