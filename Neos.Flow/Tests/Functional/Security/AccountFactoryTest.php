<?php
namespace Neos\Flow\Tests\Functional\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\AccountFactory;
use Neos\Flow\Tests\FunctionalTestCase;

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
     * @var \Neos\Flow\Security\Policy\PolicyService
     */
    protected $policyService;

    /**
     * @test
     */
    public function createAccountWithPasswordCreatesANewAccountWithTheGivenIdentifierPasswordRolesAndProviderName()
    {
        $factory = new AccountFactory();

        $actualAccount = $factory->createAccountWithPassword('username', 'password', ['Neos.Flow:Administrator', 'Neos.Flow:Customer'], 'OtherProvider');

        $this->assertEquals('username', $actualAccount->getAccountIdentifier());
        $this->assertEquals('OtherProvider', $actualAccount->getAuthenticationProviderName());

        $this->assertTrue($actualAccount->hasRole($this->policyService->getRole('Neos.Flow:Administrator')));
        $this->assertTrue($actualAccount->hasRole($this->policyService->getRole('Neos.Flow:Customer')));
    }
}
