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

use TYPO3\Flow\Package\Package;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

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
        vfsStream::setup('Packages');
        $this->mockPackageManager = $this->getMockBuilder(\TYPO3\Flow\Package\PackageManager::class)->disableOriginalConstructor()->getMock();
        ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', array(), true);
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathAccordingToThePsr0MappingIfItExists()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-0": { "Psr0Namespace": "Psr0/Path" }, "psr-4": { "Psr4Namespace": "Psr4/Path" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        $package = new Package('Vendor.TestPackage', 'vendor/testpackage', $packagePath, $composerManifest['autoload']);
        $this->assertSame('vfs://Packages/Vendor.TestPackage/Psr0/Path', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathAccordingToThePsr4MappingIfItExists()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-4": { "Psr4Namespace": "Psr4/Path" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        $package = new Package('Vendor.TestPackage', 'vendor/testpackage', $packagePath, $composerManifest['autoload']);
        $this->assertSame('vfs://Packages/Vendor.TestPackage/Psr4/Path', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathToFirstConfiguredPsr0Mapping()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-0": { "Psr0Namespace": ["Psr0/Path", "Psr0/Foo"] } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        $package = new Package('Vendor.TestPackage', 'vendor/testpackage', $packagePath, $composerManifest['autoload']);
        $this->assertSame('vfs://Packages/Vendor.TestPackage/Psr0/Path', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsThePsr0NamespaceIfAPsr0MappingIsDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path1" }, "psr-4": { "Namespace2": "path2" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, $composerManifest['autoload']);
        $this->assertEquals('Namespace1', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsTheFirstPsr0NamespaceIfMultiplePsr0MappingsAreDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage4123/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage4123", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path2", "Namespace2": "path2" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);
        $package = new Package('Acme.MyPackage4123', 'acme/mypackage4123', $packagePath, $composerManifest['autoload']);
        $this->assertEquals('Namespace1', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsPsr4NamespaceIfNoPsr0MappingIsDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage3412/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage3412", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);
        $package = new Package('Acme.MyPackage3412', 'acme/mypackage3412', $packagePath, $composerManifest['autoload']);
        $this->assertEquals('Namespace2', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsTheFirstPsr4NamespaceIfMultiplePsr4MappingsAreDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage2341/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage2341", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2", "Namespace3": "path3" } }}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);
        $package = new Package('Acme.MyPackage2341', 'acme/mypackage2341', $packagePath, $composerManifest['autoload']);
        $this->assertEquals('Namespace2', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getMetaPathReturnsPathToMetaDirectory()
    {
        $package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW);
        $packageMetaDataPath = $package->getMetaPath();
        $this->assertSame($package->getPackagePath() . Package::DIRECTORY_METADATA, $packageMetaDataPath);
    }

    /**
     * @test
     */
    public function getDocumentationPathReturnsPathToDocumentationDirectory()
    {
        $package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW);
        $packageDocumentationPath = $package->getDocumentationPath();

        $this->assertEquals($package->getPackagePath() . Package::DIRECTORY_DOCUMENTATION, $packageDocumentationPath);
    }

    /**
     * @test
     */
    public function getClassesPathReturnsPathToClasses()
    {
        $package = new Package('TYPO3.Flow', 'typo3/flow', FLOW_PATH_FLOW, ['psr-0' => ['TYPO3\\Flow' => 'Classes/']]);
        $packageClassesPath = $package->getClassesPath();
        $expected = $package->getPackagePath() . Package::DIRECTORY_CLASSES;
        $this->assertEquals($expected, $packageClassesPath);
    }

    /**
     * @test
     */
    public function getClassesPathReturnsNormalizedPathToClasses()
    {
        $packagePath = 'vfs://Packages/Application/Acme/MyPackage/';
        mkdir($packagePath, 0777, true);
        $rawComposerManifest = '{"name": "acme/mypackage", "type": "flow-test", "autoload": {"psr-0": {"Acme\\\\MyPackage": "no/trailing/slash/"}}}';
        $composerManifest = json_decode($rawComposerManifest, true);
        file_put_contents($packagePath . 'composer.json', $rawComposerManifest);

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, $composerManifest['autoload']);

        $packageClassesPath = $package->getClassesPath();
        $expected = $package->getPackagePath() . 'no/trailing/slash/';

        $this->assertEquals($expected, $packageClassesPath);
    }

    /**
     * @test
     */
    public function getPackageDocumentationsReturnsEmptyArrayIfDocumentationDirectoryDoesntExist()
    {
        vfsStream::setup('testDirectory');

        $packagePath = vfsStream::url('testDirectory') . '/';
        file_put_contents($packagePath . 'composer.json', '{"name": "typo3/flow", "type": "flow-test"}');

        $package = new Package('TYPO3.Flow', 'typo3/flow', $packagePath);
        $documentations = $package->getPackageDocumentations();

        $this->assertEquals(array(), $documentations);
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
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "typo3-flow-test"}');
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
    public function packageMetaDataContainsPackageType()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        $package = new Package('Acme.MyPackage', 'acme/mypackage', $packagePath, ['psr-0' => ['acme\\MyPackage' => 'Classes/']]);

        $metaData = $package->getPackageMetaData();
        $this->assertEquals('flow-test', $metaData->getPackageType());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Composer\Exception\MissingPackageManifestException
     */
    public function throwExceptionWhenSpecifyingAPathWithMissingComposerManifest()
    {
        $packagePath = 'vfs://Packages/Some/Path/Some.Package/';
        mkdir($packagePath, 0777, true);
        $package = new Package('Some.Package', 'some/package', 'vfs://Packages/Some/Path/Some.Package/', []);
        $package->getComposerManifest();
    }
}
