<?php
namespace TYPO3\Flow\Tests\Functional\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Testcase for the account factory
 *
 */
class AccountFactoryTest extends \TYPO3\Flow\Tests\FunctionalTestCase {

	/**
	 * @var boolean
	 */
	protected $testableSecurityEnabled = TRUE;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Policy\PolicyService
	 */
	protected $policyService;

	/**
	 * @test
	 */
	public function createAccountWithPasswordCreatesANewAccountWithTheGivenIdentifierPasswordRolesAndProviderName() {
		$factory = new \TYPO3\Flow\Security\AccountFactory();

		$actualAccount = $factory->createAccountWithPassword('username', 'password', array('TYPO3.Flow:Administrator', 'TYPO3.Flow:Customer'), 'OtherProvider');

		$this->assertEquals('username', $actualAccount->getAccountIdentifier());
		$this->assertEquals('OtherProvider', $actualAccount->getAuthenticationProviderName());

		$this->assertTrue($actualAccount->hasRole($this->policyService->getRole('TYPO3.Flow:Administrator')));
		$this->assertTrue($actualAccount->hasRole($this->policyService->getRole('TYPO3.Flow:Customer')));
	}
}
