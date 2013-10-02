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

use TYPO3\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider;

/**
 * Testcase for file based simple key authentication provider.
 *
 */
class FileBasedSimpleKeyProviderTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var string
	 */
	protected $testKeyClearText = 'password';

	/**
	 * @var string
	 */
	protected $testKeyHashed = 'pbkdf2=>DPIFYou4eD8=,nMRkJ9708Ryq3zIZcCLQrBiLQ0ktNfG8tVRJoKPTGcG/6N+tyzQHObfH5y5HCra1hAVTBrbgfMjPU6BipIe9xg==%';

	/**
	 * @test
	 */
	public function authenticatingAPasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword() {
		$mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService');

		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with($this->testKeyClearText, $this->testKeyHashed)->will($this->returnValue(TRUE));

		$mockFileBasedSimpleKeyService = $this->getMock('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService');
		$mockFileBasedSimpleKeyService->expects($this->once())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => $this->testKeyClearText)));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TestRoleIdentifier'))));
		$authenticationProvider->_set('hashService', $mockHashService);
		$authenticationProvider->_set('fileBasedSimpleKeyService', $mockFileBasedSimpleKeyService);
		$authenticationProvider->_set('policyService', $mockPolicyService);

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationAddsAnAccountHoldingTheConfiguredRoles() {
		$someRole = new \TYPO3\Flow\Security\Policy\Role('TestRoleIdentifier');

		$mockPolicyService = $this->getMock('TYPO3\Flow\Security\Policy\PolicyService');
		$mockPolicyService->expects($this->once())->method('getRole')->with('TestRoleIdentifier')->will($this->returnValue($someRole));

		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->will($this->returnValue(TRUE));

		$mockFileBasedSimpleKeyService = $this->getMock('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService');

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\PasswordToken', array('dummy'), array(), '', FALSE);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TestRoleIdentifier'))));
		$authenticationProvider->_set('hashService', $mockHashService);
		$authenticationProvider->_set('fileBasedSimpleKeyService', $mockFileBasedSimpleKeyService);
		$authenticationProvider->_set('policyService', $mockPolicyService);

		$authenticationProvider->authenticate($mockToken);

		$this->assertTrue($mockToken->getAccount()->hasRole($someRole));
	}

	/**
	 * @test
	 */
	public function authenticationFailsWithWrongCredentialsInAPasswordToken() {
		$mockHashService = $this->getMock('TYPO3\Flow\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with('wrong password', $this->testKeyHashed)->will($this->returnValue(FALSE));

		$mockFileBasedSimpleKeyService = $this->getMock('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService');
		$mockFileBasedSimpleKeyService->expects($this->once())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => 'wrong password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider', array('keyName' => 'testKey')));
		$authenticationProvider->_set('hashService', $mockHashService);
		$authenticationProvider->_set('fileBasedSimpleKeyService', $mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationIsSkippedIfNoCredentialsInAPasswordToken() {
		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array()));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider'));

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function getTokenClassNameReturnsCorrectClassNames() {
		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');
		$this->assertSame($authenticationProvider->getTokenClassNames(), array('TYPO3\Flow\Security\Authentication\Token\PasswordToken'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

		$authenticationProvider->authenticate($someNiceToken);
	}

	/**
	 * @test
	 */
	public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet() {
		$mockToken1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('myProvider'));
		$mockToken2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken2->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('someOtherProvider'));

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

		$this->assertTrue($authenticationProvider->canAuthenticate($mockToken1));
		$this->assertFalse($authenticationProvider->canAuthenticate($mockToken2));
	}

}
?>