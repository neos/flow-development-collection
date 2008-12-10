<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

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
 * @version 	$Id:\F3\FLOW3\Object\TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TemplateTest extends \F3\Testing\BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function scopeIsPrototype() {
		$instance1 = $this->objectManager->getObject('F3\FLOW3\MVC\View\Template');
		$instance2 = $this->objectManager->getObject('F3\FLOW3\MVC\View\Template');
		$this->assertNotSame($instance1, $instance2, 'The template view is not a prototype.');
	}
}
?>