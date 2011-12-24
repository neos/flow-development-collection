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
	 * @var \TYPO3\FLOW3\Property\PropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	/**
	 * Initialization
	 */
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
	public function shouldMapReturnsFalseByDefault() {
		$this->assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration::shouldMap
	 */
	public function shouldMapReturnsTrueIfConfigured() {
		$this->propertyMappingConfiguration->allowAllProperties();
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration::shouldMap
	 */
	public function shouldMapReturnsTrueForAllowedProperties() {
		$this->propertyMappingConfiguration->allowProperties('someSourceProperty', 'someOtherProperty');
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));
	}

	/**
	 * @test
	 * @covers \TYPO3\FLOW3\Property\PropertyMappingConfiguration::shouldMap
	 */
	public function shouldMapReturnsFalseForBlacklistedProperties() {
		$this->propertyMappingConfiguration->allowAllPropertiesExcept('someSourceProperty', 'someOtherProperty');
		$this->assertFalse($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertFalse($this->propertyMappingConfiguration->shouldMap('someOtherProperty'));

		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherPropertyWhichHasNotBeenConfigured'));
	}

	/**
	 * @test
	 */
	public function setTypeConverterOptionsCanBeRetrievedAgain() {
		$mockTypeConverterClass = $this->getMockClass('TYPO3\FLOW3\Property\TypeConverterInterface');

		$this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, array('k1' => 'v1', 'k2' => 'v2'));
		$this->assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
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
		$mockTypeConverterClass = $this->getMockClass('TYPO3\FLOW3\Property\TypeConverterInterface');
		$this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, array('k3' => 'v3'));

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k3'));
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
	}

	/**
	 * @test
	 */
	public function setTypeConverterOptionShouldOverrideAlreadySetOptions() {
		$mockTypeConverterClass = $this->getMockClass('TYPO3\FLOW3\Property\TypeConverterInterface');
		$this->propertyMappingConfiguration->setTypeConverterOptions($mockTypeConverterClass, array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOption($mockTypeConverterClass, 'k1', 'v3');

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue($mockTypeConverterClass, 'k2'));
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

	/**
	 * @test
	 */
	public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithGetConfigurationFor() {
			// using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
		$this->propertyMappingConfiguration->forProperty('items.*')->setTypeConverterOptions('stdClass', array('k1' => 'v1'));

		$configuration = $this->propertyMappingConfiguration->getConfigurationFor('items')->getConfigurationFor('6');
		$this->assertSame('v1', $configuration->getConfigurationValue('stdClass', 'k1'));
	}

	/**
	 * @test
	 */
	public function forPropertyWithAsteriskAllowsArbitraryPropertyNamesWithForProperty() {
			// using stdClass so that class_parents() in getTypeConvertersWithParentClasses() is happy
		$this->propertyMappingConfiguration->forProperty('items.*.foo')->setTypeConverterOptions('stdClass', array('k1' => 'v1'));

		$configuration = $this->propertyMappingConfiguration->forProperty('items.6.foo');
		$this->assertSame('v1', $configuration->getConfigurationValue('stdClass', 'k1'));
	}

}
?>