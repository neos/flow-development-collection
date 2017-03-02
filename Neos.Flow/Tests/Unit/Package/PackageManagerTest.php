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

        mkdir('vfs://Test/Packages/Application', 0700, true);
        mkdir('vfs://Test/Configuration');

        $this->packageManager = new PackageManager('vfs://Test/Configuration/PackageStates.php');

        $composerNameToPackageKeyMap = [
            'neos/flow' => 'Neos.Flow'
        ];

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
        $this->packageManager->createPackage('Neos.Flow');

        $package = $this->packageManager->getPackage('Neos.Flow');
        $this->assertInstanceOf(PackageInterface::class, $package, 'The result of getPackage() was no valid package object.');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Package\Exception\UnknownPackageException
     */
    public function getPackageThrowsExceptionOnUnknownPackage()
    {
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
            PackageInterface::DIRECTORY_CLASSES,
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
        $expectedPackageKeys = [
            'Neos.Flow' . md5(uniqid(mt_rand(), true)),
            'Neos.Flow.Test' . md5(uniqid(mt_rand(), true)),
            'Neos.YetAnotherTestPackage' . md5(uniqid(mt_rand(), true)),
            'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), true))
        ];

        foreach ($expectedPackageKeys as $packageKey) {
            $packageName = ComposerUtility::getComposerPackageNameFromPackageKey($packageKey);
            $packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

            mkdir($packagePath, 0770, true);
            mkdir($packagePath . 'Classes');
            file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageName . '", "type": "flow-test"}');
        }

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['emitPackageStatesUpdated']);
        $packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packageStatesConfiguration', [
            'packages' => [
                $packageName => [
                    'state' => 'inactive',
                    'frozen' => false,
                    'packagePath' => 'Application/' . $packageKey . '/',
                    'classesPath' => 'Classes/'
                ]
            ],
            'version' => 2
        ]);
        $packageStates = $packageManager->rescanPackages(false);
        $this->assertEquals('inactive', $packageStates['packages'][$packageName]['state']);
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
        $packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

        $packageFactory = new PackageFactory($packageManager);
        $this->inject($packageManager, 'packageFactory', $packageFactory);

        $packageManager->_set('packages', []);
        $actualPackageStatesConfiguration = $packageManager->rescanPackages();

        $expectedPackageStatesConfiguration = [];
        foreach ($packageKeys as $packageKey) {
            $composerName = ComposerUtility::getComposerPackageNameFromPackageKey($packageKey);
            $expectedPackageStatesConfiguration[$composerName] = [
                'state' => 'active',
                'packagePath' => 'Application/' . $packageKey . '/',
                'composerName' => $composerName,
                'packageClassInformation' => [],
                'packageKey' => $packageKey,
                'autoloadConfiguration' => []
            ];
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
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', [
            'name' => 'acme/yetanothertestpackage',
            'type' => 'neos-package',
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

        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2', $metaData);

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        $this->assertEquals('flow-custom-package', $composerManifest->type);
    }


    /**
     * @test
     */
    public function createPackageAlwaysSetsThePackageType()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage2');

        $json = file_get_contents($package->getPackagePath() . '/composer.json');
        $composerManifest = json_decode($json);

        $this->assertEquals('neos-package', $composerManifest->type);
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
        } catch (InvalidPackageKeyException $exception) {
        }
        $this->assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
    }

    /**
     * Makes sure that duplicate package keys are detected.
     *
     * @test
     * @expectedException \Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException
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
     * @expectedException \Neos\Flow\Package\Exception\ProtectedPackageKeyException
     */
    public function deactivatePackageThrowsAnExceptionIfPackageIsProtected()
    {
        $package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
        $package->setProtected(true);
        $this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Package\Exception\UnknownPackageException
     */
    public function deletePackageThrowsErrorIfPackageIsNotAvailable()
    {
        $this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Package\Exception\ProtectedPackageKeyException
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

        $packageManager = $this->getAccessibleMock(PackageManager::class, ['resolvePackageDependencies']);
        $packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

        $this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Package\Exception\PackageKeyAlreadyExistsException
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

        $this->packageManager->createPackage('Some.Package', [
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

        $this->packageManager->createPackage('Some.Package', [
            'name' => 'some/package',
            'type' => 'neos-package'
        ]);
        $this->packageManager->freezePackage('Some.Package');

        $this->mockDispatcher->expects($this->once())->method('dispatch')->with(PackageManager::class, 'packageStatesUpdated');
        $this->packageManager->unfreezePackage('Some.Package');
    }
}
