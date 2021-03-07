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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Tests\Functional\Security\Fixtures\Controller\PolicyAnnotatedController;
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
     * @Flow\Inject
     * @var \Neos\Flow\Security\Policy\PolicyService
     */
    protected $policyService;

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

        self::assertEquals(2, count($this->securityContext->getRoles()));

        self::assertTrue($this->securityContext->hasRole('Neos.Flow:Everybody'), 'Everybody - hasRole()');
        self::assertTrue($hasEverybodyRole, 'Everybody - getRoles()');

        self::assertTrue($this->securityContext->hasRole('Neos.Flow:Anonymous'), 'Anonymous - hasRole()');
        self::assertTrue($hasAnonymousRole, 'Anonymous - getRoles()');
    }


    /**
     * @test
     */
    public function hasRoleReturnsTrueEvenIfNotConfigured()
    {
        self::assertTrue($this->policyService->hasRole('Neos.Flow:AnnotatedRole'));
    }

    /**
     * @test
     */
    public function annotatedRoleWithGrantPermissionIsGrantedPermission()
    {
        $annotatedRole = $this->policyService->getRole('Neos.Flow:AnnotatedRole');

        $className = PolicyAnnotatedController::class;
        $methodName = 'singleRoleWithGrantPermissionAction';
        $privilegeTarget = sprintf('Neos.Flow:PolicyAnnotated.%s.%s', str_replace('\\', '.', $className), $methodName);

        self::assertTrue($annotatedRole->getPrivilegeForTarget($privilegeTarget)->isGranted());
    }

    /**
     * @test
     */
    public function annotatedWithMultiplePoliciesGrantsPermissionAccordingly()
    {
        $deniedRole = $this->policyService->getRole('Neos.Flow:DeniedRole');
        $grantedRole = $this->policyService->getRole('Neos.Flow:GrantedRole');
        $abstainedRole = $this->policyService->getRole('Neos.Flow:AbstainedRole');

        $className = PolicyAnnotatedController::class;
        $methodName = 'multipleAnnotationsWithDifferentPermissionsAction';
        $privilegeTarget = sprintf('Neos.Flow:PolicyAnnotated.%s.%s', str_replace('\\', '.', $className), $methodName);

        self::assertTrue($deniedRole->getPrivilegeForTarget($privilegeTarget)->isDenied());
        self::assertTrue($grantedRole->getPrivilegeForTarget($privilegeTarget)->isGranted());
        self::assertTrue($abstainedRole->getPrivilegeForTarget($privilegeTarget)->isAbstained());
    }
}
