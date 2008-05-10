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
 * Testcase for the Utility Environment class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_AOP_PointcutTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Utility_EnvironmentTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsPathWithTrailingSlash() {
		$environment = new F3_FLOW3_Utility_Environment();
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertEquals(DIRECTORY_SEPARATOR, substr($path, -1, 1), 'The temporary path did not end with slash (as in DIRECTORY_SEPARATOR).');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPathToTemporaryDirectoryReturnsAnExistingPath() {
		$environment = new F3_FLOW3_Utility_Environment();
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertTrue(file_exists($path), 'The temporary path does not exist.');
	}
}
?>