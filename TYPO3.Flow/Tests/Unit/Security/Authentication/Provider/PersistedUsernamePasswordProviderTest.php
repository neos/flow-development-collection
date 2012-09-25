<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\Provider;

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
 * Testcase for username/password authentication provider. The account are stored in the CR.
 *
 */
class PersistedUsernamePasswordProviderTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword() {
		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(TRUE));

		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository', array(), array(), '', FALSE);
		$mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($mockAccount));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$mockToken->expects($this->once())->method('setAccount')->with($mockAccount);

		$usernamePasswordCRProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider', array('dummy'), array('myProvider', array()));
		$usernamePasswordCRProvider->_set('accountRepository', $mockAccountRepository);
		$usernamePasswordCRProvider->_set('hashService', $mockHashService);

		$usernamePasswordCRProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken() {
		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(FALSE));

		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account', array(), array(), '', FALSE);
		$mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository', array(), array(), '', FALSE);
		$mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($mockAccount));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'wrong password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

		$usernamePasswordCRProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider', array('dummy'), array('myProvider', array()));
		$usernamePasswordCRProvider->_set('accountRepository', $mockAccountRepository);
		$usernamePasswordCRProvider->_set('hashService', $mockHashService);

		$usernamePasswordCRProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');

		$usernamePasswordProvider = new \TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());

		$usernamePasswordProvider->authenticate($someNiceToken);
	}

	/**
	 * @test
	 */
	public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet() {
		$mockToken1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('myProvider'));
		$mockToken2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken2->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('someOtherProvider'));

		$usernamePasswordProvider = new \TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider('myProvider', array());

		$this->assertTrue($usernamePasswordProvider->canAuthenticate($mockToken1));
		$this->assertFalse($usernamePasswordProvider->canAuthenticate($mockToken2));
	}

}
?>