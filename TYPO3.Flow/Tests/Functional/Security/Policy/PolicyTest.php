<?php
namespace TYPO3\Flow\Tests\Functional\Security\Policy;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the security policy behavior
 */
class PolicyTest extends \TYPO3\Flow\Tests\FunctionalTestCase
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
            if ((string)$role === 'Everybody') {
                $hasEverybodyRole = true;
            }
            if ((string)$role === 'Anonymous') {
                $hasAnonymousRole = true;
            }
        }

        $this->assertEquals(2, count($this->securityContext->getRoles()));

        $this->assertTrue($this->securityContext->hasRole('Everybody'), 'Everybody - hasRole()');
        $this->assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

        $this->assertTrue($this->securityContext->hasRole('Anonymous'), 'Anonymous - hasRole()');
        $this->assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
    }
}
