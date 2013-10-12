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

use TYPO3\Flow\Http\Request,
	TYPO3\Flow\Http\Uri,
	TYPO3\Flow\Security\Authentication\TokenInterface,
	TYPO3\Flow\Security\Authentication\Token\PasswordToken;

/**
 * Testcase for password authentication token
 *
 */
class PasswordTokenTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromPostArguments() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$actionRequest = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments)->createActionRequest();

		$token = new PasswordToken();
		$token->updateCredentials($actionRequest);

		$expectedCredentials = array('password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$actionRequest = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments)->createActionRequest();

		$token = new PasswordToken();
		$token->updateCredentials($actionRequest);

		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function updateCredentialsIgnoresAnythingOtherThanPostRequests() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$actionRequest = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments)->createActionRequest();
		$token = new PasswordToken();
		$token->updateCredentials($actionRequest);
		$this->assertEquals(array('password' => 'verysecurepassword'), $token->getCredentials());

		$actionRequest = Request::create(new Uri('http://robertlemke.com/login'), 'GET', $arguments)->createActionRequest();
		$token = new PasswordToken();
		$token->updateCredentials($actionRequest);
		$this->assertEquals(array('password' => ''), $token->getCredentials());
	}
}
