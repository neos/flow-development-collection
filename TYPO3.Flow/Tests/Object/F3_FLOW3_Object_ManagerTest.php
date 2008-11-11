<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Object::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the Object Manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Object::ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if getContext() returns the "Development" context if nothing else has been defined.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContextReturnsDefaultContext() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$objectManager = new F3::FLOW3::Object::Manager($mockReflectionService);
		$this->assertEquals('Development', $objectManager->getContext(), 'getContext() did not return "Development".');
	}

	/**
	 * Checks if setting and retrieving the context delivers the expected results
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContextBasicallyWorks() {
		$mockReflectionService = $this->getMock('F3::FLOW3::Reflection::Service');
		$objectManager = new F3::FLOW3::Object::Manager($mockReflectionService);
		$objectManager->setContext('halululu');
		$this->assertEquals('halululu', $objectManager->getContext(), 'getContext() did not return the context we set.');

	}

	/**
	 * Checks if registerObject() can register valid and unspectactular classes
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectCanRegisterNormalClasses() {
		$reflectionService = $this->objectManager->getObject('F3::FLOW3::Reflection::Service');
		$objectManager = new F3::FLOW3::Object::Manager($reflectionService);
		$this->assertEquals($objectManager->isObjectRegistered('F3::TestPackage::BasicClass'), FALSE, 'isObjectRegistered() did not return FALSE although object is not yet registered.');
		$objectManager->registerObject('F3::TestPackage::BasicClass');
		$this->assertTrue($objectManager->isObjectRegistered('F3::TestPackage::BasicClass'), 'isObjectRegistered() did not return TRUE although object has been registered.');
	}

	/**
	 * Checks if registerObject() can register classes in sub directories to the
	 * Classes/ directory.
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectCanRegisterClassesInSubDirectories() {
		$reflectionService = $this->objectManager->getObject('F3::FLOW3::Reflection::Service');
		$objectManager = new F3::FLOW3::Object::Manager($reflectionService);
		$this->assertFalse($objectManager->isObjectRegistered('F3::TestPackage::BasicClass'), 'isObjectRegistered() did not return FALSE although object is not yet registered.');
		$this->assertFalse($objectManager->isObjectRegistered('F3::TestPackage::SubDirectory::ClassInSubDirectory'), 'isObjectRegistered() did not return FALSE although object is not yet registered.');
		$objectManager->registerObject('F3::TestPackage::SubDirectory::ClassInSubDirectory');
		$this->assertTrue($this->objectManager->isObjectRegistered('F3::TestPackage::SubDirectory::ClassInSubDirectory'), 'isObjectRegistered() did not return TRUE although object has been registered.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectRejectsAbstractClasses() {
		$reflectionService = $this->objectManager->getObject('F3::FLOW3::Reflection::Service');
		$objectManager = new F3::FLOW3::Object::Manager($reflectionService);
		$this->assertFalse($objectManager->isObjectRegistered('F3::TestPackage::AbstractClass'), 'isObjectRegistered() did not return FALSE although the abstract class is not yet registered.');
		try {
			$objectManager->registerObject('F3::TestPackage::AbstractClass');
			$this->fail('The object manager did not reject the registration of an abstract class.');
		} catch (F3::FLOW3::Object::Exception::InvalidClass $exception) {
			return;
		}
		$this->fail('The object manager did not throw the right kind of exception.');
	}

	/**
	 * Checks if unregisterObject() unregisters objects
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function unregisterObjectThrowsExceptionForNonExistentObject() {
		try {
			$this->objectManager->unregisterObject('F3::NonExistentPackage::NonExistentClass');
		} catch (F3::FLOW3::Object::Exception::UnknownObject $exception) {
			return;
		}
		$this->fail('unregisterObject() did not throw an exception while unregistering a non existent or not registered object.');
	}

	/**
	 * Checks if unregisterObject() unregisters objects
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function unregisterObjectReallyUnregistersObjects() {
		$this->assertEquals($this->objectManager->isObjectRegistered('F3::TestPackage::BasicClass'), TRUE, 'F3::TestPackage::BasicClass is not a registered object.');
		$this->objectManager->unregisterObject('F3::TestPackage::BasicClass');
		$this->assertEquals($this->objectManager->isObjectRegistered('F3::TestPackage::BasicClass'), FALSE, 'isObjectRegistered() did not return FALSE although object should not be registered anymore.');
	}

	/**
	 * Checks if setObjectConfigurations() throws an exception if the configuration is no valid configuration object
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurationsThrowsExceptionForNonArray() {
		try {
			$this->objectManager->setObjectconfigurations(array('F3::TestPackage::BasicClass' => 'Some string'));
		} catch (::Exception $exception) {
			$this->assertEquals(1167826954, $exception->getCode(), 'setObjectConfigurations() throwed an exception but returned the wrong error code.');
			return;
		}
		$this->fail('setObjectConfigurations() accepted an invalid configuration object without throwing an exception.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurationsRegistersYetUnknownObjectsFromObjectConfiguration() {
		$objectConfigurations = $this->objectManager->getObjectConfigurations();
		$objectConfigurations['F3::TestPackage::SomeNonExistingObject'] = new F3::FLOW3::Object::Configuration('F3::TestPackage::SomeNonExistingObject', __CLASS__);
		$this->objectManager->setObjectConfigurations($objectConfigurations);
		$this->assertTrue($this->objectManager->isObjectRegistered('F3::TestPackage::SomeNonExistingObject'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfigurationReturnsCloneOfConfiguration() {
		$configuration1 = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$configuration2 = $this->objectManager->getObjectConfiguration('F3::TestPackage::BasicClass');
		$this->assertNotSame($configuration1, $configuration2, 'getObjectConfiguration() did not return a clone but the same object configuration!');
	}

	/**
	 * Checks if the object manager registers object types (interfaces) correctly
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectTypeBasicallyWorks() {
		$implementation = $this->objectManager->getObject('F3::TestPackage::SomeInterface');
		$this->assertType('F3::TestPackage::SomeImplementation', $implementation, 'The object of object type ...SomeInterface is not implemented by ...SomeImplementation!');
	}

	/**
	 * Checks if the class name of an object can be really set
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectClassNameWorksAsExpected() {
		$objectName = 'F3::TestPackage::BasicClass';
		$this->objectManager->setObjectClassName($objectName, 'F3::TestPackage::ReplacingClass');
		$object = $this->objectManager->getObject($objectName);

		$this->assertEquals('F3::TestPackage::ReplacingClass', get_class($object), 'The object was not of the expected class.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredObjectsReturnsArrayOfMixedCaseAndLowerCaseObjectNames() {
		$registeredObjects = $this->objectManager->getRegisteredObjects();
		$this->assertTrue(is_array($registeredObjects), 'The result is not an array.');
		foreach ($registeredObjects as $mixedCase => $lowerCase) {
			$this->assertTrue(strlen($mixedCase) > 0, 'The object name was an empty string.');
			$this->assertTrue(strtolower($mixedCase) == $lowerCase, 'The key and value were not equal after strtolower().');
		}
	}
}
?>