<?php
declare(ENCODING = 'utf-8');

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
 * Testcase for the default component manager
 *
 * @package		FLOW3
 * @version 	$Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Component_TransientObjectCacheTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_Component_TransientObjectCache
	 */
	protected $componentObjectCache;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->componentObjectCache = new F3_FLOW3_Component_TransientObjectCache();
	}

	/**
	 * Checks if getComponentObject() returns the object we have put into the cache previously
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function getComponentObjectReturnsSameObjectWhichHasBeenStoredByPutComponentObject() {
		$originalObject = new F3_FLOW3_Fixture_DummyClass();
		$this->componentObjectCache->putComponentObject('DummyComponent', $originalObject);
		$this->assertSame($originalObject, $this->componentObjectCache->getComponentObject('DummyComponent'), 'getComponentObject() did not return the object we stored in the object cache previously.');
	}

	/**
	 * Checks if putComponentObject() throws an exception if no component name or valid object is passed
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function putComponentObjectThrowsExceptionsOnInvalidArguments() {
		$someObject = new F3_FLOW3_Fixture_DummyClass();
		$exceptionsThrown = 0;
		try {
			$this->componentObjectCache->putComponentObject(NULL, $someObject);
		} catch (Exception $exception) {
			$exceptionsThrown ++;
		}
		try {
			$this->componentObjectCache->putComponentObject('DummyComponent', 'no object');
		} catch (Exception $exception) {
			$exceptionsThrown ++;
		}

		$this->assertEquals(2, $exceptionsThrown, 'putComponentObject() did not throw enough exceptions.');
	}

	/**
	 * Checks if removeComponentObject() really removes the instance from the cache
	 *
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function removeComponentObjectReallyRemovesTheObjectFromStorage() {
		$originalObject = new F3_FLOW3_Fixture_DummyClass();
		$this->componentObjectCache->putComponentObject('DummyComponent', $originalObject);
		$this->componentObjectCache->removeComponentObject('DummyComponent');
		$this->assertFalse($this->componentObjectCache->componentObjectExists('DummyComponent'), 'removeComponentObject() did not really remove the object.');
	}

	/**
	 * @test
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	public function componentObjectExistsReturnsCorrectResult() {
		$originalObject = new F3_FLOW3_Fixture_DummyClass();
		$this->assertFalse($this->componentObjectCache->componentObjectExists('DummyComponent'), 'componentObjectExists() did not return FALSE although the object should not exist yet.');
		$this->componentObjectCache->putComponentObject('DummyComponent', $originalObject);
		$this->assertTrue($this->componentObjectCache->componentObjectExists('DummyComponent'), 'componentObjectExists() did not return TRUE although the object should exist.');
	}
}
?>