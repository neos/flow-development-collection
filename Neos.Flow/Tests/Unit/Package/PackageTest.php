<?php
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
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageManager;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the package class
 *
 */
class PackageTest extends UnitTestCase
{
    /**
     * @var PackageManager
     */
    protected $mockPackageManager;

    /**
     */
    protected function setUp(): void
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(\Neos\Flow\Package\PackageManager::class)->disableOriginalConstructor()->getMock();
        ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', [], true);
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function packageManifestContainsPackageType()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, ['psr-0' => ['acme\\MyPackage' => 'Classes/']]);

        $packageType = $package->getComposerManifest('type');
        self::assertEquals('flow-test', $packageType);
    }

    /**
     * @test
     */
    public function throwExceptionWhenSpecifyingAPathWithMissingComposerManifest()
    {
        $this->expectException(MissingPackageManifestException::class);
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        mkdir($packagePath, 0777, true);
        $package = new Package('Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []);
        $package->getComposerManifest();
    }

    /**
     * @test
     */
    public function getInstalledVersionReturnsFallback()
    {
        /** @var Package|\PHPUnit\Framework\MockObject\MockObject $package */
        $package = $this->getMockBuilder(\Neos\Flow\Package\Package::class)->setMethods(['getComposerManifest'])->setConstructorArgs(['Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []])->getMock();
        $package->method('getComposerManifest')->willReturn('1.2.3');

        self::assertEquals('1.2.3', $package->getInstalledVersion('some/package'));
    }
}
