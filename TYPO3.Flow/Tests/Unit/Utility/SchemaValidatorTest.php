<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the configuration validator
 *
 */
class SchemaValidatorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Utility\SchemaValidator
	 */
	protected $configurationValidator;

	public function setUp() {
		$this->configurationValidator = $this->getAccessibleMock('TYPO3\Flow\Utility\SchemaValidator', array('getError'));
	}

	/**
	 * Handle the assertion that the given result object has errors
	 *
	 * @param \TYPO3\Flow\Error\Result $result
	 * @param boolean $expectError
	 * @return void
	 */
	protected function assertError(\TYPO3\Flow\Error\Result $result, $expectError = TRUE) {
		if ($expectError === TRUE) {
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
	protected function assertSuccess(\TYPO3\Flow\Error\Result $result, $expectSuccess = TRUE) {
		if ($expectSuccess === TRUE) {
			$this->assertFalse($result->hasErrors());
		} else {
			$this->assertTrue($result->hasErrors());
		}
	}

	/**
	 * @return array
	 */
	public function validateHandlesRequiredPropertyDataProvider() {
		return array(
			array(array('foo' => 'a string'), TRUE),
			array(array('foo' => 'a string', 'bar' => 'a string'), TRUE),
			array(array('foo' => 'a string', 'bar' => 123), FALSE),
			array(array('foo' => 'a string', 'bar' => 'a string'), TRUE),
			array(array('foo' => 123, 'bar' => 'a string'), FALSE),
			array(array('foo' => NULL, 'bar' => 'a string'), FALSE),
			array(array('bar'=> 'string'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesRequiredPropertyDataProvider
	 */
	public function validateHandlesRequiredProperty($value, $expectSuccess) {
		$schema = array(
			'type' => 'dictionary',
			'properties' => array(
				'foo' => array(
					'type' => 'string',
					'required' => TRUE
				),
				'bar' => 'string'
			)
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDisallowPropertyDataProvider() {
		return array(
			array('string', TRUE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDisallowPropertyDataProvider
	 */
	public function validateHandlesDisallowProperty($value, $expectSuccess) {
		$schema = array(
			'disallow'=>'integer'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @return array
	 */
	public function validateHandlesEnumPropertyDataProvider() {
		return array(
			array(1, TRUE),
			array(2, TRUE),
			array(NULL, FALSE),
			array(4, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesEnumPropertyDataProvider
	 */
	public function validateHandlesEnumProperty($value, $expectSuccess) {
		$schema = array(
			'enum'=>array(1,2,3)
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @test
	 */
	public function validateReturnsErrorPath() {
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

	/// INTEGER ///

	/**
	 * @return array
	 */
	public function validateHandlesIntegerTypePropertyDataProvider() {
		return array(
			array(23, TRUE),
			array('foo', FALSE),
			array(23.42, FALSE),
			array(array(), FALSE),
			array(NULL, FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesIntegerTypePropertyDataProvider
	 */
	public function validateHandlesIntegerTypeProperty($value, $expectSuccess) {
		$schema = array(
			'type' => 'integer'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/// NUMBER ///

	/**
	 * @return array
	 */
	public function validateHandlesNumberTypePropertyDataProvider() {
		return array(
			array(23.42, TRUE),
			array(42, TRUE),
			array('foo', FALSE),
			array(NULL, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesNumberTypePropertyDataProvider
	 */
	public function validateHandlesNumberTypeProperty($value, $expectSuccess) {
		$schema = array(
			'type' => 'number'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @return array
	 */
	public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider (){
		return array(
			array(33, TRUE),
			array(99, FALSE),
			array(1, FALSE),
			array(23, TRUE),
			array(42, TRUE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraintDataProvider
	 */
	public function validateHandlesNumberTypePropertyWithMinimumAndMaximumConstraint($value, $expectSuccess) {
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
	public function validateHandlesNumberTypePropertyWithNonExclusiveMinimumAndMaximumConstraint($value, $expectSuccess) {
		$schema = array(
			'type' => 'number',
			'minimum' => 23,
			'exclusiveMinimum' => FALSE,
			'maximum' => 42,
			'exclusiveMaximum' => FALSE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @return array
	 */
	public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider (){
		return array(
			array(10, FALSE),
			array(22, FALSE),
			array(23, TRUE),
			array(42, TRUE),
			array(43, FALSE),
			array(99, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraintDataProvider
	 */
	public function validateHandlesNumberTypePropertyWithExclusiveMinimumAndMaximumConstraint($value, $expectSuccess) {
		$schema = array(
			'type' => 'number',
			'minimum' => 22,
			'exclusiveMinimum' => TRUE,
			'maximum' => 43,
			'exclusiveMaximum' => TRUE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectSuccess);
	}

	/**
	 * @return array
	 */
	public function validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider() {
		return array(
			array(4, TRUE),
			array(3, FALSE),
			array(-3, FALSE),
			array(-4, TRUE),
			array(0, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesNumberTypePropertyWithDivisibleByConstraintDataProvider
	 */
	public function validateHandlesNumberTypePropertyWithDivisibleByConstraint($value, $expectSuccess) {
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
	public function validateHandlesStringTypePropertyDataProvider() {
		return array(
			array('FooBar', TRUE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyDataProvider
	 */
	public function validateHandlesStringTypeProperty($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithPatternConstraintDataProvider() {
		return array(
			array('12a', TRUE),
			array('1236', FALSE),
			array('12c', FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithPatternConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithPatternConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'pattern' => '/^[123ab]{3}$/'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider() {
		return array(
			array('01:25:00', FALSE),
			array('1976-04-18', FALSE),
			array('1976-04-18T01:25:00+00:00', TRUE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithDateTimeConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithDateTimeConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'date-time'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider() {
		return array(
			array('01:25:00', FALSE),
			array('1976-04-18', TRUE),
			array('1976-04-18T01:25:00+00:00', FALSE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatDateConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatDateConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'date'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider() {
		return array(
			array('01:25:00', TRUE),
			array('1976-04-18', FALSE),
			array('1976-04-18T01:25:00+00:00', FALSE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatTimeConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatTimeConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'time'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider() {
		return array(
			array('http://foo.bar.de', TRUE),
			array('ftp://dasdas.de/foo/bar/?asds=123&dasdasd#dasdas', TRUE),
			array('foo', FALSE),
			array(123, FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatUriPConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatUriPConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'uri'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider() {
		return array(
			array('www.typo3.org', TRUE),
			array('this.is.an.invalid.hostname', FALSE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatHostnameConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatHostnameConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'host-name'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider() {
		return array(
			array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', FALSE),
			array('123.132.123.132', TRUE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatIpv4ConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatIpv4Constraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'ipv4'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);

	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider() {
		return array(
			array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', TRUE),
			array('123.132.123.132', FALSE),
			array('foobar', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatIpv6ConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatIpv6Constraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'ipv6'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider() {
		return array(
			array('2001:0db8:85a3:08d3:1319:8a2e:0370:7344', TRUE),
			array('123.132.123.132', TRUE),
			array('foobar', FALSE),
			array('ab1', FALSE),
			array(123, FALSE)
		);
	}

	/**
	* @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatIpAddressConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatIpAddressConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'ip-address'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider() {
		return array(
			array('\TYPO3\Flow\Package\PackageManager', TRUE),
			array('\TYPO3\Flow\UnknownClass', FALSE),
			array('foobar', FALSE),
			array('foo bar', FALSE),
			array('foo/bar', FALSE),
			array('flow/welcome', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatClassNameConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatClassNameConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'class-name'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider() {
		return array(
			array('\TYPO3\Flow\Package\PackageManagerInterface', TRUE),
			array('\TYPO3\Flow\UnknownClass', FALSE),
			array('foobar', FALSE),
			array('foo bar', FALSE),
			array('foo/bar', FALSE),
			array('flow/welcome', FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithFormatInterfaceNameConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithFormatInterfaceNameConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'format' => 'interface-name'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider() {
		return array(
			array('12356', TRUE),
			array('1235', TRUE),
			array('123', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithMinLengthConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithMinLengthConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'string',
			'minLength' => 4
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider() {
		return array(
			array('123', TRUE),
			array('1234', TRUE),
			array('12345', FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesStringTypePropertyWithMaxLengthConstraintDataProvider
	 */
	public function validateHandlesStringTypePropertyWithMaxLengthConstraint($value, $expectedResult) {
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
	public function validateHandlesBooleanTypeDataProvider() {
		return array(
			array(TRUE, TRUE),
			array(FALSE, TRUE),
			array('foo', FALSE),
			array(123, FALSE),
			array(12.34, FALSE),
			array(array(1,2,3), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesBooleanTypeDataProvider
	 */
	public function validateHandlesBooleanType($value, $expectedResult) {
		$schema = array(
			'type' => 'boolean',
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/// ARRAY ///

	/**
	 * @return array
	 */
	public function validateHandlesArrayTypePropertyDataProvider() {
		return array(
			array(array(1, 2, 3), TRUE),
			array('foo', FALSE),
			array(array('foo'=>'bar'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesArrayTypePropertyDataProvider
	 */
	public function validateHandlesArrayTypeProperty($value, $expectedResult) {
		$schema = array(
			'type' => 'array'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesArrayTypePropertyWithItemsConstraintDataProvider() {
		return array(
			array(array(1, 2, 3), TRUE),
			array(array(1, 2, 'test string'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesArrayTypePropertyWithItemsConstraintDataProvider
	 */
	public function validateHandlesArrayTypePropertyWithItemsConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'array',
			'items'=> 'integer'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider() {
		return array(
			array(array(1, 2, 3), TRUE),
			array(array(1, 2, 'test string'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesArrayTypePropertyWithItemsSchemaConstraintDataProvider
	 */
	public function validateHandlesArrayTypePropertyWithItemsSchemaConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'array',
			'items'=> array (
				'type'=>'integer'
			)
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider() {
		return array(
			array(array(1, 2, 'test string'), TRUE),
			array(array(1, 2, 'test string', 1.56), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesArrayTypePropertyWithItemsArrayConstraintDataProvider
	 */
	public function validateHandlesArrayTypePropertyWithItemsArrayConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'array',
			'items'=> array (
				array('type'=>'integer'),
				'string'
			)
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesArrayUniqueItemsConstraintDataProvider() {
		return array(
			array(array(1,2,3), TRUE),
			array(array(1,2,1), FALSE),
			array(array(array(1,2), array(1,3)), TRUE),
			array(array(array(1,2), array(1,3), array(1,2)), FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesArrayUniqueItemsConstraintDataProvider
	 */
	public function validateHandlesArrayUniqueItemsConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'array',
			'uniqueItems' => TRUE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/// DICTIONARY ///

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeDataProvider() {
		return array(
			array(array('A' => 1, 'B' => 2, 'C' => 3), TRUE),
			array(array(1, 2, 3), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeDataProvider
	 */
	public function validateHandlesDictionaryType($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider() {
		return array(
			array(array('foo'=>123, 'bar'=>'baz'), TRUE),
			array(array('foo'=>'baz', 'bar'=>'baz'), FALSE),
			array(array('foo'=>123, 'bar'=>123), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeWithPropertiesConstraintDataProvider
	 */
	public function validateHandlesDictionaryTypeWithPropertiesConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary',
			'properties' => array(
				'foo' => 'integer',
				'bar' => array('type'=>'string')
			)
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider() {
		return array(
			array(array("ab1" => 'string'), TRUE),
			array(array('bbb' => 123), FALSE),
			array(array('ab' => 123), FALSE),
			array(array('ad12' => 'string'), FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeWithPatternPropertiesConstraintDataProvider
	 */
	public function validateHandlesDictionaryTypeWithPatternPropertiesConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary',
			'patternProperties' => array(
				'/^[123ab]{3}$/' => 'string'
			),
			'additionalProperties' => FALSE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider() {
		return array(
			array(array("127.0.0.1" => 'string'), TRUE),
			array(array('string' => 123), FALSE),
			array(array('127.0.0.1' => 123), FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeWithFormatPropertiesConstraintDataProvider
	 */
	public function validateHandlesDictionaryTypeWithFormatPropertiesConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary',
			'formatProperties' => array(
				'ip-address' => 'string'
			),
			'additionalProperties' => FALSE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider() {
		return array(
			array(array('empty' => NULL), TRUE),
			array(array('foo'=>123, 'bar'=>'baz'), TRUE),
			array(array('foo'=>123, 'bar'=>'baz', 'baz'=>'blah'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraintDataProvider
	 */
	public function validateHandlesDictionaryTypeWithAdditionalPropertyFalseConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary',
			'properties' => array(
				'empty' => 'null',
				'foo' => 'integer',
				'bar' => array('type'=>'string')
			),
			'additionalProperties' => FALSE
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider() {
		return array(
			array(array('foo'=>123, 'bar'=>'baz'), TRUE),
			array(array('foo'=>123, 'bar'=>'baz', 'baz'=>123), TRUE),
			array(array('foo'=>123, 'bar'=>123, 'baz'=>'string'), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraintDataProvider
	 */
	public function validateHandlesDictionaryTypeWithAdditionalPropertySchemaConstraint($value, $expectedResult) {
		$schema = array(
			'type' => 'dictionary',
			'properties' => array(
				'foo' => 'integer',
				'bar' => array('type'=>'string')
			),
			'additionalProperties' => 'integer'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @test
	 */
	public function validateHandlesDictionaryTypeWithAdditionalPropertyTrueSchemaConstraint() {
		$schema = array(
			'type' => 'dictionary',
			'additionalProperties' => TRUE
		);
		$value = array(
			'foo' => 42
		);

		$this->assertSuccess($this->configurationValidator->validate($value, $schema), TRUE);
	}

	/// NULL ///

	/**
	 * @return array
	 */
	public function validateHandlesNullTypeDataProvider() {
		return array(
			array(NULL, TRUE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesNullTypeDataProvider
	 */
	public function validateHandlesNullType($value, $expectedResult) {
		$schema = array(
			'type' => 'null'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}

	/**
	 * @return array
	 */
	public function validateHandlesUnknownTypeDataProvider() {
		return array(
			array(NULL, FALSE),
			array(123, FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validateHandlesUnknownTypeDataProvider
	 */
	public function validateHandlesUnknownType($value, $expectedResult) {
		$schema = array(
			'type' => 'unknown'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}


	/// ANY ///

	/**
	 * @return array
	 */
	public function validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider() {
		return array(
			array(23, TRUE),
			array(23.42, TRUE),
			array('foo', TRUE),
			array(array(1,2,3), TRUE),
			array(array('A' => 1, 'B' => 2, 'C' => 3), TRUE),
			array(NULL, TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider validateAnyTypeResultHasNoErrorsInAnyCaseDataProvider
	 */
	public function validateAnyTypeResultHasNoErrorsInAnyCase($value, $expectedResult) {
		$schema = array(
			'type' => 'any'
		);
		$this->assertSuccess($this->configurationValidator->validate($value, $schema), $expectedResult);
	}
}
