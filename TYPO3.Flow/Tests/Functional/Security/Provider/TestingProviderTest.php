<?php
namespace TYPO3\Flow\Tests\Functional\Security\Provider;

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
 * Testcase for the TestingProvider
 */
class TestingProviderTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @test
	 */
	public function authenticateStartsASession() {
		$session = $this->objectManager->get('TYPO3\Flow\Session\SessionInterface');
		$this->assertFalse($session->isStarted(), 'Session has been started already before authentication!');

		$this->authenticateRoles(array('Administrator'));

		$this->assertTrue($session->isStarted(), 'No session was started with authentication');
	}
}
?>