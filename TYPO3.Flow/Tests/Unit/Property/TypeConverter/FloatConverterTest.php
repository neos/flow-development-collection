<?php
namespace TYPO3\FLOW3\Tests\Unit\Property\TypeConverter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Float converter
 *
 * @covers \TYPO3\FLOW3\Property\TypeConverter\FloatConverter<extended>
 */
class FloatConverterTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Property\TypeConverterInterface
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new \TYPO3\FLOW3\Property\TypeConverter\FloatConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('float', 'integer', 'string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('float', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromShouldCastTheStringToFloat() {
		$this->assertSame(1.5, $this->converter->convertFrom('1.5', 'float'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsNullIfEmptyStringSpecified() {
		$this->assertNull($this->converter->convertFrom('', 'float'));
	}

	/**
	 * @test
	 */
	public function convertFromShouldAcceptIntegers() {
		$this->assertSame((float)123, $this->converter->convertFrom(123, 'float'));
	}

	/**
	 * @test
	 */
	public function convertFromReturnsAnErrorIfSpecifiedStringIsNotNumeric() {
		$this->assertInstanceOf('TYPO3\FLOW3\Error\Error', $this->converter->convertFrom('not numeric', 'float'));
	}

	/**
	 * @test
	 */
	public function canConvertFromShouldReturnTrue() {
		$this->assertTrue($this->converter->canConvertFrom('1.5', 'float'));
	}

	/**
	 * @test
	 */
	public function canConvertFromShouldReturnTrueForAnEmptyValue() {
		$this->assertTrue($this->converter->canConvertFrom('', 'integer'));
	}

	/**
	 * @test
	 */
	public function canConvertFromShouldReturnTrueForANullValue() {
		$this->assertTrue($this->converter->canConvertFrom(NULL, 'integer'));
	}

	/**
	 * @test
	 */
	public function getSourceChildPropertiesToBeConvertedShouldReturnEmptyArray() {
		$this->assertEquals(array(), $this->converter->getSourceChildPropertiesToBeConverted('myString'));
	}
}
?>