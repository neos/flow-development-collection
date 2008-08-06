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

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the session based on PHP session functionality
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Session_PHPTest extends F3_Testing_BaseTestCase {

	/**
	 * Sets the php.ini settings needed for the tests
	 * As we can't change the cookies while running the tests we have to disable them
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		$this->sessionUseCookie = ini_get('session.use_cookies');
		$this->sessionCacheLimiter = ini_get('session.cache_limiter');
		ini_set('session.use_cookies', 0);
		ini_set('session.cache_limiter', '');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function startingASessionWorks() {
		$session = new F3_FLOW3_Session_PHP();
		$session->start();

		$this->assertTrue($session->getID() != '', 'No session ID has been created on startSession()');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function anExceptionIsThrownIfTheSessionIsNotInitialized() {
		$session = new F3_FLOW3_Session_PHP();

		try {
			$session->getData('someKey');
			$this->fail('No exception has been thrown, but session has not been initialized');
		} catch (F3_FLOW3_Session_Exception_SessionNotStarted $e) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function anExceptionIsThrownIfSessionAutoStartIsEnabled() {
		ini_set('session.auto_start', 1);

		try {
			$session = new F3_FLOW3_Session_PHP();
			$this->fail('No exception has been thrown, but session.auto_start was enabled in php.ini');
		} catch (F3_FLOW3_Session_Exception_SessionAutostartIsEnabled $e) {}

		ini_set('session.auto_start', 0);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function putDataThrowsAnExceptionIfTheSessionIsNotInitialized() {
		$session = new F3_FLOW3_Session_PHP();

		try {
			$session->putData('someKey', 'some data');
			$this->fail('No exception has been thrown, but session has not been initialized');
		} catch (F3_FLOW3_Session_Exception_SessionNotStarted $e) {}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function nullIsReturnedIfANotExistingKeyIsRequested() {
		$session = new F3_FLOW3_Session_PHP();
		$session->start();

		$this->assertEquals(NULL, $session->getData('someNotExistingKey'), 'The session did not return NULL while requesting a non existing key');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function storingDataWithASpecificKeyInTheSessionWorks() {
		$session = new F3_FLOW3_Session_PHP();

		$session->start();
		$session->putData('someKey', 'some nice data');
		$session->close();

		$restoredSession = new F3_FLOW3_Session_PHP();
		$restoredSession->start();

		$this->assertEquals('some nice data', $restoredSession->getData('someKey'), 'The session data was not restored correctly');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function nestedObjectsAreRestoredCorrectlyFromTheSession() {
		$nestedObject = new F3_TestPackage_BasicClass();
		$nestedObject->setFirstDependency(new F3_TestPackage_BasicClass());
		$secondNestedObject = new F3_TestPackage_BasicClass();
		$secondNestedObject->setFirstDependency($nestedObject);

		$session = new F3_FLOW3_Session_PHP();
		$session->start();
		$session->putData('nestedObjects', $secondNestedObject);
		$session->close();

		$restoredSession = new F3_FLOW3_Session_PHP();
		$restoredSession->start();

		$this->assertEquals($secondNestedObject, $restoredSession->getData('nestedObjects'), 'The object structure has not been saved and restored correctly from the session');
	}

	/**
	 * Restores the php.ini configuration and destroys all session data in the PHP session
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function tearDown() {
		$session = new F3_FLOW3_Session_PHP();
		$session->start();
		$session->destroy();
		ini_set('session.use_cookies', $this->sessionUseCookie);
		ini_set('session.cache_limiter', $this->sessionCacheLimiter);
	}
}

?>