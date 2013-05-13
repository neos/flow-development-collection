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
 * Testcase for the Array converter
 *
 */
class ArrayConverterTest extends \TYPO3\Flow\Tests\UnitTestCase {


	/**
	 * @var \TYPO3\Flow\Property\TypeConverter\ArrayConverter
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new \TYPO3\Flow\Property\TypeConverter\ArrayConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array', 'string'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('array', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(1, $this->converter->getPriority(), 'Priority does not match');
	}

	/**
	 * @test
	 */
	public function convertFromDoesNotModifyTheSourceArray() {
		$sourceArray = array('Foo' => 'Bar', 'Baz');
		$this->assertEquals($sourceArray, $this->converter->convertFrom($sourceArray, 'array'));
	}

	/**
	 * @test
	 * @dataProvider stringToArrayDataProvider
	 */
	public function canConvertFromStringToArray($source, $expectedResult) {
		$this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array'));
	}

	public function stringToArrayDataProvider() {
		return array(
			array('Foo,Bar,Baz', array('Foo', 'Bar', 'Baz')),
			array('', array())
		);
	}

	/**
	 * @test
	 */
	public function explodeDelimiterCanBeChangedByThePropertyMappingConfiguration() {
		$source = 'Foo, Bar, Baz';
		$expectedResult = array('Foo', 'Bar', 'Baz');

		$propertyMappingConfiguration = $this->getMock('\TYPO3\Flow\Property\PropertyMappingConfiguration');
		$propertyMappingConfiguration
			->expects($this->any())
			->method('getConfigurationValue')
			->will($this->returnValue(', '));

		$this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', array(), $propertyMappingConfiguration));
	}

}
?>