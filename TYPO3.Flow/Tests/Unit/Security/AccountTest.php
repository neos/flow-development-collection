<?php
namespace TYPO3\FLOW3\Tests\Unit\Security;

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

use TYPO3\FLOW3\Security\Account;
use TYPO3\FLOW3\Security\Policy\Role;

/**
 * Testcase for the account
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AccountTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var Role
	 */
	protected $role1;

	/**
	 * @var Role
	 */
	protected $role2;

	/**
	 * Setup function for the testcase
	 */
	public function setUp() {
		$this->role1 = new Role('role1');
		$this->role2 = new Role('role2');
	}

	/**
	 * @test
	 */
	public function addRoleAddsRoleToAccountIfNotAssigned() {
		$account = new Account();
		$account->setRoles(array($this->role1));
		$account->addRole($this->role2);

		$this->assertAttributeContains('role2', 'roles', $account);
	}

	/**
	 * @test
	 */
	public function addRoleSkipsRoleIfAssigned() {
		$account = new Account();
		$account->setRoles(array($this->role1));
		$account->addRole($this->role1);

		$this->assertAttributeEquals(array('role1'), 'roles', $account);
	}

	/**
	 * @test
	 */
	public function removeRoleRemovesRoleFromAccountIfAssigned() {
		$account = new Account();
		$account->setRoles(array($this->role1, $this->role2));
		$account->removeRole($this->role2);

		$this->assertAttributeEquals(array('role1'), 'roles', $account);
	}

	/**
	 * @test
	 */
	public function removeRoleSkipsRemovalIfRoleNotAssigned() {
		$account = new Account();
		$account->setRoles(array($this->role1));
		$account->removeRole($this->role2);

		$this->assertAttributeEquals(array('role1'), 'roles', $account);
	}

}
?>