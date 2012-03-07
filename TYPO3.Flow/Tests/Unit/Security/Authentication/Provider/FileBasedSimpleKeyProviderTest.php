<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\Provider;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Security\Authentication\Provider\FileBasedSimpleKeyProvider;

/**
 * Testcase for file based simple key authentication provider.
 *
 */
class FileBasedSimpleKeyProviderTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

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
		$mockHashService = $this->getMock('TYPO3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with($this->testKeyClearText, $this->testKeyHashed)->will($this->returnValue(TRUE));

		$mockFileBasedSimpleKeyService = $this->getMock('TYPO3\FLOW3\Security\Cryptography\FileBasedSimpleKeyService');
		$mockFileBasedSimpleKeyService->expects($this->once())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

		$mockToken = $this->getMock('TYPO3\FLOW3\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => $this->testKeyClearText)));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider', array('keyName' => 'testKey')));
		$authenticationProvider->_set('hashService', $mockHashService);
		$authenticationProvider->_set('fileBasedSimpleKeyService', $mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationFailsWithWrongCredentialsInAPasswordToken() {
		$mockHashService = $this->getMock('TYPO3\FLOW3\Security\Cryptography\HashService');
		$mockHashService->expects($this->once())->method('validatePassword')->with('wrong password', $this->testKeyHashed)->will($this->returnValue(FALSE));

		$mockFileBasedSimpleKeyService = $this->getMock('TYPO3\FLOW3\Security\Cryptography\FileBasedSimpleKeyService');
		$mockFileBasedSimpleKeyService->expects($this->once())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

		$mockToken = $this->getMock('TYPO3\FLOW3\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => 'wrong password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\FLOW3\Security\Authentication\TokenInterface::WRONG_CREDENTIALS);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider', array('keyName' => 'testKey')));
		$authenticationProvider->_set('hashService', $mockHashService);
		$authenticationProvider->_set('fileBasedSimpleKeyService', $mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationIsSkippedIfNoCredentialsInAPasswordToken() {
		$mockToken = $this->getMock('TYPO3\FLOW3\Security\Authentication\Token\PasswordToken', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array()));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);

		$authenticationProvider = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Provider\FileBasedSimpleKeyProvider', array('dummy'), array('myProvider'));

		$authenticationProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 */
	public function getTokenClassNameReturnsCorrectClassNames() {
		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');
		$this->assertSame($authenticationProvider->getTokenClassNames(), array('TYPO3\FLOW3\Security\Authentication\Token\PasswordToken'));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Security\Exception\UnsupportedAuthenticationTokenException
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface');

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

		$authenticationProvider->authenticate($someNiceToken);
	}

	/**
	 * @test
	 */
	public function canAuthenticateReturnsTrueOnlyForAnTokenThatHasTheCorrectProviderNameSet() {
		$mockToken1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('myProvider'));
		$mockToken2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken2->expects($this->once())->method('getAuthenticationProviderName')->will($this->returnValue('someOtherProvider'));

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

		$this->assertTrue($authenticationProvider->canAuthenticate($mockToken1));
		$this->assertFalse($authenticationProvider->canAuthenticate($mockToken2));
	}

}
?>