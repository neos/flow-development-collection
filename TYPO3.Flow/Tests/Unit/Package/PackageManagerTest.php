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

require_once('vfs/vfsStream.php');

/**
 * Testcase for the default package manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PackageManagerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Package\PackageManager
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

		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration', 'saveConfiguration'), array(), '', FALSE);

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');

		$this->packageManager = new \F3\FLOW3\Package\PackageManager();
		$this->packageManager->injectObjectManager($mockObjectManager);
		$this->packageManager->injectConfigurationManager($mockConfigurationManager);
		$this->packageManager->initialize();
	}

	/**
	 * Tests the method getPackage()
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackageException
	 */
	public function getPackageReturnsPackagesAndThrowsExcpetions() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$package = $this->packageManager->getPackage('FLOW3');
		$this->assertType('F3\FLOW3\Package\PackageInterface', $package, 'The result of getPackage() was no valid package object.');
		$this->packageManager->getPackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitivePackageKeyReturnsTheUpperCamelCaseVersionOfAGivenPackageKeyIfThePackageIsRegistered() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('dummy'), array(), '', FALSE);
		$packageManager->_set('packageKeys', array('testpackage' => 'TestPackage'));

		$this->assertEquals('TestPackage', $packageManager->getCaseSensitivePackageKey('testpackage'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesPackageFolderAndReturnsPackage() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		
		$packageKey = 'YetAnotherTestPackage';
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$packageMetaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$packageMetaDataWriter->expects($this->once())->method('writePackageMetaData')->will($this->returnValue(TRUE));

		$this->packageManager->injectPackageMetaDataWriter($packageMetaDataWriter);

		$this->packageManager->initialize();

		$actualPackage = $this->packageManager->createPackage($packageKey, NULL, $packagesPath);

		$packagePath = $packagesPath . $packageKey . '/';
		$this->assertTrue(is_dir($packagePath), 'Path "' . $packagePath . '" should exist after createPackage');

		$this->assertSame($mockPackage, $actualPackage);
		$this->assertTrue($this->packageManager->isPackageAvailable($packageKey));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageWithMetaDataUsesMetaDataWriter() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->atLeastOnce())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$this->packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$metaData = $this->getMock('F3\FLOW3\Package\MetaData', array(), array('YetAnotherTestPackage'));

		$this->packageManager->createPackage('YetAnotherTestPackage', $metaData, $packagesPath);
	}

	/**
	 * Check create package creates the folders for
	 * classes, configuration, documentation, resources and tests
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageCreatesClassesConfigurationDocumentationResourcesAndTestsFolders() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$this->packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$package = $this->packageManager->createPackage('YetAnotherTestPackage', NULL, $packagesPath);

		$packagePath = $package->getPackagePath('YetAnotherTestPackage');
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_CLASSES), "Classes directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_CONFIGURATION), "Configuration directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_DOCUMENTATION), "Documentation directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_RESOURCES), "Resources directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_TESTS_UNIT), "Tests/Unit directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_TESTS_INTEGRATION), "Tests/Integration directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_TESTS_SYSTEM), "Tests/System directory was not created");
		$this->assertTrue(is_dir($packagePath . \F3\FLOW3\Package\Package::DIRECTORY_METADATA), "Metadata directory was not created");
	}

	/**
	 * Test creation of package with an invalid package key fails.
	 *
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function createPackageThrowsExceptionForInvalidPackageKey() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packagesPath = \vfsStream::url('testDirectory') . '/';

		try {
			$this->packageManager->createPackage('Invalid*PackageKey', NULL, $packagesPath);
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
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$this->packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$this->packageManager->createPackage('TestPackage', NULL, $packagesPath);

		try {
			$this->packageManager->createPackage('TestPackage', NULL, $packagesPath);
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
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageManager = new \F3\FLOW3\Package\PackageManager();
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
		$this->assertFalse($this->packageManager->isPackageKeyValid('invalidPackageKey'));
		$this->assertFalse($this->packageManager->isPackageKeyValid('invalid PackageKey'));
		$this->assertFalse($this->packageManager->isPackageKeyValid('1nvalidPackageKey'));
		$this->assertTrue($this->packageManager->isPackageKeyValid('ValidPackageKey'));
		$this->assertTrue($this->packageManager->isPackageKeyValid('Valid_PackageKey'));
		$this->assertTrue($this->packageManager->isPackageKeyValid('ValidPackage123Key'));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function deactivatePackageRemovesPackageFromActivePackagesAndUpdatesPackageStatesConfiguration() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue('YetAnotherTestPackage'));

		$configurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration', 'setConfiguration', 'saveConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->once())
			->method('getConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES)
			->will($this->returnValue(array('YetAnotherTestPackage' => array('state' => 'active', 'foo' => 'bar'))));
		$configurationManager->expects($this->once())
			->method('setConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES, array('YetAnotherTestPackage' => array('state' => 'inactive', 'foo' => 'bar')));
		$configurationManager->expects($this->once())
			->method('saveConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES);

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->injectConfigurationManager($configurationManager);
		$packageManager->_set('packages', array('YetAnotherTestPackage' => $mockPackage));
		$packageManager->_set('activePackages', array('YetAnotherTestPackage' => $mockPackage));
		$packageManager->deactivatePackage('YetAnotherTestPackage');

		$this->assertFalse($packageManager->isPackageActive('YetAnotherTestPackage'));
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function activatePackagesAddsPackageToActivePackagesAndUpdatesPackageStatesConfiguration() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue('YetAnotherTestPackage'));

		$configurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration', 'setConfiguration', 'saveConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->once())
			->method('getConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES)
			->will($this->returnValue(array('YetAnotherTestPackage' => array('foo' => 'bar'))));
		$configurationManager->expects($this->once())
			->method('setConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES, array('YetAnotherTestPackage' => array('state' => 'active', 'foo' => 'bar')));
		$configurationManager->expects($this->once())
			->method('saveConfiguration')
			->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES);

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->injectConfigurationManager($configurationManager);
		$packageManager->_set('packages', array('YetAnotherTestPackage' => $mockPackage));
		$packageManager->_set('activePackages', array());
		$packageManager->activatePackage('YetAnotherTestPackage');

		$this->assertTrue($packageManager->isPackageActive('YetAnotherTestPackage'));
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\InvalidPackageStateException
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function activatePackageThrowsExceptionAndDoesntUpdateConfigurationForAlreadyActivePackage() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$mockPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue('YetAnotherTestPackage'));

		$configurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration', 'setConfiguration', 'save'), array(), '', FALSE);
		$configurationManager->expects($this->never())->method('setConfiguration');
		$configurationManager->expects($this->never())->method('saveConfiguration');

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->injectConfigurationManager($configurationManager);
		$packageManager->_set('packages', array('YetAnotherTestPackage' => $mockPackage));
		$packageManager->_set('activePackages', array('YetAnotherTestPackage' => $mockPackage));
		$packageManager->activatePackage('YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackageException
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function activatePackageThrowsExceptionForUnavailablePackage() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('dummy'));
		$packageManager->activatePackage('YetAnotherTestPackage');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\UnknownPackageException
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageThrowsErrorIfPackageIsNotAvailable() {
		$this->packageManager->deletePackage('PrettyUnlikelyThatThisPackageExists');
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\Package\Exception\ProtectedPackageKeyException
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageThrowsErrorIfPackageIsProtected() {
		$this->packageManager->deletePackage('FLOW3');
	}

	/**
	 * @test
	 * @author Thomas Hempel <thomas@typo3.org>
	 */
	public function removePackageRemovesPackageFromAvailablePackages() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageManager = new \F3\FLOW3\Package\PackageManager();
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
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageManager = new \F3\FLOW3\Package\PackageManager();
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
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$metaDataWriter = $this->getMock('F3\FLOW3\Package\MetaData\WriterInterface');
		$metaDataWriter->expects($this->any())
			->method('writePackageMetaData')
			->will($this->returnValue('<package/>'));

		$this->packageManager->injectPackageMetaDataWriter($metaDataWriter);
		$packagesPath = \vfsStream::url('testDirectory') . '/';

		$packageKey = 'YetAnotherTestPackage';
		$package = $this->packageManager->createPackage($packageKey, NULL, $packagesPath);
		$packagePath = $package->getPackagePath($packageKey);

		$this->packageManager->deletePackage($packageKey);

		$this->assertFalse(file_exists($packagePath), $packagePath, "Package directory was not deleted.");
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function scanAvailablePackagesUsesObjectManagerToCreateNewPackages() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function initializeUsesPackageStatesConfigurationForActivePackages() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');

		$packageStatesConfiguration = array(
			'FLOW3' => array(
				'state' => 'active'
			)
		);

		$configurationManager = $this->getMock('F3\FLOW3\Configuration\ConfigurationManager', array('getConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->once())->method('getConfiguration')->with(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGESTATES)->will($this->returnValue($packageStatesConfiguration));

		$mockFLOW3Package = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockFLOW3Package->expects($this->any())->method('getPackageKey')->will($this->returnValue('FLOW3'));
		$mockTestPackage = $this->getMock('F3\FLOW3\Package\PackageInterface');
		$mockTestPackage->expects($this->any())->method('getPackageKey')->will($this->returnValue('Test'));

		$packageManager = $this->getAccessibleMock('F3\FLOW3\Package\PackageManager', array('scanAvailablePackages'));
		$packageManager->_set('packages', array('FLOW3' => $mockFLOW3Package, 'Test' => $mockTestPackage));
		$packageManager->injectConfigurationManager($configurationManager);
		$packageManager->initialize();

		$activePackages = $packageManager->getActivePackages();
		$this->assertEquals(array('FLOW3' => $mockFLOW3Package), $activePackages);
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function protectedPackagesAreAlwaysActive() {
		$this->markTestSkipped('Rewrite tests for Package Manager!');
	}
}
?>