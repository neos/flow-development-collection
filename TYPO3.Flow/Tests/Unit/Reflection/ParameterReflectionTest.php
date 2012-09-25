<?php
namespace TYPO3\Flow\Tests\Unit\Reflection;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the ParameterReflection
 *
 */
class ParameterReflectionTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getDeclaringClassReturnsFlowsClassReflection($dummy = NULL) {
		$parameter = new \TYPO3\Flow\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg2');
		$this->assertInstanceOf('TYPO3\Flow\Reflection\ClassReflection', $parameter->getDeclaringClass());
	}

	/**
	 * @test
	 */
	public function getClassReturnsFlowsClassReflection($dummy = NULL) {
		$parameter = new \TYPO3\Flow\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg1');
		$this->assertInstanceOf('TYPO3\Flow\Reflection\ClassReflection', $parameter->getClass());
	}

	/**
	 * Just a fixture method
	 */
	protected function fixtureMethod(\ArrayObject $arg1, $arg2 = NULL) {
	}
}
?>