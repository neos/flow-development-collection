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

require_once(FLOW3_PATH_PACKAGES . 'FLOW3/Tests/Fixtures/F3_FLOW3_Fixture_DummyClass.php');

/**
 * @package FLOW3
 * @version $Id:F3::FLOW3::Object::TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

/**
 * Testcase for the default object manager
 *
 * @package FLOW3
 * @version $Id:F3::FLOW3::Object::TransientRegistryTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TransientRegistryTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::Object::TransientRegistry
	 */
	protected $objectRegistry;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->objectRegistry = new F3::FLOW3::Object::TransientRegistry();
	}

	/**
	 * Checks if getObject() returns the object we have put into the cache previously
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getObjectReturnsSameObjectWhichHasBeenStoredByPutObject() {
		$originalObject = new F3::FLOW3::Fixture::DummyClass();
		$this->objectRegistry->putObject('DummyObject', $originalObject);
		$this->assertSame($originalObject, $this->objectRegistry->getObject('DummyObject'), 'getObject() did not return the object we stored in the object registry previously.');
	}

	/**
	 * Checks if putObject() throws an exception if no object name or valid object is passed
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function putObjectThrowsExceptionsOnInvalidArguments() {
		$someObject = new F3::FLOW3::Fixture::DummyClass();
		$exceptionsThrown = 0;
		try {
			$this->objectRegistry->putObject(NULL, $someObject);
		} catch (::Exception $exception) {
			$exceptionsThrown ++;
		}
		try {
			$this->objectRegistry->putObject('DummyObject', 'no object');
		} catch (::Exception $exception) {
			$exceptionsThrown ++;
		}

		$this->assertEquals(2, $exceptionsThrown, 'putObject() did not throw enough exceptions.');
	}

	/**
	 * Checks if removeObject() really removes the instance from the cache
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function removeObjectReallyRemovesTheObjectFromStorage() {
		$originalObject = new F3::FLOW3::Fixture::DummyClass();
		$this->objectRegistry->putObject('DummyObject', $originalObject);
		$this->objectRegistry->removeObject('DummyObject');
		$this->assertFalse($this->objectRegistry->objectExists('DummyObject'), 'removeObject() did not really remove the object.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function objectExistsReturnsCorrectResult() {
		$originalObject = new F3::FLOW3::Fixture::DummyClass();
		$this->assertFalse($this->objectRegistry->objectExists('DummyObject'), 'objectExists() did not return FALSE although the object should not exist yet.');
		$this->objectRegistry->putObject('DummyObject', $originalObject);
		$this->assertTrue($this->objectRegistry->objectExists('DummyObject'), 'objectExists() did not return TRUE although the object should exist.');
	}
}
?>