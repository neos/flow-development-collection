<?php
namespace F3\FLOW3\Tests\Unit\Reflection;

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

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');

/**
 * Testcase for ClassReflection
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class ClassReflectionTest extends \F3\FLOW3\Tests\UnitTestCase implements \F3\FLOW3\Tests\Reflection\Fixture\DummyInterface1, \F3\FLOW3\Tests\Reflection\Fixture\DummyInterface2 {

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
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$properties = $class->getProperties();

		$this->assertTrue(is_array($properties), 'The returned value is no array.');
		$this->assertInstanceOf('F3\FLOW3\Reflection\PropertyReflection', array_pop($properties), 'The returned properties are not of type \F3\FLOW3\Reflection\PropertyReflection.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyReturnsFLOW3sPropertyReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$this->assertInstanceOf('F3\FLOW3\Reflection\PropertyReflection', $class->getProperty('someProperty'), 'The returned property is not of type \F3\FLOW3\Reflection\PropertyReflection.');
		$this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodsReturnsFLOW3sMethodReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$methods = $class->getMethods();
		foreach ($methods as $method) {
			$this->assertInstanceOf('F3\FLOW3\Reflection\MethodReflection', $method, 'The returned methods are not of type \F3\FLOW3\Reflection\MethodReflection.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodsReturnsArrayWithNumericIndex() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$methods = $class->getMethods();
		foreach (array_keys($methods) as $key) {
			$this->assertInternalType('integer', $key, 'The index was not an integer.');
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodReturnsFLOW3sMethodReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$method = $class->getMethod('getMethodReturnsFLOW3sMethodReflection');
		$this->assertInstanceOf('F3\FLOW3\Reflection\MethodReflection', $method, 'The returned method is not of type \F3\FLOW3\Reflection\MethodReflection.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConstructorReturnsFLOW3sMethodReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$constructor = $class->getConstructor();
		$this->assertInstanceOf('F3\FLOW3\Reflection\MethodReflection', $constructor, 'The returned method is not of type \F3\FLOW3\Reflection\MethodReflection.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInterfacesReturnsFLOW3sClassReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$interfaces = $class->getInterfaces();
		foreach ($interfaces as $interface) {
			$this->assertInstanceOf('F3\FLOW3\Reflection\ClassReflection', $interface);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParentClassReturnsFLOW3sClassReflection() {
		$class = new \F3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$parentClass = $class->getParentClass();
		$this->assertInstanceOf('F3\FLOW3\Reflection\ClassReflection', $parentClass);
	}
}
?>