<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Authorization;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\JoinPoint;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\AbstractPrivilege;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use Neos\Flow\Security\Authorization\PrivilegeManager;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\Role;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Testcase for the privilege manager
 *
 */
final class PrivilegeManagerTest extends UnitTestCase
{
    /**
     * @var Context|MockObject
     */
    protected $mockSecurityContext;

    /**
     * @var PrivilegeInterface|MockObject
     */
    protected $grantPrivilege;

    /**
     * @var PrivilegeInterface|MockObject
     */
    protected $denyPrivilege;

    /**
     * @var PrivilegeInterface|MockObject
     */
    protected $abstainPrivilege;

    /**
     * @var PrivilegeManager
     */
    protected $privilegeManager;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->mockSecurityContext = $this->createMock(Context::class);

        $this->privilegeManager = new PrivilegeManager($this->createStub(ObjectManagerInterface::class), $this->mockSecurityContext);

        $this->grantPrivilege = $this->createMock(AbstractPrivilege::class);
        $this->grantPrivilege->method('getPermission')->willReturn((PrivilegeInterface::GRANT));
        $this->grantPrivilege->method('matchesSubject')->willReturn((true));
        $this->grantPrivilege->method('getParameters')->willReturn(([]));
        $this->grantPrivilege->method('isGranted')->willReturn((true));
        $this->grantPrivilege->method('isDenied')->willReturn((false));

        $this->denyPrivilege = $this->createMock(AbstractPrivilege::class);
        $this->denyPrivilege->method('getPermission')->willReturn((PrivilegeInterface::DENY));
        $this->denyPrivilege->method('matchesSubject')->willReturn((true));
        $this->denyPrivilege->method('getParameters')->willReturn(([]));
        $this->denyPrivilege->method('isGranted')->willReturn((false));
        $this->denyPrivilege->method('isDenied')->willReturn((true));

