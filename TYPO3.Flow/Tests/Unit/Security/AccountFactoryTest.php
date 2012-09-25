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

/**
 * Testcase for the account factory
 *
 */
class AccountFactoryTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function createAccountWithPasswordCreatesANewAccountWithTheGivenIdentifierPasswordRolesAndProviderName() {
		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('hashPassword')->with('password')->will($this->returnValue('hashed password'));

		$mockRole1 = new \TYPO3\Flow\Security\Policy\Role('role1');
		$mockRole2 = new \TYPO3\Flow\Security\Policy\Role('role2');

		$factory = $this->getAccessibleMock('TYPO3\Flow\Security\AccountFactory', array('dummy'));
		$factory->_set('hashService', $mockHashService);

		$actualAccount = $factory->createAccountWithPassword('username', 'password', array('role1', 'role2'), 'OtherProvider');
		$this->assertEquals('username', $actualAccount->getAccountIdentifier());
		$this->assertEquals('OtherProvider', $actualAccount->getAuthenticationProviderName());
		$this->assertEquals(array($mockRole1, $mockRole2), $actualAccount->getRoles());
	}
}
?>