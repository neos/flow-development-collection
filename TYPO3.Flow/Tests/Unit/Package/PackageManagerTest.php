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

use TYPO3\Flow\Composer\ComposerUtility;
use TYPO3\Flow\Core\ApplicationContext;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;
use TYPO3\Flow\Package\PackageManager;
use TYPO3\Flow\SignalSlot\Dispatcher;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        ComposerUtility::flushCaches();
        vfsStream::setup('Test');
        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects($this->any())->method('getSignalSlotDispatcher')->will($this->returnValue($this->createMock(\TYPO3\Flow\SignalSlot\Dispatcher::class)));

        $this->mockApplicationContext = $this->getMockBuilder(ApplicationContext::class)->disableOriginalConstructor()->getMock();
        $this->mockBootstrap->expects($this->any())->method('getContext')->will($this->returnValue($this->mockApplicationContext));

        $mockObjectManager = $this->createMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $this->mockBootstrap->expects($this->any())->method('getObjectManager')->will($this->returnValue($mockObjectManager));
        $mockReflectionService = $this->createMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->any())->method('getClassNameByObject')->will($this->returnCallback(function ($object) {
            if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
                return get_parent_class($object);
            }
            return get_class($object);
        }));
        $mockObjectManager->expects($this->any())->method('get')->with(\TYPO3\Flow\Reflection\ReflectionService::class)->will($this->returnValue($mockReflectionService));

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');

        $this->packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php');

        $composerNameToPackageKeyMap = array(
            'typo3/flow' => 'TYPO3.Flow'
        );

        $this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
        $this->inject($this->packageManager, 'packagesBasePath', 'vfs://Test/Packages/');

        $this->mockDispatcher = $this->getMockBuilder(Dispatcher::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->packageManager, 'dispatcher', $this->mockDispatcher);

        $this->packageManager->initialize($this->mockBootstrap);
    }

    /**
     * @test
     */
    public function getPackageReturnsTheSpecifiedPackage()
    {
        $this->packageManager->createPackage('TYPO3.Flow');

        $package = $this->packageManager->getPackage('TYPO3.Flow');
        $this->assertInstanceOf(\TYPO3\Flow\Package\PackageInterface::class, $package, 'The result of getPackage() was no valid package object.');
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
        $dummyClassFilePath = \TYPO3\Flow\Utility\Files::concatenatePaths(array(
            $package->getPackagePath(),
            PackageInterface::DIRECTORY_CLASSES,
            $dummyClassName . '.php'
        ));
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
        $packageManager = $this->getAccessibleMock(PackageManager::class, array('dummy'));
        $packageManager->_set('packageKeys', array('acme.testpackage' => 'Acme.TestPackage'));
        $this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds()
    {
        $expectedPackageKeys = array(
            'TYPO3.Flow' . md5(uniqid(mt_rand(), true)),
            'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), true)),
            'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true))
        );

        foreach ($expectedPackageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, array('emitPackageStatesUpdated'));
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', array());
        $packageManager->rescanPackages();

        $packageStates = require('vfs://Test/Configuration/PackageStates.php');
        $actualPackageKeys = array_keys($packageStates['packages']);
        $this->assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
    }

    /**
     * @test
     */
    public function scanAvailablePackagesKeepsExistingPackageConfiguration()
    {
        $expectedPackageKeys = array(
            'TYPO3.Flow' . md5(uniqid(mt_rand(), true)),
            'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), true)),
            'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true))
        );

        foreach ($expectedPackageKeys as $packageKey) {
            $packageName = ComposerUtility::getComposerPackageNameFromPackageKey($packageKey);
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageName . '", "type": "flow-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, array('emitPackageStatesUpdated'));
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packageStatesConfiguration', array(
            'packages' => array(
                $packageName => array(
                    'state' => 'inactive',
                    'frozen' => false,
                    'packagePath' => 'Application/' . $packageKey . '/',
                    'classesPath' => 'Classes/'
                )
            ),
            'version' => 2
        ));
        $packageStates = $packageManager->rescanPackages(false);
        $this->assertEquals('inactive', $packageStates['packages'][$packageName]['state']);
    }


    /**
     * @test
     */
    public function packageStatesConfigurationContainsRelativePaths()
    {
        $packageKeys = array(
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true)),
            'TYPO3.Flow' . md5(uniqid(mt_rand(), true)),
            'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
        );

        foreach ($packageKeys as $packageKey) {
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            ComposerUtility::writeComposerManifest($packagePath, $packageKey, ['type' => 'flow-test', 'autoload' => []]);
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, array('updateShortcuts', 'emitPackageStatesUpdated'), array(), '', false);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', array());
        $actualPackageStatesConfiguration = $packageManager->rescanPackages();

        $expectedPackageStatesConfiguration = array();
        foreach ($packageKeys as $packageKey) {
            $composerName = ComposerUtility::getComposerPackageNameFromPackageKey($packageKey);
            $expectedPackageStatesConfiguration[$composerName] = array(
                'state' => 'active',
                'packagePath' => 'Application/' . $packageKey . '/',
                'composerName' => $composerName,
                'packageClassInformation' => array(),
                'packageKey' => $packageKey,
                'autoloadConfiguration' => []
            );
        }

        $this->assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
    }

    /**
     * Data Provider returning valid package keys and the corresponding path
     *
     * @return array
     */
    public function packageKeysAndPaths()
    {
        return array(
            array('TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3.YetAnotherTestPackage/'),
            array('RobertLemke.Flow.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.Flow.NothingElse/')
        );
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
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', null, null, null, [
            'name' => 'acme/yetanothertestpackage',
            'type' => 'typo3-flow-package',
            'description' => 'Yet Another Test Package',
            'autoload' => [
                'psr-0' => [
                    'Acme\\YetAnotherTestPackage' => 'Classes/'
                ]
            ]
        ]);

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

        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', null, null, null, $metaData);

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

        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CLASSES), 'Classes directory was not created');
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), 'Configuration directory was not created');
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_DOCUMENTATION), 'Documentation directory was not created');
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_RESOURCES), 'Resources directory was not created');
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_UNIT), 'Tests/Unit directory was not created');
        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_TESTS_FUNCTIONAL), 'Tests/Functional directory was not created');
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
        } catch (\TYPO3\Flow\Package\Exception\InvalidPackageKeyException $exception) {
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

        $this->assertTrue(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), 'The package configuration directory does not exist.');
        $this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'), 'The package is not active.');
        $this->assertTrue($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'), 'The package is not available.');

        $this->packageManager->deletePackage('Acme.YetAnotherTestPackage');

        $this->assertFalse(is_dir($packagePath . PackageInterface::DIRECTORY_CONFIGURATION), 'The package configuration directory does still exist.');
        $this->assertFalse($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'), 'The package is still active.');
        $this->assertFalse($this->packageManager->isPackageAvailable('Acme.YetAnotherTestPackage'), 'The package is still available.');
    }

    /**
     * @return array
     */
    public function composerNamesAndPackageKeys()
    {
        return array(
            array('imagine/Imagine', 'imagine.Imagine'),
            array('imagine/imagine', 'imagine.Imagine'),
            array('typo3/flow', 'TYPO3.Flow'),
            array('TYPO3/Flow', 'TYPO3.Flow')
        );
    }

    /**
     * @test
     * @dataProvider composerNamesAndPackageKeys
     */
    public function getPackageKeyFromComposerNameIgnoresCaseDifferences($composerName, $packageKey)
    {
        $packageStatesConfiguration = [
            'packages' => [
                'typo3/flow' => [
                    'packageKey' => 'TYPO3.Flow',
                    'composerName' => 'typo3/flow'
                ],
                'imagine/imagine' => [
                    'packageKey' => 'imagine.Imagine',
                    'composerName' => 'imagine/imagine'
                ]
            ]
        ];

        $packageManager = $this->getAccessibleMock(PackageManager::class, array('resolvePackageDependencies'));
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

        $this->packageManager->createPackage('Some.Package', null, null, null, [
            'name' => 'some/package'
        ]);

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->freezePackage('Some.Package');
    }

    /**
     * @test
     */
    public function unfreezePackageEmitsPackageStatesUpdatedSignal()
    {
        $this->mockApplicationContext->expects($this->atLeastOnce())->method('isDevelopment')->will($this->returnValue(true));

        $this->packageManager->createPackage('Some.Package', null, null, null, [
            'name' => 'some/package',
            'type' => 'typo3-flow-package'
        ]);
        $this->packageManager->freezePackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->unfreezePackage('Some.Package');
    }
}
