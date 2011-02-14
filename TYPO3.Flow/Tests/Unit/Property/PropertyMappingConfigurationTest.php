<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once (__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \F3\FLOW3\Property\PropertyMappingConfiguration
 */
class PropertyMappingConfigurationTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 *
	 * @var \F3\FLOW3\Property\PropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	public function setUp() {
		$this->propertyMappingConfiguration = new \F3\FLOW3\Property\PropertyMappingConfiguration();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @covers \F3\FLOW3\Property\PropertyMappingConfiguration::getTargetPropertyName
	 */
	public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration() {
		$this->assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
		$this->assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @covers \F3\FLOW3\Property\PropertyMappingConfiguration::shouldMap
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shouldMapReturnsTrue() {
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionsCanBeRetrievedAgain() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function inexistentTypeConverterOptionsReturnNull() {
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionsShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k3' => 'v3'));

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k3'));
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k1', 'v3');

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeConverterReturnsNullIfNoTypeConverterSet() {
		$this->assertNull($this->propertyMappingConfiguration->getTypeConverter());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getConfigurationReturnsCurrentTypeConverterIfNoSubTypeConverterIsSet() {
		$this->assertSame($this->propertyMappingConfiguration, $this->propertyMappingConfiguration->getConfigurationFor('someNotExplicitKey'));
	}

	/**
	 * @return \F3\FLOW3\Property\PropertyMappingConfiguration
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildChildConfigurationForAllProperties() {
		$childConfiguration = $this->propertyMappingConfiguration->forAllProperties();
		$childConfiguration->setTypeConverterOption('someConverter', 'foo', 'bar');

		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'otherkey', 'parent');
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'foo', 'baz');

		return $childConfiguration;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getConfigurationReturnsSubConfigurationObject() {
		$childConfiguration = $this->buildChildConfigurationForAllProperties();
		$this->assertSame($childConfiguration, $this->propertyMappingConfiguration->getConfigurationFor('someNotExplicitKey'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function subConfigurationOptionsCanBeRetrieved() {
		$childConfiguration = $this->buildChildConfigurationForAllProperties();
		$this->assertEquals('bar', $childConfiguration->getConfigurationValue('someConverter', 'foo'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function subConfigurationOptionsAreInheritedFromParent() {
		$childConfiguration = $this->buildChildConfigurationForAllProperties();
		$this->assertEquals('parent', $childConfiguration->getConfigurationValue('someConverter', 'otherkey'));
	}

	/**
	 * @return \F3\FLOW3\Property\PropertyMappingConfiguration
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildChildConfigurationForSingleProperty() {
		$childConfiguration = $this->propertyMappingConfiguration->forProperty('key1.key2');
		$childConfiguration->setTypeConverterOption('someConverter', 'foo', 'specialChildConverter');

		return $childConfiguration;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function subConfigurationOptionsForSpecificValuesOverrideForParent() {
		$this->buildChildConfigurationForAllProperties();
		$childConfiguration = $this->buildChildConfigurationForSingleProperty();
		$this->assertSame($childConfiguration, $this->propertyMappingConfiguration->getConfigurationFor('key1')->getConfigurationFor('key2'));

		$this->assertEquals('specialChildConverter', $childConfiguration->getConfigurationValue('someConverter', 'foo'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function subConfigurationOptionsForSpecificValuesAreInheritedFromParent() {
		$this->buildChildConfigurationForAllProperties();
		$childConfiguration = $this->buildChildConfigurationForSingleProperty();

		$this->assertEquals('parent', $childConfiguration->getConfigurationValue('someConverter', 'otherkey'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function subConfigurationOptionsForSpecificValuesAreMergedWithGeneralOptionsForTheSameLayer() {
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k1', 'global');
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k2', 'global');
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k3', 'global');

		$this->propertyMappingConfiguration->forProperty('a.b.c')->setTypeConverterOption('someConverter', 'k1', 'local');
		$this->propertyMappingConfiguration->forProperty('a')->forAllProperties()->setTypeConverterOption('someConverter', 'k1', 'middle');
		$this->propertyMappingConfiguration->forProperty('a')->forAllProperties()->setTypeConverterOption('someConverter', 'k2', 'middle');

		$that = $this;
		$expectation = function($configuration, $path, $keysAndExpectations) use ($that) {
			foreach (explode('.', $path) as $pathElement) {
				$configuration = $configuration->getConfigurationFor($pathElement);
			}
			foreach ($keysAndExpectations as $key => $expected) {
				$that->assertEquals($expected, $configuration->getConfigurationValue('someConverter', $key));
			}

		};

		$expectation($this->propertyMappingConfiguration, 'a.b.c', array(
			'k1' => 'local',
			'k2' => 'middle',
			'k3' => 'global',
		));

		$expectation($this->propertyMappingConfiguration, 'a.b', array(
			'k1' => 'middle',
			'k2' => 'middle',
			'k3' => 'global',
		));

		$expectation($this->propertyMappingConfiguration, 'a', array(
			'k1' => 'global',
			'k2' => 'global',
			'k3' => 'global',
		));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTargetPropertyNameShouldRespectMapping() {
		$this->propertyMappingConfiguration->setMapping('k1', 'k1a');
		$this->assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
		$this->assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
	}
}
?>