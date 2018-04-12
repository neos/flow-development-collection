<?php
namespace Neos\Flow\Tests\Functional\Security\Policy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Tests\FunctionalTestCase;

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
            if ((string)$role === 'Neos.Flow:Everybody') {
                $hasEverybodyRole = true;
            }
            if ((string)$role === 'Neos.Flow:Anonymous') {
                $hasAnonymousRole = true;
            }
        }

        $this->assertEquals(2, count($this->securityContext->getRoles()));

        $this->assertTrue($this->securityContext->hasRole('Neos.Flow:Everybody'), 'Everybody - hasRole()');
        $this->assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

        $this->assertTrue($this->securityContext->hasRole('Neos.Flow:Anonymous'), 'Anonymous - hasRole()');
        $this->assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
    }
}
