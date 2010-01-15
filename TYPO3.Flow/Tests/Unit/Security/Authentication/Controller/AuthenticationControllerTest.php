<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Authentication\Controller;

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
 * Testcase for the authentication controller
 *
 * @version $Id: EntryPointResolverTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
use F3\FLOW3\Security\Authentication;

class AuthenticationControllerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateActionCallsAuthenticateOfTheAuthenticationManager() {
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('authenticate');

		$authenticationController = $this->getMock('F3\FLOW3\Security\Authentication\Controller\AuthenticationController', array('dummy'), array(), '', FALSE);
		$authenticationController->injectAuthenticationManager($mockAuthenticationManager);

		$authenticationController->authenticateAction();
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function logoutActionCallsLogoutOfTheAuthenticationManager() {
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('logout');

		$authenticationController = $this->getMock('F3\FLOW3\Security\Authentication\Controller\AuthenticationController', array('dummy'), array(), '', FALSE);
		$authenticationController->injectAuthenticationManager($mockAuthenticationManager);

		$authenticationController->logoutAction();
	}
}
?>