<?php
declare(ENCODING = 'utf-8');
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
class PolicyTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointAbstainsIfNoPrivilegeWasConfigured() {
		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), uniqid('role1'), FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), uniqid('role2'), FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegesForJoinPoint')->will($this->returnValue(array()));

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
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

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

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

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForJoinPointGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

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

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForJoinPoint($mockSecurityContext, $mockJoinPoint), Policy::VOTE_GRANT , 'The wrong vote was returned!');
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

		$voter = new Policy($mockPolicyService);
		$this->assertEquals($voter->voteForResource($mockSecurityContext, 'myResource'), Policy::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceDeniesAccessIfADenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getRoles')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));

		$getPrivilegeCallback = function() use (&$role1ClassName) {
			$args = func_get_args();
			if ($args[0] instanceof $role1ClassName) {
				return array(\F3\FLOW3\Security\Policy\PolicyService::PRIVILEGE_DENY);
			} else {
				return array();
			}
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivilegeForResource')->will($this->returnCallback($getPrivilegeCallback));

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), Policy::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteForResourceGrantsAccessIfAGrantPrivilegeAndNoDenyPrivilegeWasConfigured() {
		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

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

		$Policy = new Policy($mockPolicyService);
		$this->assertEquals($Policy->voteForResource($mockSecurityContext, 'myResource'), Policy::VOTE_GRANT , 'The wrong vote was returned!');
	}
}

?>