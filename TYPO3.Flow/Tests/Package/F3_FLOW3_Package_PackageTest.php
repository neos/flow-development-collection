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
 * Testcase for the package class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class PackageTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackagePath
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsPackageDoesNotExistException() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		new \F3\FLOW3\Package\Package('TestPackage', FLOW3_PATH_PACKAGES . 'ThisPackageSurelyDoesNotExist', $mockPackageManager);
	}

	/**
	 * Checks if the constructor throws exceptions
	 *
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackagePath
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsInvalidPathException() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		new \F3\FLOW3\Package\Package('TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage', $mockPackageManager);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackageKey
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRejectsInvalidPackageKeys() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		new \F3\FLOW3\Package\Package('Invalid_Package_Key', FLOW3_PATH_PACKAGES . 'TestPackage/', $mockPackageManager);
	}

	/**
	 * Test the method getClassFiles() without initializing the package manager
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassFilesWorks() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\Manager', array(), array(), '', FALSE);
		$package = new \F3\FLOW3\Package\Package('TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage/', $mockPackageManager);
		$classFiles = $package->getClassFiles();

		$this->assertTrue(array_key_exists('F3\TestPackage\BasicClass', $classFiles), 'The BasicClass is not in the class files array!');
		$this->assertTrue(array_key_exists('F3\TestPackage\SubDirectory\ClassInSubDirectory', $classFiles), 'Class from sub directory is not in the class files array!');
		$this->assertTrue($classFiles['F3\TestPackage\BasicClass'] == 'F3_TestPackage_BasicClass.php', 'Class files array contains wrong path for BasicClass!');
		$this->assertTrue($classFiles['F3\TestPackage\SubDirectory\ClassInSubDirectory'] == 'SubDirectory/F3_TestPackage_SubDirectory_ClassInSubDirectory.php', 'Class files array contains wrong path for ClassInSubDirectory!');
	}
}
?>