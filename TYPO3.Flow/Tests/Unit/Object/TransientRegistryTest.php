<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
require_once(__DIR__ . '/../Fixtures/DummyClass.php');

/**
 * Testcase for the transient object registry
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TransientRegistryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\TransientRegistry
	 */
	protected $objectRegistry;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->objectRegistry = new \F3\FLOW3\Object\TransientRegistry();
	}

	/**
	 * Checks if getObject() returns the object we have put into the cache previously
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getObjectReturnsSameObjectWhichHasBeenStoredByPutObject() {
		$originalObject = new \F3\FLOW3\Fixture\DummyClass();
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
		$someObject = new \F3\FLOW3\Fixture\DummyClass();
		$exceptionsThrown = 0;
		try {
			$this->objectRegistry->putObject(NULL, $someObject);
		} catch (\Exception $exception) {
			$exceptionsThrown ++;
		}
		try {
			$this->objectRegistry->putObject('DummyObject', 'no object');
		} catch (\Exception $exception) {
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
		$originalObject = new \F3\FLOW3\Fixture\DummyClass();
		$this->objectRegistry->putObject('DummyObject', $originalObject);
		$this->objectRegistry->removeObject('DummyObject');
		$this->assertFalse($this->objectRegistry->objectExists('DummyObject'), 'removeObject() did not really remove the object.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function objectExistsReturnsCorrectResult() {
		$originalObject = new \F3\FLOW3\Fixture\DummyClass();
		$this->assertFalse($this->objectRegistry->objectExists('DummyObject'), 'objectExists() did not return FALSE although the object should not exist yet.');
		$this->objectRegistry->putObject('DummyObject', $originalObject);
		$this->assertTrue($this->objectRegistry->objectExists('DummyObject'), 'objectExists() did not return TRUE although the object should exist.');
	}
}
?>