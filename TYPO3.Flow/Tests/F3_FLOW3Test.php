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
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the FLOW3 base class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_FLOW3Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3Test extends F3_Testing_BaseTestCase {

	/**
	 * Checks the method getComponentManager() and the magic getter
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManagerReturnsComponentManager() {
		$FLOW3 = new F3_FLOW3;
		$FLOW3->initializeClassLoader();
		$FLOW3->initializeFLOW3();
		$this->assertTrue($FLOW3->getComponentManager() instanceof F3_FLOW3_Component_ManagerInterface, 'getComponentManager did not deliver an object implementing F3_FLOW3_Component_ManagerInterface!');
	}
}
?>