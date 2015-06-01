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

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageFactory;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the package factory
 */
class PackageFactoryTest extends UnitTestCase {

	/**
	 * @var PackageFactory
	 */
	protected $packageFactory;

	/**
	 * @var PackageManager|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPackageManager;

	/**
	 */
	public function setUp() {
		vfsStream::setup('Packages');
		$this->mockPackageManager = $this->getMockBuilder('TYPO3\Flow\Package\PackageManager')->disableOriginalConstructor()->getMock();
		ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', array(), TRUE);

		$this->packageFactory = new PackageFactory($this->mockPackageManager);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
	 */
	public function createThrowsExceptionWhenSpecifyingANonExistingPackagePath() {
		$this->packageFactory->create('vfs://Packages/', 'Some/Non/Existing/Path/Some.Package/', 'Some.Package');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
	 */
	public function createThrowsExceptionWhenSpecifyingAnInvalidPackageKey() {
		$this->packageFactory->create('vfs://Packages/', 'Some/Path/InvalidPackageKey/', 'InvalidPackageKey');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageManifestException
	 */
	public function createThrowsExceptionWhenSpecifyingAPathWithMissingComposerManifest() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		mkdir($packagePath, 0777, TRUE);
		$this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	public function createThrowsExceptionIfCustomPackageFileCantBeAnalyzed() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		$packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
		mkdir(dirname($packageFilePath), 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "flow-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
		file_put_contents($packageFilePath, '<?php // no class');

		$this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\CorruptPackageException
	 */
	public function createThrowsExceptionIfCustomPackageDoesNotImplementPackageInterface() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		$packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
		mkdir(dirname($packageFilePath), 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "flow-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
		file_put_contents($packageFilePath, '<?php namespace TYPO3\\Flow\\Fixtures { class CustomPackage1 {}}');

		require($packageFilePath);

		$this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
	}

	/**
	 * @test
	 */
	public function createReturnsInstanceOfCustomPackageIfItExists() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		$packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
		mkdir(dirname($packageFilePath), 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "flow-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
		file_put_contents($packageFilePath, '<?php namespace TYPO3\\Flow\\Fixtures { class CustomPackage2 extends \\TYPO3\\Flow\\Package\\Package {}}');

		require($packageFilePath);

		$package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
		$this->assertSame('TYPO3\Flow\Fixtures\CustomPackage2', get_class($package));
	}

	/**
	 * @test
	 */
	public function createTakesAutoloaderTypeIntoAccountWhenLoadingCustomPackage() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		$packageFilePath = $packagePath . 'Classes/Package.php';
		mkdir(dirname($packageFilePath), 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "flow-test", "autoload": { "psr-4": { "Foo": "bar" }}}');
		file_put_contents($packageFilePath, '<?php namespace TYPO3\\Flow\\Fixtures { class CustomPackage3 extends \\TYPO3\\Flow\\Package\\Package {}}');

		require($packageFilePath);

		$package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
		$this->assertSame('TYPO3\Flow\Fixtures\CustomPackage3', get_class($package));
	}

	/**
	 * @test
	 */
	public function createReturnsAnInstanceOfTheDefaultPackageIfNoCustomPackageExists() {
		$packagePath = 'vfs://Packages/Some/Path/Some.Package/';
		mkdir($packagePath, 0777, TRUE);
		file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "flow-test"}');

		$package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package');
		$this->assertSame('TYPO3\Flow\Package\Package', get_class($package));
	}
}
