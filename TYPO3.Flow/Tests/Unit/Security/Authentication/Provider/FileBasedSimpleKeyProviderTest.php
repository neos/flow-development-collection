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
use TYPO3\Flow\Security\Authentication\Token\PasswordToken;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService;
use TYPO3\Flow\Security\Cryptography\HashService;
use TYPO3\Flow\Security\Policy\PolicyService;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for file based simple key authentication provider.
 *
 */
class FileBasedSimpleKeyProviderTest extends UnitTestCase {

	/**
	 * @var string
	 */
	protected $testKeyClearText = 'password';

	/**
	 * @var string
	 */
	protected $testKeyHashed = 'pbkdf2=>DPIFYou4eD8=,nMRkJ9708Ryq3zIZcCLQrBiLQ0ktNfG8tVRJoKPTGcG/6N+tyzQHObfH5y5HCra1hAVTBrbgfMjPU6BipIe9xg==%';

	/**
	 * @var PolicyService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockPolicyService;

	/**
	 * @var FileBasedSimpleKeyService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockFileBasedSimpleKeyService;

	/**
	 * @var HashService|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockHashService;

	/**
	 * @var Role|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockRole;

	/**
	 * @var PasswordToken|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $mockToken;

	public function setUp() {
		$this->mockRole = $this->getMockBuilder('TYPO3\Flow\Security\Policy\Role')->disableOriginalConstructor()->getMock();
		$this->mockRole->expects($this->any())->method('getIdentifier')->will($this->returnValue('TYPO3.Flow:TestRoleIdentifier'));

		$this->mockPolicyService = $this->getMockBuilder('TYPO3\Flow\Security\Policy\PolicyService')->disableOriginalConstructor()->getMock();
		$this->mockPolicyService->expects($this->any())->method('getRole')->with('TYPO3.Flow:TestRoleIdentifier')->will($this->returnValue($this->mockRole));

		$this->mockHashService = $this->getMockBuilder('TYPO3\Flow\Security\Cryptography\HashService')->disableOriginalConstructor()->getMock();

		$expectedPassword = $this->testKeyClearText;
		$expectedHashedPasswordAndSalt = $this->testKeyHashed;
		$this->mockHashService->expects($this->any())->method('validatePassword')->will($this->returnCallback(function($password, $hashedPasswordAndSalt) use ($expectedPassword, $expectedHashedPasswordAndSalt) {
			return $hashedPasswordAndSalt === $expectedHashedPasswordAndSalt && $password === $expectedPassword;
		}));

		$this->mockFileBasedSimpleKeyService = $this->getMockBuilder('TYPO3\Flow\Security\Cryptography\FileBasedSimpleKeyService')->disableOriginalConstructor()->getMock();
		$this->mockFileBasedSimpleKeyService->expects($this->any())->method('getKey')->with('testKey')->will($this->returnValue($this->testKeyHashed));

		$this->mockToken = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\Token\PasswordToken')->disableOriginalConstructor()->getMock();
	}

	/**
	 * @test
	 */
	public function authenticatingAPasswordTokenChecksIfTheGivenClearTextPasswordMatchesThePersistedHashedPassword() {
		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => $this->testKeyClearText)));
		$this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::AUTHENTICATION_SUCCESSFUL);

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TYPO3.Flow:TestRoleIdentifier')));
		$this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
		$this->inject($authenticationProvider, 'hashService', $this->mockHashService);
		$this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($this->mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationAddsAnAccountHoldingTheConfiguredRoles() {
		$this->mockToken = $this->getMockBuilder('TYPO3\Flow\Security\Authentication\Token\PasswordToken')->disableOriginalConstructor()->setMethods(array('getCredentials'))->getMock();
		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => $this->testKeyClearText)));

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TYPO3.Flow:TestRoleIdentifier')));
		$this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
		$this->inject($authenticationProvider, 'hashService', $this->mockHashService);
		$this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($this->mockToken);

		$authenticatedRoles = $this->mockToken->getAccount()->getRoles();
		$this->assertTrue(in_array('TYPO3.Flow:TestRoleIdentifier', array_keys($authenticatedRoles)));
	}

	/**
	 * @test
	 */
	public function authenticationFailsWithWrongCredentialsInAPasswordToken() {
		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('password' => 'wrong password')));
		$this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::WRONG_CREDENTIALS);

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TYPO3.Flow:TestRoleIdentifier')));
		$this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
		$this->inject($authenticationProvider, 'hashService', $this->mockHashService);
		$this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($this->mockToken);
	}

	/**
	 * @test
	 */
	public function authenticationIsSkippedIfNoCredentialsInAPasswordToken() {
		$this->mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array()));
		$this->mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider', array('keyName' => 'testKey', 'authenticateRoles' => array('TYPO3.Flow:TestRoleIdentifier')));
		$this->inject($authenticationProvider, 'policyService', $this->mockPolicyService);
		$this->inject($authenticationProvider, 'hashService', $this->mockHashService);
		$this->inject($authenticationProvider, 'fileBasedSimpleKeyService', $this->mockFileBasedSimpleKeyService);

		$authenticationProvider->authenticate($this->mockToken);
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
		$someInvalidToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');

		$authenticationProvider = new FileBasedSimpleKeyProvider('myProvider');

		$authenticationProvider->authenticate($someInvalidToken);
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
