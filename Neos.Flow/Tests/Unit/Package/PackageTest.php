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
use Neos\Flow\Composer\Exception\MissingPackageManifestException;
use Neos\Flow\Package\Package;
use Neos\Flow\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the package class
 *
 */
final class PackageTest extends UnitTestCase
{
    /**
     */
    protected function setUp(): void
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Packages');
    }

    #[Test]
    public function getClassFilesReturnsAListOfClassFilesOfThePackage()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage", "type": "flow-test", "autoload": {"psr-0": {"Acme\\\\MyPackage": "Classes/"}}}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        mkdir($packagePath . 'Classes/Acme/MyPackage/Controller', 0770, true);
        mkdir($packagePath . 'Classes/Acme/MyPackage/Domain/Model', 0770, true);

        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Controller/FooController.php', '');
        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Foo.php', '');
        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php', '');

        $expectedClassFilesArray = [
            'Acme\MyPackage\Controller\FooController' => $packagePath .'Classes/Acme/MyPackage/Controller/FooController.php',
            'Acme\MyPackage\Domain\Model\Foo' => $packagePath .'Classes/Acme/MyPackage/Domain/Model/Foo.php',
            'Acme\MyPackage\Domain\Model\Bar' => $packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php',
        ];

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, $composerManifest['autoload']);
        foreach ($package->getClassFiles() as $className => $classPath) {
            self::assertArrayHasKey($className, $expectedClassFilesArray);
            self::assertEquals($expectedClassFilesArray[$className], $classPath);
        }
    }

    #[Test]
    public function packageManifestContainsPackageType()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, ['psr-0' => ['acme\\MyPackage' => 'Classes/']]);

        $packageType = $package->getComposerManifest('type');
        self::assertEquals('flow-test', $packageType);
    }

    #[Test]
    public function throwExceptionWhenSpecifyingAPathWithMissingComposerManifest()
    {
        $this->expectException(MissingPackageManifestException::class);
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        mkdir($packagePath, 0777, true);
        $package = new Package('Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []);
        $package->getComposerManifest();
    }

    #[Test]
    public function getInstalledVersionReturnsFallback()
    {
        /** @var Package|MockObject $package */
        $package = $this->getMockBuilder(Package::class)->onlyMethods(['getComposerManifest'])->setConstructorArgs(['Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []])->getMock();
        $package->method('getComposerManifest')->willReturn('1.2.3');

        self::assertEquals('1.2.3', $package->getInstalledVersion('some/package'));
    }
}
