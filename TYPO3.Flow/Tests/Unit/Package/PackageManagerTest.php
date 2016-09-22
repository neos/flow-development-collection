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

use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Core\ClassLoader;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Package\Exception\InvalidPackageKeyException;
use TYPO3\Flow\Package\MetaData;
use TYPO3\Flow\Package\MetaDataInterface;
use TYPO3\Flow\Package\PackageFactory;
use TYPO3\Flow\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\SignalSlot\Dispatcher;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Flow\Utility\Files;

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
     * @var Bootstrap|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockBootstrap;

    /**
     * @var ApplicationContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApplicationContext;

    /**
     * @var Dispatcher|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockDispatcher;

    /**
     * Sets up this test case
     *
     */
    protected function setUp()
    {
        vfsStream::setup('Test');
        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects($this->any())->method('getSignalSlotDispatcher')->will($this->returnValue($this->createMock(Dispatcher::class)));

        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects($this->any())->method('getContext')->will($this->returnValue($this->mockApplicationContext));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockBootstrap->expects($this->any())->method('getObjectManager')->will($this->returnValue($mockObjectManager));
        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassNameByObject')->will($this->returnCallback(function ($object) {
            if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
                return get_parent_class($object);
            }
            return get_class($object);
        }));
        $mockObjectManager->expects($this->any())->method('get')->with(ReflectionService::class)->will($this->returnValue($mockReflectionService));
        $this->packageManager = new PackageManager();

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');

        $mockClassLoader = $this->createMock(ClassLoader::class);

        $composerNameToPackageKeyMap = [
            'neos/flow' => 'Neos.Flow'
        ];

        $this->packageManager->injectClassLoader($mockClassLoader);
        $this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
        $this->inject($this->packageManager, 'packagesBasePath', 'vfs://Test/Packages/');
        $this->inject($this->packageManager, 'packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->packageManager, 'dispatcher', $this->mockDispatcher);

        $this->packageManager->initialize($this->mockBootstrap);
    }

    /**
     * @test
     */
    public function getPackageReturnsTheSpecifiedPackage()
    {
        $this->packageManager->createPackage('Neos.Flow');

        $package = $this->packageManager->getPackage('Neos.Flow');
        $this->assertInstanceOf(PackageInterface::class, $package, 'The result of getPackage() was no valid package object.');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
     */
    public function getPackageThrowsExceptionOnUnknownPackage()
    {
        $this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     */
    public function getPackageOfObjectGetsPackageByGivenObject()
    {
        $package = $this->packageManager->createPackage('Acme.Foobar');
        $dummyObject = $this->createDummyObjectForPackage($package);
        $actual = $this->packageManager->getPackageOfObject($dummyObject);
        $this->assertSame($package, $actual);
    }

    /**
     * @test
     */
    public function getPackageOfObjectAssumesParentClassIfDoctrineProxyClassGiven()
    {
        $package = $this->packageManager->createPackage('Acme.Foobar');
        $dummyObject = $this->createDummyObjectForPackage($package);

        mkdir('vfs://Test/Somewhere/For/DoctrineProxies', 0700, true);
        $dummyProxyClassName = 'Proxy_' . str_replace('\\', '_', get_class($dummyObject));
        $dummyProxyClassPath = 'vfs://Test/Somewhere/For/DoctrineProxies/' . $dummyProxyClassName . '.php';
        file_put_contents($dummyProxyClassPath, '<?php class ' . $dummyProxyClassName . ' extends ' . get_class($dummyObject) . ' implements \Doctrine\ORM\Proxy\Proxy {
            public function __setInitialized($initialized) {}
            public function __setInitializer(Closure $initializer = null) {}
            public function __getInitializer() {}
            public function __setCloner(Closure $cloner = null) {}
            public function __getCloner() {}
            public function __getLazyProperties() {}
            public function __load() {}
            public function __isInitialized() {}
        } ?>');
        require $dummyProxyClassPath;
        $dummyProxy = new $dummyProxyClassName();

        $actual = $this->packageManager->getPackageOfObject($dummyProxy);
        $this->assertSame($package, $actual);
    }

    /**
     * @test
     */
    public function getPackageOfObjectDoesNotGivePackageWithShorterPathPrematurely()
    {
        $package1 = $this->packageManager->createPackage('Acme.Foo');
        $package2 = $this->packageManager->createPackage('Acme.Foobaz');
        $dummy1Object = $this->createDummyObjectForPackage($package1);
        $dummy2Object = $this->createDummyObjectForPackage($package2);
        $this->assertSame($package1, $this->packageManager->getPackageOfObject($dummy1Object));
        $this->assertSame($package2, $this->packageManager->getPackageOfObject($dummy2Object));
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
        $dummyClassName = 'Someclass' . md5(uniqid(mt_rand(), true));
        $fullyQualifiedClassName = '\\' . $package->getNamespace() . '\\' . $dummyClassName;
        $dummyClassFilePath = Files::concatenatePaths([
            $package->getPackagePath(),
            PackageInterface::DIRECTORY_CLASSES,
            $dummyClassName . '.php'
        ]);
        file_put_contents($dummyClassFilePath, '<?php namespace ' . $package->getNamespace() . '; class ' . $dummyClassName . ' {}');
        require $dummyClassFilePath;
        return new $fullyQualifiedClassName();
    }

    /**
     * @test
     */
    public function getPackageOfObjectReturnsNullIfPackageCouldNotBeResolved()
    {
        $this->assertNull($this->packageManager->getPackageOfObject(new \ArrayObject()));
    }

    /**
     * @test
     */
    public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered()
    {
        $packageManager = $this->getAccessibleMock(PackageManager::class, ['dummy']);
        $packageManager->_set('packageKeys', ['acme.testpackage' => 'Acme.TestPackage']);
        $this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['emitPackageStatesUpdated']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $actualPackageKeys = array_keys($packageStates['packages']);
        $this->assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesKeepsExistingPackageConfiguration()
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['emitPackageStatesUpdated']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packageStatesConfiguration', [
            'packages' => [
                $packageKey => [
                    'state' => 'inactive',
                    'frozen' => false,
                    'packagePath' => 'Application/' . $packageKey . '/',
                    'classesPath' => 'Classes/'
                ]
            ],
            'version' => 2
        ]);
        $packageManager->_call('scanAvailablePackages');
        $packageManager->_call('sortAndsavePackageStates');

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $this->assertEquals('inactive', $packageStates['packages'][$packageKey]['state']);
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
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['updateShortcuts', 'emitPackageStatesUpdated'], [], '', false);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $packageManager->_call('scanAvailablePackages');

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $expectedPackageStatesConfiguration[$packageKey] = [
                'state' => 'active',
                'packagePath' => 'Application/' . $packageKey . '/',
                'classesPath' => 'Classes/',
                'manifestPath' => '',
                'composerName' => $packageKey
            ];
        }

        $actualPackageStatesConfiguration = $packageManager->_get('packageStatesConfiguration');
        $this->assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
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
        $actualPackage = $this->packageManager->createPackage($packageKey);
        $actualPackagePath = $actualPackage->getPackagePath();

        $this->assertEquals($expectedPackagePath, $actualPackagePath);
        $this->assertTrue(is_dir($actualPackagePath), 'Package path should exist after createPackage()');
        $this->assertEquals($packageKey, $actualPackage->getPackageKey());
        $this->assertTrue($this->packageManager->isPackageAvailable($packageKey));
    }

    /**
     * @test
     */
    public function createPackageWritesAComposerManifestUsingTheGivenMetaObject()
    {
        $metaData = new MetaData('Acme.YetAnotherTestPackage');
        $metaData->setDescription('Yet Another Test Package');

        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', $metaData);

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        $this->assertEquals('acme/yetanothertestpackage', $composerManifest->name);
        $this->assertEquals('Yet Another Test Package', $composerManifest->description);
    }

    /**
     * @test
     */
    public function createPackageCanChangePackageTypeInComposerManifest()
    {
        $metaData = new MetaData('Acme.YetAnotherTestPackage2');
        $metaData->setDescription('Yet Another Test Package');
        $metaData->setPackageType('flow-custom-package');

        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', $metaData);

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        $this->assertEquals('flow-custom-package', $composerManifest->type);
    }

    /**
     * Checks if createPackage() creates the folders for classes, configuration, documentation, resources and tests.
     *
     * @test
     */
    public function createPackageCreatesCommonFolders()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $packagePath = $package->getPackagePath();

        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CLASSES), "Classes directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), "Configuration directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_DOCUMENTATION), "Documentation directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_RESOURCES), "Resources directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_UNIT), "Tests/Unit directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_FUNCTIONAL), "Tests/Functional directory was not created");
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA), "Metadata directory was not created");
    }

    /**
     * Makes sure that an exception is thrown and no directory is created on passing invalid package keys.
     *
     * @test
     */
    public function createPackageThrowsExceptionOnInvalidPackageKey()
    {
        try {
            $this->packageManager->createPackage('Invalid_PackageKey');
        } catch (InvalidPackageKeyException $exception) {
        }
        $this->assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
    }

    /**
     * Makes sure that duplicate package keys are detected.
     *
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException
     */
    public function createPackageThrowsExceptionForExistingPackageKey()
    {
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     */
    public function createPackageActivatesTheNewlyCreatedPackage()
    {
        $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
    }

    /**
     * @test
     */
    public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage()
    {
        $packageKey = 'Acme.YetAnotherTestPackage';

        $this->packageManager->createPackage($packageKey);

        $this->packageManager->deactivatePackage($packageKey);
        $this->assertFalse($this->packageManager->isPackageActive($packageKey));

        $this->packageManager->activatePackage($packageKey);
        $this->assertTrue($this->packageManager->isPackageActive($packageKey));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException
     */
    public function deactivatePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable()
    {
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException
     */
    public function deletePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     */
    public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $packagePath = $package->getPackagePath();

        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
        $this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        $this->assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));

        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

        $this->assertFalse(is_dir($packagePath . PackageInterface::DIRECTORY_METADATA));
        $this->assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
        $this->assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'));
    }

    /**
     * @test
     * @dataProvider packagesAndDependenciesOrder
     * @param array $packages
     * @param array $expectedPackageOrder
     */
    public function availablePackagesAreSortedAfterTheirDependencies($packages, $expectedPackageOrder)
    {
        $unsortedPackages = [];
        foreach ($packages as $packageKey => $package) {
            $mockPackageConstraints = [];
            foreach ($package['dependencies'] as $dependency) {
                $mockPackageConstraints[] = new MetaData\PackageConstraint('depends', $dependency);
            }
            $mockMetaData = $this->createMock(MetaDataInterface::class);
            $mockMetaData->expects($this->any())->method('getConstraintsByType')->will($this->returnValue($mockPackageConstraints));
            $mockPackage = $this->createMock(PackageInterface::class);
            $mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue($packageKey));
            $mockPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockMetaData));
            $unsortedPackages[$packageKey] = $mockPackage;
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['emitPackageStatesUpdated']);
        $packageManager->_set('packages', $unsortedPackages);
        $packageManager->_set('packageStatesConfiguration', ['packages' => $packages]);
        $packageManager->_call('sortAvailablePackagesByDependencies');

        /*
        // There are many "correct" orders of packages. A order is correct if all dependent
        // packages are ordered before a given package (except for cyclic dependencies).
        // The following can be used to check that order (but due to cyclic dependencies between
        // e.g. Flow and Fluid this can not be asserted by default).
        $newPackageOrder = array_keys($packageManager->_get('packages'));
        foreach ($packages as $packageKey => $package) {
            $packagePosition = array_search($packageKey, $newPackageOrder);
            foreach ($package['dependencies'] as $dependency) {
                $dependencyPosition = array_search($dependency, $newPackageOrder);
                if ($dependencyPosition > $packagePosition) {
                    echo "$packageKey->$dependency";
                }
            }
        }
        */

        $actualPackages = $packageManager->_get('packages');
        $actualPackageStatesConfiguration = $packageManager->_get('packageStatesConfiguration');

        $this->assertEquals($expectedPackageOrder, array_keys($actualPackages), 'The packages have not been ordered according to their dependencies!');
        $this->assertEquals($expectedPackageOrder, array_keys($actualPackageStatesConfiguration['packages']), 'The package states configurations have not been ordered according to their dependencies!');
    }

    public function packagesAndDependenciesOrder()
    {
        return [
            [
                [
                    'Doctrine.ORM' => [
                        'dependencies' => ['Doctrine.DBAL'],
                    ],
                    'Symfony.Component.Yaml' => [
                        'dependencies' => [],
                    ],
                    'Neos.Flow' => [
                        'dependencies' => ['Symfony.Component.Yaml', 'Doctrine.ORM'],
                    ],
                    'Doctrine.Common' => [
                        'dependencies' => [],
                    ],
                    'Doctrine.DBAL' => [
                        'dependencies' => ['Doctrine.Common'],
                    ],
                ],
                [
                    'Doctrine.Common',
                    'Doctrine.DBAL',
                    'Doctrine.ORM',
                    'Symfony.Component.Yaml',
                    'Neos.Flow'
                ],
            ],
            [
                [
                    'Neos.NeosDemoTypo3Org' => [
                        'dependencies' => [
                            'Neos.Neos',
                        ],
                    ],
                    'Flowpack.Behat' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Imagine' => [
                        'dependencies' => [
                            'imagine.imagine',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.TYPO3CR' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Neos' => [
                        'dependencies' => [
                            'Neos.TYPO3CR',
                            'Neos.Twitter.Bootstrap',
                            'Neos.Setup',
                            'Neos.TypoScript',
                            'Neos.Neos.NodeTypes',
                            'Neos.Media',
                            'Neos.ExtJS',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Setup' => [
                        'dependencies' => [
                            'Neos.Twitter.Bootstrap',
                            'Neos.Form',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Media' => [
                        'dependencies' => [
                            'imagine.imagine',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.ExtJS' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Neos.NodeTypes' => [
                        'dependencies' => [
                            'Neos.TypoScript',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.TypoScript' => [
                        'dependencies' => [
                            'Neos.Eel',
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Form' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Twitter.Bootstrap' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.SiteKickstarter' => [
                        'dependencies' => [
                            'Neos.Kickstart',
                            'Neos.Flow',
                        ],
                    ],
                    'imagine.imagine' => [
                        'dependencies' => [],
                    ],
                    'mikey179.vfsStream' => [
                        'dependencies' => [],
                    ],
                    'Composer.Installers' => [
                        'dependencies' => [],
                    ],
                    'symfony.console' => [
                        'dependencies' => [],
                    ],
                    'symfony.domcrawler' => [
                        'dependencies' => [],
                    ],
                    'symfony.yaml' => [
                        'dependencies' => [],
                    ],
                    'doctrine.annotations' => [
                        'dependencies' => [
                            0 => 'doctrine.lexer',
                        ],
                    ],
                    'doctrine.cache' => [
                        'dependencies' => [],
                    ],
                    'doctrine.collections' => [
                        'dependencies' => [],
                    ],
                    'Doctrine.Common' => [
                        'dependencies' => [
                            'doctrine.annotations',
                            'doctrine.lexer',
                            'doctrine.collections',
                            'doctrine.cache',
                            'doctrine.inflector',
                        ],
                    ],
                    'Doctrine.DBAL' => [
                        'dependencies' => [
                            'Doctrine.Common',
                        ],
                    ],
                    'doctrine.inflector' => [
                        'dependencies' => [],
                    ],
                    'doctrine.lexer' => [
                        'dependencies' => [],
                    ],
                    'doctrine.migrations' => [
                        'dependencies' => [
                            'Doctrine.DBAL',
                        ],
                    ],
                    'Doctrine.ORM' => [
                        'dependencies' => [
                            'symfony.console',
                            'Doctrine.DBAL',
                        ],
                    ],
                    'phpunit.phpcodecoverage' => [
                        'dependencies' => [
                            'phpunit.phptexttemplate',
                            'phpunit.phptokenstream',
                            'phpunit.phpfileiterator',
                        ],
                    ],
                    'phpunit.phpfileiterator' => [
                        'dependencies' => [],
                    ],
                    'phpunit.phptexttemplate' => [
                        'dependencies' => [],
                    ],
                    'phpunit.phptimer' => [
                        'dependencies' => [],
                    ],
                    'phpunit.phptokenstream' => [
                        'dependencies' => [],
                    ],
                    'phpunit.phpunitmockobjects' => [
                        'dependencies' => [
                            'phpunit.phptexttemplate',
                        ],
                    ],
                    'phpunit.phpunit' => [
                        'dependencies' => [
                            'symfony.yaml',
                            'phpunit.phpunitmockobjects',
                            'phpunit.phptimer',
                            'phpunit.phpcodecoverage',
                            'phpunit.phptexttemplate',
                            'phpunit.phpfileiterator',
                        ],
                    ],
                    'Neos.Party' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Flow' => [
                        'dependencies' => [
                            'Composer.Installers',
                            'symfony.domcrawler',
                            'symfony.yaml',
                            'doctrine.migrations',
                            'Doctrine.ORM',
                            'Neos.Eel',
                            'Neos.Party',
                            'Neos.Fluid',
                        ],
                    ],
                    'Neos.Eel' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Kickstart' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                    'Neos.Fluid' => [
                        'dependencies' => [
                            'Neos.Flow',
                        ],
                    ],
                ],
                [
                    'Composer.Installers',
                    'symfony.domcrawler',
                    'symfony.yaml',
                    'doctrine.lexer',
                    'doctrine.annotations',
                    'doctrine.collections',
                    'doctrine.cache',
                    'doctrine.inflector',
                    'Doctrine.Common',
                    'Doctrine.DBAL',
                    'doctrine.migrations',
                    'symfony.console',
                    'Doctrine.ORM',
                    'Neos.Eel',
                    'Neos.Party',
                    'Neos.Fluid',
                    'Neos.Flow',
                    'Neos.TYPO3CR',
                    'Neos.Twitter.Bootstrap',
                    'Neos.Form',
                    'Neos.Setup',
                    'Neos.TypoScript',
                    'Neos.Neos.NodeTypes',
                    'imagine.imagine',
                    'Neos.Media',
                    'Neos.ExtJS',
                    'Neos.Neos',
                    'Neos.NeosDemoTypo3Org',
                    'Flowpack.Behat',
                    'Neos.Imagine',
                    'Neos.Kickstart',
                    'Neos.SiteKickstarter',
                    'mikey179.vfsStream',
                    'phpunit.phptexttemplate',
                    'phpunit.phptokenstream',
                    'phpunit.phpfileiterator',
                    'phpunit.phpcodecoverage',
                    'phpunit.phptimer',
                    'phpunit.phpunitmockobjects',
                    'phpunit.phpunit',
                ],
            ],
        ];
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
        $packageStatesConfiguration = ['packages' =>
            [
                'Neos.Flow' => [
                    'composerName' => 'neos/flow'
                ],
                'imagine.Imagine' => [
                    'composerName' => 'imagine/Imagine'
                ]
            ]
        ];

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies']);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

        $this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Package\Exception\PackageKeyAlreadyExistsException
     */
    public function registeringTheSamePackageKeyWithDifferentCaseShouldThrowException()
    {
        $this->packageManager->createPackage('doctrine.instantiator');
        $this->packageManager->createPackage('doctrine.Instantiator');
    }

    /**
     * @test
     */
    public function createPackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->createPackage('Some.Package');
    }

    /**
     * @test
     */
    public function activatePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->packageManager->createPackage('Some.Package');
        $this->packageManager->deactivatePackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->activatePackage('Some.Package');
    }

    /**
     * @test
     */
    public function deactivatePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->packageManager->createPackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->deactivatePackage('Some.Package');
    }


    /**
     * @test
     */
    public function freezePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));

        $this->packageManager->createPackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->freezePackage('Some.Package');
    }

    /**
     * @test
     */
    public function unfreezePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));

        $this->packageManager->createPackage('Some.Package');
        $this->packageManager->freezePackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->unfreezePackage('Some.Package');
    }
}
