<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security;

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
 * Testcase for for the session based security context holder
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class ContextHolderSessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextIsStoredInTheSessionCorrectly() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$mockSession->expects($this->once())->method('putData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'), $this->equalTo($mockContext));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->setContext($mockContext);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextIsRestoredFromTheSessionCorrectly() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);

		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$this->assertEquals($mockContext, $securityContextHolder->getContext());
	}

	/**
	 * @test
	 * @category unit
	 * @expectedException \F3\FLOW3\Security\Exception\NoContextAvailable
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getContextThrowsAnExceptionIfThereIsNoContextInTheSession() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');

		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue(NULL));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->getContext();
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function currentRequestIsSetInTheSecurityContext() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');

		$mockContext->expects($this->once())->method('setRequest')->with($mockRequest);
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface'));

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextCallsTheAuthenticationManagerToSetItsTokens() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');

		$mockAuthenticationManager->expects($this->once())->method('getTokens');

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1');
		$token1Clone = new \F3\FLOW3\Security\Authentication\token1();
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2');
		$token2Clone = new \F3\FLOW3\Security\Authentication\token2();
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token3');

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);
		$mergedTokens = array($token1Clone, $token2Clone, $token3);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue($tokensFromTheSession));
		$mockContext->expects($this->once())->method('setAuthenticationTokens')->with($this->identicalTo($mergedTokens));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeContextCallsUpdateCredentialsOnAllTokens() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');

		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->once())->method('updateCredentials');
		$mockToken3->expects($this->once())->method('updateCredentials');
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theSecurityContextHolderSetsAReferenceToTheSecurityContextInTheAuthenticationManager() {
		$mockSession = $this->getMock('F3\FLOW3\Session\SessionInterface');
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Request');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');

		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array()));
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));
		$mockAuthenticationManager->expects($this->once())->method('setSecurityContext')->with($mockContext);

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}
}
?>