<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Aop\JoinPointInterface;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeSubject;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Security\Authorization\PrivilegeManager;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the privilege manager
 *
 */
class PrivilegeManagerTest extends UnitTestCase {

	/**
	 * @var Context|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityContext;

	/**
	 * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockObjectManager;

	/**
	 * @var JoinPointInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockJoinPoint;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $grantPrivilege;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $denyPrivilege;

	/**
	 * @var PrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $abstainPrivilege;

	/**
	 * @var PrivilegeManager
	 */
	protected $privilegeManager;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();
		$this->mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->getMock();
		$this->mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPoint')->disableOriginalConstructor()->getMock();

		$this->privilegeManager = new PrivilegeManager($this->mockObjectManager, $this->mockSecurityContext);

		$this->grantPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->getMock();
		$this->grantPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::GRANT));
		$this->grantPrivilege->expects($this->any())->method('matchesSubject')->will($this->returnValue(TRUE));
		$this->grantPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));
		$this->grantPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(TRUE));
		$this->grantPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(FALSE));

		$this->denyPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->getMock();
		$this->denyPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::DENY));
		$this->denyPrivilege->expects($this->any())->method('matchesSubject')->will($this->returnValue(TRUE));
		$this->denyPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));
		$this->denyPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(FALSE));
		$this->denyPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(TRUE));

		$this->abstainPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\AbstractPrivilege')->disableOriginalConstructor()->getMock();
		$this->abstainPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::ABSTAIN));
		$this->abstainPrivilege->expects($this->any())->method('matchesSubject')->will($this->returnValue(TRUE));
		$this->abstainPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));
		$this->abstainPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(FALSE));
		$this->abstainPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(FALSE));
	}

	/**
	 * @test
	 */
	public function isGrantedGrantsIfNoPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedGrantsAccessIfNoRolesAreAvailable() {
		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedGrantsAccessIfNoPolicyEntryCouldBeFound() {
		$testRole1 = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\Role', array('getPrivilegesByType'), array('Acme.Demo:TestRole1'));
		$testRole1->expects($this->once())->method('getPrivilegesByType')->with('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($testRole1)));

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', $this->mockJoinPoint));
	}

	/**
	 * @test
	 */
	public function isGrantedDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array($this->denyPrivilege)));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$this->assertFalse($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', new MethodPrivilegeSubject($this->mockJoinPoint)));
	}

	/**
	 * @test
	 */
	public function isGrantedGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array($this->grantPrivilege)));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$this->assertTrue($this->privilegeManager->isGranted('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface', new MethodPrivilegeSubject($this->mockJoinPoint)));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsFalseIfOneVoterReturnsADenyVote() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->grantPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->denyPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsFalseIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsFalse() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertFalse($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedPrivilegeReturnsTrueIfAllVotersAbstainAndAllowAccessIfAllVotersAbstainIsTrue() {
		$this->inject($this->privilegeManager, 'allowAccessIfAllAbstain', TRUE);

		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}

	/**
	 * @test
	 */
	public function isPrivilegeTargetGrantedReturnsTrueIfThereIsNoDenyVoteAndOneGrantVote() {
		$mockRole1 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole1->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));
		$mockRole2 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole2->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->grantPrivilege));
		$mockRole3 = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$mockRole3->expects($this->any())->method('getPrivilegeForTarget')->will($this->returnValue($this->abstainPrivilege));

		$this->mockSecurityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array($mockRole1, $mockRole2, $mockRole3)));

		$this->assertTrue($this->privilegeManager->isPrivilegeTargetGranted('somePrivilegeTargetIdentifier'));
	}
}
