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

use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Uri;
use TYPO3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic;
use TYPO3\FLOW3\Security\Authentication\TokenInterface;

/**
 * Testcase for username/password HTTP Basic Auth authentication token
 *
 */
class UsernamePasswordHttpBasicTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromRequestHeadersArguments() {
		$serverEnvironment = array(
			'PHP_AUTH_USER' => 'robert',
			'PHP_AUTH_PW' => 'mysecretpassword, containing a : colon ;-)'
		);

		$request = Request::create(new Uri('http://foo.com'), 'GET', array(), array(), array(), $serverEnvironment);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePasswordHttpBasic();
		$token->updateCredentials($actionRequest);

		$expectedCredentials = array ('username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)');
		$this->assertEquals($expectedCredentials, $token->getCredentials());
		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyForCGI() {
		$expectedCredentials = array ('username' => 'robert', 'password' => 'mysecretpassword, containing a : colon ;-)');

		$serverEnvironment = array(
			'REDIRECT_REMOTE_AUTHORIZATION' => 'Basic ' . base64_encode($expectedCredentials['username'] . ':' . $expectedCredentials['password'])
		);

		$request = Request::create(new Uri('http://foo.com'), 'GET', array(), array(), array(), $serverEnvironment);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePasswordHttpBasic();
		$token->updateCredentials($actionRequest);

		$this->assertEquals($expectedCredentials, $token->getCredentials());
		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived() {
		$request = Request::create(new Uri('http://foo.com'));
		$actionRequest = $request->createActionRequest();

		$token = new UsernamePasswordHttpBasic();
		$token->updateCredentials($actionRequest);

		$this->assertSame(TokenInterface::NO_CREDENTIALS_GIVEN, $token->getAuthenticationStatus());
	}
}
?>