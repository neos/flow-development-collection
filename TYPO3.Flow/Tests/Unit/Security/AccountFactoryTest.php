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

/**
 * Testcase for the account factory
 *
 */
class AccountFactoryTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAccountWithPasswordCreatesANewAccountWithTheGivenIdentifierPasswordRolesAndProviderName() {
		$mockHashService = $this->getMock('TYPO3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('hashPassword')->with('password')->will($this->returnValue('hashed password'));

		$mockRole1 = new \TYPO3\FLOW3\Security\Policy\Role('role1');
		$mockRole2 = new \TYPO3\FLOW3\Security\Policy\Role('role2');

		$factory = $this->getAccessibleMock('TYPO3\FLOW3\Security\AccountFactory', array('dummy'));
		$factory->_set('hashService', $mockHashService);

		$actualAccount = $factory->createAccountWithPassword('username', 'password', array('role1', 'role2'), 'OtherProvider');
		$this->assertEquals('username', $actualAccount->getAccountIdentifier());
		$this->assertEquals('OtherProvider', $actualAccount->getAuthenticationProviderName());
		$this->assertEquals(array($mockRole1, $mockRole2), $actualAccount->getRoles());
	}
}
?>