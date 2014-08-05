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

use TYPO3\Flow\Http\Request as HttpRequest;
use TYPO3\Flow\Mvc\ActionRequest;
use	TYPO3\Flow\Security\Authentication\TokenInterface;
use	TYPO3\Flow\Security\Authentication\Token\PasswordToken;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for password authentication token
 */
class PasswordTokenTest extends UnitTestCase {

	/**
	 * @var PasswordToken
	 */
	protected $token;

	/**
	 * @var ActionRequest
	 */
	protected $mockActionRequest;

	/**
	 * @var HttpRequest
	 */
	protected $mockHttpRequest;

	/**
	 * Set up this test case
	 */
	public function setUp() {
		$this->token = new PasswordToken();

		$this->mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($this->mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function credentialsAreSetCorrectlyFromPostArguments() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
		$this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

		$this->token->updateCredentials($this->mockActionRequest);

		$expectedCredentials = array('password' => 'verysecurepassword');
		$this->assertEquals($expectedCredentials, $this->token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
		$this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

		$this->token->updateCredentials($this->mockActionRequest);

		$this->assertSame(TokenInterface::AUTHENTICATION_NEEDED, $this->token->getAuthenticationStatus());
	}

	/**
	 * @test
	 */
	public function updateCredentialsIgnoresAnythingOtherThanPostRequests() {
		$arguments = array();
		$arguments['__authentication']['TYPO3']['Flow']['Security']['Authentication']['Token']['PasswordToken']['password'] = 'verysecurepassword';

		$this->mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('POST'));
		$this->mockActionRequest->expects($this->atLeastOnce())->method('getInternalArguments')->will($this->returnValue($arguments));

		$this->token->updateCredentials($this->mockActionRequest);
		$this->assertEquals(array('password' => 'verysecurepassword'), $this->token->getCredentials());

		$secondToken = new PasswordToken();
		$secondMockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$secondMockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$secondMockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($secondMockHttpRequest));
		$secondMockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('GET'));
		$secondToken->updateCredentials($secondMockActionRequest);
		$this->assertEquals(array('password' => ''), $secondToken->getCredentials());
	}
}
