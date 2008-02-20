<?php
declare(ENCODING = 'utf-8');

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
 * @version 	$Id:T3_FLOW3_AOP_PointcutClassFilterTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_AOP_PointcutMethodNameFilterTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks if the method name filter ignores methods declared as final
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function pointcutFilterDoesNotMatchFinalMethod() {
		$targetClass = new ReflectionClass('T3_TestPackage_BasicClass');
		$targetMethod = $targetClass->getMethod('someFinalMethod');
		
		$methodNameFilter = new T3_FLOW3_AOP_PointcutMethodNameFilter('.*');
		$matches = $methodNameFilter->matches($targetClass, $targetMethod, 1);
		$this->assertFalse($matches, 'Method name filter matches final method although it should not.');
	}
}
?>