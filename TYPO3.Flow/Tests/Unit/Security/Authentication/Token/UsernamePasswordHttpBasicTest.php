<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security\Authentication\Token;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for username/password HTTP Basic Auth authentication token
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class UsernamePasswordHttpBasicTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function credentialsAreSetCorrectlyFromRequestHeadersArguments() {
		$requestHeaders = array(
			'User' => 'admin',
			'Pw' => 'password'
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');

		$token = new \F3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic();
		$token->injectEnvironment($mockEnvironment);

		$token->updateCredentials($mockRequest);

		$expectedCredentials = array ('username' => 'admin', 'password' => 'password');
		$this->assertEquals($expectedCredentials, $token->getCredentials(), 'The credentials have not been extracted correctly from the POST arguments');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNewCredentialsArrived() {
		$requestHeaders = array(
			'User' => 'admin',
			'Pw' => 'password'
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');

		$token = new \F3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic();
		$token->injectEnvironment($mockEnvironment);

		$token->updateCredentials($mockRequest);

		$this->assertSame(\F3\FLOW3\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED, $token->getAuthenticationStatus());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function updateCredentialsSetsTheCorrectAuthenticationStatusIfNoCredentialsArrived() {
		$requestHeaders = array(
			'Custom-Header' => 'xyt'
		);

		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);
		$mockEnvironment->expects($this->once())->method('getRequestHeaders')->will($this->returnValue($requestHeaders));
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');

		$token = new \F3\FLOW3\Security\Authentication\Token\UsernamePasswordHttpBasic();
		$token->injectEnvironment($mockEnvironment);

		$token->updateCredentials($mockRequest);

		$this->assertSame(\F3\FLOW3\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN, $token->getAuthenticationStatus());
	}
}
?>