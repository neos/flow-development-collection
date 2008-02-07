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
 * Testcase for the default package manager
 * 
 * @package     TYPO3
 * @package  TYPO3
 * @version     $Id:T3_FLOW3_Package_ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright   Copyright belongs to the respective authors
 * @license     http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Package_ManagerTest extends T3_Testing_BaseTestCase {

	/**
	 * @var T3_FLOW3_Package_Manager
	 */
	protected $packageManager;
	
	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$TYPO3 = new T3_FLOW3;
		$this->componentManager = $TYPO3->getComponentManager();
		$this->packageManager = $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface');
	}
	
	/**
	 * Tests the method isPackageAvailable()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function isPackageAvailableReturnsCorrectResult() {
		$this->packageManager->initialize();
		$this->assertFalse($this->packageManager->isPackageAvailable('PrettyUnlikelyThatThisPackageExists'), 'isPackageAvailable() did not return FALSE although the package in question does not exist.');
		$this->assertTrue($this->packageManager->isPackageAvailable('FLOW3'), 'isPackageAvailable() did not return TRUE although the package "TYPO3" does (or should) exist.');
	}
	
	/**
	 * Tests the method getPackage()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getPackageReturnsPackagesAndThrowsExcpetions() {
		$this->packageManager->initialize();
		$package = $this->packageManager->getPackage('FLOW3');
		$this->assertType('T3_FLOW3_Package_PackageInterface', $package, 'The result of getPackage() was no valid package object.');
		try {
			$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
		} catch (Exception $exception) {
			$this->assertEquals(1166546734, $exception->getCode(), 'getPackage() throwed an exception but with an unexpected error code.');
			return;
		}	
		$this->fail('getPackage() did not throw an exception while asking for the path to a non existent package.');
	}
		
	/**
	 * Tests the method getPackages()
	 * 
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getPackagesReturnsMultiplePackages() {
		$this->packageManager->initialize();
		$availablePackages = $this->packageManager->getPackages();
		$this->assertTrue(key_exists('FLOW3', $availablePackages), 'The package "TYPO3" was not in the result of getPackages().');
		$this->assertType('T3_FLOW3_Package_PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "TYPO3" delivered by getPackages() is not a valid package object.');
	}
	
	/**
	 * Checks the method getPackagePath()
	 * 
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getPackagePathReturnsTheCorrectPathOfTheTestPackage() {
		$this->packageManager->initialize();
		$actualPackagePath = $this->packageManager->getPackagePath('TestPackage');
		$expectedPackagePath = TYPO3_PATH_ROOT . 'Packages/TestPackage/';
		$this->assertEquals($expectedPackagePath, $actualPackagePath, 'getPackagePath() did not return the correct path for package "TestPackage".');
		
		try {
			$returnedPackagePath = $this->packageManager->getPackagePath('PrettyUnlikelyThatThisPackageExists');
		} catch (Exception $exception) {
			$this->assertEquals(1166543253, $exception->getCode(), 'getPackagePath() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('getPackagePath() did not throw an exception while asking for the path to a non existent package.');
	}
	
	/**
	 * Checks the method getPackageClassesPath()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getPackageClassesPathReturnsClassesPathOfTestPackage() {
		$this->packageManager->initialize();
		$actualPackageClassesPath = $this->packageManager->getPackageClassesPath('TestPackage');
		$expectedPackageClassesPath = TYPO3_PATH_ROOT . 'Packages/TestPackage/Classes/';
		$this->assertEquals($expectedPackageClassesPath, $actualPackageClassesPath, 'getPackageClassesPath() did not return the correct path for package "TestPackage".');
		
		try {
			$returnedPackageClassesPath = $this->packageManager->getPackageClassesPath('PrettyUnlikelyThatThisPackageExists');
		} catch (Exception $exception) {
			$this->assertEquals(1167574237, $exception->getCode(), 'getPackageClassesPath() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('getPackageClassesPath() did not throw an exception while asking for the path to a non existent package.');
	}
}
?>