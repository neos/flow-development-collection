<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the object configuration builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ConfigurationBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allBasicOptionsAreSetCorrectly() {
		$factoryClassName = uniqid('ConfigurationBuilderTest');
		eval('class ' . $factoryClassName . ' { public function manufacture() {} } ');

		$configurationArray = array();
		$configurationArray['scope'] = 'prototype';
		$configurationArray['className'] = __CLASS__;
		$configurationArray['factoryClassName'] = $factoryClassName;
		$configurationArray['factoryMethodName'] = 'manufacture';
		$configurationArray['lifecycleInitializationMethodName'] = 'initializationMethod';
		$configurationArray['lifecycleShutdownMethodName'] = 'shutdownMethod';
		$configurationArray['autoWiringMode'] = FALSE;

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', __CLASS__);
		$objectConfiguration->setScope(\F3\FLOW3\Object\Configuration::SCOPE_PROTOTYPE);
		$objectConfiguration->setClassName(__CLASS__);
		$objectConfiguration->setFactoryClassName($factoryClassName);
		$objectConfiguration->setFactoryMethodName('manufacture');
		$objectConfiguration->setLifecycleInitializationMethodName('initializationMethod');
		$objectConfiguration->setLifecycleShutdownMethodName('shutdownMethod');
		$objectConfiguration->setAutoWiringMode(FALSE);

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration, 'The manually created and the built object configuration don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function argumentsOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['object']['name'] = 'Foo';
		$configurationArray['arguments'][1]['object']['className'] = __CLASS__;

		$argumentObjectConfiguration = new \F3\FLOW3\Object\Configuration('Foo', __CLASS__);
		$argumentObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ' / argument "1"');

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setArgument(new \F3\FLOW3\Object\ConfigurationArgument(1, $argumentObjectConfiguration, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_OBJECT));

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function propertiesOfTypeObjectCanSpecifyAdditionalObjectConfigurationOptions() {
		$configurationArray = array();
		$configurationArray['properties']['theProperty']['object']['name'] = 'Foo';
		$configurationArray['properties']['theProperty']['object']['className'] = __CLASS__;

		$propertyObjectConfiguration = new \F3\FLOW3\Object\Configuration('Foo', __CLASS__);
		$propertyObjectConfiguration->setConfigurationSourceHint(__CLASS__ . ' / property "theProperty"');

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\ConfigurationProperty('theProperty', $propertyObjectConfiguration, \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_OBJECT));

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToPassArraysAsStraightArgumentOrPropertyValues() {
		$configurationArray = array();
		$configurationArray['properties']['straightValueProperty']['value'] = array('foo' => 'bar', 'object' => 'nö');
		$configurationArray['arguments'][1]['value'] = array('foo' => 'bar', 'object' => 'nö');

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\ConfigurationProperty('straightValueProperty', array('foo' => 'bar', 'object' => 'nö')));
		$objectConfiguration->setArgument(new \F3\FLOW3\Object\ConfigurationArgument(1, array('foo' => 'bar', 'object' => 'nö')));

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function settingsCanBeInjectedAsArgumentOrProperty() {
		$configurationArray = array();
		$configurationArray['arguments'][1]['setting'] = 'F3.Foo.Bar';
		$configurationArray['properties']['someProperty']['setting'] = 'F3.Bar.Baz';

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', 'TestObject');
		$objectConfiguration->setArgument(new \F3\FLOW3\Object\ConfigurationArgument(1, 'F3.Foo.Bar', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_SETTING));
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\ConfigurationProperty('someProperty', 'F3.Bar.Baz', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_SETTING));

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function existingObjectConfigurationIsUsedIfSpecified() {
		$configurationArray = array();
		$configurationArray['scope'] = 'prototype';
		$configurationArray['properties']['firstProperty'] = 'straightValue';

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', __CLASS__);

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__, $objectConfiguration);
		$this->assertSame($objectConfiguration, $builtObjectConfiguration, 'The returned object configuration object is not the one we passed to the builder.');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Object\Exception\InvalidObjectConfiguration
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidOptionResultsInException() {
		$configurationArray = array('scoopy' => 'prototype');
		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
	}
}
?>