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
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Authentication\Token\UsernamePassword;

/**
 * Testcase for username/password authentication token
 *
 */
class UsernamePasswordTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromPostArguments() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'johndoe';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();

		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);

		$expectedCredentials = array ('username' => 'johndoe', 'password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.Flow';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();

		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);

		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function updateCredentialsIgnoresAnythingOtherThanPostRequests() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.Flow';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);
		$this->assertEquals(array('username' => 'TYPO3.Flow', 'password' => 'verysecurepassword'), $token->getCredentials());

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'GET', $arguments);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);
		$this->assertEquals(array('username' => '', 'password' => ''), $token->getCredentials());
	}

	/**
	 * @test
	 */
	public function tokenCanBeCastToString() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.Flow';
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);

		$this->assertEquals('Username: "TYPO3.Flow"', (string)$token);
	}
}
