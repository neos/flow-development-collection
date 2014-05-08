<?php
namespace TYPO3\Flow\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\Package;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the package class
 *
 */
class PackageTest extends UnitTestCase {

	/**
	 * @var PackageManager
	 */
	protected $mockPackageManager;

	/**
	 */
	public function setUp() {
		vfsStream::setup('Packages');
		$this->mockPackageManager = $this->getMockBuilder('TYPO3\Flow\Package\PackageManager')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
	 */
	public function constructorThrowsInvalidPackageKeyExceptionIfTheSpecifiedPackageKeyIsNotValid() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage';
		mkdir($packagePath, 0777, TRUE);
		new Package($this->mockPackageManager, 'InvalidPackageKey', $packagePath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
	 */
	public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedPackagePathDoesNotExist() {
		new Package($this->mockPackageManager, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
	 */
	public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedPackagePathDoesHaveATrailingSlash() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage';
		mkdir($packagePath, 0777, TRUE);
		new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
	 */
	public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedClassesPathHasALeadingSlash() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath, 0777, TRUE);
		new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, '/tmp');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageManifestException
	 */
	public function constructorThrowsInvalidPackageManifestExceptionIfNoComposerManifestWasFound() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath, 0777, TRUE);
		new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath);
	}

	/**
	 * @test
	 */
	public function constructorSetsTheClassesPathAsSpecifiedIfNoPsrMappingExists() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test"}');

		$package = new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Some/Classes/Path');
		$this->assertSame('vfs://Packages/Vendor.TestPackage/Some/Classes/Path/', $package->getClassesPath());
	}

	/**
	 * @test
	 */
	public function constructorSetsTheClassesPathAccordingToThePsr0MappingIfItExists() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-0": { "Psr0Namespace": "Psr0/Path" }, "psr-4": { "Psr4Namespace": "Psr4/Path" } }}');

		$package = new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Some/Classes/Path');
		$this->assertSame('vfs://Packages/Vendor.TestPackage/Psr0/Path/', $package->getClassesPath());
	}

	/**
	 * @test
	 */
	public function constructorSetsTheClassesPathAccordingToThePsr4MappingIfItExists() {
		$packagePath = 'vfs://Packages/Vendor.TestPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-4": { "Psr4Namespace": "Psr4/Path" } }}');

		$package = new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Some/Classes/Path');
		$this->assertSame('vfs://Packages/Vendor.TestPackage/Psr4/Path/', $package->getClassesPath());
	}

	/**
	 */
	public function validPackageKeys() {
		return array(
			array('Doctrine.DBAL'),
			array('TYPO3.Flow'),
			array('RobertLemke.Flow.Twitter'),
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
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');

		$package = new Package($this->mockPackageManager, $packageKey, $packagePath);
		$this->assertEquals($packageKey, $package->getPackageKey());
	}

	/**
	 */
	public function invalidPackageKeys() {
		return array(
			array('TYPO3..Flow'),
			array('RobertLemke.Flow. Twitter'),
			array('Schalke*4')
		);
	}

	/**
	 * @test
	 * @dataProvider invalidPackageKeys
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
	 */
	public function constructRejectsInvalidPackageKeys($packageKey) {
		$packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
		mkdir($packagePath, 0777, TRUE);
		new Package($this->mockPackageManager, $packageKey, $packagePath);
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsThePsr0NamespaceIfAPsr0MappingIsDefined() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path1" }, "psr-4": { "Namespace2": "path2" } }}');
		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Namespace1', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsTheFirstPsr0NamespaceIfMultiplePsr0MappingsAreDefined() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path2", "Namespace2": "path2" } }}');
		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Namespace1', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsPsr4NamespaceIfNoPsr0MappingIsDefined() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2" } }}');
		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Namespace2', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsTheFirstPsr4NamespaceIfMultiplePsr4MappingsAreDefined() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2", "Namespace3": "path3" } }}');
		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Namespace2', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getNamespaceReturnsThePhpNamespaceCorrespondingToThePackageKeyIfNoPsrMappingIsDefined() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');
		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
		$this->assertEquals('Acme\\MyPackage', $package->getNamespace());
	}

	/**
	 * @test
	 */
	public function getMetaPathReturnsPathToMetaDirectory() {
		$package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW);
		$packageMetaDataPath = $package->getMetaPath();
		$this->assertSame($package->getPackagePath() . Package::DIRECTORY_METADATA, $packageMetaDataPath);
	}

	/**
	 * @test
	 */
	public function getDocumentationPathReturnsPathToDocumentationDirectory() {
		$package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW);
		$packageDocumentationPath = $package->getDocumentationPath();

		$this->assertEquals($package->getPackagePath() . Package::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
	}

	/**
	 * @test
	 */
	public function getClassesPathReturnsPathToClasses() {
		$package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW, Package::DIRECTORY_CLASSES);
		$packageClassesPath = $package->getClassesPath();
		$expected = $package->getPackagePath() . Package::DIRECTORY_CLASSES;
		$this->assertEquals($expected, $packageClassesPath);
	}

	/**
	 * @test
	 */
	public function getClassesPathReturnsNormalizedPathToClasses() {
		$packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath, 'no/trailing/slash');

		$packageClassesPath = $package->getClassesPath();
		$expected = $package->getPackagePath() . 'no/trailing/slash/';

		$this->assertEquals($expected, $packageClassesPath);
	}

	/**
	 * @test
	 */
	public function getPackageDocumentationsReturnsEmptyArrayIfDocumentationDirectoryDoesntExist() {
		vfsStream::setup('testDirectory');

		$packagePath = vfsStream::url('testDirectory') . '/';
		file_put_contents($packagePath . 'composer.json', '{"name": "typo3/flow", "type": "flow-test"}');

		$package = new Package($this->mockPackageManager, 'TYPO3.Flow', $packagePath);
		$documentations = $package->getPackageDocumentations();

		$this->assertEquals(array(), $documentations);
	}

	/**
	 * @test
	 */
	public function aPackageCanBeFlaggedAsProtected() {
		$packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
		mkdir($packagePath, 0700, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
		$package = new Package($this->mockPackageManager, 'Vendor.Dummy', $packagePath);

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
		file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
		$package = new Package($this->mockPackageManager, 'Vendor.Dummy', $packagePath);

		$this->assertTrue($package->isObjectManagementEnabled());
	}

	/**
	 * @test
	 */
	public function getClassFilesReturnsAListOfClassFilesOfThePackage() {
		$packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

		mkdir($packagePath . 'Classes/Acme/MyPackage/Controller', 0770, TRUE);
		mkdir($packagePath . 'Classes/Acme/MyPackage/Domain/Model', 0770, TRUE);

		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Controller/FooController.php', '');
		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Foo.php', '');
		file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php', '');

		$expectedClassFilesArray = array(
			'Acme\MyPackage\Controller\FooController' => 'Classes/Acme/MyPackage/Controller/FooController.php',
			'Acme\MyPackage\Domain\Model\Foo' => 'Classes/Acme/MyPackage/Domain/Model/Foo.php',
			'Acme\MyPackage\Domain\Model\Bar' => 'Classes/Acme/MyPackage/Domain/Model/Bar.php',
		);

		$package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath, 'Classes');
		$actualClassFilesArray = $package->getClassFiles();

		$this->assertEquals($expectedClassFilesArray, $actualClassFilesArray);
	}
}