        $this->abstainPrivilege = $this->createMock(AbstractPrivilege::class);
        $this->abstainPrivilege->method('getPermission')->willReturn((PrivilegeInterface::ABSTAIN));
        $this->abstainPrivilege->method('matchesSubject')->willReturn((true));
        $this->abstainPrivilege->method('getParameters')->willReturn(([]));
        $this->abstainPrivilege->method('isGranted')->willReturn((false));
        $this->abstainPrivilege->method('isDenied')->willReturn((false));
    }

    #[Test]
    public function isGrantedGrantsIfNoPrivilegeWasConfigured()
    {
        $role1ClassName = 'role1' . md5(uniqid((string)mt_rand(), true));
        $role2ClassName = 'role2' . md5(uniqid((string)mt_rand(), true));

        $mockRoleAdministrator = $this->createMock(Role::class, [], [], $role1ClassName, false);
        $mockRoleAdministrator->method('getPrivilegesByType')->willReturn(([]));

        $mockRoleCustomer = $this->createMock(Role::class, [], [], $role2ClassName, false);
        $mockRoleCustomer->method('getPrivilegesByType')->willReturn(([]));

        $this->mockSecurityContext->expects($this->once())->method('getRoles')->willReturn(([$mockRoleAdministrator, $mockRoleCustomer]));

        self::assertTrue($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, $this->createStub(JoinPoint::class)));
    }

    #[Test]
    public function isGrantedGrantsAccessIfNoRolesAreAvailable()
    {
        $this->mockSecurityContext->expects($this->once())->method('getRoles')->willReturn(([]));

        self::assertTrue($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, $this->createStub(JoinPoint::class)));
    }

    #[Test]
    public function isGrantedGrantsAccessIfNoPolicyEntryCouldBeFound()
    {
        $testRole1 = $this->getAccessibleMock(Role::class, ['getPrivilegesByType'], ['Acme.Demo:TestRole1']);
        $testRole1->expects($this->once())->method('getPrivilegesByType')->with(MethodPrivilegeInterface::class)->willReturn(([]));

        $this->mockSecurityContext->expects($this->once())->method('getRoles')->willReturn(([$testRole1]));

        self::assertTrue($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, $this->createStub(JoinPoint::class)));
    }

    #[Test]
    public function isGrantedDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles()
    {
        $role1ClassName = 'role1' . md5(uniqid((string)mt_rand(), true));
        $role2ClassName = 'role2' . md5(uniqid((string)mt_rand(), true));

        $mockRoleAdministrator = $this->createMock(Role::class, [], [], $role1ClassName, false);
        $mockRoleAdministrator->method('getPrivilegesByType')->willReturn(([$this->denyPrivilege]));

        $mockRoleCustomer = $this->createMock(Role::class, [], [], $role2ClassName, false);
        $mockRoleCustomer->method('getPrivilegesByType')->willReturn(([]));

        $this->mockSecurityContext->expects($this->once())->method('getRoles')->willReturn(([$mockRoleAdministrator, $mockRoleCustomer]));

        self::assertFalse($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, new MethodPrivilegeSubject($this->createStub(JoinPoint::class))));
    }

    #[Test]
    public function isGrantedGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured()
    {
        $role1ClassName = 'role1' . md5(uniqid((string)mt_rand(), true));
        $role2ClassName = 'role2' . md5(uniqid((string)mt_rand(), true));

        $mockRoleAdministrator = $this->createMock(Role::class, [], [], $role1ClassName, false);
        $mockRoleAdministrator->method('getPrivilegesByType')->willReturn(([$this->grantPrivilege]));

        $mockRoleCustomer = $this->createMock(Role::class, [], [], $role2ClassName, false);
        $mockRoleCustomer->method('getPrivilegesByType')->willReturn(([]));

        $this->mockSecurityContext->expects($this->once())->method('getRoles')->willReturn(([$mockRoleAdministrator, $mockRoleCustomer]));

        self::assertTrue($this->privilegeManager->isGranted(MethodPrivilegeInterface::class, new MethodPrivilegeSubject($this->createStub(JoinPoint::class))));
    }

    #[Test]
    public function isPrivilegeTargetGrantedReturnsFalseIfOneVoterReturnsADenyVote()
    {
        $mockRole1 = $this->createMock(Role::class);
        $mockRole1->method('getPrivilegeForTarget')->willReturn(($this->grantPrivilege));
        $mockRole2 = $this->createMock(Role::class);
        $mockRole2->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole3 = $this->createMock(Role::class);
        $mockRole3->method('getPrivilegeForTarget')->willReturn(($this->denyPrivilege));

        $this->mockSecurityContext->method('getRoles')->willReturn(([$mockRole1, $mockRole2, $mockRole3]));

        self::assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
    }

    #[Test]
    public function isPrivilegeTargetGrantedReturnsFalseIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse()
    {
        $mockRole1 = $this->createMock(Role::class);
        $mockRole1->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole2 = $this->createMock(Role::class);
        $mockRole2->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole3 = $this->createMock(Role::class);
        $mockRole3->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));

        $this->mockSecurityContext->method('getRoles')->willReturn(([$mockRole1, $mockRole2, $mockRole3]));

        self::assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
    }

    #[Test]
    public function isPrivilegeTargetGrantedPrivilegeReturnsTrueIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue()
    {
        $this->inject($this->privilegeManager, 'allowAccessIfAllAbstain', true);

        $mockRole1 = $this->createMock(Role::class);
        $mockRole1->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole2 = $this->createMock(Role::class);
        $mockRole2->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole3 = $this->createMock(Role::class);
        $mockRole3->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));

        $this->mockSecurityContext->method('getRoles')->willReturn(([$mockRole1, $mockRole2, $mockRole3]));

        self::assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
    }

    #[Test]
    public function isPrivilegeTargetGrantedReturnsTrueIfThereIsNoDenyVoteAndOneGrantVote()
    {
        $mockRole1 = $this->createMock(Role::class);
        $mockRole1->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));
        $mockRole2 = $this->createMock(Role::class);
        $mockRole2->method('getPrivilegeForTarget')->willReturn(($this->grantPrivilege));
        $mockRole3 = $this->createMock(Role::class);
        $mockRole3->method('getPrivilegeForTarget')->willReturn(($this->abstainPrivilege));

        $this->mockSecurityContext->method('getRoles')->willReturn(([$mockRole1, $mockRole2, $mockRole3]));

        self::assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
    }
}
