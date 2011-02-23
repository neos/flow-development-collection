<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Package;

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
 * Testcase for the package class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PackageTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackagePathException
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsPackageDoesNotExistException() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManager', array(), array(), '', FALSE);
		new \F3\FLOW3\Package\Package('TestPackage', './ThisPackageSurelyDoesNotExist', $mockPackageManager);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackageKeyException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRejectsInvalidPackageKeys() {
		$mockPackageManager = $this->getMock('F3\FLOW3\Package\PackageManager', array(), array(), '', FALSE);
		new \F3\FLOW3\Package\Package('Invalid*PackageKey', './TestPackage/', $mockPackageManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMetaPathReturnsPathToMetaDirectory() {
		$package = new \F3\FLOW3\Package\Package('FLOW3', FLOW3_PATH_FLOW3);
		$packageMetaDataPath = $package->getMetaPath();

		$this->assertSame($package->getPackagePath() . \F3\FLOW3\Package\Package::DIRECTORY_METADATA, $packageMetaDataPath);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getDocumentationPathReturnsPathToDocumentationDirectory() {
		$package = new \F3\FLOW3\Package\Package('FLOW3', FLOW3_PATH_FLOW3);
		$packageDocumentationPath = $package->getDocumentationPath();

		$this->assertEquals($package->getPackagePath() . \F3\FLOW3\Package\Package::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPackageDocumentationsReturnsEmptyArrayIfDocumentationDirectoryDoesntExist() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$packagePath = \vfsStream::url('testDirectory') . '/';

		$package = new \F3\FLOW3\Package\Package('FLOW3', $packagePath);
		$documentations = $package->getPackageDocumentations();

		$this->assertEquals(array(), $documentations);
	}
}
?>