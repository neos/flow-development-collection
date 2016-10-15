<?php
namespace TYPO3\Flow\Tests\Unit\Package;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Composer\ComposerUtility;
use TYPO3\Flow\Package\Package;
use TYPO3\Flow\Package\PackageFactory;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the package factory
 */
class PackageFactoryTest extends UnitTestCase
{
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
    public function setUp()
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(PackageManager::class)->disableOriginalConstructor()->getMock();
        ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', [], true);

        $this->packageFactory = new PackageFactory($this->mockPackageManager);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
     */
    public function createThrowsExceptionWhenSpecifyingANonExistingPackagePath()
    {
        $this->packageFactory->create('vfs://Packages/', 'Some/Non/Existing/Path/Some.Package/', 'Some.Package', 'some/package');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\CorruptPackageException
     */
    public function createThrowsExceptionIfCustomPackageFileCantBeAnalyzed()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php // no class');

        $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package', 'some/package');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\CorruptPackageException
     */
    public function createThrowsExceptionIfCustomPackageDoesNotImplementPackageInterface()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php namespace TYPO3\\Flow\\Fixtures { class CustomPackage1 {}}');

        require($packageFilePath);

        $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package', 'some/package');
    }

    /**
     * @test
     */
    public function createReturnsInstanceOfCustomPackageIfItExists()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php namespace Neos\\Flow\\Fixtures { class CustomPackage2 extends \\TYPO3\\Flow\\Package\\Package {}}');

        require($packageFilePath);

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package', 'some/package');
        $this->assertSame('Neos\Flow\Fixtures\CustomPackage2', get_class($package));
    }

    /**
     * @test
     */
    public function createTakesAutoloaderTypeIntoAccountWhenLoadingCustomPackage()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        $rawComposerManifest = '{"name": "some/package", "type": "neos-test", "autoload": { "psr-4": { "Foo": "bar" }}}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);
        file_put_contents($packageFilePath, '<?php namespace Neos\\Flow\\Fixtures { class CustomPackage3 extends \\TYPO3\\Flow\\Package\\Package {}}');

        require($packageFilePath);

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package', 'some/package', $composerManifest['autoload']);
        $this->assertSame('Neos\Flow\Fixtures\CustomPackage3', get_class($package));
    }

    /**
     * @test
     */
    public function createReturnsAnInstanceOfTheDefaultPackageIfNoCustomPackageExists()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test"}');

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', 'Some.Package', 'some/package');
        $this->assertSame(Package::class, get_class($package));
    }
}
