<?php
namespace TYPO3\Flow\Tests\Functional\Security;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\AccountFactory;
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the account factory
 *
 */
class AccountFactoryTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Security\Policy\PolicyService
     */
    protected $policyService;

    /**
     * @test
     */
    public function createAccountWithPasswordCreatesANewAccountWithTheGivenIdentifierPasswordRolesAndProviderName()
    {
        $factory = new AccountFactory();

        $actualAccount = $factory->createAccountWithPassword('username', 'password', ['TYPO3.Flow:Administrator', 'TYPO3.Flow:Customer'], 'OtherProvider');

        $this->assertEquals('username', $actualAccount->getAccountIdentifier());
        $this->assertEquals('OtherProvider', $actualAccount->getAuthenticationProviderName());

        $this->assertTrue($actualAccount->hasRole($this->policyService->getRole('TYPO3.Flow:Administrator')));
        $this->assertTrue($actualAccount->hasRole($this->policyService->getRole('TYPO3.Flow:Customer')));
    }
}
