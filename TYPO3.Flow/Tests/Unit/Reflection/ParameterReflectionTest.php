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
 * Testcase for the ParameterReflection
 *
 */
class ParameterReflectionTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new \TYPO3\FLOW3\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg2');
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ClassReflection', $parameter->getDeclaringClass());
	}

	/**
	 * @test
	 */
	public function getClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new \TYPO3\FLOW3\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg1');
		$this->assertInstanceOf('TYPO3\FLOW3\Reflection\ClassReflection', $parameter->getClass());
	}

	/**
	 * Just a fixture method
	 */
	protected function fixtureMethod(\ArrayObject $arg1, $arg2 = NULL) {
	}
}
?>