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
 * Testcase for the Parameter Reflection
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::AOP::Framework.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ParameterTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new F3::FLOW3::Reflection::ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg2');
		$this->assertType('F3::FLOW3::Reflection::ClassReflection', $parameter->getDeclaringClass());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassReturnsFLOW3sClassReflection($dummy = NULL) {
		$parameter = new F3::FLOW3::Reflection::ParameterReflection(array(__CLASS__, 'fixtureMethod'), 'arg1');
		$this->assertType('F3::FLOW3::Reflection::ClassReflection', $parameter->getClass());
	}

	/**
	 * Just a fixture method
	 */
	protected function fixtureMethod(::ArrayObject $arg1, $arg2 = NULL) {
	}
}
?>