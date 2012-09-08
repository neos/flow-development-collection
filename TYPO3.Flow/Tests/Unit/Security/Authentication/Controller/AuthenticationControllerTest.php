<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authentication\Controller;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the authentication controller
 *
 */
use TYPO3\FLOW3\Security\Authentication;

class AuthenticationControllerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function authenticateActionCallsAuthenticateOfTheAuthenticationManager() {
		$mockSecurityContext = $this->getMock('TYPO3\FLOW3\Security\Context');
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('authenticate');

		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer');
		$authenticationController = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', FALSE);
		$authenticationController->_set('authenticationManager', $mockAuthenticationManager);
		$authenticationController->_set('securityContext', $mockSecurityContext);
		$authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$authenticationController->authenticateAction();
	}

	/**
	 * @test
	 */
	public function logoutActionCallsLogoutOfTheAuthenticationManager() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('logout');

		$mockFlashMessageContainer = $this->getMock('TYPO3\FLOW3\Mvc\FlashMessageContainer');
		$authenticationController = $this->getAccessibleMock('TYPO3\FLOW3\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', FALSE);
		$authenticationController->_set('authenticationManager', $mockAuthenticationManager);
		$authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$authenticationController->logoutAction();
	}
}
?>