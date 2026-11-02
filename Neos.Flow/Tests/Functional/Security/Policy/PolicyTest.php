<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the security policy behavior
 */
final class PolicyTest extends FunctionalTestCase
{
    /**
     * @var boolean
     */
    protected $testableSecurityEnabled = true;

    #[Test]
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

        self::assertCount(2, $this->securityContext->getRoles());

        self::assertTrue($this->securityContext->hasRole('Neos.Flow:Everybody'), 'Everybody - hasRole()');
        self::assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

        self::assertTrue($this->securityContext->hasRole('Neos.Flow:Anonymous'), 'Anonymous - hasRole()');
        self::assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
    }
}
