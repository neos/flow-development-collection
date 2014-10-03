<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Privilege\Method;

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
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilege;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Policy voter
 *
 */
class MethodPrivilegeTest extends UnitTestCase {

	/**
	 * @var Context|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockSecurityContext;

	/**
	 * @var JoinPointInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockJoinPoint;

	/**
	 * @var PolicyService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPolicyService;

	/**
	 * @var MethodPrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $grantPrivilege;

	/**
	 * @var MethodPrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $denyPrivilege;

	/**
	 * @var MethodPrivilegeInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $abstainPrivilege;

	/**
	 * @var Policy
	 */
	protected $policyVoter;

	/**
	 * Set tests up
	 */
	public function setUp() {
		$this->mockSecurityContext = $this->getMockBuilder('TYPO3\Flow\Security\Context')->disableOriginalConstructor()->getMock();
		$mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->disableOriginalConstructor()->getMock();
		$mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($this->mockSecurityContext));
		Bootstrap::$staticObjectManager = $mockObjectManager;

		$this->mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->getMock();

		$this->mockPolicyService = $this->getMockBuilder('TYPO3\Flow\Security\Policy\PolicyService')->disableOriginalConstructor()->getMock();

		$this->grantPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface')->getMock();
		$this->grantPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(TRUE));
		$this->grantPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(FALSE));
		$this->grantPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::GRANT));
		$this->grantPrivilege->expects($this->any())->method('matchesJoinpoint')->will($this->returnValue(TRUE));
		$this->grantPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));

		$this->denyPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface')->getMock();
		$this->denyPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(FALSE));
		$this->denyPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(TRUE));
		$this->denyPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::DENY));
		$this->denyPrivilege->expects($this->any())->method('matchesJoinpoint')->will($this->returnValue(TRUE));
		$this->denyPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));

		$this->abstainPrivilege = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface')->getMock();
		$this->abstainPrivilege->expects($this->any())->method('isGranted')->will($this->returnValue(FALSE));
		$this->abstainPrivilege->expects($this->any())->method('isDenied')->will($this->returnValue(FALSE));
		$this->abstainPrivilege->expects($this->any())->method('getPermission')->will($this->returnValue(PrivilegeInterface::ABSTAIN));
		$this->abstainPrivilege->expects($this->any())->method('matchesJoinpoint')->will($this->returnValue(TRUE));
		$this->abstainPrivilege->expects($this->any())->method('getParameters')->will($this->returnValue(array()));
	}

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isAbstained(), 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoRolesAreAvailable() {
		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isAbstained(), 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoPolicyEntryCouldBeFound() {
		$testRole1 = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\Role', array('getPrivilegesByType'), array('Acme.Demo:TestRole1'));
		$testRole1->expects($this->once())->method('getPrivilegesByType')->with('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($testRole1)));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isAbstained(), 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array($this->denyPrivilege)));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isDenied(), 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array($this->grantPrivilege)));

		$mockRoleCustomer = $this->getMock('TYPO3\Flow\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('getPrivilegesByType')->will($this->returnValue(array()));

		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isGranted(), 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForPrivilegeAbstainsIfNoRolesAreAvailable() {
		$this->mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$voteResult = MethodPrivilege::vote($this->mockJoinPoint);
		$this->assertTrue($voteResult->isAbstained(), 'The wrong vote was returned!');
	}
}
