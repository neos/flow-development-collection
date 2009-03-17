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
	public function voteDeniesAccessIfNoAccessPrivilegeWasConfigured() {
		$this->markTestIncomplete();

		$mockCustomDenyPrivilege = $this->getMock('F3\FLOW3\Security\ACL\Privilege');
		$mockCustomDenyPrivilege->expects($this->any())->method('isGrant')->will($this->returnValue(FALSE));
		$mockCustomDenyPrivilege->expects($this->any())->method('isDeny')->will($this->returnValue(FALSE));
		$mockCustomDenyPrivilege->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMPRIVILEGE'));

		$mockCustomDenyPrivilege2 = $this->getMock('F3\FLOW3\Security\ACL\Privilege');
		$mockCustomDenyPrivilege2->expects($this->any())->method('isGrant')->will($this->returnValue(FALSE));
		$mockCustomDenyPrivilege2->expects($this->any())->method('isDeny')->will($this->returnValue(FALSE));
		$mockCustomDenyPrivilege2->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMPRIVILEGE'));

		$mockRoleAdministrator = $this->getMock('F3\FLOW3\Security\ACL\Role');
		$mockRoleAdministrator->expects($this->any())->method('__toString')->will($this->returnValue('ADMINISTRATOR'));

		$mockRoleCustomer = $this->getMock('F3\FLOW3\Security\ACL\Role');
		$mockRoleCustomer->expects($this->any())->method('__toString')->will($this->returnValue('CUSTOMER'));

		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->atLeastOnce())->method('getGrantedAuthorities')->will($this->returnValue(array($mockRoleAdministrator)));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context');
		$mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1)));
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface');

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteAbstainsIfNoTokenIsAuthenticated() {
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$mockToken2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2)));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockPolicyService = $this->getMock('F3\FLOW3\Security\ACL\PolicyService', array(), array(), '', FALSE);

		$ACLVoter = new ACL($mockPolicyService);
		$this->assertEquals($ACLVoter->vote($mockSecurityContext, $mockJoinPoint), ACL::VOTE_ABSTAIN , 'The wrong vote was returned!');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteDeniesAccessIfAnAccessDenyPrivilegeWasConfiguredForOneOfTheRoles() {
		$this->markTestIncomplete();

	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function voteGrantsAccessIfAnAccessGrantPrivilegeAndNoAccessDenyPrivilegeWasConfigured() {
		$this->markTestIncomplete();
	}
}

?>