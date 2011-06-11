<?php
namespace F3\FLOW3\Tests\Unit\Security\Authorization\Voter;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the Policy voter
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointAbstainsIfNoPrivilegeWasConfigured() {
		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), 'role1' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), 'role2' . md5(uniqid(mt_rand(), TRUE)), FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnValue(array()));

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointAbstainsIfNoRolesAreAvailable() {
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointAbstainsIfNoPolicyEntryCouldBeFound() {
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array(new \F3\FLOW3\Security\Policy\Role('role1'))));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getPrivilegesForJoinPoint')->will($this->throwException(new \F3\FLOW3\Security\Exception\NoEntryInPolicyException()));

		$voter = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return array(\F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY);
			} else {
				return array(\F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);
			}
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return array(\F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT);
			} else {
				return array();
			}
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_GRANT , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceAbstainsIfNoRolesAreAvailable() {
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array()));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);

		$voter = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForResource($mockSecurityContext, 'myResource'), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceAbstainsIfNoPolicyEntryCouldBeFound() {
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array(new \F3\FLOW3\Security\Policy\Role('role1'))));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->once())->method('getPrivilegeForResource')->will($this->throwException(new \F3\FLOW3\Security\Exception\NoEntryInPolicyException()));

		$voter = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($voter->voteForResource($mockSecurityContext, 'myResource'), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$getPrivilegeCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return \F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY;
			} else {
				return NULL;
			}
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegeForResource')->will($this->returnCallback($getPrivilegeCallback));

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = 'role1' . md5(uniqid(mt_rand(), TRUE));
		$role2ClassName = 'role2' . md5(uniqid(mt_rand(), TRUE));

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('Administrator'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('Customer'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$getPrivilegesCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return \F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_GRANT;
			} else {
				return NULL;
			}
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegeForResource')->will($this->returnCallback($getPrivilegesCallback));

		$Policy = new \F3\FLOW3\Security\Authorization\Voter\Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), \F3\FLOW3\Security\Authorization\Voter\Policy::VOTE_GRANT , 'The wrong vote was returned!');
	}
}

?>