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
		$this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
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
	 */
	public function convertOnlyMapsAllowedProperties() {
		$sourceArray = array('Foo' => 'Bar', 'Baz');
		$expectedTargetArray = array('Foo' => 'Bar');
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->allowProperties('Foo')->skipUnknownProperties();

		$this->assertEquals($expectedTargetArray, $this->converter->convertFrom($sourceArray, 'array', array('Foo' => 'Bar'), $configuration));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Property\Exception\InvalidPropertyMappingConfigurationException
	 */
	public function convertThrowsExceptionOnUnknownProperties() {
		$sourceArray = array('Foo' => 'Bar', 'Baz');
		$expectedTargetArray = array('Foo' => 'Bar');
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->allowProperties('Foo');

		$this->assertEquals($expectedTargetArray, $this->converter->convertFrom($sourceArray, 'array', array('Foo' => 'Bar'), $configuration));
	}

	/**
	 * @test
	 */
	public function convertSkipsPropertiesIfConfiguredTo() {
		$sourceArray = array('Foo' => 'Bar', 'Baz');
		$expectedTargetArray = array('Baz');
		$configuration = new \TYPO3\Flow\Property\PropertyMappingConfiguration();
		$configuration->allowAllProperties()->skipProperties('Foo');

		$this->assertEquals($expectedTargetArray, $this->converter->convertFrom($sourceArray, 'array', array('Foo' => 'Bar'), $configuration));
	}
}
?>