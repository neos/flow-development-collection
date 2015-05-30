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
	 * @var \TYPO3\Flow\Security\Cryptography\HashService
	 */
	protected $mockHashService;

	/**
	 * @var \TYPO3\Flow\Security\Account
	 */
	protected $mockAccount;

	/**
	 * @var \TYPO3\Flow\Security\AccountRepository
	 */
	protected $mockAccountRepository;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\Token\UsernamePassword
	 */
	protected $mockToken;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $mockSecurityContext;

	/**
	 * @var \TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider
	 */
	protected $persistedUsernamePasswordProvider;


	public function setUp() {
		$this->mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$this->mockAccount = $this->getMock('TYPO3\Flow\Security\Account', array(), array(), '', FALSE);
		$this->mockAccountRepository = $this->getMock('TYPO3\Flow\Security\AccountRepository', array(), array(), '', FALSE);
		$this->mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);

		$this->mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$this->mockSecurityContext->expects($this->any())->method('withoutAuthorizationChecks')->will($this->returnCallback(function($callback) {
			return $callback->__invoke();
		}));

		$this->persistedUsernamePasswordProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\PersistedUsernamePasswordProvider', array('dummy'), array('myProvider', array()));
		$this->persistedUsernamePasswordProvider->_set('hashService', $this->mockHashService);
		$this->persistedUsernamePasswordProvider->_set('accountRepository', $this->mockAccountRepository);
		$this->persistedUsernamePasswordProvider->_set('securityContext', $this->mockSecurityContext);

	}

	/**
	 * @test
	 */
	public function authenticatingAnUsernamePasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword() {
		$this->mockHashService->expects($this->once())->method('validatePassword')->with('password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(TRUE));

		$this->mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$this->mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($this->mockAccount));

		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'password')));
		$this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);
		$this->mockToken->expects($this->once())->method('setAccount')->with($this->mockAccount);

		$this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
	}

	/**
	 * @test
	 */
	public function authenticatingAnUsernamePasswordTokenFetchesAccountWithDisabledAuthorization() {
		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'password')));
		$this->mockSecurityContext->expects($this->once())->method('withoutAuthorizationChecks');
		$this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationFailsWithWrongCredentialsInAnUsernamePasswordToken() {
		$this->mockHashService->expects($this->once())->method('validatePassword')->with('wrong password', '8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086')->will($this->returnValue(FALSE));

		$this->mockAccount->expects($this->once())->method('getCredentialsSource')->will($this->returnValue('8bf0abbb93000e2e47f0e0a80721e834,80f117a78cff75f3f73793fd02aa9086'));

		$this->mockAccountRepository->expects($this->once())->method('findActiveByAccountIdentifierAndAuthenticationProviderName')->with('admin', 'myProvider')->will($this->returnValue($this->mockAccount));

		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'wrong password')));
		$this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

		$this->persistedUsernamePasswordProvider->authenticate($this->mockToken);
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
