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
 * Testcase for the security context
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContextTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function currentRequestIsSetInTheSecurityContext() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->initialize($mockRequest);

		$this->assertSame($mockRequest, $securityContext->_get('request'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSeparatesActiveAndInactiveTokens() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->expects($this->once())->method('separateActiveAndInactiveTokens');
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->initialize($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function securityContextCallsTheAuthenticationManagerToSetItsTokens() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = new \F3\FLOW3\Security\Context();
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->initialize($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession() {
		$token1ClassName = uniqid('token1');
		$token2ClassName = uniqid('token2');
		$token3ClassName = uniqid('token3');

		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $token1ClassName);
		$token1Clone = new $token1ClassName();
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $token2ClassName);
		$token2Clone = new $token2ClassName();
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $token3ClassName);

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('tokens', $tokensFromTheSession);

		$securityContext->initialize($mockRequest);

		$expectedMergedTokens = array($token1Clone, $token2Clone, $token3);

		$this->assertEquals($securityContext->_get('tokens'), $expectedMergedTokens);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeCallsUpdateCredentialsOnAllTokens() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');
		$mockToken3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface');

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->once())->method('updateCredentials');
		$mockToken3->expects($this->once())->method('updateCredentials');

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->initialize($mockRequest);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectAuthenticationManagerSetsAReferenceToTheSecurityContextInTheAuthenticationManager() {
		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'));
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('setSecurityContext')->with($securityContext);
		
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function filterInactiveTokensWorks() {
		$matchingRequestPatternClassName = uniqid('matchingRequestPattern');
		$notMatchingRequestPatternClassName = uniqid('notMatchingRequestPattern');
		$abstainingRequestPatternClassName = uniqid('abstainingRequestPattern');
		$authenticationToken1ClassName = uniqid('authenticationToken1');
		$authenticationToken2ClassName = uniqid('authenticationToken2');
		$authenticationToken3ClassName = uniqid('authenticationToken3');
		$authenticationToken4ClassName = uniqid('authenticationToken4');
		$authenticationToken5ClassName = uniqid('authenticationToken5');
		$authenticationToken6ClassName = uniqid('authenticationToken6');

		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $matchingRequestPatternClassName);
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $notMatchingRequestPatternClassName);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $abstainingRequestPatternClassName);
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken4ClassName);
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken5ClassName);
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$resultTokens = $securityContext->_call('filterInactiveTokens', array($token1, $token2, $token3, $token4, $token5, $token6), $request);

		$this->assertEquals(array($token1, $token2, $token4, $token6), $resultTokens);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokensReturnsOnlyTokensActiveForThisRequest() {
		$matchingRequestPatternClassName = uniqid('matchingRequestPattern');
		$notMatchingRequestPatternClassName = uniqid('notMatchingRequestPattern');
		$abstainingRequestPatternClassName = uniqid('abstainingRequestPattern');
		$authenticationToken1ClassName = uniqid('authenticationToken1');
		$authenticationToken2ClassName = uniqid('authenticationToken2');
		$authenticationToken3ClassName = uniqid('authenticationToken3');
		$authenticationToken4ClassName = uniqid('authenticationToken4');
		$authenticationToken5ClassName = uniqid('authenticationToken5');
		$authenticationToken6ClassName = uniqid('authenticationToken6');

		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;
		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $matchingRequestPatternClassName);
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $notMatchingRequestPatternClassName);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $abstainingRequestPatternClassName);
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken4ClassName);
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken5ClassName);
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->_set('request', $request);
		$securityContext->_call('separateActiveAndInactiveTokens');

		$this->assertEquals(array($token1, $token2, $token4, $token6), $securityContext->getAuthenticationTokens());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function authenticateAllTokensIsSetCorrectlyFromConfiguration() {
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = TRUE;

		$securityContext = new \F3\FLOW3\Security\Context();
		$securityContext->injectSettings($settings);

		$this->assertTrue($securityContext->authenticateAllTokens());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getAuthenticationTokenReturnsTheCorrectToken() {
		$matchingRequestPatternClassName = uniqid('matchingRequestPattern');
		$notMatchingRequestPatternClassName = uniqid('notMatchingRequestPattern');
		$abstainingRequestPatternClassName = uniqid('abstainingRequestPattern');
		$authenticationToken1ClassName = uniqid('authenticationToken1');
		$authenticationToken2ClassName = uniqid('authenticationToken2');
		$authenticationToken3ClassName = uniqid('authenticationToken3');
		$authenticationToken4ClassName = uniqid('authenticationToken4');
		$authenticationToken5ClassName = uniqid('authenticationToken5');
		$authenticationToken6ClassName = uniqid('authenticationToken6');

		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;

		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $matchingRequestPatternClassName);
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $notMatchingRequestPatternClassName);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $abstainingRequestPatternClassName);
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken4ClassName);
		$token4->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken5ClassName);
		$token5->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->_set('request', $request);
		$securityContext->_call('separateActiveAndInactiveTokens');

		$this->assertEquals(array($token1), $securityContext->getAuthenticationTokensOfType($authenticationToken1ClassName));
		$this->assertEquals(array(), $securityContext->getAuthenticationTokensOfType($authenticationToken3ClassName));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesReturnsTheCorrectRoles() {
		$matchingRequestPatternClassName = uniqid('matchingRequestPattern');
		$notMatchingRequestPatternClassName = uniqid('notMatchingRequestPattern');
		$abstainingRequestPatternClassName = uniqid('abstainingRequestPattern');
		$authenticationToken1ClassName = uniqid('authenticationToken1');
		$authenticationToken2ClassName = uniqid('authenticationToken2');
		$authenticationToken3ClassName = uniqid('authenticationToken3');
		$authenticationToken4ClassName = uniqid('authenticationToken4');
		$authenticationToken5ClassName = uniqid('authenticationToken5');
		$authenticationToken6ClassName = uniqid('authenticationToken6');
		$role1ClassName = uniqid('role1');
		$role11ClassName = uniqid('role11');
		$role2ClassName = uniqid('role2');
		$role3ClassName = uniqid('role3');
		$role33ClassName = uniqid('role33');
		$role4ClassName = uniqid('role4');
		$role44ClassName = uniqid('role44');
		$role6ClassName = uniqid('role6');

		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;

		$request = $this->getMock('F3\FLOW3\MVC\RequestInterface');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $matchingRequestPatternClassName);
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $notMatchingRequestPatternClassName);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), $abstainingRequestPatternClassName);
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$role1 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role1ClassName, FALSE);
		$role11 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role11ClassName, FALSE);
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($role1, $role11)));

		$role2 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role2ClassName, FALSE);
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($role2)));

		$role3 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role3ClassName, FALSE);
		$role33 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role33ClassName, FALSE);
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRoles')->will($this->returnValue(array($role3, $role33)));

		$role4 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role4ClassName, FALSE);
		$role44 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role44ClassName, FALSE);
		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken4ClassName);
		$token4->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));
		$token4->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token4->expects($this->any())->method('getRoles')->will($this->returnValue(array($role4, $role44)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken5ClassName);
		$token5->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRoles')->will($this->returnValue(array()));

		$role6 = $this->getMock('F3\FLOW3\Security\Policy\Role', array(), array(), $role6ClassName, FALSE);
		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));
		$token6->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRoles')->will($this->returnValue(array($role6)));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));

		$everybodyRole = new \F3\FLOW3\Security\Policy\Role('Everybody');
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Policy\Role', 'Everybody')->will($this->returnValue($everybodyRole));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->injectPolicyService($mockPolicyService);
		$securityContext->injectObjectManager($mockObjectManager);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->_set('request', $request);
		$securityContext->_call('separateActiveAndInactiveTokens');

		$expectedResult = array($everybodyRole, $role1, $role11, $role2, $role6);

		$this->assertEquals($expectedResult, $securityContext->getRoles());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesTakesInheritanceOfRolesIntoAccount() {
		$role1 = new \F3\FLOW3\Security\Policy\Role('role1');
		$role2 = new \F3\FLOW3\Security\Policy\Role('role2');
		$role3 = new \F3\FLOW3\Security\Policy\Role('role3');
		$role4 = new \F3\FLOW3\Security\Policy\Role('role4');
		$role5 = new \F3\FLOW3\Security\Policy\Role('role5');
		$role6 = new \F3\FLOW3\Security\Policy\Role('role6');
		$role7 = new \F3\FLOW3\Security\Policy\Role('role7');
		$role8 = new \F3\FLOW3\Security\Policy\Role('role8');
		$role9 = new \F3\FLOW3\Security\Policy\Role('role9');
		$everybodyRole = new \F3\FLOW3\Security\Policy\Role('Everybody');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRoles')->will($this->returnValue(array($role1, $role2, $role3)));
		
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getRoles')->will($this->returnValue(array($role2, $role4, $role5)));

		$policyServiceCallback = function() use (&$role1, &$role2, &$role5, &$role6, &$role7, &$role8, &$role9) {
			$args = func_get_args();

			if ((string)$args[0] === 'role1') return array($role6);
			if ((string)$args[0] === 'role2') return array($role6, $role7);
			if ((string)$args[0] === 'role5') return array($role8, $role9);

			return array();
		};

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnCallback($policyServiceCallback));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Policy\Role', 'Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$securityContext->injectPolicyService($mockPolicyService);
		$securityContext->injectObjectManager($mockObjectManager);

		$expectedResult = array($everybodyRole, $role1, $role2, $role3, $role4, $role5, $role6, $role7, $role8, $role9);
		$result = $securityContext->getRoles();

		sort($expectedResult);
		sort($result);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated() {
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$everybodyRole = new \F3\FLOW3\Security\Policy\Role('Everybody');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Policy\Role', 'Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$securityContext->injectObjectManager($mockObjectManager);

		$result = $securityContext->getRoles();

		$this->assertType('F3\FLOW3\Security\Policy\Role', $result[0]);
		$this->assertEquals('Everybody', (string)($result[0]));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesAddsTheEverybodyRoleToTheRolesFromTheAuthenticatedTokens() {
		$role1 = new \F3\FLOW3\Security\Policy\Role('Role1');
		$role2 = new \F3\FLOW3\Security\Policy\Role('Role2');
		$role3 = new \F3\FLOW3\Security\Policy\Role('Role3');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRoles')->will($this->returnValue(array($role1)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getRoles')->will($this->returnValue(array($role2, $role3)));

		$everybodyRole = new \F3\FLOW3\Security\Policy\Role('Everybody');

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface', array(), array(), '', FALSE);
		$mockObjectManager->expects($this->once())->method('get')->with('F3\FLOW3\Security\Policy\Role', 'Everybody')->will($this->returnValue($everybodyRole));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$securityContext->injectObjectManager($mockObjectManager);
		$securityContext->injectPolicyService($mockPolicyService);

		$result = $securityContext->getRoles();

		$everybodyRoleFound = FALSE;
		foreach ($result as $resultRole) {
			$this->assertType('F3\FLOW3\Security\Policy\Role', $resultRole);
			if ('Everybody' === (string)($resultRole)) $everybodyRoleFound = TRUE;
		}

		$this->assertTrue($everybodyRoleFound, 'The Everybody role could not be found as expected.');
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRoleWorks() {
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array('Administrator', 'LicenseToKill')));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array('Customer')));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$this->assertTrue($securityContext->hasRole('LicenseToKill'));
		$this->assertFalse($securityContext->hasRole('Customer'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRoleReturnsTrueForTheEverybodyRoleIfNoOtherRoleIsAuthenticated() {
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$this->assertTrue($securityContext->hasRole('Everybody'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function hasRoleReturnsFalseForTheEverybodyRoleIfAtLeastOneOtherRoleIsAuthenticated() {
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array('Administrator', 'LicenseToKill')));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array('Customer')));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$this->assertFalse($securityContext->hasRole('Everybody'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getPartyAsksTheCorrectAuthenticationTokenAndReturnsItsParty() {
		$mockParty = $this->getMock('F3\Party\Domain\Model\Party');

		$mockAccount = $this->getMock('F3\FLOW3\Security\Account');
		$mockAccount->expects($this->once())->method('getParty')->will($this->returnValue($mockParty));

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token3'));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$mockContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockParty, $mockContext->getParty());
	}

	/**
	 * @test
	 * @category unit
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken() {
		$mockAccount = $this->getMock('F3\FLOW3\Security\Account');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token3'));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$mockContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockAccount, $mockContext->getAccount());
	}

}
?>