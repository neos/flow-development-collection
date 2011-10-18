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


/**
 * Testcase for MethodReflection
 *
 */
class MethodReflectionTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var mixed
	 */
	protected $someProperty;

	/**
	 * @test
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection() {
		$method = new \TYPO3\FLOW3\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ClassReflection', $method->getDeclaringClass());
	}

	/**
	 * @test
	 */
	public function getParametersReturnsFLOW3sParameterReflection($dummyArg1 = NULL, $dummyArg2 = NULL) {
		$method = new \TYPO3\FLOW3\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
		foreach ($method->getParameters() as $parameter) {
			$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ParameterReflection', $parameter);
			$this->assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
		}
	}
}
?>