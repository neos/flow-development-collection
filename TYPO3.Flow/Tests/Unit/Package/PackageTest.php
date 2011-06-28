<?php
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

use \F3\FLOW3\Package\Package;

/**
 * Testcase for the package class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PackageTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Packages'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackagePathException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructThrowsPackageDoesNotExistException() {
		new Package('Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function validPackageKeys() {
		return array(
			array('Doctrine'),
			array('TYPO3.FLOW3'),
			array('RobertLemke.FLOW3.Twitter'),
			array('Sumphonos.Stem'),
			array('Schalke04.Soccer.MagicTrainer')
		);
	}

	/**
	 * @test
	 * @dataProvider validPackageKeys
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructAcceptsValidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir ($packagePath, 0777, TRUE);

		$package = new Package($packageKey, $packagePath);
		$this->assertEquals($packageKey, $package->getPackageKey());
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidPackageKeys() {
		return array(
			array('3TYPO.FLOW3'),
			array('TYPO3..FLOW3'),
			array('RobertLemke.FLOW3. Twitter'),
			array('sumphonos.stem'),
			array('Schalke*4')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPackageKeys
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackageKeyException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructRejectsInvalidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir ($packagePath, 0777, TRUE);
		new Package($packageKey, $packagePath);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPackageNamespaceReturnsThePhpNamespaceCorrespondingToThePageKey() {
		$packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
		mkdir ($packagePath, 0777, TRUE);
		$package = new Package('Acme.MyPackage', $packagePath);
		$this->assertEquals('Acme\\MyPackage', $package->getPackageNamespace());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMetaPathReturnsPathToMetaDirectory() {
		$package = new Package('TYPO3.FLOW3', FLOW3_PATH_FLOW3);
		$packageMetaDataPath = $package->getMetaPath();
		$this->assertSame($package->getPackagePath() . Package::DIRECTORY_METADATA, $packageMetaDataPath);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getDocumentationPathReturnsPathToDocumentationDirectory() {
		$package = new Package('TYPO3.FLOW3', FLOW3_PATH_FLOW3);
		$packageDocumentationPath = $package->getDocumentationPath();

		$this->assertEquals($package->getPackagePath() . Package::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPackageDocumentationsReturnsEmptyArrayIfDocumentationDirectoryDoesntExist() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$packagePath = \vfsStream::url('testDirectory') . '/';

		$package = new Package('TYPO3.FLOW3', $packagePath);
		$documentations = $package->getPackageDocumentations();

		$this->assertEquals(array(), $documentations);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aPackageCanBeFlaggedAsProtected() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		$package = new Package('Vendor.Dummy', $packagePath);

		$this->assertFalse($package->isProtected());
		$package->setProtected(TRUE);
		$this->assertTrue($package->isProtected());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isObjectManagementEnabledTellsIfObjectManagementShouldBeEnabledForThePackage() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		$package = new Package('Vendor.Dummy', $packagePath);

		$this->assertTrue($package->isObjectManagementEnabled());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassFilesReturnsAListOfClassFilesOfThePackage() {
		$packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
		mkdir ($packagePath, 0777, TRUE);

		mkdir($packagePath . 'Classes/Controller', 0770, TRUE);
		mkdir($packagePath . 'Classes/Domain/Model', 0770, TRUE);

		file_put_contents($packagePath . 'Classes/Controller/FooController.php', '');
		file_put_contents($packagePath . 'Classes/Domain/Model/Foo.php', '');
		file_put_contents($packagePath . 'Classes/Domain/Model/Bar.php', '');

		$expectedClassFilesArray = array(
			'Acme\MyPackage\Controller\FooController' => 'Classes/Controller/FooController.php',
			'Acme\MyPackage\Domain\Model\Foo' => 'Classes/Domain/Model/Foo.php',
			'Acme\MyPackage\Domain\Model\Bar' => 'Classes/Domain/Model/Bar.php',
		);

		$package = new Package('Acme.MyPackage', $packagePath);
		$actualClassFilesArray = $package->getClassFiles();

		$this->assertEquals($expectedClassFilesArray, $actualClassFilesArray);
	}
}
?>