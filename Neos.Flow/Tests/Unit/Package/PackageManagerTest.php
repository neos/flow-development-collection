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
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\InvalidPackageKeyException;
use Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageFactory;
use Neos\Flow\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends UnitTestCase
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var Bootstrap|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockBootstrap;

    /**
     * @var ApplicationContext|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockApplicationContext;

    /**
     * @var Dispatcher|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockDispatcher;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        ComposerUtility::flushCaches();
        vfsStream::setup('Test');
        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects(self::any())->method('getSignalSlotDispatcher')->will(self::returnValue($this->createMock(Dispatcher::class)));

        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects(self::any())->method('getContext')->will(self::returnValue($this->mockApplicationContext));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockBootstrap->expects(self::any())->method('getObjectManager')->will(self::returnValue($mockObjectManager));
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockObjectManager->expects(self::any())->method('get')->with(ReflectionService::class)->will(self::returnValue($mockReflectionService));

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');

        $this->packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/');

        $composerNameToPackageKeyMap = [
            'neos/flow' => 'Neos.Flow'
        ];

        $this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->packageManager, 'dispatcher', $this->mockDispatcher);

        $this->packageManager->initialize($this->mockBootstrap);
    }

    /**
     * @test
     */
    public function getPackageReturnsTheSpecifiedPackage()
    {
        $this->packageManager->createPackage('Some.Test.Package', [], 'vfs://Test/Packages/Application');

        $package = $this->packageManager->getPackage('Some.Test.Package');
        self::assertInstanceOf(PackageInterface::class, $package, 'The result of getPackage() was no valid package object.');
    }

    /**
     * @test
     */
    public function getPackageThrowsExceptionOnUnknownPackage()
    {
        $this->expectException(UnknownPackageException::class);
        $this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * Creates a dummy class file inside $package's path
     * and requires it for propagation
     *
     * @param PackageInterface $package
     * @return object The dummy object of the class which was created
     */
    protected function createDummyObjectForPackage(PackageInterface $package)
    {
        $namespaces = $package->getNamespaces();
        $dummyClassName = 'Someclass' . md5(uniqid(mt_rand(), true));

        $fullyQualifiedClassName = '\\' . reset($namespaces) . '\\' . $dummyClassName;

        $dummyClassFilePath = Files::concatenatePaths([
            $package->getPackagePath(),
            FlowPackageInterface::DIRECTORY_CLASSES,
            $dummyClassName . '.php'
        ]);
        file_put_contents($dummyClassFilePath, '<?php namespace ' . reset($namespaces) . '; class ' . $dummyClassName . ' {}');
        require $dummyClassFilePath;
        return new $fullyQualifiedClassName();
    }

    /**
     * @test
     */
    public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered()
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy'], ['', '']);
        $packageManager->_set('packageKeys', ['acme.testpackage' => 'Acme.TestPackage']);
        self::assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds()
    {
        $expectedPackageKeys = [
            'Neos.Flow' . md5(uniqid(mt_rand(), true)),
            'Neos.Flow.Test' . md5(uniqid(mt_rand(), true)),
            'Neos.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true))
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['emitPackageStatesUpdated'], ['vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/']);

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $packageManager->rescanPackages();

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $actualPackageKeys = array_keys($packageStates['packages']);
        self::assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
    }

    /**
     * @test
     */
    public function packageStatesConfigurationContainsRelativePaths()
    {
        $packageKeys = [
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true)),
            'Neos.Flow' . md5(uniqid(mt_rand(), true)),
            'Neos.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
        ];

        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            ComposerUtility::writeComposerManifest($packagePath, $packageKey, ['type' => 'flow-test', 'autoload' => []]);
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['updateShortcuts', 'emitPackageStatesUpdated'], [], '', false);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageInformationCacheFilePath', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $actualPackageStatesConfiguration = $packageManager->rescanPackages();

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $composerName = ComposerUtility::getComposerPackageNameFromPackageKey($packageKey);
            $expectedPackageStatesConfiguration[$composerName] = [
                'packagePath' => 'Application/' . $packageKey . '/',
                'composerName' => $composerName,
                'packageClassInformation' => ['className' => 'Neos\Flow\Package\GenericPackage', 'pathAndFilename' => ''],
                'packageKey' => $packageKey,
                'autoloadConfiguration' => []
            ];
        }

        self::assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
    }

    /**
     * Data Provider returning valid package keys and the corresponding path
     *
     * @return array
     */
    public function packageKeysAndPaths()
    {
        return [
            ['Neos.YetAnotherTestPackage', 'vfs://Test/Packages/Application/Neos.YetAnotherTestPackage/'],
            ['RobertLemke.Flow.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.Flow.NothingElse/']
        ];
    }

    /**
     * @test
     * @dataProvider packageKeysAndPaths
     */
    public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath)
    {
        $actualPackage = $this->packageManager->createPackage($packageKey, [], 'vfs://Test/Packages/Application');
        $actualPackagePath = $actualPackage->getPackagePath();

        self::assertEquals($expectedPackagePath, $actualPackagePath);
        self::assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
        self::assertEquals($packageKey, $actualPackage->getPackageKey());
        self::assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    /**
     * @test
     */
    public function createPackageWritesAComposerManifestUsingTheGivenMetaObject()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [
            'name' => 'acme/yetanothertestpackage',
            'type' => 'neos-package',
            'description' => 'Yet Another Test Package',
            'autoload' => [
                'psr-0' => [
                    'Acme\\YetAnotherTestPackage' => 'Classes/'
                ]
            ]
        ], 'vfs://Test/Packages/Application');

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        self::assertEquals('acme/yetanothertestpackage', $composerManifest->name);
        self::assertEquals('Yet Another Test Package', $composerManifest->description);
    }

    /**
     * @test
     */
    public function createPackageCanChangePackageTypeInComposerManifest()
    {
        $metaData = [
            'name' => 'acme/yetanothertestpackage2',
            'type' => 'flow-custom-package',
            'description' => 'Yet Another Test Package',
            'autoload' => [
                'psr-0' => [
                    'Acme\\YetAnotherTestPackage2' => 'Classes/'
                ]
            ]
        ];

        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', $metaData, 'vfs://Test/Packages/Application');

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        self::assertEquals('flow-custom-package', $composerManifest->type);
    }


    /**
     * @test
     */
    public function createPackageAlwaysSetsThePackageType()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', [], 'vfs://Test/Packages/Application');

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        self::assertEquals('neos-package', $composerManifest->type);
    }

    /**
     * Checks if createPackage() creates the folders for classes, configuration, documentation, resources and tests.
     *
     * @test
     */
    public function createPackageCreatesCommonFolders()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        $packagePath = $package->getPackagePath();

        self::assertTrue(is_dir($packagePath . FlowPackageInterface::DIRECTORY_CLASSES), 'Classes directory was not created');
        self::assertTrue(is_dir($packagePath . FlowPackageInterface::DIRECTORY_CONFIGURATION), 'Configuration directory was not created');
        self::assertTrue(is_dir($packagePath . FlowPackageInterface::DIRECTORY_RESOURCES), 'Resources directory was not created');
        self::assertTrue(is_dir($packagePath . FlowPackageInterface::DIRECTORY_TESTS_UNIT), 'Tests/Unit directory was not created');
        self::assertTrue(is_dir($packagePath . FlowPackageInterface::DIRECTORY_TESTS_FUNCTIONAL), 'Tests/Functional directory was not created');
    }

    /**
     * Makes sure that an exception is thrown and no directory is created on passing invalid package keys.
     *
     * @test
     */
    public function createPackageThrowsExceptionOnInvalidPackageKey()
    {
        try {
            $this->packageManager->createPackage('Invalid_PackageKey', [], 'vfs://Test/Packages/Application');
        } catch (InvalidPackageKeyException $exception) {
        }
        self::assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
    }

    /**
     * Makes sure that duplicate package keys are detected.
     *
     * @test
     */
    public function createPackageThrowsExceptionForExistingPackageKey()
    {
        $this->expectException(PackageKeyAlreadyExistsException::class);
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
    }

    /**
     * @test
     */
    public function createPackageMakesTheNewlyCreatedPackageAvailable()
    {
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        self::assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @return array
     */
    public function composerNamesAndPackageKeys()
    {
        return [
            ['imagine/Imagine', 'imagine.Imagine'],
            ['imagine/imagine', 'imagine.Imagine'],
            ['neos/flow', 'Neos.Flow'],
            ['Neos/Flow', 'Neos.Flow']
        ];
    }

    /**
     * @test
     * @dataProvider composerNamesAndPackageKeys
     */
    public function getPackageKeyFromComposerNameIgnoresCaseDifferences($composerName, $packageKey)
    {
        $packageStatesConfiguration = [
            'packages' => [
                'neos/flow' => [
                    'packageKey' => 'Neos.Flow',
                    'composerName' => 'neos/flow'
                ],
                'imagine/imagine' => [
                    'packageKey' => 'imagine.Imagine',
                    'composerName' => 'imagine/imagine'
                ]
            ]
        ];

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies'], ['', '']);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

        self::assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }

    /**
     * @test
     */
    public function registeringTheSamePackageKeyWithDifferentCaseShouldThrowException()
    {
        $this->expectException(PackageKeyAlreadyExistsException::class);
        $this->packageManager->createPackage('doctrine.instantiator', [], 'vfs://Test/Packages/Application');
        $this->packageManager->createPackage('doctrine.Instantiator', [], 'vfs://Test/Packages/Application');
    }

    /**
     * @test
     */
    public function createPackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockDispatcher->expects(self::once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->createPackage('Some.Package', [], 'vfs://Test/Packages/Application');
    }

    /**
     * @test
     */
    public function freezePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(true));

        $this->packageManager->createPackage('Some.Package', [
            'name' => 'some/package'
        ], 'vfs://Test/Packages/Application');

        $this->mockDispatcher->expects(self::once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->freezePackage('Some.Package');
    }

    /**
     * @test
     */
    public function unfreezePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockApplicationContext->expects(self::atLeastOnce())->method('isDevelopment')->will(self::returnValue(true));

        $this->packageManager->createPackage('Some.Package', [
            'name' => 'some/package',
            'type' => 'neos-package'
        ], 'vfs://Test/Packages/Application');
        $this->packageManager->freezePackage('Some.Package');

        $this->mockDispatcher->expects(self::once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->unfreezePackage('Some.Package');
    }
}
