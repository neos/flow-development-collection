<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for username/password authentication provider
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_Authentication_Provider_UsernamePasswordTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAUsernamePasswordTokenWorks() {
		$mockToken = $this->getMock('F3_FLOW3_Security_Authentication_Token_UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'FLOW3', 'password' => 'verysecurepassword')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TRUE);

		$usernamePasswordProvider = new F3_FLOW3_Security_Authentication_Provider_UsernamePassword();
		$usernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticationFailsWithWrongCredentialsInAUsernamePasswordToken() {
		$mockToken = $this->getMock('F3_FLOW3_Security_Authentication_Token_UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'flow3', 'password' => 'wrongpassword')));
		$mockToken->expects($this->never())->method('setAuthenticationStatus');

		$usernamePasswordProvider = new F3_FLOW3_Security_Authentication_Provider_UsernamePassword();
		$usernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface');

		$usernamePasswordProvider = new F3_FLOW3_Security_Authentication_Provider_UsernamePassword();

		try {
			$usernamePasswordProvider->authenticate($someNiceToken);
			$this->fail('No exception has been thrown.');
		} catch (F3_FLOW3_Security_Exception_UnsupportedAuthenticationToken $exception) {

		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticateReturnsTrueOnlyForTheUsernamePasswordTokenClass() {
		$usernamePasswordProvider = new F3_FLOW3_Security_Authentication_Provider_UsernamePassword();

		$this->assertTrue($usernamePasswordProvider->canAuthenticate('F3_FLOW3_Security_Authentication_Token_UsernamePassword'));
		$this->assertFalse($usernamePasswordProvider->canAuthenticate('F3_TestPackage_TestAuthenticationToken'));
	}
}
?>