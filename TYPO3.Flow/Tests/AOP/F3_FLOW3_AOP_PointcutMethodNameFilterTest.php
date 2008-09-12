<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::AOP;

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
 * Testcase for the Pointcut Method Name Filter
 *
 * @package		FLOW3
 * @version 	$Id:F3::FLOW3::AOP::PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PointcutMethodNameFilterTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if the method name filter ignores methods declared as final
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pointcutFilterDoesNotMatchFinalMethod() {
		$targetClass = new F3::FLOW3::Reflection::ReflectionClass('F3::TestPackage::BasicClass');
		$targetMethod = $targetClass->getMethod('someFinalMethod');

		$methodNameFilter = new F3::FLOW3::AOP::PointcutMethodNameFilter('.*');
		$matches = $methodNameFilter->matches($targetClass, $targetMethod, 1);
		$this->assertFalse($matches, 'Method name filter matches final method although it should not.');
	}
}
?>