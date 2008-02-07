<?php
declare(encoding = 'utf-8');

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
 * Testcase for the default component manager
 * 
 * @package		TYPO3
 * @version 	$Id:TYPO3Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3Test extends T3_Testing_BaseTestCase {

	/**
	 * Checks the method getComponentManager() and the magic getter
	 *
	 * @test
	 * @author	Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManagerReturnsComponentManager() {
		$TYPO3 = new T3_FLOW3;		
		$this->assertTrue($TYPO3->getComponentManager() instanceof T3_FLOW3_Component_ManagerInterface, 'TYPO3->getComponentManager did not deliver an object implementing T3_FLOW3_Component_ManagerInterface!');
	}
}
?>