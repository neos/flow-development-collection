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
use Neos\Flow\Security\Authorization\Privilege\PrivilegeTarget;
use Neos\Flow\Tests\Functional\Security\Fixtures\Controller\PrivilegeAnnotatedController;
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
    public function annotatedPrivilegeWithGrantedRolesGrantsPermission()
    {
        $annotatedRole = $this->policyService->getRole('Neos.Flow:PrivilegeAnnotation.Role1');

        $className = PrivilegeAnnotatedController::class;
        $methodName = 'actionWithGrantedRolesAction';
        $privilegeId = sprintf('%s:Privilege.%s', 'Neos.Flow', md5($className.'->'.$methodName));

        self::assertTrue($annotatedRole->getPrivilegeForTarget($privilegeId)->isGranted());
    }

    /**
     * @test
     */
    public function annotatedPrivilegeWithGrantedRolesAndIdGrantsPermissionToPrivilegeId()
    {
        $annotatedRole = $this->policyService->getRole('Neos.Flow:PrivilegeAnnotation.Role3');
        self::assertTrue($annotatedRole->getPrivilegeForTarget('Neos.Flow:Granted.Roles.Privilege')->isGranted());
    }

    /**
     * @test
     */
    public function annotatedPrivilegeWithIdConfiguresPrivilege()
    {
        $privilegeTarget = $this->policyService->getPrivilegeTargetByIdentifier('Neos.Flow:Privilege.From.Annotation');
        self::assertInstanceOf(PrivilegeTarget::class, get_class($privilegeTarget));
        self::assertEquals(
            $privilegeTarget->getMatcher(),
            sprintf('method(%s->%s())', PrivilegeAnnotatedController::class, 'actionWithPrivilegeIdAndNoGrantedRoles')
        );
    }
}
