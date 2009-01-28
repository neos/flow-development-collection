<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Provider;

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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
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