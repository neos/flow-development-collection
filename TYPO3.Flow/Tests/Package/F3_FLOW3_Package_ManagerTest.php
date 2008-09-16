<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Package;

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
 * @version $Id:F3::FLOW3::Package::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the default package manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Package::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Package::Manager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->packageManager = $this->componentFactory->getComponent('F3::FLOW3::Package::ManagerInterface');
	}

	/**
	 * Tests the method isPackageAvailable()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function isPackageAvailableReturnsCorrectResult() {
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
		$package = $this->packageManager->getPackage('FLOW3');
		$this->assertType('F3::FLOW3::Package::PackageInterface', $package, 'The result of getPackage() was no valid package object.');
		try {
			$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
		} catch (::Exception $exception) {
			$this->assertEquals(1166546734, $exception->getCode(), 'getPackage() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('getPackage() did not throw an exception while asking for the path to a non existent package.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getAvailablePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getAvailablePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getAvailablePackages().');
		$this->assertType('F3::FLOW3::Package::PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getAvailablePackages() is not a valid package object.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getActivePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getActivePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getActivePackages().');
		$this->assertType('F3::FLOW3::Package::PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getActiveePackages() is not a valid package object.');
	}

	/**
	 * Checks the method getPackagePath()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getPackagePathReturnsTheCorrectPathOfTheTestPackage() {
		$actualPackagePath = $this->packageManager->getPackagePath('TestPackage');
		$expectedPackagePath = FLOW3_PATH_PACKAGES . 'TestPackage/';
		$this->assertEquals($expectedPackagePath, $actualPackagePath, 'getPackagePath() did not return the correct path for package "TestPackage".');

		try {
			$this->packageManager->getPackagePath('PrettyUnlikelyThatThisPackageExists');
		} catch (::Exception $exception) {
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
		$actualPackageClassesPath = $this->packageManager->getPackageClassesPath('TestPackage');
		$expectedPackageClassesPath = FLOW3_PATH_PACKAGES . 'TestPackage/Classes/';
		$this->assertEquals($expectedPackageClassesPath, $actualPackageClassesPath, 'getPackageClassesPath() did not return the correct path for package "TestPackage".');

		try {
			$this->packageManager->getPackageClassesPath('PrettyUnlikelyThatThisPackageExists');
		} catch (::Exception $exception) {
			$this->assertEquals(1167574237, $exception->getCode(), 'getPackageClassesPath() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('getPackageClassesPath() did not throw an exception while asking for the path to a non existent package.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = new F3::FLOW3::Package::Manager();
		$packageManager->initialize();
		$this->assertEquals('TestPackage', $packageManager->getCaseSensitivePackageKey('testpackage'));
	}
}
?>