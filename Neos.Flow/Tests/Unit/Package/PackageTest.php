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
    public function setUp()
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(\Neos\Flow\Package\PackageManager::class)->disableOriginalConstructor()->getMock();
        ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', array(), true);
    }

    /**
     * @test
     */
    public function aPackageCanBeFlaggedAsProtected()
    {
        $packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
        $package = new Package('Vendor.Dummy', 'vendor/dummy', $packagePath);

        $this->assertFalse($package->isProtected());
        $package->setProtected(true);
        $this->assertTrue($package->isProtected());
    }

    /**
     * @test
     */
    public function isObjectManagementEnabledTellsIfObjectManagementShouldBeEnabledForPackages()
    {
        $packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "neos-test"}');
        $package = new Package('Vendor.Dummy', 'vendor/dummy', $packagePath);

        $this->assertTrue($package->isObjectManagementEnabled());
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

        $expectedClassFilesArray = array(
            'Acme\MyPackage\Controller\FooController' => $packagePath .'Classes/Acme/MyPackage/Controller/FooController.php',
            'Acme\MyPackage\Domain\Model\Foo' => $packagePath .'Classes/Acme/MyPackage/Domain/Model/Foo.php',
            'Acme\MyPackage\Domain\Model\Bar' => $packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php',
        );

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, $composerManifest['autoload']);
        foreach ($package->getClassFiles() as $className => $classPath) {
            $this->assertArrayHasKey($className, $expectedClassFilesArray);
            $this->assertEquals($expectedClassFilesArray[$className], $classPath);
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
        $this->assertEquals('flow-test', $packageType);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Composer\Exception\MissingPackageManifestException
     */
    public function throwExceptionWhenSpecifyingAPathWithMissingComposerManifest()
    {
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
        /** @var Package|\PHPUnit_Framework_MockObject_MockObject $package */
        $package = $this->getMockBuilder(\Neos\Flow\Package\Package::class)->setMethods(['getComposerManifest'])->setConstructorArgs(['Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []])->getMock();
        $package->method('getComposerManifest')->willReturn('1.2.3');

        $this->assertEquals('1.2.3', $package->getInstalledVersion('some/package'));
    }
}
