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
 * @version $Id:F3_FLOW3_Package_Test.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the package class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Package_Test.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Package_PackageTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @expectedException F3_FLOW3_Package_Exception_InvalidPackagePath
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsPackageDoesNotExistException() {
		$mockPackageManager = $this->getMock('F3_FLOW3_Package_Manager', array(), array(), '', FALSE);
		new F3_FLOW3_Package_Package('TestPackage', FLOW3_PATH_PACKAGES . 'ThisPackageSurelyDoesNotExist', $mockPackageManager);
	}

	/**
	 * Checks if the constructor throws exceptions
	 *
	 * @test
	 * @expectedException F3_FLOW3_Package_Exception_InvalidPackagePath
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsInvalidPathException() {
		$mockPackageManager = $this->getMock('F3_FLOW3_Package_Manager', array(), array(), '', FALSE);
		new F3_FLOW3_Package_Package('TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage', $mockPackageManager);
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_Package_Exception_InvalidPackageKey
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRejectsInvalidPackageKeys() {
		$mockPackageManager = $this->getMock('F3_FLOW3_Package_Manager', array(), array(), '', FALSE);
		new F3_FLOW3_Package_Package('Invalid_Package_Key', FLOW3_PATH_PACKAGES . 'TestPackage/', $mockPackageManager);
	}

	/**
	 * Test the method getClassFiles() without initializing the package manager
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassFilesWorks() {
		$mockPackageManager = $this->getMock('F3_FLOW3_Package_Manager', array(), array(), '', FALSE);
		$package = new F3_FLOW3_Package_Package('TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage/', $mockPackageManager);
		$classFiles = $package->getClassFiles();

		$this->assertTrue(key_exists('F3_TestPackage_BasicClass', $classFiles), 'The BasicClass is not in the class files array!');
		$this->assertTrue(key_exists('F3_TestPackage_SubDirectory_ClassInSubDirectory', $classFiles), 'Class from sub directory is not in the class files array!');
		$this->assertTrue($classFiles['F3_TestPackage_BasicClass'] == 'F3_TestPackage_BasicClass.php', 'Class files array contains wrong path for BasicClass!');
		$this->assertTrue($classFiles['F3_TestPackage_SubDirectory_ClassInSubDirectory'] == 'SubDirectory/F3_TestPackage_SubDirectory_ClassInSubDirectory.php', 'Class files array contains wrong path for ClassInSubDirectory!');
	}
}
?>