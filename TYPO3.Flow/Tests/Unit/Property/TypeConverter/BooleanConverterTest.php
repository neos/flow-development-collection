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
		$this->assertEquals(array('boolean', 'string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
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
			array('source' => '', 'expected' => FALSE),
			array('source' => '0', 'expected' => FALSE),
			array('source' => '1', 'expected' => TRUE),
			array('source' => 'false', 'expected' => FALSE),
			array('source' => 'true', 'expected' => TRUE),
			array('source' => 'some string', 'expected' => TRUE),
			array('source' => 'FaLsE', 'expected' => FALSE),
			array('source' => 'tRuE', 'expected' => TRUE),
			array('source' => 'tRuE', 'expected' => TRUE),
			array('source' => 'off', 'expected' => FALSE),
			array('source' => 'N', 'expected' => FALSE),
			array('source' => 'no', 'expected' => FALSE),
			array('source' => 'not no', 'expected' => TRUE),
			array('source' => TRUE, 'expected' => TRUE),
			array('source' => FALSE, 'expected' => FALSE),
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
?>