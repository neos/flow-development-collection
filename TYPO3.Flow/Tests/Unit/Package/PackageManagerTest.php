<?php
namespace TYPO3\FLOW3\Tests\Unit\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Package\PackageInterface;
use org\bovigo\vfs\vfsStream;

/**
 * Testcase for the default package manager
 *
 */
class PackageManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 */
	protected function setUp() {
		vfsStream::setup('Test');
		$mockBootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);
		$mockBootstrap->expects($this->any())->method('getSignalSlotDispatcher')->will($this->returnValue($this->getMock('TYPO3\FLOW3\SignalSlot\Dispatcher')));
		$this->packageManager = new \TYPO3\FLOW3\Package\PackageManager();

		mkdir('vfs://Test/Packages/Application', 0700, TRUE);
		mkdir('vfs://Test/Configuration');

		$mockClassLoader = $this->getMock('TYPO3\FLOW3\Core\ClassLoader');

		$this->packageManager->injectClassLoader($mockClassLoader);
		$this->packageManager->initialize($mockBootstrap, 'vfs://Test/Packages/', 'vfs://Test/Configuration/PackageStates.php');
	}

	/**
	 * @test
	 */
	public function initializeUsesPackageStatesConfigurationForActivePackages() {
	}

	/**
	 * @test
	 */
	public function getPackageReturnsTheSpecifiedPackage() {
		$this->packageManager->createPackage('TYPO3.FLOW3');

		$package = $this->packageManager->getPackage('TYPO3.FLOW3');
		$this->assertInstanceOf('TYPO3\FLOW3\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\UnknownPackageException
	 */
	public function getPackageThrowsExceptionOnUnknownPackage() {
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->_set('packageKeys', array('acme.testpackage' => 'Acme.TestPackage'));
		$this->assertEquals('Acme.TestPackage', $packageManager->getCaseSensitivePackageKey('acme.testpackage'));
	}

	/**
	 * @test
	 */
	public function scanAvailablePackagesTraversesThePackagesDirectoryAndRegistersPackagesItFinds() {
		$expectedPackageKeys = array(
			'TYPO3.FLOW3' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.FLOW3.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.FLOW3.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

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
			'TYPO3.FLOW3' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.FLOW3.Test' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
			'RobertLemke.FLOW3.NothingElse' . md5(uniqid(mt_rand(), TRUE))
		);

		foreach ($expectedPackageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

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

		$packageStates = require('vfs://Test/Configuration/PackageStates.php');
		$this->assertEquals('inactive', $packageStates['packages'][$packageKey]['state']);
	}


	/**
	 * @test
	 */
	public function packageStatesConfigurationContainsRelativePaths() {
		$packageKeys = array(
			'RobertLemke.FLOW3.NothingElse' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.FLOW3' . md5(uniqid(mt_rand(), TRUE)),
			'TYPO3.YetAnotherTestPackage' . md5(uniqid(mt_rand(), TRUE)),
		);

		foreach ($packageKeys as $packageKey) {
			$packagePath = 'vfs://Test/Packages/Application/' . $packageKey . '/';

			mkdir($packagePath, 0770, TRUE);
			mkdir($packagePath . 'Classes');
			file_put_contents($packagePath . 'composer.json', '{"name": "' . $packageKey . '", "type": "flow3-test"}');
		}

		$packageManager = $this->getAccessibleMock('TYPO3\FLOW3\Package\PackageManager', array('updateShortcuts'), array(), '', FALSE);
		$packageManager->_set('packagesBasePath', 'vfs://Test/Packages/');
		$packageManager->_set('packageStatesPathAndFilename', 'vfs://Test/Configuration/PackageStates.php');

		$packageManager->_set('packages', array());
		$packageManager->_call('scanAvailablePackages');

		$expectedPackageStatesConfiguration = array();
		foreach ($packageKeys as $packageKey) {
			$expectedPackageStatesConfiguration[$packageKey] = array(
				'state' => 'active',
				'packagePath' => 'Application/' . $packageKey . '/',
				'classesPath' => 'Classes/',
				'manifestPath' => ''
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
			array('RobertLemke.FLOW3.NothingElse', 'vfs://Test/Packages/Application/RobertLemke.FLOW3.NothingElse/')
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
		$metaData = new \TYPO3\FLOW3\Package\MetaData('Acme.YetAnotherTestPackage');
		$metaData->setDescription('Yet Another Test Package');

		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage', $metaData);

		$json = file_get_contents($package->getPackagePath() . '/composer.json');
		$composerManifest = json_decode($json);

		$this->assertEquals('acme/yetanothertestpackage', $composerManifest->name);
		$this->assertEquals('Yet Another Test Package', $composerManifest->description);
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
		} catch (\TYPO3\FLOW3\Package\Exception\InvalidPackageKeyException $exception) {
		}
		$this->assertFalse(is_dir('vfs://Test/Packages/Application/Invalid_PackageKey'), 'Package folder with invalid package key was created');
	}

	/**
	 * Makes sure that duplicate package keys are detected.
	 *
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\PackageKeyAlreadyExistsException
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
	 * @expectedException \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException
	 */
	public function deactivatePackageThrowsAnExceptionIfPackageIsProtected() {
		$package = $this->packageManager->createPackage('Acme.YetAnotherTestPackage');
		$package->setProtected(TRUE);
		$this->packageManager->deactivatePackage('Acme.YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\UnknownPackageException
	 */
	public function deletePackageThrowsErrorIfPackageIsNotAvailable() {
		$this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Package\Exception\ProtectedPackageKeyException
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
	 */
	public function getDependencyArrayForPackageReturnsCorrectResult() {
		$mockFlow3Metadata = $this->getMock('TYPO3\FLOW3\Package\MetaDataInterface');
		$mockFlow3Metadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'TYPO3.Fluid'),
			new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'Doctrine.ORM')
		)));
		$mockFlow3Package = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockFlow3Package->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockFlow3Metadata));

		$mockFluidMetadata = $this->getMock('TYPO3\FLOW3\Package\MetaDataInterface');
		$mockFluidMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'TYPO3.FLOW3')
		)));
		$mockFluidPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockFluidPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockFluidMetadata));

		$mockOrmMetadata = $this->getMock('TYPO3\FLOW3\Package\MetaDataInterface');
		$mockOrmMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'Doctrine.DBAL')
		)));
		$mockOrmPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockOrmPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockOrmMetadata));

		$mockDbalMetadata = $this->getMock('TYPO3\FLOW3\Package\MetaDataInterface');
		$mockDbalMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array(
			new \TYPO3\FLOW3\Package\MetaData\PackageConstraint('depends', 'Doctrine.Common')
		)));
		$mockDbalPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockDbalPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockDbalMetadata));

		$mockCommonMetadata = $this->getMock('TYPO3\FLOW3\Package\MetaDataInterface');
		$mockCommonMetadata->expects($this->any())->method('getConstraintsByType')->will($this->returnValue(array()));
		$mockCommonPackage = $this->getMock('TYPO3\FLOW3\Package\PackageInterface');
		$mockCommonPackage->expects($this->any())->method('getPackageMetaData')->will($this->returnValue($mockCommonMetadata));

		$packages = array(
			'TYPO3.FLOW3' => $mockFlow3Package,
			'TYPO3.Fluid' => $mockFluidPackage,
			'Doctrine.ORM' => $mockOrmPackage,
			'Doctrine.DBAL' => $mockDbalPackage,
			'Doctrine.Common' => $mockCommonPackage
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->_set('packages', $packages);
		$dependencyArray = $packageManager->_call('getDependencyArrayForPackage', 'TYPO3.FLOW3');

		$this->assertEquals(array('Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM', 'TYPO3.Fluid'), $dependencyArray);
	}

	/**
	 * @test
	 */
	public function sortAvailablePackagesByDependenciesMakesSureThatDependantPackagesAreStandingBeforeAPackageInTheInternalPackagesAndPackagesConfigurationArrays() {
		$doctrineCommon = $this->getMock('\TYPO3\FLOW3\Package\PackageInterface');
		$doctrineCommon->expects($this->any())->method('getPackageKey')->will($this->returnValue('Doctrine.Common'));

		$doctrineDbal = $this->getMock('\TYPO3\FLOW3\Package\PackageInterface');
		$doctrineDbal->expects($this->any())->method('getPackageKey')->will($this->returnValue('Doctrine.DBAL'));

		$doctrineOrm = $this->getMock('\TYPO3\FLOW3\Package\PackageInterface');
		$doctrineOrm->expects($this->any())->method('getPackageKey')->will($this->returnValue('Doctrine.ORM'));

		$typo3Flow3 = $this->getMock('\TYPO3\FLOW3\Package\PackageInterface');
		$typo3Flow3->expects($this->any())->method('getPackageKey')->will($this->returnValue('TYPO3.FLOW3'));

		$symfonyComponentYaml = $this->getMock('\TYPO3\FLOW3\Package\PackageInterface');
		$symfonyComponentYaml->expects($this->any())->method('getPackageKey')->will($this->returnValue('Symfony.Component.Yaml'));

		$unsortedPackageStatesConfiguration = array('packages' =>
			array(
				'Doctrine.ORM' => array(
					'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
				),
				'Symfony.Component.Yaml' => array(
					'dependencies' => array()
				),
				'TYPO3.FLOW3' => array(
					'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
				),
				'Doctrine.Common' => array(
					'dependencies' => array()
				),
				'Doctrine.DBAL' => array(
					'dependencies' => array('Doctrine.Common')
				)
			)
		);

		$unsortedPackages = array(
			'Doctrine.ORM' => $doctrineOrm,
			'Symfony.Component.Yaml' => $symfonyComponentYaml,
			'TYPO3.FLOW3' => $typo3Flow3,
			'Doctrine.Common' => $doctrineCommon,
			'Doctrine.DBAL' => $doctrineDbal
		);

		$packageManager = $this->getAccessibleMock('\TYPO3\FLOW3\Package\PackageManager', array('resolvePackageDependencies'));
		$packageManager->_set('packages', $unsortedPackages);
		$packageManager->_set('packageStatesConfiguration', $unsortedPackageStatesConfiguration);
		$packageManager->_call('sortAvailablePackagesByDependencies');

		$expectedSortedPackageKeys = array(
			'Doctrine.Common',
			'Doctrine.DBAL',
			'Doctrine.ORM',
			'Symfony.Component.Yaml',
			'TYPO3.FLOW3'
		);

		$expectedSortedPackageStatesConfiguration = array('packages' =>
			array(
				'Doctrine.Common' => array(
					'dependencies' => array()
				),
				'Doctrine.DBAL' => array(
					'dependencies' => array('Doctrine.Common')
				),
				'Doctrine.ORM' => array(
					'dependencies' => array('Doctrine.Common', 'Doctrine.DBAL')
				),
				'Symfony.Component.Yaml' => array(
					'dependencies' => array()
				),
				'TYPO3.FLOW3' => array(
					'dependencies' => array('Symfony.Component.Yaml', 'Doctrine.Common', 'Doctrine.DBAL', 'Doctrine.ORM')
				)
			)
		);

		$this->assertEquals($expectedSortedPackageKeys, array_keys($packageManager->_get('packages')), 'The packages have not been ordered according to their dependencies!');
		$this->assertEquals($expectedSortedPackageStatesConfiguration, $packageManager->_get('packageStatesConfiguration'), 'The package states configurations have not been ordered according to their dependencies!');
	}
}
?>