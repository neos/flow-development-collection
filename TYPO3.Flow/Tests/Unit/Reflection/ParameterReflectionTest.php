<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * Testcase for the ParameterReflection
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ParameterReflectionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new \F3\FLOW3\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg2');
		$this->assertType('F3\FLOW3\Reflection\ClassReflection', $parameter->getDeclaringClass());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new \F3\FLOW3\Reflection\ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg1');
		$this->assertType('F3\FLOW3\Reflection\ClassReflection', $parameter->getClass());
	}

	/**
	 * Just a fixture method
	 */
	protected function fixtureMethod(\ArrayObject $arg1, $arg2 = NULL) {
	}
}
?>