<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

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
 * @version $Id$
 */

/**
 * Testcase for the default package manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Package\Manager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->packageManager = new \F3\FLOW3\Package\Manager();
		$this->packageManager->initialize();
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
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackageReturnsPackagesAndThrowsExcpetions() {
		$package = $this->packageManager->getPackage('FLOW3');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');

		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getAvailablePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getAvailablePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getAvailablePackages().');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getAvailablePackages() is not a valid package object.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getActivePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getActivePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getActivePackages().');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getActiveePackages() is not a valid package object.');
	}

	/**
	 * Checks the method getPackagePath()
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackagePathReturnsTheCorrectPathOfTheTestPackage() {
		$actualPackagePath = $this->packageManager->getPackagePath('TestPackage');
		$expectedPackagePath = FLOW3_PATH_PACKAGES . 'TestPackage/';
		$this->assertEquals($expectedPackagePath, $actualPackagePath, 'getPackagePath() did not return the correct path for package "TestPackage".');

		$this->packageManager->getPackagePath('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * Checks the method getPackageClassesPath()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackageClassesPathReturnsClassesPathOfTestPackage() {
		$actualPackageClassesPath = $this->packageManager->getPackageClassesPath('TestPackage');
		$expectedPackageClassesPath = FLOW3_PATH_PACKAGES . 'TestPackage/Classes/';
		$this->assertEquals($expectedPackageClassesPath, $actualPackageClassesPath, 'getPackageClassesPath() did not return the correct path for package "TestPackage".');

		$this->packageManager->getPackageClassesPath('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();
		$this->assertEquals('TestPackage', $packageManager->getCaseSensitivePackageKey('testpackage'));
	}
}
?>