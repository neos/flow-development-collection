<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

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
 * Testcase for the object configuration class
 *
 * @package     FLOW3
 * @version     $Id:F3::FLOW3::Object::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ConfigurationTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Object::Configuration
	 */
	protected $objectConfiguration;

	/**
	 * Prepares everything for a test
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->objectConfiguration = new F3::FLOW3::Object::Configuration('F3::TestPackage::BasicClass', FLOW3_PATH_PACKAGES . 'TestPackage/Classes/F3::TestPackage::BasicClass.php');
	}

	/**
	 * Checks if setScope accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setScopeOnlyAcceptsValidValues() {
		try {
			$this->objectConfiguration->setScope('singleton');
			$this->objectConfiguration->setScope('prototype');
			$this->objectConfiguration->setScope('session');
		} catch (::Exception $exception) {
			$this->fail('setScope throwed an exception although the values were valid.');
		}

		try {
			$this->objectConfiguration->setScope(-1);
		} catch (::Exception $exception) {
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
			'validProperty' => new F3::FLOW3::Object::ConfigurationProperty('validProperty', 'simple string'),
			'invalidProperty' => 'foo'
		);
		try {
			$this->objectConfiguration->setProperties($invalidProperties);
		} catch (::Exception $exception) {
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
			1 => new F3::FLOW3::Object::ConfigurationArgument(1, 'simple string'),
			2 => 'foo'
		);
		try {
			$this->objectConfiguration->setConstructorArguments($invalidArguments);
		} catch (::Exception $exception) {
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
			1 => new F3::FLOW3::Object::ConfigurationArgument(1, 'simple string'),
			2 => new F3::FLOW3::Object::ConfigurationArgument(2, 'another string')
		);
		$this->objectConfiguration->setConstructorArguments($someArguments);
		$this->assertEquals($someArguments, $this->objectConfiguration->getConstructorArguments(), 'The set arguments could not be retrieved again.');

		$this->objectConfiguration->setConstructorArguments(array());
		$this->assertEquals(array(), $this->objectConfiguration->getConstructorArguments(), 'The constructor arguments have not been cleared.');
	}
}
?>