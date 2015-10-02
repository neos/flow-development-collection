<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\Controller;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * Testcase for the authentication controller
 *
 */
use TYPO3\Flow\Security\Authentication;

class AuthenticationControllerTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function authenticateActionCallsAuthenticateOfTheAuthenticationManager()
    {
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $mockAuthenticationManager->expects($this->once())->method('authenticate');

        $mockFlashMessageContainer = $this->getMock('TYPO3\Flow\Mvc\FlashMessageContainer');
        $authenticationController = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', false);
        $authenticationController->_set('authenticationManager', $mockAuthenticationManager);
        $authenticationController->_set('securityContext', $mockSecurityContext);
        $authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

        $authenticationController->authenticateAction();
    }

    /**
     * @test
     */
    public function logoutActionCallsLogoutOfTheAuthenticationManager()
    {
        $mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $mockAuthenticationManager->expects($this->once())->method('logout');

        $mockFlashMessageContainer = $this->getMock('TYPO3\Flow\Mvc\FlashMessageContainer');
        $authenticationController = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\Controller\AuthenticationController', array('errorAction'), array(), '', false);
        $authenticationController->_set('authenticationManager', $mockAuthenticationManager);
        $authenticationController->_set('flashMessageContainer', $mockFlashMessageContainer);

        $authenticationController->logoutAction();
    }
}
