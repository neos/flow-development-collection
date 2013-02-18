<?php
namespace TYPO3\Flow\Tests\Unit\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Policy\Role;

/**
 * Testcase for the account
 *
 */
class AccountTest extends \TYPO3\Flow\Tests\UnitTestCase {

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

		$this->assertAttributeEquals(array($this->role1), 'roles', $account);
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

	/**
	 * @test
	 */
	public function hasRoleWorks() {
		$account = new Account();
		$account->setRoles(array($this->role1));

		$this->assertTrue($account->hasRole($this->role1));
		$this->assertFalse($account->hasRole($this->role2));
	}

	/**
	 * @test
	 */
	public function hasRoleWorksOnRoleInstancesWithTheSameIdentifier() {
		$account = new Account();
		$account->addRole(new Role('role1'));
		$this->assertEquals(array($this->role1), $account->getRoles());
	}

	/**
	 * @test
	 */
	public function setRolesWorks() {
		$account = new Account();
		$account->setRoles(array($this->role1, $this->role2));

		$this->assertEquals(array($this->role1, $this->role2), $account->getRoles());
	}

	/**
	 * @test
	 */
	public function setRolesHandlesStringValuesAsRole() {
		$account = new Account();
		$account->setRoles(array($this->role1, 'role2'));

		$this->assertEquals(array($this->role1, $this->role2), $account->getRoles());
	}

	/**
	 * @test
	 */
	public function expirationDateCanBeSetNull() {
		$account = new Account();

		$account->setExpirationDate(new \DateTime());
		$account->setExpirationDate(NULL);

		$this->assertEquals(NULL, $account->getExpirationDate());
	}

}
?>