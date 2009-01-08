<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\View;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Template View
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
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