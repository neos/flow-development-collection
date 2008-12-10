<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the Object Factory
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:\F3\FLOW3\Object\ManagerTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class FactoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * Checks if create() returns the expected class type
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createReturnsCorrectClassType() {
		$testObjectInstance = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$this->assertTrue($testObjectInstance instanceof \F3\TestPackage\BasicClass, 'Object instance is no instance of our basic test class!');
	}

	/**
	 * Checks if create() fails on non-existing objects
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createFailsOnNonExistentObject() {
		try {
			$this->objectManager->getObject('F3\TestPackage\ThisClassDoesNotExist');
		} catch (\F3\FLOW3\Object\Exception\UnknownObject $exception) {
			return;
		}
		$this->fail('create() did not throw an exception although it has been asked for a non-existent object.');
	}

	/**
	 * Checks if create() delivers a unique instance of the object with the default configuration
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createReturnsUniqueInstanceByDefault() {
		$firstInstance = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$secondInstance = $this->objectManager->getObject('F3\TestPackage\BasicClass');
		$this->assertSame($secondInstance, $firstInstance, 'create() did not return a truly unique instance when asked for a non-configured object.');
	}

	/**
	 * Checks if create() delivers a prototype of an object which is configured as a prototype
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createReturnsPrototypeInstanceIfConfigured() {
		$firstInstance = $this->objectManager->getObject('F3\TestPackage\PrototypeClass');
		$secondInstance = $this->objectManager->getObject('F3\TestPackage\PrototypeClass');
		$this->assertNotSame($secondInstance, $firstInstance, 'create() did not return a fresh prototype instance when asked for an object configured as prototype.');
	}

	/**
	 * Checks if create() delivers the correct class if the class name is different from the object name
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createReturnsCorrectClassIfDifferentFromObjectName() {
		$object = $this->objectManager->getObject('F3\TestPackage\ClassToBeReplaced');
		$this->assertTrue($object instanceof \F3\TestPackage\ReplacingClass, 'create() did not return a the replacing class.');
	}

	/**
	 * Checks if create() passes arguments to the constructor of an object class
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function createPassesArgumentsToObjectClassConstructor() {
		$object = $this->objectManager->getObject('F3\TestPackage\ClassWithOptionalConstructorArguments', 'test1', 'test2', 'test3');
		$checkSucceeded = (
			$object->argument1 == 'test1' &&
			$object->argument2 == 'test2' &&
			$object->argument3 == 'test3'
		);
		$this->assertTrue($checkSucceeded, 'create() did not instantiate the object with the specified constructor parameters.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function constructorArgumentsPassedToCreateAreNotAddedToRealObjectConfiguration() {
		$objectName = 'F3\TestPackage\ClassWithOptionalConstructorArguments';
		$objectConfiguration = $this->objectManager->getObjectConfiguration($objectName);
		$objectConfiguration->setConstructorArguments(array());

		$this->objectManager->setObjectConfiguration($objectConfiguration);

		$object1 = $this->objectManager->getObject($objectName, 'theFirstArgument');
		$this->assertEquals('theFirstArgument', $object1->argument1, 'The constructor argument has not been set.');

		$object2 = $this->objectManager->getObject($objectName);

		$this->assertEquals('', $object2->argument1, 'The constructor argument1 is still not empty although no argument was passed to create().');
		$this->assertEquals('', $object2->argument2, 'The constructor argument2 is still not empty although no argument was passed to create().');
		$this->assertEquals('', $object2->argument3, 'The constructor argument3 is still not empty although no argument was passed to create().');
	}
}
?>