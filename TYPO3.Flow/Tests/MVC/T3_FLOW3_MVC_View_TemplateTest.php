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
 * Testcase for the MVC Template View
 * 
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_View_TemplateTest extends T3_Testing_BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function scopeIsPrototype() {
		$instance1 = $this->componentManager->getComponent('T3_FLOW3_MVC_View_Template');
		$instance2 = $this->componentManager->getComponent('T3_FLOW3_MVC_View_Template');
		$this->assertNotSame($instance1, $instance2, 'The template view is not a prototype.');
	}
}
?>