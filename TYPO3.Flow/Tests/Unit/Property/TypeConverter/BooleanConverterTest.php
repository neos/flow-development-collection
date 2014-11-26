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

/**
 * Testcase for the Boolean converter
 *
 */
class BooleanConverterTest extends \TYPO3\Flow\Tests\UnitTestCase {


	/**
	 * @var \TYPO3\Flow\Property\TypeConverter\BooleanConverter
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new \TYPO3\Flow\Property\TypeConverter\BooleanConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('boolean', 'string', 'integer', 'float'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('boolean', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromDoesNotModifyTheBooleanSource() {
		$source = TRUE;
		$this->assertSame($source, $this->converter->convertFrom($source, 'boolean'));
	}

	/**
	 * @test
	 */
	public function convertFromCastsSourceStringToBoolean() {
		$source = 'true';
		$this->assertTrue($this->converter->convertFrom($source, 'boolean'));
	}

	/**
	 * @test
	 */
	public function convertFromCastsNumericSourceStringToBoolean() {
		$source = '1';
		$this->assertTrue($this->converter->convertFrom($source, 'boolean'));
	}

	public function convertFromDataProvider() {
		return array(
			array('', FALSE),
			array('0', FALSE),
			array('1', TRUE),
			array('false', FALSE),
			array('true', TRUE),
			array('some string', TRUE),
			array('FaLsE', FALSE),
			array('tRuE', TRUE),
			array('tRuE', TRUE),
			array('off', FALSE),
			array('N', FALSE),
			array('no', FALSE),
			array('not no', TRUE),
			array(TRUE, TRUE),
			array(FALSE, FALSE),
			array(1, TRUE),
			array(0, FALSE),
			array(1.0, TRUE),
		);
	}

	/**
	 * @test
	 * @param mixed $source
	 * @param boolean $expected
	 * @dataProvider convertFromDataProvider
	 */
	public function convertFromTests($source, $expected) {
		$this->assertSame($expected, $this->converter->convertFrom($source, 'boolean'));
	}
}
