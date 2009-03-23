<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Voter;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the ACL voter
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ACLTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteAbstainsIfNoAccessPrivilegeWasConfigured() {
		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), uniqid('role1'), FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), uniqid('role2'), FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getGrantedAuthorities')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$mockCustomDenyPrivilege, &$mockCustomDenyPrivilege2) {
			$args = func_get_args();

			if ($args[2] !== 'ACCESS') return array($mockCustomDenyPrivilege, $mockCustomDenyPrivilege2);
			else return array();
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivileges')->will($this->returnCallback($getPrivilegesCallback));

		$ACLVoter = new ACL($mockPolicyService);
		$this->assertEquals($ACLVoter->vote($mockSecurityContext, $mockJoinPoint), ACL::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteAbstainsIfNoGrantedAuthoritiesAreAvailable() {
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getGrantedAuthorities')->will($this->returnValue(array()));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService', array(), array(), '', FALSE);

		$ACLVoter = new ACL($mockPolicyService);
		$this->assertEquals($ACLVoter->vote($mockSecurityContext, $mockJoinPoint), ACL::VOTE_ABSTAIN, 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteDeniesAccessIfAnAccessDenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$mockAccessDenyPrivilege = $this->getMock('F3\FLOW3\Security\ACL\Privilege', array(), array(), '', FALSE);
		$mockAccessDenyPrivilege->expects($this->any())->method('isGrant')->will($this->returnValue(FALSE));
		$mockAccessDenyPrivilege->expects($this->any())->method('isDeny')->will($this->returnValue(TRUE));
		$mockAccessDenyPrivilege->expects($this->any())->method('__toString')->will($this->returnValue('ACCESS'));

		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getGrantedAuthorities')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$mockAccessDenyPrivilege, &$role1ClassName) {
			$args = func_get_args();

			$fullClassName = 'F3\FLOW3\Security\ACL\\' . $role1ClassName;

			if ($args[0] instanceof $fullClassName) return array($mockAccessDenyPrivilege);
			else return array();
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivileges')->will($this->returnCallback($getPrivilegesCallback));

		$ACLVoter = new ACL($mockPolicyService);
		$this->assertEquals($ACLVoter->vote($mockSecurityContext, $mockJoinPoint), ACL::VOTE_DENY , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteGrantsAccessIfAnAccessGrantPrivilegeAndNoAccessDenyPrivilegeWasConfigured() {
		$mockAccessGrantPrivilege = $this->getMock('F3\FLOW3\Security\ACL\Privilege', array(), array(), '', FALSE);
		$mockAccessGrantPrivilege->expects($this->any())->method('isGrant')->will($this->returnValue(TRUE));
		$mockAccessGrantPrivilege->expects($this->any())->method('isDeny')->will($this->returnValue(FALSE));
		$mockAccessGrantPrivilege->expects($this->any())->method('__toString')->will($this->returnValue('ACCESS'));

		$role1ClassName = uniqid('role1');
		$role2ClassName = uniqid('role2');

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role1ClassName, FALSE);
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role2ClassName, FALSE);
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getGrantedAuthorities')->will($this->returnValue(array($mockRoleAdministrator, $mockRoleCustomer)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$getPrivilegesCallback = function() use (&$mockAccessGrantPrivilege, &$role1ClassName) {
			$args = func_get_args();

			$fullClassName = 'F3\FLOW3\Security\ACL\\' . $role1ClassName;

			if ($args[0] instanceof $fullClassName) return array($mockAccessGrantPrivilege);
			else return array();
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService');
		$mockPolicyService->expects($this->any())->method('getPrivileges')->will($this->returnCallback($getPrivilegesCallback));

		$ACLVoter = new ACL($mockPolicyService);
		$this->assertEquals($ACLVoter->vote($mockSecurityContext, $mockJoinPoint), ACL::VOTE_GRANT , 'The wrong vote was returned!');
	}
}

?>