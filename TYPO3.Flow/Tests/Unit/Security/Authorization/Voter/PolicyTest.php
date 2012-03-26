<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization\Voter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Policy voter
 *
 */
class PolicyTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoPrivilegeWasConfigured() {
		$mockRoleAdministrator = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), 'role1' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), 'role2' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnValue(array()));

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoRolesAreAvailable() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointAbstainsIfNoPolicyEntryCouldBeFound() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array(new \TYPO3\FLOW3\Security\Policy\Role('role1'))));

		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getPrivilegesForJoinPoint')->will($this->throwException(new \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException()));

		$voter = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return array(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY);
			} else {
				return array(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);
			}
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForJoinPointGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return array(\TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);
			} else {
				return array();
			}
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_GRANT , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForResourceAbstainsIfNoRolesAreAvailable() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);

		$voter = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForResource($mockSecurityContext, 'myResource'), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForResourceAbstainsIfNoPolicyEntryCouldBeFound() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array(new \TYPO3\FLOW3\Security\Policy\Role('role1'))));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getPrivilegeForResource')->will($this->throwException(new \TYPO3\FLOW3\Security\Exception\NoEntryInPolicyException()));

		$voter = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForResource($mockSecurityContext, 'myResource'), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForResourceDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$getPrivilegeCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY;
			} else {
				return NULL;
			}
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegeForResource')->will($this->returnCallback($getPrivilegeCallback));

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 */
	public function voteForResourceGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('TYPO3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return \TYPO3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT;
			} else {
				return NULL;
			}
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegeForResource')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \TYPO3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), \TYPO3\FLOW3\Security\Authorization\Voter\Policy::VOTE_GRANT , 'The wrong vote was returned!');
	}
}

?>