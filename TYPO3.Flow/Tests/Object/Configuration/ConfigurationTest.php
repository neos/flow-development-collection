<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Configuration;

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
 * @subpackage Object
 * @version $Id$
 */

/**
 * Testcase for the object configuration class
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ConfigurationTest extends \F3\Testing\BaseTestCase {

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
		$this->objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration('F3\Foo\Bar');
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
			'validProperty' => new \F3\FLOW3\Object\Configuration\ConfigurationProperty('validProperty', 'simple string'),
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