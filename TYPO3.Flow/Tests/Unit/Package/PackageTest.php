<?php
namespace TYPO3\FLOW3\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Package\Package;

/**
 * Testcase for the package class
 *
 */
class PackageTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 */
	public function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('Packages'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\InvalidPackagePathException
	 */
	public function constructThrowsPackageDoesNotExistException() {
		new Package('Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
	}

	/**
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
	 */
	public function constructAcceptsValidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir ($packagePath, 0777, TRUE);

		$package = new Package($packageKey, $packagePath);
		$this->assertEquals($packageKey, $package->getPackageKey());
	}

	/**
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
	 * @expectedException \TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException
	 */
	public function constructRejectsInvalidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir ($packagePath, 0777, TRUE);
		new Package($packageKey, $packagePath);
	}

	/**
	 * @test
	 */
	public function getPackageNamespaceReturnsThePhpNamespaceCorrespondingToThePageKey() {
		$packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
		mkdir ($packagePath, 0777, TRUE);
		$package = new Package('Acme.MyPackage', $packagePath);
		$this->assertEquals('Acme\\MyPackage', $package->getPackageNamespace());
	}

	/**
	 * @test
	 */
	public function getMetaPathReturnsPathToMetaDirectory() {
		$package = new Package('TYPO3.FLOW3', FLOW3_PATH_FLOW3);
		$packageMetaDataPath = $package->getMetaPath();
		$this->assertSame($package->getPackagePath() . Package::DIRECTORY_METADATA, $packageMetaDataPath);
	}

	/**
	 * @test
	 */
	public function getDocumentationPathReturnsPathToDocumentationDirectory() {
		$package = new Package('TYPO3.FLOW3', FLOW3_PATH_FLOW3);
		$packageDocumentationPath = $package->getDocumentationPath();

		$this->assertEquals($package->getPackagePath() . Package::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
	}

	/**
	 * @test
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
	 */
	public function isObjectManagementEnabledTellsIfObjectManagementShouldBeEnabledForThePackage() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		$package = new Package('Vendor.Dummy', $packagePath);

		$this->assertTrue($package->isObjectManagementEnabled());
	}

	/**
	 * @test
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