<?php
namespace TYPO3\FLOW3\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once('Fixture/DummyInterface1.php');
require_once('Fixture/DummyInterface2.php');


/**
 * Testcase for ClassReflection
 *
 */
class ClassReflectionTest extends \TYPO3\FLOW3\Tests\UnitTestCase implements \TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface1, \TYPO3\FLOW3\Tests\Reflection\Fixture\DummyInterface2 {

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
	 */
	public function getPropertiesReturnsFLOW3sPropertyReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$properties = $class->getProperties();

		$this->assertTrue(is_array($properties), 'The returned value is no array.');
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\PropertyReflection', array_pop($properties), 'The returned properties are not of type \TYPO3\FLOW3\Reflection\PropertyReflection.');
	}

	/**
	 * @test
	 */
	public function getPropertyReturnsFLOW3sPropertyReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\PropertyReflection', $class->getProperty('someProperty'), 'The returned property is not of type \TYPO3\FLOW3\Reflection\PropertyReflection.');
		$this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
	}

	/**
	 * @test
	 */
	public function getMethodsReturnsFLOW3sMethodReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$methods = $class->getMethods();
		foreach ($methods as $method) {
			$this->assertInstanceOf('TYPO3\FLOW3\Reflection\MethodReflection', $method, 'The returned methods are not of type \TYPO3\FLOW3\Reflection\MethodReflection.');
		}
	}

	/**
	 * @test
	 */
	public function getMethodsReturnsArrayWithNumericIndex() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$methods = $class->getMethods();
		foreach (array_keys($methods) as $key) {
			$this->assertInternalType('integer', $key, 'The index was not an integer.');
		}
	}

	/**
	 * @test
	 */
	public function getMethodReturnsFLOW3sMethodReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$method = $class->getMethod('getMethodReturnsFLOW3sMethodReflection');
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\MethodReflection', $method, 'The returned method is not of type \TYPO3\FLOW3\Reflection\MethodReflection.');
	}

	/**
	 * @test
	 */
	public function getConstructorReturnsFLOW3sMethodReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$constructor = $class->getConstructor();
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\MethodReflection', $constructor, 'The returned method is not of type \TYPO3\FLOW3\Reflection\MethodReflection.');
	}

	/**
	 * @test
	 */
	public function getInterfacesReturnsFLOW3sClassReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$interfaces = $class->getInterfaces();
		foreach ($interfaces as $interface) {
			$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ClassReflection', $interface);
		}
	}

	/**
	 * @test
	 */
	public function getParentClassReturnsFLOW3sClassReflection() {
		$class = new \TYPO3\FLOW3\Reflection\ClassReflection(__CLASS__);
		$parentClass = $class->getParentClass();
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ClassReflection', $parentClass);
	}
}
?>