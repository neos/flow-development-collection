<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
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
use TYPO3\Flow\Security\Authentication;

class AuthenticationControllerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function authenticateActionCallsAuthenticateOfTheAuthenticationManager() {
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('authenticate');

		$mockFlashMessageContainer = $this->getMock('TYPO3\Flow\Mvc\FlashMessageContainer');
		$authenticationController = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', FALSE);
		$authenticationController->_set('authenticationManager', $mockAuthenticationManager);
		$authenticationController->_set('securityContext', $mockSecurityContext);
		$authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$authenticationController->authenticateAction();
	}

	/**
	 * @test
	 */
	public function logoutActionCallsLogoutOfTheAuthenticationManager() {
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('logout');

		$mockFlashMessageContainer = $this->getMock('TYPO3\Flow\Mvc\FlashMessageContainer');
		$authenticationController = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', FALSE);
		$authenticationController->_set('authenticationManager', $mockAuthenticationManager);
		$authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

		$authenticationController->logoutAction();
	}
}
?>