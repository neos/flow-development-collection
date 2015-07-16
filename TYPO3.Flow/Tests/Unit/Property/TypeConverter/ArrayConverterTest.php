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

use TYPO3\Flow\Property\TypeConverter\ArrayConverter;

/**
 * Testcase for the Array converter
 *
 */
class ArrayConverterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var ArrayConverter
	 */
	protected $converter;

	public function setUp() {
		$this->converter = new ArrayConverter();
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array', 'string', 'TYPO3\Flow\Resource\Resource'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
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

	public function stringToArrayDataProvider() {
		return array(
			array('Foo,Bar,Baz', array('Foo', 'Bar', 'Baz'), array()),
			array('Foo, Bar, Baz', array('Foo', 'Bar', 'Baz'), array(ArrayConverter::CONFIGURATION_STRING_DELIMITER => ', ')),
			array('', array(), array()),
			array('[1,2,"foo"]', array(1,2, 'foo'), array(ArrayConverter::CONFIGURATION_STRING_FORMAT => ArrayConverter::STRING_FORMAT_JSON))
		);
	}

	/**
	 * @test
	 * @dataProvider stringToArrayDataProvider
	 */
	public function canConvertFromStringToArray($source, $expectedResult, $mappingConfiguration) {

		// Create a map of arguments to return values.
		$configurationValueMap = array();
		foreach ($mappingConfiguration as $setting => $value) {
			$configurationValueMap[] = array('TYPO3\Flow\Property\TypeConverter\ArrayConverter', $setting, $value);
		}

		$propertyMappingConfiguration = $this->getMock('\TYPO3\Flow\Property\PropertyMappingConfiguration');
		$propertyMappingConfiguration
			->expects($this->any())
			->method('getConfigurationValue')
			->will($this->returnValueMap($configurationValueMap));

		$this->assertEquals($expectedResult, $this->converter->convertFrom($source, 'array', array(), $propertyMappingConfiguration));
	}

}
