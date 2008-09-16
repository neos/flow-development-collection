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

/**
 * Testcase for Reflection Method
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class MethodTest extends F3::Testing::BaseTestCase {

	/**
	 * @var mixed
	 */
	protected $someProperty;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection() {
		$method = new F3::FLOW3::Reflection::MethodReflection(__CLASS__, __FUNCTION__);
		$this->assertType('F3::FLOW3::Reflection::ClassReflection', $method->getDeclaringClass());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParametersReturnsFLOW3sParameterReflection($dummyArg1 = NULL, $dummyArg2 = NULL) {
		$method = new F3::FLOW3::Reflection::MethodReflection(__CLASS__, __FUNCTION__);
		foreach ($method->getParameters() as $parameter) {
			$this->assertType('F3::FLOW3::Reflection::ParameterReflection', $parameter);
			$this->assertEquals(__CLASS__, $parameter->getDeclaringClass()->getName());
		}
	}
}
?>