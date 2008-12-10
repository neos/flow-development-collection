<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Provider;

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
 * @version $Id$
 */

/**
 * Testcase for username/password authentication provider
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class UsernamePasswordTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAUsernamePasswordTokenWorks() {
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'admin', 'password' => 'password')));
		$mockToken->expects($this->once())->method('setAuthenticationStatus')->with(TRUE);

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\UsernamePassword();
		$usernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticationFailsWithWrongCredentialsInAUsernamePasswordToken() {
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('getCredentials')->will($this->returnValue(array('username' => 'flow3', 'password' => 'wrongpassword')));
		$mockToken->expects($this->never())->method('setAuthenticationStatus');

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\UsernamePassword();
		$usernamePasswordProvider->authenticate($mockToken);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticatingAnUnsupportedTokenThrowsAnException() {
		$someNiceToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\UsernamePassword();

		try {
			$usernamePasswordProvider->authenticate($someNiceToken);
			$this->fail('No exception has been thrown.');
		} catch (\F3\FLOW3\Security\Exception\UnsupportedAuthenticationToken $exception) {

		}
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function canAuthenticateReturnsTrueOnlyForTheUsernamePasswordToken() {
		$mockUserNamePasswordToken = $this->getMock('F3\FLOW3\Security\Authentication\Token\UsernamePassword', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$usernamePasswordProvider = new \F3\FLOW3\Security\Authentication\Provider\UsernamePassword();

		$this->assertTrue($usernamePasswordProvider->canAuthenticate($mockUserNamePasswordToken));
		$this->assertFalse($usernamePasswordProvider->canAuthenticate($mockToken));
	}
}
?>