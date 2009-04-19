<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Package;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

require_once('vfs/vfsStream.php');

/**
 * Testcase for the default package manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Package\Manager
	 */
	protected $packageManager;

	/**
	 * Sets up this test case
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		\vfsStreamWrapper::register();
		\vfsStreamWrapper::setRoot(new \vfsStreamDirectory('testDirectory'));

		$this->packageManager = new \F3\FLOW3\Package\Manager();
		$this->packageManager->initialize();
	}

	/**
	 * Tests the method isPackageAvailable()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPackageAvailableReturnsCorrectResult() {
		$this->assertFalse($this->packageManager->isPackageAvailable('PrettyUnlikelyThatThisPackageExists'), 'isPackageAvailable() did not return FALSE although the package in question does not exist.');
		$this->assertTrue($this->packageManager->isPackageAvailable('FLOW3'), 'isPackageAvailable() did not return TRUE although the package "TYPO3" does (or should) exist.');
	}

	/**
	 * Tests the method getPackage()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackageReturnsPackagesAndThrowsExcpetions() {
		$package = $this->packageManager->getPackage('FLOW3');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAvailablePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getAvailablePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getAvailablePackages().');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getAvailablePackages() is not a valid package object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getActivePackagesReturnsAtLeastTheFLOW3Package() {
		$availablePackages = $this->packageManager->getActivePackages();
		$this->assertTrue(array_key_exists('FLOW3', $availablePackages), 'The package "FLOW3" was not in the result of getActivePackages().');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $availablePackages['FLOW3'], 'The meta information about package "FLOW3" delivered by getActiveePackages() is not a valid package object.');
	}

	/**
	 * Checks the method getPackagePath()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackagePathReturnsTheCorrectPathOfTheTestPackage() {
		$mockPackage = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackage->expects($this->once())->method('getPackagePath')->will($this->returnValue('ThePackagePath'));

		$packagesReflection = new \ReflectionProperty($this->packageManager, 'packages');
		$packagesReflection->setAccessible(TRUE);
		$packagesReflection->setValue($this->packageManager, array('TestPackage' => $mockPackage));

		$actualPackagePath = $this->packageManager->getPackagePath('TestPackage');
		$this->assertEquals('ThePackagePath', $actualPackagePath);

		$this->packageManager->getPackagePath('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * Checks the method getPackageClassesPath()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackage
	 */
	public function getPackageClassesPathReturnsClassesPathOfTestPackage() {
		$mockPackage = $this->getMock('F3\FLOW3\Package\Package', array(), array(), '', FALSE);
		$mockPackage->expects($this->once())->method('getClassesPath')->will($this->returnValue('TheClassesPath'));

		$packagesReflection = new \ReflectionProperty($this->packageManager, 'packages');
		$packagesReflection->setAccessible(TRUE);
		$packagesReflection->setValue($this->packageManager, array('TestPackage' => $mockPackage));

		$actualPackageClassesPath = $this->packageManager->getPackageClassesPath('TestPackage');
		$this->assertEquals('TheClassesPath', $actualPackageClassesPath, 'getPackageClassesPath() did not return the correct path for package "TestPackage".');

		$this->packageManager->getPackageClassesPath('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$packageManager = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Package\Manager'), array('dummy'), array(), '', FALSE);
		$packageManager->_set('packageKeys', array('testpackage' => 'TestPackage'));

		$this->assertEquals('TestPackage', $packageManager->getCaseSensitivePackageKey('testpackage'));
	}
	
	/**
	 * FIXME do we test something like this?
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getLocalPackagesPathReturnsPathToLocalPackagesDirectory() {
		$packagesPath = $this->packageManager->getLocalPackagesPath();
		$this->assertEquals(\F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_PUBLIC . '../Packages/Local/') . '/'), $packagesPath);
	}	

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage() {
		$packageKey = 'YetAnotherTestPackage';

		$packageMetaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$packageMetaDataWriter->expects($this->once())->method('writePackageMetaData')->will($this->returnValue(TRUE));

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->injectPackageMetaDataWriter($packageMetaDataWriter);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$package = $packageManager->createPackage($packageKey, NULL, $packagesPath);

		$packagePath = $packagesPath . $packageKey . '/';
		$this->assertTrue(is_dir($packagePath), 'Path "' . $packagePath . '" should exist after createPackage');

		$this->assertType('F3\FLOW3\Package\PackageInterface', $package);
		$this->assertEquals($packageKey, $package->getPackageKey());

		$this->assertTrue($packageManager->isPackageAvailable($packageKey));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageWithMetaDataUsesMetaDataWriter() {
		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->atLeastOnce())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$metaData = $this->getMock('F3\FLOW3\Package\MetaData', array(), array('YetAnotherTestPackage'));

		$packageManager->createPackage('YetAnotherTestPackage', $metaData, $packagesPath);
	}

	/**
	 * Check create package creates the folders for
	 * classes, configuration, documentation, resources and tests
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesClassesConfigurationDocumentationResourcesAndTestsFolders() {
		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));
		
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';
		
		$packageManager->createPackage('YetAnotherTestPackage', NULL, $packagesPath);

		$packagePath = $packageManager->getPackagePath('YetAnotherTestPackage');
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_CLASSES), "Classes directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_CONFIGURATION), "Configuration directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_DOCUMENTATION), "Documentation directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_RESOURCES), "Resources directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_TESTS), "Tests directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_METADATA), "Metadata directory was not created");
	}

	/**
	 * Test creation of package with an invalid package key fails. 
	 * 
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageThrowsExceptionForInvalidPackageKey() {
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		try {
			$packageManager->createPackage('Invalid_Package_Key', NULL, $packagesPath);
		} catch(Exception $exception) {
			$this->assertEquals(1220722210, $exception->getCode(), 'createPackage() throwed an exception but with an unexpected error code.');
		}

		$this->assertFalse(is_dir($packagesPath . 'Invalid_Package_Key'), 'Package folder with invalid package key was created');
	}

	/**
	 * Test handling of duplicate package keys in package creation.
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageThrowsExceptionForExistingPackageKey() {
		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$packageManager->createPackage('TestPackage', NULL, $packagesPath);
		
		try {
			$packageManager->createPackage('TestPackage', NULL, $packagesPath);
		} catch(Exception $exception) {
			$this->assertEquals(1220722873, $exception->getCode(), 'createPackage() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('Create package didn\'t throw an exception for an existing package key');
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function createPackageCreatesDeactivatedPackage() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey);

		$this->assertFalse($packageManager->isPackageActive($packageKey));
	}


	/**
	 * Check package key validation accepts only valid keys
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPackageKeyValidationWorks() {
		$this->assertFalse($this->packageManager->isPackageKeyValid('Invalid_Package_Key'));
		$this->assertFalse($this->packageManager->isPackageKeyValid('invalidPackageKey'));
		$this->assertFalse($this->packageManager->isPackageKeyValid('1nvalidPackageKey'));
		$this->assertTrue($this->packageManager->isPackageKeyValid('ValidPackageKey'));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function deacivatePackageRemovesPackageFromActivePackages() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';

		$packageManager->createPackage($packageKey);
		$packageManager->activatePackage($packageKey);
		$packageManager->deactivatePackage($packageKey);

		$this->assertFalse($packageManager->isPackageActive($packageKey));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function activatePackagesAddsPackageToActivePackages() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey);
		$packageManager->activatePackage($packageKey);

		$this->assertTrue($packageManager->isPackageActive($packageKey));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageThrowsErrorIfPackageIsNotAvailable() {
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		try {
			$packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
		} catch (Exception $exception) {
			$this->assertEquals(1166543253, $exception->getCode(), 'deletePackage() threw an exception.');
			return;
		}
		$this->fail('deletePackage() did not throw an exception while asking to remove a non existent package.');
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageThrowsErrorIfPackageIsProtected() {
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		try {
			$packageManager->deletePackage('PHP6');
		} catch (Exception $exception) {
			$this->assertEquals(1220722120, $exception->getCode(), 'deletePackage() threw an exception.');
			return;
		}
		$this->fail('deletePackage() did not throw an exception while asking to remove a protected package.');
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageRemovesPackageFromAvailablePackages() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey);
		$packageManager->removePackage($packageKey);

		$this->assertFalse($packageManager->isPackageAvailable($packageKey));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageRemovesPackageFromActivePackages() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey);
		$packageManager->activatePackage($packageKey);
		$packageManager->removePackage($packageKey);

		$this->assertFalse($packageManager->isPackageActive($packageKey));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function deletePackageDeletesPackageDirectoryFromFilesystem() {
		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packageManager->injectObjectFactory($this->objectFactory);
		$packageManager->initialize();
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey, NULL, $packagesPath);
		$packagePath = $packageManager->getPackagePath($packageKey);

		$packageManager->deletePackage($packageKey);

		$this->assertFalse(file_exists($packagePath), $packagePath, "Package directory was not deleted.");
	}
}
?>