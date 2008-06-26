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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the Utility Array class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Utility_ArraysTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnEmptyArray() {
		$this->assertFalse(F3_FLOW3_Utility_Arrays::containsMultipleTypes(array()), 'An empty array was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithIntegers() {
		$this->assertFalse(F3_FLOW3_Utility_Arrays::containsMultipleTypes(array(1, 2, 3)), 'An array with only integers was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsFalseOnArrayWithObjects() {
		$this->assertFalse(F3_FLOW3_Utility_Arrays::containsMultipleTypes(array(new stdClass(), new stdClass(), new stdClass())), 'An array with only stdClass was seen as containing multiple types');
	}

	/**
	 * @test
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function containsMultipleTypesReturnsTrueOnMixedArray() {
		$this->assertTrue(F3_FLOW3_Utility_Arrays::containsMultipleTypes(array(1, 'string', 1.25, new stdClass())), 'An array with mixed contents was not seen as containing multiple types');
	}

}
?>