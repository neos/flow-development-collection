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
	 * @var Account
	 */
	protected $account;

	/**
	 * Setup function for the testcase
	 */
	public function setUp() {
		$administratorRole = new Role('TYPO3.Flow:Administrator');
		$this->administratorRole = $administratorRole;
		$customerRole = new Role('TYPO3.Flow:Customer');
		$this->customerRole = $customerRole;

		$mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->any())->method('getRole')->will($this->returnCallback(function($roleIdentifier) use ($administratorRole, $customerRole) {
			switch($roleIdentifier) {
				case 'TYPO3.Flow:Administrator':
					return $administratorRole;
					break;
				case 'TYPO3.Flow:Customer':
					return $customerRole;
					break;
				default:
					throw new NoSuchRoleException();
			}
		}));
		$mockPolicyService->expects($this->any())->method('hasRole')->will($this->returnCallback(function($roleIdentifier) use ($administratorRole, $customerRole) {
			switch($roleIdentifier) {
				case 'TYPO3.Flow:Administrator':
				case 'TYPO3.Flow:Customer':
					return TRUE;
					break;
				default:
					return FALSE;
			}
		}));

		$this->account = $this->getAccessibleMock('TYPO3\Flow\Security\Account', array('dummy'));
		$this->account->_set('policyService', $mockPolicyService);
	}

	/**
	 * @test
	 */
	public function addRoleAddsRoleToAccountIfNotAssigned() {
		$this->account->setRoles(array($this->administratorRole));
		$this->account->addRole($this->customerRole);
		$this->assertCount(2, $this->account->getRoles());
	}

	/**
	 * @test
	 */
	public function addRoleSkipsRoleIfAssigned() {
		$this->account->setRoles(array($this->administratorRole));
		$this->account->addRole($this->administratorRole);

		$this->assertCount(1, $this->account->getRoles());
	}

	/**
	 * @test
	 */
	public function removeRoleRemovesRoleFromAccountIfAssigned() {
		$this->account->setRoles(array($this->administratorRole, $this->customerRole));
		$this->account->removeRole($this->customerRole);

		$this->assertCount(1, $this->account->getRoles());
	}

	/**
	 * @test
	 */
	public function removeRoleSkipsRemovalIfRoleNotAssigned() {
		$this->account->setRoles(array($this->administratorRole));
		$this->account->removeRole($this->customerRole);

		$this->assertCount(1, $this->account->getRoles());
	}

	/**
	 * @test
	 */
	public function hasRoleWorks() {
		$this->account->setRoles(array($this->administratorRole));

		$this->assertTrue($this->account->hasRole($this->administratorRole));
		$this->assertFalse($this->account->hasRole($this->customerRole));
	}

	/**
	 * @test
	 */
	public function getRolesReturnsOnlyExistingRoles() {
		$this->inject($this->account, 'roleIdentifiers', array('Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()));

		$roles = $this->account->getRoles();
		$this->assertCount(1, $roles);
		$this->assertArrayHasKey($this->administratorRole->getIdentifier(), $roles);
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsFalseForAssignedButNonExistentRole() {
		$this->inject($this->account, 'roleIdentifiers', array('Acme.Demo:NoLongerThere', $this->administratorRole->getIdentifier()));

		$this->assertTrue($this->account->hasRole($this->administratorRole));
		$this->assertFalse($this->account->hasRole(new Role('Acme.Demo:NoLongerThere')));
	}

	/**
	 * @test
	 */
	public function setRolesWorks() {
		$roles = array($this->administratorRole, $this->customerRole);
		$expectedRoles = array($this->administratorRole->getIdentifier() => $this->administratorRole, $this->customerRole->getIdentifier() => $this->customerRole);
		$this->account->setRoles($roles);

		$this->assertSame($expectedRoles, $this->account->getRoles());
	}

	/**
	 * @test
	 */
	public function expirationDateCanBeSetNull() {
		$this->account->setExpirationDate(new \DateTime());
		$this->account->setExpirationDate(NULL);

		$this->assertEquals(NULL, $this->account->getExpirationDate());
	}

}
