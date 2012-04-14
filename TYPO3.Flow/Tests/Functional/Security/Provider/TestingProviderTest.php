<?php
namespace TYPO3\FLOW3\Tests\Functional\Security\Provider;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
class TestingProviderTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @test
	 */
	public function authenticateStartsASession() {
		$session = $this->objectManager->get('TYPO3\FLOW3\Session\SessionInterface');
		$this->assertFalse($session->isStarted());

		$this->authenticateRoles(array('Administrator'));

		$this->assertTrue($session->isStarted());
	}
}
?>