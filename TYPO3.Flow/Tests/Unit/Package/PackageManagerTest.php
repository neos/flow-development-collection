<?php
namespace TYPO3\Flow\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		vfsStream::setup('Test');
		$mockBootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getSignalSlotDispatcher')->will($this->returnValue($this->getMock('TYPO3\Flow\SignalSlot\Dispatcher')));
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockBootstrap->expects($this->any())->method('getObjectManager')->will($this->returnValue($mockObjectManager));
		$mockReflectionService = $this->getMock('TYPO3\Flow\Reflection\ReflectionService');
		$mockReflectionService->expects($this->any())->method('getClassNameByObject')->will($this->returnCallback(function($object) {
			if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
				return get_parent_class($object);
			}
			return get_class($object);
		}));
		$mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Reflection\ReflectionService')->will($this->returnValue($mockReflectionService));
		$this->packageManager = new \TYPO3\Flow\Package\PackageManager();

		mkdir('vfs://Test/Packages/Application', 0700, TRUE);
		mkdir('vfs://Test/Configuration');

		$mockClassLoader = $this->getMock('TYPO3\Flow\Core\ClassLoader');

		$composerNameToPackageKeyMap = array(
			'typo3/flow' => 'TYPO3.Flow'
		);

		$this->packageManager->injectClassLoader($mockClassLoader);
		$this->inject($this->packageManager, 'composerNameToPackageKeyMap', $composerNameToPackageKeyMap);
		$this->packageManager->initialize($mockBootstrap, 'vfs://Test/Packages/', 'vfs://Test/Configuration/PackageStates.php');
	}

	/**
	 * @test
	 */
	public function getPackageReturnsTheSpecifiedPackage() {
		$this->packageManager->createPackage('TYPO3.Flow');

		$package = $this->packageManager->getPackage('TYPO3.Flow');
		$this->assertInstanceOf('TYPO3\Flow\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
	 */
	public function getPackageThrowsExceptionOnUnknownPackage() {
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 */
	public function getPackageOfObjectGetsPackageByGivenObject() {
		$package = $this->packageManager->createPackage('Acme.Foobar');
		$dummyObject = $this->createDummyObjectForPackage($package);
		$actual = $this->packageManager->getPackageOfObject($dummyObject);
		$this->assertSame($package, $actual);
	}

	/**
	 * @test
	 */
	public function getPackageOfObjectAssumesParentClassIfDoctrineProxyClassGiven() {
		$package = $this->packageManager->createPackage('Acme.Foobar');
		$dummyObject = $this->createDummyObjectForPackage($package);

		eval('namespace Doctrine\ORM\Proxy; interface Proxy {}');
		mkdir('vfs://Test/Somewhere/For/DoctrineProxies', 0700, TRUE);
		$dummyProxyClassName = 'Proxy_' . str_replace('\\', '_', get_class($dummyObject));
		$dummyProxyClassPath = 'vfs://Test/Somewhere/For/DoctrineProxies/' . $dummyProxyClassName . '.php';
		file_put_contents($dummyProxyClassPath, '<?php class ' . $dummyProxyClassName . ' extends ' . get_class($dummyObject) . ' implements \Doctrine\ORM\Proxy\Proxy {} ?>');
		require $dummyProxyClassPath;
		$dummyProxy = new $dummyProxyClassName();

		$actual = $this->packageManager->getPackageOfObject($dummyProxy);
		$this->assertSame($package, $actual);
	}

	/**
	 * @test
	 */
	public function getPackageOfObjectDoesNotGivePackageWithShorterPathPrematurely() {
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
	protected function createDummyObjectForPackage(PackageInterface $package) {
		$dummyClassName = 'Someclass' . md5(uniqid(mt_rand(), TRUE));
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
	public function getPackageOfObjectReturnsNullIfPackageCouldNotBeResolved() {
		$this->assertNull($this->packageManager->getPackageOfObject(new \ArrayObject()));
	}

	/**
	 * @test
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packageKeys', array('acme.testpackage' => 'Acme.TestPackage'));
		$this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
	}

	/**
	 * @test
	 */
	public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds() {
		$expectedPackageKeys = array(
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');

		$packageStates = require('vfs://Test/Configuration/PackageStates.php');
		$actualPackageKeys = array_keys($packageStates['packages']);
		$this->assertEquals(sort($expectedPackageKeys), sort($actualPackageKeys));
	}

	/**
	 * @test
	 */
	public function scanAvailablePackagesKeepsExistingPackageConfiguration() {
		$expectedPackageKeys = array(
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packageStatesConfiguration', array(
			'packages' => array(
				$packageKey => array(
					'state' => 'inactive',
					'frozen' => FALSE,
					'packagePath' => 'Application/' . $packageKey . '/',
					'classesPath' => 'Classes/'
				)
			),
			'version' => 2
		));
		$packageManager->_call('scanAvailablePackages');
		$packageManager->_call('sortAndsavePackageStates');

		$packageStates = require('vfs://Test/Configuration/PackageStates.php');
		$this->assertEquals('inactive', $packageStates['packages'][$packageKey]['state']);
	}


	/**
	 * @test
	 */
	public function packageStatesConfigurationContainsRelativePaths() {
		$packageKeys = array(
			'RobertLemke.Flow.NothingElse' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.Flow' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
		);

		foreach ($packageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\Flow\Package\PackageManager', array('updateShortcuts'), array(), '', FALSE);
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageFactory = new \TYPO3\Flow\Package\PackageFactory($packageManager);
		$this->inject($packageManager, 'packageFactory', $packageFactory);

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');

		$expectedPackageStatesConfiguration = array();
		foreach ($packageKeys as $packageKey) {
			$expectedPackageStatesConfiguration[$packageKey] = array(
				'state' => 'active',
				'packagePath' => 'Application/' . $packageKey . '/',
				'classesPath' => 'Classes/',
				'manifestPath' => '',
				'composerName' => $packageKey
			);
		}

		$actualPackageStatesConfiguration = $packageManager->_get('packageStatesConfiguration');
		$this->assertEquals($expectedPackageStatesConfiguration, $actualPackageStatesConfiguration['packages']);
	}

	/**
	 * Data Provider returning valid package keys and the corresponding path
	 *
	 * @return array
	 */
	public function packageKeysAndPaths() {
		return array(
			array('TYPO3.YetAnotherTestPackage', 'vfs://Test/Packages/Application/TYPO3.YetAnotherTestPackage/'),
			array('RobertLemke.Flow.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.Flow.NothingElse/')
		);
	}

	/**
	 * @test
	 * @dataProvider packageKeysAndPaths
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage($packageKey, $expectedPackagePath) {
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
	public function createPackageWritesAComposerManifestUsingTheGivenMetaObject() {
		$metaData = new \TYPO3\Flow\Package\MetaData('Acme.YetAnotherTestPackage');
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
	public function createPackageCanChangePackageTypeInComposerManifest() {
		$metaData = new \TYPO3\Flow\Package\MetaData('Acme.YetAnotherTestPackage2');
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
	public function createPackageCreatesCommonFolders() {
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
	public function createPackageThrowsExceptionOnInvalidPackageKey() {
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
	public function createPackageThrowsExceptionForExistingPackageKey() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 */
	public function createPackageActivatesTheNewlyCreatedPackage() {
		$this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$this->assertTrue($this->packageManager->isPackageActive('Acme.YetAnotherTestPackage'));
	}

	/**
	 * @test
	 */
	public function activatePackageAndDeactivatePackageActivateAndDeactivateTheGivenPackage() {
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
	public function deactivatePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\UnknownPackageException
	 */
	public function deletePackageThrowsErrorIfPackageIsNotAvailable() {
		$this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Package\Exception\ProtectedPackageKeyException
	 */
	public function deletePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deletePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 */
	public function deletePackageRemovesPackageFromAvailableAndActivePackagesAndDeletesThePackageDirectory() {
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
	public function availablePackagesAreSortedAfterTheirDependencies($packages, $expectedPackageOrder) {
		$unsortedPackages = array();
		foreach ($packages as $packageKey => $package) {
			$mockPackageConstraints = array();
			foreach ($package['dependencies'] as $dependency) {
				$mockPackageConstraints[] = new \TYPO3\Flow\Package\MetaData\PackageConstraint('depends', $dependency);
			}
			$mockMetaData = $this->getMock('TYPO3\Flow\Package\MetaDataInterface');
			$mockMetaData->expects($this->any())->method('getConstraintsByType')->will($this->returnValue($mockPackageConstraints));
			$mockPackage = $this->getMock('TYPO3\Flow\Package\PackageInterface');
			$mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue($packageKey));
			$mockPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockMetaData));
			$unsortedPackages[$packageKey] = $mockPackage;
		}

		$packageManager = $this->getAccessibleMock('\TYPO3\Flow\Package\PackageManager', array('dummy'));
		$packageManager->_set('packages', $unsortedPackages);
		$packageManager->_set('packageStatesConfiguration', array('packages' => $packages));
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

	public function packagesAndDependenciesOrder() {
		return array(
			array(
				array(
					'Doctrine.ORM' => array(
						'dependencies' => array('Doctrine.DBAL'),
					),
					'Symfony.Component.Yaml' => array(
						'dependencies' => array(),
					),
					'TYPO3.Flow' => array(
						'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.ORM'),
					),
					'Doctrine.Common' => array(
						'dependencies' => array(),
					),
					'Doctrine.DBAL' => array(
						'dependencies' => array('Doctrine.Common'),
					),
				),
				array(
					'Doctrine.Common',
					'Doctrine.DBAL',
					'Doctrine.ORM',
					'Symfony.Component.Yaml',
					'TYPO3.Flow'
				),
			),
			array(
				array(
					'TYPO3.NeosDemoTypo3Org' => array(
						'dependencies' => array(
							'TYPO3.Neos',
						),
					),
					'Flowpack.Behat' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Imagine' => array(
						'dependencies' => array(
							'imagine.imagine',
							'TYPO3.Flow',
						),
					),
					'TYPO3.TYPO3CR' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Neos' => array(
						'dependencies' => array(
							'TYPO3.TYPO3CR',
							'TYPO3.Twitter.Bootstrap',
							'TYPO3.Setup',
							'TYPO3.TypoScript',
							'TYPO3.Neos.NodeTypes',
							'TYPO3.Media',
							'TYPO3.ExtJS',
							'TYPO3.Flow',
						),
					),
					'TYPO3.Setup' => array(
						'dependencies' => array(
							'TYPO3.Twitter.Bootstrap',
							'TYPO3.Form',
							'TYPO3.Flow',
						),
					),
					'TYPO3.Media' => array(
						'dependencies' => array(
							'imagine.imagine',
							'TYPO3.Flow',
						),
					),
					'TYPO3.ExtJS' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Neos.NodeTypes' => array(
						'dependencies' => array(
							'TYPO3.TypoScript',
							'TYPO3.Flow',
						),
					),
					'TYPO3.TypoScript' => array(
						'dependencies' => array(
							'TYPO3.Eel',
							'TYPO3.Flow',
						),
					),
					'TYPO3.Form' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Twitter.Bootstrap' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.SiteKickstarter' => array(
						'dependencies' => array(
							'TYPO3.Kickstart',
							'TYPO3.Flow',
						),
					),
					'imagine.imagine' => array(
						'dependencies' => array(),
					),
					'mikey179.vfsStream' => array(
						'dependencies' => array(),
					),
					'Composer.Installers' => array(
						'dependencies' => array(),
					),
					'symfony.console' => array(
						'dependencies' => array(),
					),
					'symfony.domcrawler' => array(
						'dependencies' => array(),
					),
					'symfony.yaml' => array(
						'dependencies' => array(),
					),
					'doctrine.annotations' => array(
						'dependencies' => array(
							0 => 'doctrine.lexer',
						),
					),
					'doctrine.cache' => array(
						'dependencies' => array(),
					),
					'doctrine.collections' => array(
						'dependencies' => array(),
					),
					'Doctrine.Common' => array(
						'dependencies' => array(
							'doctrine.annotations',
							'doctrine.lexer',
							'doctrine.collections',
							'doctrine.cache',
							'doctrine.inflector',
						),
					),
					'Doctrine.DBAL' => array(
						'dependencies' => array(
							'Doctrine.Common',
						),
					),
					'doctrine.inflector' => array(
						'dependencies' => array(),
					),
					'doctrine.lexer' => array(
						'dependencies' => array(),
					),
					'doctrine.migrations' => array(
						'dependencies' => array(
							'Doctrine.DBAL',
						),
					),
					'Doctrine.ORM' => array(
						'dependencies' => array(
							'symfony.console',
							'Doctrine.DBAL',
						),
					),
					'phpunit.phpcodecoverage' => array(
						'dependencies' => array(
							'phpunit.phptexttemplate',
							'phpunit.phptokenstream',
							'phpunit.phpfileiterator',
						),
					),
					'phpunit.phpfileiterator' => array(
						'dependencies' => array(),
					),
					'phpunit.phptexttemplate' => array(
						'dependencies' => array(),
					),
					'phpunit.phptimer' => array(
						'dependencies' => array(),
					),
					'phpunit.phptokenstream' => array(
						'dependencies' => array(),
					),
					'phpunit.phpunitmockobjects' => array(
						'dependencies' => array(
							'phpunit.phptexttemplate',
						),
					),
					'phpunit.phpunit' => array(
						'dependencies' => array(
							'symfony.yaml',
							'phpunit.phpunitmockobjects',
							'phpunit.phptimer',
							'phpunit.phpcodecoverage',
							'phpunit.phptexttemplate',
							'phpunit.phpfileiterator',
						),
					),
					'TYPO3.Party' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Flow' => array(
						'dependencies' => array(
							'Composer.Installers',
							'symfony.domcrawler',
							'symfony.yaml',
							'doctrine.migrations',
							'Doctrine.ORM',
							'TYPO3.Eel',
							'TYPO3.Party',
							'TYPO3.Fluid',
						),
					),
					'TYPO3.Eel' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Kickstart' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
					'TYPO3.Fluid' => array(
						'dependencies' => array(
							'TYPO3.Flow',
						),
					),
				),
				array(
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
					'TYPO3.Eel',
					'TYPO3.Party',
					'TYPO3.Fluid',
					'TYPO3.Flow',
					'TYPO3.TYPO3CR',
					'TYPO3.Twitter.Bootstrap',
					'TYPO3.Form',
					'TYPO3.Setup',
					'TYPO3.TypoScript',
					'TYPO3.Neos.NodeTypes',
					'imagine.imagine',
					'TYPO3.Media',
					'TYPO3.ExtJS',
					'TYPO3.Neos',
					'TYPO3.NeosDemoTypo3Org',
					'Flowpack.Behat',
					'TYPO3.Imagine',
					'TYPO3.Kickstart',
					'TYPO3.SiteKickstarter',
					'mikey179.vfsStream',
					'phpunit.phptexttemplate',
					'phpunit.phptokenstream',
					'phpunit.phpfileiterator',
					'phpunit.phpcodecoverage',
					'phpunit.phptimer',
					'phpunit.phpunitmockobjects',
					'phpunit.phpunit',
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function composerNamesAndPackageKeys() {
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
	public function getPackageKeyFromComposerNameIgnoresCaseDifferences($composerName, $packageKey) {

		$packageStatesConfiguration = array('packages' =>
			array(
				'TYPO3.Flow' => array(
					'composerName' => 'typo3/flow'
				),
				'imagine.Imagine' => array(
					'composerName' => 'imagine/Imagine'
				)
			)
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\Flow\Package\PackageManager', array('resolvePackageDependencies'));
		$packageManager->_set('packageStatesConfiguration', $packageStatesConfiguration);

		$this->assertEquals($packageKey, $packageManager->_call('getPackageKeyFromComposerName', $composerName));
	}

}
