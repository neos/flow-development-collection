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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContextHolderSessionTest extends \F3\Testing\BaseTestCase {

	/**
	 * Set up.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setUp() {
		$mockObjectConfiguration = $this->getMock('F3\FLOW3\Object\Configuration', array(), array(), '', FALSE);
		$mockObjectBuilder = $this->getMock('F3\FLOW3\Object\Builder', array(), array(), '', FALSE);

		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\Manager', array('getObject', 'getObjectConfiguration', 'reinjectDependencies'), array(), '', FALSE);
		$this->mockObjectManager->expects($this->any())->method('getObjectConfiguration')->will($this->returnValue($mockObjectConfiguration));
		$this->mockObjectManager->expects($this->any())->method('getObject')->will($this->returnValue($mockObjectBuilder));
	}

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
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\ManagerInterface');

		$mockContext->expects($this->once())->method('setRequest')->with($mockRequest);
		$mockSession->expects($this->once())->method('getData')->with($this->equalTo('F3\FLOW3\Security\ContextHolderSession'))->will($this->returnValue($mockContext));
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectManager($this->mockObjectManager);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

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

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));

		$securityContextHolder = new \F3\FLOW3\Security\ContextHolderSession($mockSession);
		$securityContextHolder->injectObjectManager($this->mockObjectManager);
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
		$securityContextHolder->injectObjectManager($this->mockObjectManager);
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
		$securityContextHolder->injectObjectManager($this->mockObjectManager);
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
		$securityContextHolder->injectObjectManager($this->mockObjectManager);
		$securityContextHolder->injectObjectFactory($this->objectFactory);
		$securityContextHolder->injectAuthenticationManager($mockAuthenticationManager);

		$securityContextHolder->initializeContext($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterInactiveTokensWorks() {
		$mockConfigurationManager = $this->getMock('F3\FLOW3\Configuration\Manager', array(), array(), '', FALSE);
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;
		$mockConfigurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));
		$request = $this->getMock('F3\FLOW3\MVC\Request');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'matchingRequestPattern1');
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'notMatchingRequestPattern1');
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'abstainingRequestPattern1');
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken11');
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken12');
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken13');
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken14');
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken15');
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken16');
		$token6->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));

		$securityContextHolder = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\Security\ContextHolderSession'), array('dummy'), array(), '', FALSE);
		$resultTokens = $securityContextHolder->_call('filterInactiveTokens', array($token1, $token2, $token3, $token4, $token5, $token6), $request);

		$this->assertEquals(array($token1, $token2, $token4, $token6), $resultTokens);
	}
}
?>