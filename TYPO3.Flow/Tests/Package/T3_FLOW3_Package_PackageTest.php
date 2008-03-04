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
 * Testcase for the package class
 * 
 * @package     TYPO3
 * @package  TYPO3
 * @version     $Id:T3_FLOW3_Package_Test.php 201 2007-03-30 11:18:30Z robert $
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Package_PackageTest extends T3_Testing_BaseTestCase {

	protected $packageManager;
	
	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->packageManager = $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface');
	}

	/**
	 * Checks if the constructor throws exceptions
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsPackageDoesNotExistException() {
		try {
			$package = $this->componentManager->getComponent('T3_FLOW3_Package_Package', 'TestPackage', FLOW3_PATH_PACKAGES . 'ThisPackageSurelyDoesNotExist', $this->packageManager);
		} catch (Exception $exception) {
			$this->assertEquals(1166631889, $exception->getCode(), 'The constructor throwed an exception but with an unexpected error code (' . $exception->getCode() . ')');
			return;
		}
		$this->fail('The constructor did not throw an exception although the package path did not exist.');
	}
	
	/**
	 * Checks if the constructor throws exceptions
	 * 
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsInvalidPathException() {
		try {
			$package = $this->componentManager->getComponent('T3_FLOW3_Package_Package', 'TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage', $this->packageManager);
		} catch (Exception $exception) {
			$this->assertEquals(1166633720, $exception->getCode(), 'The constructor throwed an exception but with an unexpected error code (' . $exception->getCode() . ')');
			return;
		}
		$this->fail('The constructor did not throw an exception although the package path did not end with a slash.');
	}
	
	/**
	 * Test the method getClassFiles() without initializing the package manager
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassFilesWorks() {
		$package = $this->componentManager->getComponent('T3_FLOW3_Package_Package', 'TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage/', $this->packageManager);
		$classFiles = $package->getClassFiles();

		$this->assertTrue(key_exists('T3_TestPackage_BasicClass', $classFiles), 'The BasicClass is not in the class files array!');
		$this->assertTrue(key_exists('T3_TestPackage_SubDirectory_ClassInSubDirectory', $classFiles), 'Class from sub directory is not in the class files array!');
		$this->assertTrue($classFiles['T3_TestPackage_BasicClass'] == 'T3_TestPackage_BasicClass.php', 'Class files array contains wrong path for BasicClass!');
		$this->assertTrue($classFiles['T3_TestPackage_SubDirectory_ClassInSubDirectory'] == 'SubDirectory/T3_TestPackage_SubDirectory_ClassInSubDirectory.php', 'Class files array contains wrong path for ClassInSubDirectory!');
	}
}
?>