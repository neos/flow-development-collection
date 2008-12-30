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
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the object configuration class
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ConfigurationTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\Configuration
	 */
	protected $objectConfiguration;

	/**
	 * Prepares everything for a test
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->objectConfiguration = new \F3\FLOW3\Object\Configuration('F3\TestPackage\BasicClass');
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
		} catch (\Exception $exception) {
			$this->fail('setScope throwed an exception although the values were valid.');
		}

		try {
			$this->objectConfiguration->setScope(-1);
		} catch (\Exception $exception) {
			return;
		}
		$this->fail('setScope throwed no exception although the value was invalid.');
	}

	/**
	 * Checks if setProperties accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \Exception
	 */
	public function setPropertiesOnlyAcceptsValidValues() {
		$invalidProperties = array (
			'validProperty' => new \F3\FLOW3\Object\ConfigurationProperty('validProperty', 'simple string'),
			'invalidProperty' => 'foo'
		);

		$this->objectConfiguration->setProperties($invalidProperties);
	}

	/**
	 * Checks if setArguments accepts only valid values
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \Exception
	 */
	public function setArgumentsOnlyAcceptsValidValues() {
		$invalidArguments = array (
			1 => new \F3\FLOW3\Object\ConfigurationArgument(1, 'simple string'),
			2 => 'foo'
		);

		$this->objectConfiguration->setArguments($invalidArguments);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingAnEmptyArrayToSetArgumentsRemovesAllExistingArguments() {
		$someArguments = array (
			1 => new \F3\FLOW3\Object\ConfigurationArgument(1, 'simple string'),
			2 => new \F3\FLOW3\Object\ConfigurationArgument(2, 'another string')
		);
		$this->objectConfiguration->setArguments($someArguments);
		$this->assertEquals($someArguments, $this->objectConfiguration->getArguments(), 'The set arguments could not be retrieved again.');

		$this->objectConfiguration->setArguments(array());
		$this->assertEquals(array(), $this->objectConfiguration->getArguments(), 'The constructor arguments have not been cleared.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFactoryClassNameAcceptsValidClassNames() {
		$this->objectConfiguration->setFactoryClassName(__CLASS__);
		$this->assertSame(__CLASS__, $this->objectConfiguration->getFactoryClassName());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Object\Exception\InvalidClass
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFactoryClassNameRejectsNamesOfNonExistingNlasses() {
		$this->objectConfiguration->setFactoryClassName('F3\Virtual\NonExistingClass');
	}

	/**
	 * @test
	 */
	public function setFactoryMethodNameAcceptsValidStrings() {
		$this->objectConfiguration->setFactoryMethodName('someMethodName');
		$this->assertSame('someMethodName', $this->objectConfiguration->getFactoryMethodName());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFactoryMethodNameRejectsAnythingElseThanAString() {
		$this->objectConfiguration->setFactoryMethodName(array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultFactoryMethodNameIsCreate() {
		$this->assertSame('create', $this->objectConfiguration->getFactoryMethodName());
	}
}
?>