<?php
namespace TYPO3\Flow\Tests\Functional\Session;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Test suite for the Session Management
 */
class SessionManagementTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function objectManagerAlwaysReturnsTheSameSessionIfInterfaceIsSpecified() {
		$session1 = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		$session2 = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		$this->assertSame($session1, $session2);
	}

	/**
	 * @test
	 */
	public function objectManagerAlwaysReturnsANewSessionInstanceIfClassNameIsSpecified() {
		$session1 = $this->objectManager->get('TYPO3\Flow\Session\Session');
		$session2 = $this->objectManager->get('TYPO3\Flow\Session\Session');
		$this->assertNotSame($session1, $session2);
	}

	/**
	 * Checks if getCurrentSessionSession() returns the one and only session which can also
	 * be retrieved through Dependency Injection using the SessionInterface.
	 *
	 * @test
	 */
	public function getCurrentSessionReturnsTheCurrentlyActiveSession() {
		$injectedSession = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		$sessionManager = $this->objectManager->get('TYPO3\Flow\Session\SessionManagerInterface');
		$otherInjectedSession = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');

		$retrievedSession = $sessionManager->getCurrentSession();
		$this->assertSame($injectedSession, $retrievedSession);
		$this->assertSame($otherInjectedSession, $retrievedSession);
	}

	/**
	 * Makes sure that the functional base testcase initializes an HTTP request and
	 * an HTTP response which can be retrieved from the special request handler by
	 * the session initialization in order to retrieve or set the session cookie.
	 *
	 * See bug #43590
	 *
	 * @test
	 */
	public function aSessionCanBeStartedInAFunctionalTest() {
		$session = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		$session->start();
			// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}
}
?>