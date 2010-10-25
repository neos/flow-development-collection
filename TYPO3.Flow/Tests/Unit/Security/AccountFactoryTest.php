<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AccountFactoryTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createAccountWithPasswordCreatesANewAccountWithTheGivenPasswordRolesAndProviderName() {
		$mockHashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('generateSaltedMd5')->with('password')->will($this->returnValue('hashed password'));

		$mockRole1 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);
		$mockRole2 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), '', FALSE);

		$expectedAccount = $this->getMock('F3\FLOW3\Security\Account');
		$expectedAccount->expects($this->once())->method('setAccountIdentifier')->with('username');
		$expectedAccount->expects($this->once())->method('setAccountIdentifier')->with('username');
		$expectedAccount->expects($this->once())->method('setCredentialsSource')->with('hashed password');
		$expectedAccount->expects($this->once())->method('setAuthenticationProviderName')->with('OtherProvider');
		$expectedAccount->expects($this->once())->method('setRoles')->with(array($mockRole1, $mockRole2));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->at(0))->method('create')->with('F3\FLOW3\Security\Policy\Role', 'role1')->will($this->returnValue($mockRole1));
		$mockObjectManager->expects($this->at(1))->method('create')->with('F3\FLOW3\Security\Policy\Role', 'role2')->will($this->returnValue($mockRole2));
		$mockObjectManager->expects($this->at(2))->method('create')->with('F3\FLOW3\Security\Account')->will($this->returnValue($expectedAccount));

		$factory = new \F3\FLOW3\Security\AccountFactory;
		$factory->injectObjectManager($mockObjectManager);
		$factory->injectHashService($mockHashService);

		$actualAccount = $factory->createAccountWithPassword('username', 'password', array('role1', 'role2'), 'OtherProvider');
		$this->assertSame($expectedAccount, $actualAccount);
	}
}
?>