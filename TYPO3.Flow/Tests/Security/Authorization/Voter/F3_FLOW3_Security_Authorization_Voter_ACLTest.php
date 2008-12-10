<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authorization\Voter;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
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