<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for username/password HTTP Basic Auth authentication token
 *
 */
class UsernamePasswordHttpBasicTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 */
	public function credentialsAreSetCorrectlyFromRequestHeadersArguments() {
		$requestHeaders = array(
			'User' => 'admin',
			'Pw' => 'password'
		);

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');

		$token = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic', array('dummy'));
		$token->_set('environment', $mockEnvironment);

		$token->updateCredentials($mockRequest);

		$expectedCredentials = array ('username' => 'admin', 'password' => 'password');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 * @category unit
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$requestHeaders = array(
			'User' => 'admin',
			'Pw' => 'password'
		);

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');

		$token = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic', array('dummy'));
		$token->_set('environment', $mockEnvironment);

		$token->updateCredentials($mockRequest);

		$this->assertSame(\TYPO3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived() {
		$requestHeaders = array(
			'Custom-Header' => 'xyt'
		);

		$mockEnvironment = $this->getMock('TYPO3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('TYPO3\FLOW3\Mvc\RequestInterface');

		$token = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic', array('dummy'));
		$token->_set('environment', $mockEnvironment);

		$token->updateCredentials($mockRequest);

		$this->assertSame(\TYPO3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN, $token->getAuthenticationStatus());
	}
}
?>