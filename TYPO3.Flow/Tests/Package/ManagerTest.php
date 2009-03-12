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
		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();
		$this->assertEquals('TestPackage', $packageManager->getCaseSensitivePackageKey('testpackage'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$package = $packageManager->createPackage('YetAnotherTestPackage');

		$this->assertType('F3\FLOW3\Package\PackageInterface', $package);
		$this->assertEquals('YetAnotherTestPackage', $package->getPackageKey());

		$this->assertTrue($packageManager->isPackageAvailable('YetAnotherTestPackage'));
	}

	/**
	 * Check creating a package creates the mandatory Package.xml
	 * (this doesn't check the content of the file)
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesPackageMetaDataFile() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageManager->createPackage('YetAnotherTestPackage');

		$packagePath = $packageManager->getPackagePath('YetAnotherTestPackage');
		$this->assertTrue(is_file($packagePath . F3\FLOW3\Package\Package::DIRECTORY_METADATA . F3\FLOW3\Package\Package::FILENAME_PACKAGEINFO),
			'Mandatory Package.xml was created');
	}

	/**
	 * Check createPackage uses a meta writer to write the contents of the package meta to a file
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageWithMetaDatadataUsesMetaDataWriter() {
		$this->markTestIncomplete();

		$metaWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaWriter->expects($this->atLeastOnce())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$packageManager = new \F3\FLOW3\Package\Manager($metaWriter);
		$packageManager->initialize();

		$meta = $this->getMock('F3\FLOW3\Package\MetaData', array(), array('YetAnotherTestPackage'));

		$packageManager->createPackage('YetAnotherTestPackage', $meta);

		$packagePath = $packageManager->getPackagePath('YetAnotherTestPackage');
		$this->assertStringEqualsFile($packagePath . F3\FLOW3\Package\Package::DIRECTORY_METADATA . F3\FLOW3\Package\Package::FILENAME_PACKAGEINFO, '<package/>');
	}

	/**
	 * Check create package creates the folders for
	 * classes, configuration, documentation, resources and tests
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesClassesConfigurationDocumentationResourcesAndTestsFolders() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageManager->createPackage('YetAnotherTestPackage');

		$packagePath = $packageManager->getPackagePath('YetAnotherTestPackage');
		$this->assertTrue(is_dir($packagePath . F3\FLOW3\Package\Package::DIRECTORY_CLASSES));
		$this->assertTrue(is_dir($packagePath . F3\FLOW3\Package\Package::DIRECTORY_CONFIGURATION));
		$this->assertTrue(is_dir($packagePath . F3\FLOW3\Package\Package::DIRECTORY_DOCUMENTATION));
		$this->assertTrue(is_dir($packagePath . F3\FLOW3\Package\Package::DIRECTORY_RESOURCES));
		$this->assertTrue(is_dir($packagePath . F3\FLOW3\Package\Package::DIRECTORY_TESTS));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageThrowsExceptionForInvalidPackageKey() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		try {
			$packageManager->createPackage('Invalid_Package_Key');
		} catch(Exception $exception) {
			$this->assertEquals(1220722210, $exception->getCode(), 'createPackage() throwed an exception but with an unexpected error code.');
		}

		$this->assertFalse(is_dir(FLOW3_PATH_PACKAGES . 'Invalid_Package_Key'), 'Package folder with invalid package key was created');
	}

	/**
	 * Check handling of duplicate package keys in package creation
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageThrowsExceptionForExistingPackageKey() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		try {
			$packageManager->createPackage('TestPackage');
		} catch(Exception $exception) {
			$this->assertEquals(1220722873, $exception->getCode(), 'createPackage() throwed an exception but with an unexpected error code.');
			return;
		}
		$this->fail('Create package didnt throw an exception for an existing package key');
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
		$this->markTestIncomplete();

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
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		try {
			$packageManager->removePackage('PrettyUnlikelyThatThisPackageExists');
		} catch (Exception $exception) {
			$this->assertEquals(1166543253, $exception->getCode(), 'removePackage() throwed an exception.');
			return;
		}
		$this->fail('removePackage() did not throw an exception while asking for the path to a non existent package.');
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageThrowsErrorIfPackageIsProtected() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();
		try {
			$packageManager->removePackage('PHP6');
		} catch (Exception $exception) {
			$this->assertEquals(1220722120, $exception->getCode(), 'removePackage() throwed an exception.');
			return;
		}
		$this->fail('removePackage() did not throw an exception while asking for removing a protected package.');
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
	public function removePackageRemovesPackageDirectoryFromFilesystem() {
		$this->markTestIncomplete();

		$packageManager = new \F3\FLOW3\Package\Manager();
		$packageManager->initialize();

		$packageKey = 'YetAnotherTestPackage';
		$packageManager->createPackage($packageKey);
		$packagePath = $packageManager->getPackagePath($packageKey);

		$packageManager->removePackage($packageKey);

		$this->assertFalse(file_exists($packagePath), $packagePath);
	}
}
?>