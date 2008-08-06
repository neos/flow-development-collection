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
 * Testcase for for the session based security context holder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Security_ContextHolderSessionTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextIsStoredInTheSessionCorrectly() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);

		$mockSession->expects($this->once())->method('putData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'), $this->equalTo($mockContext));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->setContext($mockContext);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextIsRestoredFromTheSessionCorrectly() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);

		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'))->will($this->returnValue($mockContext));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$this->assertEquals($mockContext, $securityContextHolder->getContext());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getContextReturnsANewContextInstanceIfThereIsNoneInTheSession() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');

		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'))->will($this->returnValue(NULL));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->injectComponentFactory($this->componentFactory);
		$this->assertType('F3_FLOW3_Security_Context', $securityContextHolder->getContext());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function currentRequestIsSetInTheSecurityContext() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Request');

		$mockContext->expects($this->once())->method('setRequest')->with($mockRequest);
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'))->will($this->returnValue($mockContext));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->injectComponentFactory($this->componentFactory);
		$securityContextHolder->injectAuthenticationManager($this->getMock('F3_FLOW3_Security_Authentication_ManagerInterface'));

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextCallsTheAuthenticationManagerToSetItsTokens() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Request');
		$mockAuthenticationManager = $this->getMock('F3_FLOW3_Security_Authentication_ManagerInterface');

		$mockAuthenticationManager->expects($this->once())->method('getTokens');

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->injectComponentFactory($this->componentFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Request');
		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3_FLOW3_Security_Authentication_ManagerInterface');

		$token1 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'token1');
		$token1Clone = new token1();
		$token2 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'token2');
		$token2Clone = new token2();
		$token3 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface', array(), array(), 'token3');

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);
		$mergedTokens = array($token1Clone, $token2Clone, $token3);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue($tokensFromTheSession));
		$mockContext->expects($this->once())->method('setAuthenticationTokens')->with($this->identicalTo($mergedTokens));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->injectComponentFactory($this->componentFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeContextCallsUpdateCredentialsOnAllTokens() {
		$mockSession = $this->getMock('F3_FLOW3_Session_Interface');
		$mockRequest = $this->getMock('F3_FLOW3_MVC_Request');
		$mockContext = $this->getMock('F3_FLOW3_Security_Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3_FLOW3_Security_Authentication_ManagerInterface');

		$mockToken1 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface');
		$mockToken2 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface');
		$mockToken3 = $this->getMock('F3_FLOW3_Security_Authentication_TokenInterface');

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->once())->method('updateCredentials');
		$mockToken3->expects($this->once())->method('updateCredentials');
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3_FLOW3_Security_ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$securityContextHolder = new F3_FLOW3_Security_ContextHolderSession($mockSession);
		$securityContextHolder->injectComponentFactory($this->componentFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}
}
?>