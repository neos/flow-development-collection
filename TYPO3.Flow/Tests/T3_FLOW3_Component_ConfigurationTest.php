<?php
declare(encoding = 'utf-8');

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
 * Testcase for the component configuration class
 *
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Component_ConfigurationTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_FLOW3_Component_Configuration
	 */
	protected $componentConfiguration;

	/**
	 * Prepares everything for a test
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->componentConfiguration = new T3_FLOW3_Component_Configuration('T3_TestPackage_BasicClass', TYPO3_PATH_PACKAGES . 'TestPackage/Classes/T3_TestPackage_BasicClass.php');
	}

	/**
	 * Checks if setScope accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setScopeOnlyAcceptsValidValues() {
		try {
			$this->componentConfiguration->setScope('singleton');
			$this->componentConfiguration->setScope('prototype');
			$this->componentConfiguration->setScope('session');
		} catch (Exception $exception) {
			$this->fail('setScope throwed an exception although the values were valid.');
		}

		try {
			$this->componentConfiguration->setScope(-1);
		} catch (Exception $exception) {
			return;
		}
		$this->fail('setScope throwed no exception although the value was invalid.');
	}

	/**
	 * Checks if setProperties accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPropertiesOnlyAcceptsValidValues() {
		$invalidProperties = array (
			'validProperty' => new T3_FLOW3_Component_ConfigurationProperty('validProperty', 'simple string'),
			'invalidProperty' => 'foo'
		);
		try {
			$this->componentConfiguration->setProperties($invalidProperties);
		} catch (Exception $exception) {
			$this->assertEquals(1167935337, $exception->getCode(), 'setProperties() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('setProperties throwed no exception although the values were invalid.');
	}

	/**
	 * Checks if setConstructorArguments accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConstructorArgumentsOnlyAcceptsValidValues() {
		$invalidArguments = array (
			1 => new T3_FLOW3_Component_ConfigurationArgument(1, 'simple string'),
			2 => 'foo'
		);
		try {
			$this->componentConfiguration->setConstructorArguments($invalidArguments);
		} catch (Exception $exception) {
			$this->assertEquals(1168004160, $exception->getCode(), 'setConstructorArguments() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('setConstructorArguments() throwed no exception although the values were invalid.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingAnEmptyArrayToSetConstructorArgumentsRemovesAllExistingArguments() {
		$someArguments = array (
			1 => new T3_FLOW3_Component_ConfigurationArgument(1, 'simple string'),
			2 => new T3_FLOW3_Component_ConfigurationArgument(2, 'another string')
		);
		$this->componentConfiguration->setConstructorArguments($someArguments);
		$this->assertEquals($someArguments, $this->componentConfiguration->getConstructorArguments(), 'The set arguments could not be retrieved again.');

		$this->componentConfiguration->setConstructorArguments(array());
		$this->assertEquals(array(), $this->componentConfiguration->getConstructorArguments(), 'The constructor arguments have not been cleared.');
	}
}
?>