<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the object configuration builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ConfigurationBuilderTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allValidOptionsAreSetCorrectly() {
		$configurationArray = array();
		$configurationArray['scope'] = 'prototype';
		$configurationArray['properties']['firstProperty'] = 'straightValue';
		$configurationArray['properties']['secondProperty']['reference'] = 'F3\FLOW3\Object\ManagerInterface';
		$configurationArray['constructorArguments'][1] = 'straightConstructorValue';
		$configurationArray['constructorArguments'][2]['reference'] = 'F3\FLOW3\Configuration\Manager';
		$configurationArray['className'] = __CLASS__;
		$configurationArray['lifecycleInitializationMethod'] = 'initializationMethod';
		$configurationArray['autoWiringMode'] = FALSE;

		$objectConfiguration = new \F3\FLOW3\Object\Configuration('TestObject', __CLASS__);
		$objectConfiguration->setScope(\F3\FLOW3\Object\Configuration::SCOPE_PROTOTYPE);
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\ConfigurationProperty('firstProperty', 'straightValue'));
		$objectConfiguration->setProperty(new \F3\FLOW3\Object\ConfigurationProperty('secondProperty', 'F3\FLOW3\Object\ManagerInterface', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$objectConfiguration->setConstructorArgument(new \F3\FLOW3\Object\ConfigurationArgument(1, 'straightConstructorValue'));
		$objectConfiguration->setConstructorArgument(new \F3\FLOW3\Object\ConfigurationArgument(2, 'F3\FLOW3\Configuration\Manager', \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$objectConfiguration->setClassName(__CLASS__);
		$objectConfiguration->setLifecycleInitializationMethod('initializationMethod');
		$objectConfiguration->setAutoWiringMode(FALSE);

		$builtObjectConfiguration = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray('TestObject', $configurationArray, __CLASS__);
		$this->assertEquals($objectConfiguration, $builtObjectConfiguration, 'The manually created and the built object configuration don\'t match.');
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