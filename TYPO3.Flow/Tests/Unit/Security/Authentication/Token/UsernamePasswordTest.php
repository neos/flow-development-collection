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
use TYPO3\FLOW3\Security\Authentication\TokenInterface;
use TYPO3\FLOW3\Security\Authentication\Token\UsernamePassword;

/**
 * Testcase for username/password authentication token
 *
 */
class UsernamePasswordTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromPostArguments() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'johndoe';
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

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
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.FLOW3';
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

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
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.FLOW3';
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);
		$this->assertEquals(array('username' => 'TYPO3.FLOW3', 'password' => 'verysecurepassword'), $token->getCredentials());

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
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['username'] = 'TYPO3.FLOW3';
		$arguments['__authentication']['TYPO3']['FLOW3']['Security']['Authentication']['Token']['UsernamePassword']['password'] = 'verysecurepassword';

		$request = Request::create(new Uri('http://robertlemke.com/login'), 'POST', $arguments);
		$actionRequest = $request->createActionRequest();
		$token = new UsernamePassword();
		$token->updateCredentials($actionRequest);

		$this->assertEquals('Username: "TYPO3.FLOW3"', (string)$token);
	}
}
?>