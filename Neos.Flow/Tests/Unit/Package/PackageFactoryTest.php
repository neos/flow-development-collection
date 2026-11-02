<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Package;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Composer\ComposerUtility;
use Neos\Flow\Package\Exception\CorruptPackageException;
use Neos\Flow\Package\Exception\InvalidPackagePathException;
use Neos\Flow\Package\FlowPackageKey;
use Neos\Flow\Package\Package;
use Neos\Flow\Package\PackageFactory;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the package factory
 */
final class PackageFactoryTest extends UnitTestCase
{
    /**
     * @var PackageFactory
     */
    protected $packageFactory;

    /**
     */
    protected function setUp(): void
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Packages');

        $this->packageFactory = new PackageFactory();
    }

    #[Test]
    public function createThrowsExceptionWhenSpecifyingANonExistingPackagePath()
    {
        $this->expectException(InvalidPackagePathException::class);
        $this->packageFactory->create('vfs://Packages/', 'Some/Non/Existing/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package');
    }

    #[Test]
    public function createThrowsExceptionIfCustomPackageFileCantBeAnalyzed()
    {
        $this->expectException(CorruptPackageException::class);
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php // no class');

        $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package');
    }

    #[Test]
    public function createThrowsExceptionIfCustomPackageDoesNotImplementPackageInterface()
    {
        $this->expectException(CorruptPackageException::class);
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php namespace Neos\\Flow\\Fixtures { class CustomPackage1 {}}');

        require($packageFilePath);

        $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package');
    }

    #[Test]
    public function createReturnsInstanceOfCustomPackageIfItExists()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Some/Package/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test", "autoload": { "psr-0": { "Foo": "bar" }}}');
        file_put_contents($packageFilePath, '<?php namespace Neos\\Flow\\Fixtures { class CustomPackage2 extends \\Neos\\Flow\\Package\\Package {}}');

        require($packageFilePath);

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package');
        self::assertInstanceOf('Neos\Flow\Fixtures\CustomPackage2', $package);
    }

    #[Test]
    public function createTakesAutoloaderTypeIntoAccountWhenLoadingCustomPackage()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        $packageFilePath = $packagePath . 'Classes/Package.php';
        mkdir(dirname($packageFilePath), 0777, true);
        $rawComposerManifest = '{"name": "some/package", "type": "neos-test", "autoload": { "psr-4": { "Foo": "bar" }}}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);
        file_put_contents($packageFilePath, '<?php namespace Neos\\Flow\\Fixtures { class CustomPackage3 extends \\Neos\\Flow\\Package\\Package {}}');

        require($packageFilePath);

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package', $composerManifest['autoload']);
        self::assertInstanceOf('Neos\Flow\Fixtures\CustomPackage3', $package);
    }

    #[Test]
    public function createReturnsAnInstanceOfTheDefaultPackageIfNoCustomPackageExists()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "some/package", "type": "neos-test"}');

        $package = $this->packageFactory->create('vfs://Packages/', 'Some/Path/Some.Package/', FlowPackageKey::fromString('Some.Package'), 'some/package');
        self::assertInstanceOf(Package::class, $package);
    }
}
