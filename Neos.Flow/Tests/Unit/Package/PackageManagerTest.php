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
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\Exception\InvalidPackageKeyException;
use Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException;
use Neos\Flow\Package\Exception\UnknownPackageException;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\FlowPackageKey;
use Neos\Flow\Package\PackageFactory;
use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Utility\Files;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the default package manager
 *
 */
final class PackageManagerTest extends UnitTestCase
{
    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @var Dispatcher|MockObject
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
        $mockBootstrap = $this->createMock(Bootstrap::class);
        $mockBootstrap->method('getSignalSlotDispatcher')->willReturn(($this->createMock(Dispatcher::class)));
        $mockBootstrap->method('getContext')->willReturn(($this->createMock(ApplicationContext::class)));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockBootstrap->method('getObjectManager')->willReturn(($mockObjectManager));
        $mockObjectManager->method('get')->with(ReflectionService::class)->willReturn(($this->createMock(ReflectionService::class)));

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');

        $this->packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/');

        $composerNameToPackageKeyMap = [
            'neos/flow' => 'Neos.Flow'
        ];

        $this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);

        $this->mockDispatcher = $this->createMock(Dispatcher::class);
        $this->inject($this->packageManager, 'dispatcher', $this->mockDispatcher);

        $this->packageManager->initialize($mockBootstrap);
    }

    #[Test]
    public function getPackageReturnsTheSpecifiedPackage()
    {
        $this->packageManager->createPackage('Some.Test.Package', [], 'vfs://Test/Packages/Application');

        $package = $this->packageManager->getPackage('Some.Test.Package');
        self::assertInstanceOf(PackageInterface::class, $package, 'The result of getPackage() was no valid package object.');
    }

    #[Test]
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
        $dummyClassName = 'Someclass' . md5(uniqid((string)mt_rand(), true));

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

    #[Test]
    public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered()
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, [], ['', '']);
        $packageManager->_set('packageKeys', ['acme.testpackage' => 'Acme.TestPackage']);
        self::assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
    }

    #[Test]
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds()
    {
        $expectedPackageKeys = [
            'Neos.Flow' . md5(uniqid((string)mt_rand(), true)),
            'Neos.Flow.Test' . md5(uniqid((string)mt_rand(), true)),
            'Neos.YetAnotherTestPackage' . md5(uniqid((string)mt_rand(), true)),
            'RobertLemke.Flow.NothingElse' . md5(uniqid((string)mt_rand(), true))
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        }

        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['emitPackageStatesUpdated'])
            ->setConstructorArgs(['vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/'])
            ->getMock();

        // TODO is currently not required - as the packageFactory is instantiated in the constructor
        $this->inject($packageManager, 'packageFactory', new PackageFactory());
        $this->inject($packageManager, 'packages', []);

        $packageManager->rescanPackages();

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $actualPackageKeys = array_keys($packageStates['packages']);
        self::assertSame($expectedPackageKeys, $actualPackageKeys);
    }

    #[Test]
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRespectsPackageCollectionsAndRegistersPackagesItFinds()
    {
        $expectedPackageKeys = [
            'neos/flow-test',
            'neos/flow',
            'neos/yetanothertestpackage'
        ];

        $packages = [
            'Packages' => [
                'Application' => [
                    'Neos.Flow.Test' => [
                        'composer.json' => '{"name": "neos/flow-test", "type": "flow-test"}'
                    ],
                    'FrameworkCollection' => [
                        'composer.json' => '{"name": "neos/flow-development-collection", "type": "neos-package-collection"}',
                        'Neos.Flow' => [
                            'composer.json' => '{"name": "neos/flow", "type": "neos-package"}'
                        ],
                        'Neos.YetAnotherTestPackage' => [
                            'composer.json' => '{"name": "neos/yetanothertestpackage", "type": "neos-package"}'
                        ]
                    ]
                ]
            ]
        ];

        vfsStream::setup('Test', 0770, $packages);

        $packageManager = $this->getMockBuilder(PackageManager::class)
            ->onlyMethods(['emitPackageStatesUpdated'])
            ->setConstructorArgs(['vfs://Test/Configuration/PackageStates.php', 'vfs://Test/Packages/'])
            ->getMock();

        // TODO is currently not required - as the packageFactory is instantiated in the constructor
        $this->inject($packageManager, 'packageFactory', new PackageFactory());
        $this->inject($packageManager, 'packages', []);

        $packageManager->rescanPackages();

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $actualPackageKeys = array_keys($packageStates['packages']);

        self::assertSame($expectedPackageKeys, $actualPackageKeys);
    }

    #[Test]
    public function packageStatesConfigurationContainsRelativePaths()
    {
        /** @var list<FlowPackageKey> $packageKeys */
        $packageKeys = [
            FlowPackageKey::fromString('RobertLemke.Flow.NothingElse' . md5(uniqid((string)mt_rand(), true))),
            FlowPackageKey::fromString('Neos.Flow' . md5(uniqid((string)mt_rand(), true))),
            FlowPackageKey::fromString('Neos.YetAnotherTestPackage' . md5(uniqid((string)mt_rand(), true))),
        ];

        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey->value . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            ComposerUtility::writeComposerManifest($packagePath, $packageKey, ['type' => 'flow-test', 'autoload' => []]);
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, [], [], '', false);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageInformationCacheFilePath', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory();
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $actualPackageStatesConfiguration = $packageManager->rescanPackages();

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $composerName = $packageKey->deriveComposerPackageName();
            $expectedPackageStatesConfiguration[$composerName] = [
                'packagePath' => 'Application/' . $packageKey->value . '/',
                'composerName' => $composerName,
                'packageClassInformation' => ['className' => 'Neos\Flow\Package\GenericPackage', 'pathAndFilename' => ''],
                'packageKey' => $packageKey->value,
                'autoloadConfiguration' => []
            ];
        }

        self::assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
    }

    /**
     * Data Provider returning valid package keys and the corresponding path
     *
     * @return \Iterator<(int | string), mixed>
     */
    public static function packageKeysAndPaths(): \Iterator
    {
        yield ['Neos.YetAnotherTestPackage', 'vfs://Test/Packages/Application/Neos.YetAnotherTestPackage/'];
        yield ['RobertLemke.Flow.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.Flow.NothingElse/'];
    }

    #[DataProvider('packageKeysAndPaths')]
    #[Test]
    public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath)
    {
        $actualPackage = $this->packageManager->createPackage($packageKey, [], 'vfs://Test/Packages/Application');
        $actualPackagePath = $actualPackage->getPackagePath();

        self::assertEquals($expectedPackagePath, $actualPackagePath);
        self::assertDirectoryExists($actualPackagePath, 'Package path should exist after createPackage()');
        self::assertEquals($packageKey, $actualPackage->getPackageKey());
        self::assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    #[Test]
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

    #[Test]
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

    #[Test]
    public function createPackageAlwaysSetsThePackageType()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', [], 'vfs://Test/Packages/Application');

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        self::assertEquals('neos-package', $composerManifest->type);
    }

    /**
     * Checks if createPackage() creates the folders for classes, configuration, documentation, resources and tests.
     */
    #[Test]
    public function createPackageCreatesCommonFolders()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        $packagePath = $package->getPackagePath();

        self::assertDirectoryExists($packagePath . FlowPackageInterface::DIRECTORY_CLASSES, 'Classes directory was not created');
        self::assertDirectoryExists($packagePath . FlowPackageInterface::DIRECTORY_CONFIGURATION, 'Configuration directory was not created');
        self::assertDirectoryExists($packagePath . FlowPackageInterface::DIRECTORY_RESOURCES, 'Resources directory was not created');
        self::assertDirectoryExists($packagePath . FlowPackageInterface::DIRECTORY_TESTS_UNIT, 'Tests/Unit directory was not created');
        self::assertDirectoryExists($packagePath . FlowPackageInterface::DIRECTORY_TESTS_FUNCTIONAL, 'Tests/Functional directory was not created');
    }

    /**
     * Makes sure that an exception is thrown and no directory is created on passing invalid package keys.
     */
    #[Test]
    public function createPackageThrowsExceptionOnInvalidPackageKey()
    {
        try {
            $this->packageManager->createPackage('Invalid_PackageKey', [], 'vfs://Test/Packages/Application');
        } catch (InvalidPackageKeyException $exception) {
        }
        self::assertDirectoryDoesNotExist('vfs://Test/Packages/Application/Invalid_PackageKey', 'Package folder with invalid package key was created');
    }

    /**
     * Makes sure that duplicate package keys are detected.
     */
    #[Test]
    public function createPackageThrowsExceptionForExistingPackageKey()
    {
        $this->expectException(PackageKeyAlreadyExistsException::class);
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
    }

    #[Test]
    public function createPackageMakesTheNewlyCreatedPackageAvailable()
    {
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [], 'vfs://Test/Packages/Application');
        self::assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function composerNamesAndPackageKeys(): \Iterator
    {
        yield ['imagine/Imagine', 'imagine.Imagine'];
        yield ['imagine/imagine', 'imagine.Imagine'];
        yield ['neos/flow', 'Neos.Flow'];
        yield ['Neos/Flow', 'Neos.Flow'];
    }

    #[DataProvider('composerNamesAndPackageKeys')]
    #[Test]
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, [], ['', '']);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

        self::assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }

    #[Test]
    public function registeringTheSamePackageKeyWithDifferentCaseShouldThrowException()
    {
        $this->expectException(PackageKeyAlreadyExistsException::class);
        $this->packageManager->createPackage('doctrine.instantiator', [], 'vfs://Test/Packages/Application');
        $this->packageManager->createPackage('doctrine.Instantiator', [], 'vfs://Test/Packages/Application');
    }

    #[Test]
    public function createPackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->createPackage('Some.Package', [], 'vfs://Test/Packages/Application');
    }
}
