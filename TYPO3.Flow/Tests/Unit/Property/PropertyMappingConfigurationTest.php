<?php
namespace TYPO3\FLOW3\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration
 */
class PropertyMappingConfigurationTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 *
	 * @var \TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	public function setUp() {
		$this->propertyMappingConfiguration = new \TYPO3\FLOW3\Property\PropertyMappingConfiguration();
	}

	/**
	 * @test
	 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration::getTargetPropertyName
	 */
	public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration() {
		$this->assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
		$this->assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration::shouldMap
	 */
	public function shouldMapReturnsTrueByDefault() {
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
	}

	/**
	 * @test
	 */
	public function shouldMapReturnsFalseForUnmappedProperties() {
		$this->propertyMappingConfiguration->doNotMapProperty('someSourceProperty');
		$this->assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
	}

	/**
	 * @test
	 */
	public function setTypeConverterOptionsCanBeRetrievedAgain() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 */
	public function inexistentTypeConverterOptionsReturnNull() {
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
	}

	/**
	 * @test
	 */
	public function setTypeConverterOptionsShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k3' => 'v3'));

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k3'));
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 */
	public function setTypeConverterOptionShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k1', 'v3');

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 */
	public function getTypeConverterReturnsNullIfNoTypeConverterSet() {
		$this->assertNull($this->propertyMappingConfiguration->getTypeConverter());
	}

	/**
	 * @test
	 */
	public function getTypeConverterReturnsTypeConverterIfItHasBeenSet() {
		$mockTypeConverter = $this->getMock('TYPO3\FLOW3\Property\TypeConverterInterface');
		$this->propertyMappingConfiguration->setTypeConverter($mockTypeConverter);
		$this->assertSame($mockTypeConverter, $this->propertyMappingConfiguration->getTypeConverter());
	}

	/**
	 * @return \TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 */
	protected function buildChildConfigurationForSingleProperty() {
		$childConfiguration = $this->propertyMappingConfiguration->forProperty('key1.key2');
		$childConfiguration->setTypeConverterOption('someConverter', 'foo', 'specialChildConverter');

		return $childConfiguration;
	}


	/**
	 * @test
	 */
	public function getTargetPropertyNameShouldRespectMapping() {
		$this->propertyMappingConfiguration->setMapping('k1', 'k1a');
		$this->assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
		$this->assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
	}
}
?>