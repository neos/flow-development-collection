<?php
namespace F3\FLOW3\Tests\Unit\Object\Configuration;

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
 * Testcase for the object configuration class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConfigurationTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Object\Configuration\Configuration
	 */
	protected $objectConfiguration;

	/**
	 * Prepares everything for a test
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('TYPO3\Foo\Bar');
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
			'validProperty' => new \F3\FLOW3\Object\Configuration\ConfigurationProperty('validProperty', 'simple string'),
			'invalidProperty' => 'foo'
		);

		$this->objectConfiguration->setProperties($invalidProperties);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function passingAnEmptyArrayToSetPropertiesRemovesAllExistingproperties() {
		$someProperties = array (
			'prop1' => new \F3\FLOW3\Object\Configuration\ConfigurationProperty('prop1', 'simple string'),
			'prop2' => new \F3\FLOW3\Object\Configuration\ConfigurationProperty('prop2', 'another string')
		);
		$this->objectConfiguration->setProperties($someProperties);
		$this->assertEquals($someProperties, $this->objectConfiguration->getProperties(), 'The set properties could not be retrieved again.');

		$this->objectConfiguration->setProperties(array());
		$this->assertEquals(array(), $this->objectConfiguration->getProperties(), 'The properties have not been cleared.');
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
			1 => new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'simple string'),
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
			1 => new \F3\FLOW3\Object\Configuration\ConfigurationArgument(1, 'simple string'),
			2 => new \F3\FLOW3\Object\Configuration\ConfigurationArgument(2, 'another string')
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
	public function setFactoryObjectNameAcceptsValidClassNames() {
		$this->objectConfiguration->setFactoryObjectName(__CLASS__);
		$this->assertSame(__CLASS__, $this->objectConfiguration->getFactoryObjectName());
	}

	/**
	 * @test
	 * @expectedException F3\FLOW3\Object\Exception\InvalidClassException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFactoryObjectNameRejectsNamesOfNonExistingNlasses() {
		$this->objectConfiguration->setFactoryObjectName('TYPO3\Virtual\NonExistingClass');
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