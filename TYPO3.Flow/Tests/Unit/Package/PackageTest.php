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

use TYPO3\Flow\Package\Exception\InvalidPackageStateException;
use TYPO3\Flow\Package\MetaData\PackageConstraint;
use TYPO3\Flow\Package\MetaDataInterface;
use TYPO3\Flow\Package\Package;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the package class
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
        $this->mockPackageManager = $this->getMockBuilder(PackageManager::class)->disableOriginalConstructor()->getMock();
        ObjectAccess::setProperty($this->mockPackageManager, 'composerManifestData', [], true);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
     */
    public function constructorThrowsInvalidPackageKeyExceptionIfTheSpecifiedPackageKeyIsNotValid()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage';
        mkdir($packagePath, 0777, true);
        new Package($this->mockPackageManager, 'InvalidPackageKey', $packagePath);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
     */
    public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedPackagePathDoesNotExist()
    {
        new Package($this->mockPackageManager, 'Vendor.TestPackage', './ThisPackageSurelyDoesNotExist');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
     */
    public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedPackagePathDoesHaveATrailingSlash()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage';
        mkdir($packagePath, 0777, true);
        new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackagePathException
     */
    public function constructorThrowsInvalidPackagePathExceptionIfTheSpecifiedClassesPathHasALeadingSlash()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, '/tmp');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageManifestException
     */
    public function constructorThrowsInvalidPackageManifestExceptionIfNoComposerManifestWasFound()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath);
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathAsSpecifiedIfNoPsrMappingExists()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test"}');

        $package = new Package($this->mockPackageManager, 'Vendor.TestPackage', $packagePath, 'Some/Classes/Path');
        $this->assertSame('vfs://Packages/Vendor.TestPackage/Some/Classes/Path/', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathAccordingToThePsr0MappingIfItExists()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPsr0Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-0": { "Psr0Namespace": "Psr0/Path" }, "psr-4": { "Psr4Namespace": "Psr4/Path" } }}');

        $package = new Package($this->mockPackageManager, 'Vendor.TestPsr0Package', $packagePath, 'Some/Classes/Path');
        $this->assertSame('vfs://Packages/Vendor.TestPsr0Package/Psr0/Path/', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathAccordingToThePsr4MappingIfItExists()
    {
        $packagePath = 'vfs://Packages/Vendor.TestPsr4Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-4": { "Psr4Namespace": "Psr4/Path" } }}');

        $package = new Package($this->mockPackageManager, 'Vendor.TestPsr4Package', $packagePath, 'Some/Classes/Path');
        $this->assertSame('vfs://Packages/Vendor.TestPsr4Package/Psr4/Path/', $package->getClassesPath());
    }

    /**
     * @test
     */
    public function constructorSetsTheClassesPathToFirstConfiguredPsr0Mapping()
    {
        $packagePath = 'vfs://Packages/Vendor.TestMappedPsr0Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/testpackage", "type": "flow-test", "autoload": { "psr-0": { "Psr0Namespace": ["Psr0/Path", "Psr0/Foo"] } }}');

        $package = new Package($this->mockPackageManager, 'Vendor.TestMappedPsr0Package', $packagePath, 'Some/Classes/Path');
        $this->assertSame('vfs://Packages/Vendor.TestMappedPsr0Package/Psr0/Path/', $package->getClassesPath());
    }

    /**
     */
    public function validPackageKeys()
    {
        return [
            ['Doctrine.DBAL'],
            ['Neos.Flow'],
            ['RobertLemke.Flow.Twitter'],
            ['Sumphonos.Stem'],
            ['Schalke04.Soccer.MagicTrainer']
        ];
    }

    /**
     * @test
     * @dataProvider validPackageKeys
     */
    public function constructAcceptsValidPackageKeys($packageKey)
    {
        $packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');

        $package = new Package($this->mockPackageManager, $packageKey, $packagePath);
        $this->assertEquals($packageKey, $package->getPackageKey());
    }

    /**
     */
    public function invalidPackageKeys()
    {
        return [
            ['Neos..Flow'],
            ['RobertLemke.Flow. Twitter'],
            ['Schalke*4']
        ];
    }

    /**
     * @test
     * @dataProvider invalidPackageKeys
     * @expectedException \TYPO3\Flow\Package\Exception\InvalidPackageKeyException
     */
    public function constructRejectsInvalidPackageKeys($packageKey)
    {
        $packagePath = 'vfs://Packages/' . str_replace('\\', '/', $packageKey) . '/';
        mkdir($packagePath, 0777, true);
        new Package($this->mockPackageManager, $packageKey, $packagePath);
    }

    /**
     * @test
     */
    public function getNamespaceReturnsThePsr0NamespaceIfAPsr0MappingIsDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPsr0Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path1" }, "psr-4": { "Namespace2": "path2" } }}');
        $package = new Package($this->mockPackageManager, 'Acme.MyPsr0Package', $packagePath);
        $this->assertEquals('Namespace1', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsTheFirstPsr0NamespaceIfMultiplePsr0MappingsAreDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyMultiplePsr0Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-0": { "Namespace1": "path2", "Namespace2": "path2" } }}');
        $package = new Package($this->mockPackageManager, 'Acme.MyMultiplePsr0Package', $packagePath);
        $this->assertEquals('Namespace1', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsPsr4NamespaceIfNoPsr0MappingIsDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPsr4Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2" } }}');
        $package = new Package($this->mockPackageManager, 'Acme.MyPsr4Package', $packagePath);
        $this->assertEquals('Namespace2', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsTheFirstPsr4NamespaceIfMultiplePsr4MappingsAreDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyMultiplePsr4Package/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "autoload": { "psr-4": { "Namespace2": "path2", "Namespace3": "path3" } }}');
        $package = new Package($this->mockPackageManager, 'Acme.MyMultiplePsr4Package', $packagePath);
        $this->assertEquals('Namespace2', $package->getNamespace());
    }

    /**
     * @test
     */
    public function getNamespaceReturnsThePhpNamespaceCorrespondingToThePackageKeyIfNoPsrMappingIsDefined()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');
        $package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath);
        $this->assertEquals('Acme\\MyPackage', $package->getNamespace());
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
        $package = new Package($this->mockPackageManager, 'TYPO3.Flow', FLOW_PATH_FLOW, Package::DIRECTORY_CLASSES);
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
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        $package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath, 'no/trailing/slash');

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

        $package = new Package($this->mockPackageManager, 'TYPO3.Flow', $packagePath);
        $documentations = $package->getPackageDocumentations();

        $this->assertEquals([], $documentations);
    }

    /**
     * @test
     */
    public function aPackageCanBeFlaggedAsProtected()
    {
        $packagePath = 'vfs://Packages/Application/Vendor/Dummy/';
        mkdir($packagePath, 0700, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "vendor/dummy", "type": "flow-test"}');
        $package = new Package($this->mockPackageManager, 'Vendor.Dummy', $packagePath);

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
        $package = new Package($this->mockPackageManager, 'Vendor.Dummy', $packagePath);

        $this->assertTrue($package->isObjectManagementEnabled());
    }

    /**
     * @test
     */
    public function getClassFilesReturnsAListOfClassFilesOfThePackage()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        mkdir($packagePath . 'Classes/Acme/MyPackage/Controller', 0770, true);
        mkdir($packagePath . 'Classes/Acme/MyPackage/Domain/Model', 0770, true);

        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Controller/FooController.php', '');
        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Foo.php', '');
        file_put_contents($packagePath . 'Classes/Acme/MyPackage/Domain/Model/Bar.php', '');

        $expectedClassFilesArray = [
            'Acme\MyPackage\Controller\FooController' => 'Classes/Acme/MyPackage/Controller/FooController.php',
            'Acme\MyPackage\Domain\Model\Foo' => 'Classes/Acme/MyPackage/Domain/Model/Foo.php',
            'Acme\MyPackage\Domain\Model\Bar' => 'Classes/Acme/MyPackage/Domain/Model/Bar.php',
        ];

        $package = new Package($this->mockPackageManager, 'Acme.MyPackage', $packagePath, 'Classes');
        $actualClassFilesArray = $package->getClassFiles();

        $this->assertEquals($expectedClassFilesArray, $actualClassFilesArray);
    }

    /**
     * @test
     */
    public function packageMetaDataContainsPackageType()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test"}');

        $package = new Package($this->createMock(PackageManager::class), 'Acme.MyPackage', $packagePath, 'Classes');

        $metaData = $package->getPackageMetaData();
        $this->assertEquals('flow-test', $metaData->getPackageType());
    }

    /**
     * @test
     */
    public function getPackageMetaDataAddsRequiredPackagesAsConstraint()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyMetaDataTestPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "require": { "some/other/package": "*" }}');

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)->disableOriginalConstructor()->getMock();
        $mockPackageManager->expects($this->once())->method('getPackageKeyFromComposerName')->with('some/other/package')->will($this->returnValue('Some.Other.Package'));

        $package = new Package($mockPackageManager, 'Acme.MyMetaDataTestPackage', $packagePath, 'Classes');
        $metaData = $package->getPackageMetaData();
        $packageConstraints = $metaData->getConstraintsByType(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS);

        $this->assertCount(1, $packageConstraints);

        $expectedConstraint = new PackageConstraint(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS, 'Some.Other.Package');
        $this->assertEquals($expectedConstraint, $packageConstraints[0]);
    }

    /**
     * @test
     */
    public function getPackageMetaDataIgnoresUnresolvableConstraints()
    {
        $packagePath = 'vfs://Packages/Application/Acme.MyUnresolvableConstraintsTestPackage/';
        mkdir($packagePath, 0777, true);
        file_put_contents($packagePath . 'composer.json', '{"name": "acme/mypackage", "type": "flow-test", "require": { "non/existing/package": "*" }}');

        $mockPackageManager = $this->getMockBuilder(PackageManager::class)->disableOriginalConstructor()->getMock();
        $mockPackageManager->expects($this->once())->method('getPackageKeyFromComposerName')->with('non/existing/package')->will($this->throwException(new InvalidPackageStateException()));

        $package = new Package($mockPackageManager, 'Acme.MyUnresolvableConstraintsTestPackage', $packagePath, 'Classes');
        $metaData = $package->getPackageMetaData();
        $packageConstraints = $metaData->getConstraintsByType(MetaDataInterface::CONSTRAINT_TYPE_DEPENDS);

        $this->assertCount(0, $packageConstraints);
    }
}
