<?php
namespace TYPO3\Flow\Tests\Functional\Security\Policy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Testcase for the security policy behavior
 */
class PolicyTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    /**
     * @test
     */
    public function nonAuthenticatedUsersHaveTheEverybodyAndAnonymousRole()
    {
        $hasEverybodyRole = false;
        $hasAnonymousRole = false;

        foreach ($this->securityContext->getRoles() as $role) {
            if ((string)$role === 'TYPO3.Flow:Everybody') {
                $hasEverybodyRole = true;
            }
            if ((string)$role === 'TYPO3.Flow:Anonymous') {
                $hasAnonymousRole = true;
            }
        }

        $this->assertEquals(2, count($this->securityContext->getRoles()));

        $this->assertTrue($this->securityContext->hasRole('TYPO3.Flow:Everybody'), 'Everybody - hasRole()');
        $this->assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

        $this->assertTrue($this->securityContext->hasRole('TYPO3.Flow:Anonymous'), 'Anonymous - hasRole()');
        $this->assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
    }
}
