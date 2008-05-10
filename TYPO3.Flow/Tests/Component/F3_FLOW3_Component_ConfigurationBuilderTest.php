<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the component configuration builder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Component_ConfigurationBuilderTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function allValidOptionsAreSetCorrectly() {
		$configurationContainer = new F3_FLOW3_Configuration_Container();
		$configurationContainer->scope = 'prototype';
		$configurationContainer->properties->firstProperty = 'straightValue';
		$configurationContainer->properties->secondProperty->reference = 'F3_FLOW3_Component_ManagerInterface';
		$configurationContainer->constructorArguments[1] = 'straightConstructorValue';
		$configurationContainer->constructorArguments[2]->reference = 'F3_FLOW3_Configuration_Manager';
		$configurationContainer->className = __CLASS__;
		$configurationContainer->lifecycleInitializationMethod = 'initializationMethod';
		$configurationContainer->autoWiringMode = FALSE;

		$componentConfiguration = new F3_FLOW3_Component_Configuration('TestComponent', __CLASS__);
		$componentConfiguration->setScope(F3_FLOW3_Component_Configuration::SCOPE_PROTOTYPE);
		$componentConfiguration->setProperty(new F3_FLOW3_Component_ConfigurationProperty('firstProperty', 'straightValue'));
		$componentConfiguration->setProperty(new F3_FLOW3_Component_ConfigurationProperty('secondProperty', 'F3_FLOW3_Component_ManagerInterface', F3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$componentConfiguration->setConstructorArgument(new F3_FLOW3_Component_ConfigurationArgument(1, 'straightConstructorValue'));
		$componentConfiguration->setConstructorArgument(new F3_FLOW3_Component_ConfigurationArgument(2, 'F3_FLOW3_Configuration_Manager', F3_FLOW3_Component_ConfigurationProperty::PROPERTY_TYPES_REFERENCE));
		$componentConfiguration->setClassName(__CLASS__);
		$componentConfiguration->setLifecycleInitializationMethod('initializationMethod');
		$componentConfiguration->setAutoWiringMode(FALSE);

		$builtComponentConfiguration = F3_FLOW3_Component_ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__);
		$this->assertEquals($componentConfiguration, $builtComponentConfiguration, 'The manually created and the built component configuration don\'t match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function existingComponentConfigurationIsUsedIfSpecified() {
		$configurationContainer = new F3_FLOW3_Configuration_Container();
		$configurationContainer->scope = 'prototype';
		$configurationContainer->properties->firstProperty = 'straightValue';

		$componentConfiguration = new F3_FLOW3_Component_Configuration('TestComponent', __CLASS__);

		$builtComponentConfiguration = F3_FLOW3_Component_ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__, $componentConfiguration);
		$this->assertSame($componentConfiguration, $builtComponentConfiguration, 'The returned component configuration object is not the one we passed to the builder.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidOptionResultsInException() {
		$configurationContainer = new F3_FLOW3_Configuration_Container();
		$configurationContainer->scoopy = 'prototype';

		try {
			$builtComponentConfiguration = F3_FLOW3_Component_ConfigurationBuilder::buildFromConfigurationContainer('TestComponent', $configurationContainer, __CLASS__);
			$this->fail('No exception was thrown.');
		} catch (F3_FLOW3_Component_Exception_InvalidComponentConfiguration $exception) {
		}
	}
}
?>