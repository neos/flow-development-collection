<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\Authentication\Token\UsernamePasswordHttpBasic;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for username/password HTTP Basic Auth authentication token
 *
 */
class UsernamePasswordHttpBasicTest extends UnitTestCase {

	/**
	 * @var UsernamePasswordHttpBasic
	 */
	protected $token;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->token = new UsernamePasswordHttpBasic();
	}

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromRequestHeadersArguments() {
		$serverEnvironment = array(
			'PHP_AUTH_USER' => 'robert',
			'PHP_AUTH_PW' => 'mysecretpassword, containing a : colon ;-)'
		);

		$httpRequest = Request::create(new Uri('http://foo.com'), 'GET', array(), array(), $serverEnvironment);
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));

		$this->token->updateCredentials($mockActionRequest);

		$expectedCredentials = array ('username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)');
		$this->assertEquals($expectedCredentials, $this->token->getCredentials());
		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyForCGI() {
		$expectedCredentials = array ('username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)');

		$serverEnvironment = array(
			'REDIRECT_REMOTE_AUTHORIZATION' => 'Basic ' . base64_encode($expectedCredentials['username'] . ':' . $expectedCredentials['password'])
		);

		$httpRequest = Request::create(new Uri('http://foo.com'), 'GET', array(), array(), $serverEnvironment);
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));
		$this->token->updateCredentials($mockActionRequest);

		$this->assertEquals($expectedCredentials, $this->token->getCredentials());
		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived() {
		$httpRequest = Request::create(new Uri('http://foo.com'));
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($httpRequest));
		$this->token->updateCredentials($mockActionRequest);

		$this->assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $this->token->getAuthenticationStatus());
	}
}
