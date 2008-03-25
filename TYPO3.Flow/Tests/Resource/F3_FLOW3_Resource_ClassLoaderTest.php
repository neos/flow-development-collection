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
 * Testcase for the component class loader
 *
 * @package    FLOW3
 * @version    $Id:F3_FLOW3_Component_ClassLoaderTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright  Copyright belongs to the respective authors
 * @license    http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Resource_ClassLoaderTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if the package autoloader loads classes from subdirectories.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function classesFromSubdirectoriesAreLoaded() {
		$dummyObject = new F3_TestPackage_SubDirectory_ClassInSubDirectory;
		$this->assertTrue(class_exists('F3_TestPackage_SubDirectory_ClassInSubDirectory'), 'The class in a subdirectory has not been loaded by the package autoloader.');
	}
}
?>