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
 * Testcase for Reflection Method
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class MethodTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var mixed
	 */
	protected $someProperty;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection() {
		$method = new \F3\FLOW3\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
		$this->assertType('F3\FLOW3\Reflection\ClassReflection', $method->getDeclaringClass());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParametersReturnsFLOW3sParameterReflection($dummyArg1 = NULL, $dummyArg2 = NULL) {
		$method = new \F3\FLOW3\Reflection\MethodReflection(__CLASS__, __FUNCTION__);
		foreach ($method->getParameters() as $parameter) {
			$this->assertType('F3\FLOW3\Reflection\ParameterReflection', $parameter);
			$this->assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
		}
	}
}
?>