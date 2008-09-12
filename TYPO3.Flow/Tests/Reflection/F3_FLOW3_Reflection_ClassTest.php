<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

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
 * @version $Id$
 */

require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface1.php');
require_once('Fixture/F3_FLOW3_Tests_Reflection_Fixture_DummyInterface2.php');

/**
 * Testcase for Reflection Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class ClassTest extends F3::Testing::BaseTestCase implements F3::FLOW3::Tests::Reflection::Fixture::DummyInterface1, F3::FLOW3::Tests::Reflection::Fixture::DummyInterface2 {

	/**
	 * @var mixed
	 */
	protected $someProperty;

	/**
	 * @var mixed
	 */
	static protected $someStaticProperty = 'statix';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertiesReturnsFLOW3sPropertyReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$properties = $class->getProperties();

		$this->assertTrue(is_array($properties), 'The returned value is no array.');
		$this->assertType('F3::FLOW3::Reflection::Property', array_pop($properties), 'The returned properties are not of type F3::FLOW3::Reflection::Property.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyReturnsFLOW3sPropertyReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$this->assertType('F3::FLOW3::Reflection::Property', $class->getProperty('someProperty'), 'The returned property is not of type F3::FLOW3::Reflection::Property.');
		$this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodsReturnsFLOW3sMethodReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$methods = $class->getMethods();
		foreach ($methods as $method) {
			$this->assertType('F3::FLOW3::Reflection::Method', $method, 'The returned methods are not of type F3::FLOW3::Reflection::Method.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodsReturnsArrayWithNumericIndex() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$methods = $class->getMethods();
		foreach (array_keys($methods) as $key) {
			$this->assertType('integer', $key, 'The index was not an integer.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodReturnsFLOW3sMethodReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$method = $class->getMethod('getMethodReturnsFLOW3sMethodReflection');
		$this->assertType('F3::FLOW3::Reflection::Method', $method, 'The returned method is not of type F3::FLOW3::Reflection::Method.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConstructorReturnsFLOW3sMethodReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$constructor = $class->getConstructor();
		$this->assertType('F3::FLOW3::Reflection::Method', $constructor, 'The returned method is not of type F3::FLOW3::Reflection::Method.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfacesReturnsFLOW3sClassReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$interfaces = $class->getInterfaces();
		foreach ($interfaces as $interface) {
			$this->assertType('F3::FLOW3::Reflection::ReflectionClass', $interface);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentClassReturnsFLOW3sClassReflection() {
		$class = new F3::FLOW3::Reflection::ReflectionClass(__CLASS__);
		$parentClass = $class->getParentClass();
		$this->assertType('F3::FLOW3::Reflection::ReflectionClass', $parentClass);
	}
}
?>