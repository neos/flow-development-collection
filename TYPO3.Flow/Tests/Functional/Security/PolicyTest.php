<?php
namespace TYPO3\FLOW3\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Security\Policy\Role;
use TYPO3\FLOW3\Security\Authentication\TokenInterface;

/**
 * Testcase for the security policy behavior
 */
class PolicyTest extends \TYPO3\FLOW3\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @test
	 */
	public function nonAuthenticatedUsersHaveTheEverybodyAndAnonymousRole() {
#		$this->testingProvider->setAuthenticationStatus(TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$hasEverybodyRole = FALSE;
		$hasAnonymousRole = FALSE;

		foreach ($this->securityContext->getRoles() as $role) {
			if ((string)$role === 'Everybody') {
				$hasEverybodyRole = TRUE;
			}
			if ((string)$role === 'Anonymous') {
				$hasAnonymousRole = TRUE;
			}
		}

		$this->assertEquals(2, count($this->securityContext->getRoles()));

		$this->assertTrue($this->securityContext->hasRole('Everybody'), 'Everybody - hasRole()');
		$this->assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

		$this->assertTrue($this->securityContext->hasRole('Anonymous'), 'Anonymous - hasRole()');
		$this->assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
	}
}
?>