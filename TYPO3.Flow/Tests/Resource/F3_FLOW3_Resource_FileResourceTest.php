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
 * Testcase for the File Resource
 *
 * @package    FLOW3
 * @version    $Id:F3_FLOW3_Component_ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Resource_FileResourceTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPrototype() {
		$file1 = $this->componentManager->getComponent('F3_FLOW3_Resource_FileResource', __FILE__);
		$file2 = $this->componentManager->getComponent('F3_FLOW3_Resource_FileResource', __FILE__);
		$this->assertNotSame($file1, $file2, 'File Resource seems to be singleton!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function implementsSPLInterface() {
		$file = new F3_FLOW3_Resource_FileResource(__FILE__);
		$this->assertType('SplFileObject', $file);
	}
}
?>