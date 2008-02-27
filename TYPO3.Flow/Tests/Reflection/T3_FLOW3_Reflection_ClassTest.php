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
 * @version $Id: $
 */

/**
 * Testcase for Reflection Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_AOP_Framework.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class T3_FLOW3_Reflection_ClassTest extends T3_Testing_BaseTestCase {

	protected $someProperty;

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertiesReturnsFLOW3sPropertyReflection() {
		$class = new T3_FLOW3_Reflection_Class(__CLASS__);
		$properties = $class->getProperties();

		$this->assertTrue(is_array($properties), 'The returned value is no array.');
		$this->assertType('T3_FLOW3_Reflection_Property', array_pop($properties), 'The returned properties are not of type T3_FLOW3_Reflection_Property.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyReturnsFLOW3sPropertyReflection() {
		$class = new T3_FLOW3_Reflection_Class(__CLASS__);
		$this->assertType('T3_FLOW3_Reflection_Property', $class->getProperty('someProperty'), 'The returned property is not of type T3_FLOW3_Reflection_Property.');
		$this->assertEquals('someProperty', $class->getProperty('someProperty')->getName(), 'The returned property seems not to be the right one.');
	}

}
?>