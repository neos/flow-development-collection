<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Component;

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
 * @version $Id:F3::FLOW3::Component::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the component configuration builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ConfigurationBuilderTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allValidOptionsAreSetCorrectly() {
		$configurationContainer = new F3::FLOW3::Configuration::Container();
		$configurationContainer->scope = 'prototype';
		$configurationContainer->properties->firstProperty = 'straightValue';
		$configurationContainer->properties->secondProperty->reference = 'F3::FLOW3::Component::ManagerInterface';
		$configurationContainer->constructorArguments[1] = 'straightConstructorValue';
		$configurationContainer->constructorArguments[2]->reference = 'F3::FLOW3::Configuration::Manager';
		$configurationContainer->className = __CLASS__;
		$configurationContainer->lifecycleInitializationMethod = 'initializationMethod';
		$configurationContainer->autoWiringMode = FALSE;

		$componentConfiguration = new F3::FLOW3::Component::Configuration('TestComponent', __CLASS__);
		$componentConfiguration->setScope(F3::FLOW3::Component::Configuration::SCOPE_PROTOTYPE);
		$componentConfiguration->setProperty(new F3::FLOW3::Component::ConfigurationProperty('firstProperty', 'straightValue'));
		$componentConfiguration->setProperty(new F3::FLOW3::Component::ConfigurationProperty('secondProperty', 'F3::FLOW3::Component::ManagerInterface', F3::FLOW3::Component::ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$componentConfiguration->setConstructorArgument(new F3::FLOW3::Component::ConfigurationArgument(1, 'straightConstructorValue'));
		$componentConfiguration->setConstructorArgument(new F3::FLOW3::Component::ConfigurationArgument(2, 'F3::FLOW3::Configuration::Manager', F3::FLOW3::Component::ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$componentConfiguration->setClassName(__CLASS__);
		$componentConfiguration->setLifecycleInitializationMethod('initializationMethod');
		$componentConfiguration->setAutoWiringMode(FALSE);

		$builtComponentConfiguration = F3::FLOW3::Component::ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__);
		$this->assertEquals($componentConfiguration, $builtComponentConfiguration, 'The manually created and the built component configuration don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function existingComponentConfigurationIsUsedIfSpecified() {
		$configurationContainer = new F3::FLOW3::Configuration::Container();
		$configurationContainer->scope = 'prototype';
		$configurationContainer->properties->firstProperty = 'straightValue';

		$componentConfiguration = new F3::FLOW3::Component::Configuration('TestComponent', __CLASS__);

		$builtComponentConfiguration = F3::FLOW3::Component::ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__, $componentConfiguration);
		$this->assertSame($componentConfiguration, $builtComponentConfiguration, 'The returned component configuration object is not the one we passed to the builder.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidOptionResultsInException() {
		$configurationContainer = new F3::FLOW3::Configuration::Container();
		$configurationContainer->scoopy = 'prototype';

		try {
			$builtComponentConfiguration = F3::FLOW3::Component::ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__);
			$this->fail('No exception was thrown.');
		} catch (F3::FLOW3::Component::Exception::InvalidComponentConfiguration $exception) {
		}
	}
}
?>