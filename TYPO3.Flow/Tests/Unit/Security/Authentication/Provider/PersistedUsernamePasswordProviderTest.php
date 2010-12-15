<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security\Authentication\Provider;

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
 * Testcase for username/password authentication provider. The account are stored in the CR.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PersistedUsernamePasswordProviderTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getTokenClassNamesReturnsTheCorrectClassNames() {
		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());
		$this->assertEquals(array('F3\FLOW3\Security\Authentication\Token\UsernamePassword'), $usernamePasswordProvider->getTokenClassNames());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword() {
		$mockHashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validateSaltedMd5')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(TRUE));

		$mockAccount = $this->getMock('F3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$mockAccountRepository = $this->getMock('F3\FLOW3\Security\AccountRepository', array(), array(), '', FALSE);
		$mockAccountRepository->expects($this->once())->method('findByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($mockAccount));

		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$mockToken->expects($this->once())->method('setAccount')->with($mockAccount);

		$usernamePasswordCRProvider = new \F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());
		$usernamePasswordCRProvider->injectAccountRepository($mockAccountRepository);
		$usernamePasswordCRProvider->injectHashService($mockHashService);

		$usernamePasswordCRProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken() {
		$mockHashService = $this->getMock('F3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validateSaltedMd5')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(FALSE));

		$mockAccount = $this->getMock('F3\FLOW3\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$mockAccountRepository = $this->getMock('F3\FLOW3\Security\AccountRepository', array(), array(), '', FALSE);
		$mockAccountRepository->expects($this->once())->method('findByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($mockAccount));

		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'wrong password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\F3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

		$usernamePasswordCRProvider = new \F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());
		$usernamePasswordCRProvider->injectAccountRepository($mockAccountRepository);
		$usernamePasswordCRProvider->injectHashService($mockHashService);

		$usernamePasswordCRProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());

		$usernamePasswordProvider->authenticate($someNiceToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet() {
		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('myProvider'));
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken2->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('someOtherProvider'));

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());

		$this->assertTrue($usernamePasswordProvider->canAuthenticate($mockToken1));
		$this->assertFalse($usernamePasswordProvider->canAuthenticate($mockToken2));
	}
}
?>