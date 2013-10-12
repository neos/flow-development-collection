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
 */
class AccountTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var Role
	 */
	protected $administratorRole;

	/**
	 * @var Role
	 */
	protected $customerRole;

	/**
	 * Setup function for the testcase
	 */
	public function setUp() {
		$this->administratorRole = new Role('TYPO3.Flow:Administrator');
		$this->customerRole = new Role('TYPO3.Flow:Customer');
	}

	/**
	 * @test
	 */
	public function addRoleAddsRoleToAccountIfNotAssigned() {
		$account = new Account();
		$account->setRoles(array($this->administratorRole));
		$account->addRole($this->customerRole);

		$this->assertCount(2, $account->getRoles());
	}

	/**
	 * @test
	 */
	public function addRoleSkipsRoleIfAssigned() {
		$account = new Account();
		$account->setRoles(array($this->administratorRole));
		$account->addRole($this->administratorRole);

		$this->assertCount(1, $account->getRoles());
	}

	/**
	 * @test
	 */
	public function removeRoleRemovesRoleFromAccountIfAssigned() {
		$account = new Account();
		$account->setRoles(array($this->administratorRole, $this->customerRole));
		$account->removeRole($this->customerRole);

		$this->assertCount(1, $account->getRoles());
	}

	/**
	 * @test
	 */
	public function removeRoleSkipsRemovalIfRoleNotAssigned() {
		$account = new Account();
		$account->setRoles(array($this->administratorRole));
		$account->removeRole($this->customerRole);

		$this->assertCount(1, $account->getRoles());
	}

	/**
	 * @test
	 */
	public function hasRoleWorks() {
		$account = new Account();
		$account->setRoles(array($this->administratorRole));

		$this->assertTrue($account->hasRole($this->administratorRole));
		$this->assertFalse($account->hasRole($this->customerRole));
	}

	/**
	 * @test
	 */
	public function setRolesWorks() {
		$roles = array($this->administratorRole, $this->customerRole);
		$expectedRoles = array($this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole);
		$account = new Account();
		$account->setRoles($roles);

		$this->assertSame($expectedRoles, $account->getRoles());
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
