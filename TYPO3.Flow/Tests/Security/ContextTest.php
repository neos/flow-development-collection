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
 * Testcase for the security context
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContextTest extends \F3\Testing\BaseTestCase {

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
		$securityContext->setRequest($request);

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
		$securityContext->setRequest($request);

		$this->assertEquals(array($token1), $securityContext->getAuthenticationTokensOfType($authenticationToken1ClassName));
		$this->assertEquals(array(), $securityContext->getAuthenticationTokensOfType($authenticationToken3ClassName));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getGrantedAuthoritiesReturnsTheCorrectAuthorities() {
		$matchingRequestPatternClassName = uniqid('matchingRequestPattern');
		$notMatchingRequestPatternClassName = uniqid('notMatchingRequestPattern');
		$abstainingRequestPatternClassName = uniqid('abstainingRequestPattern');
		$authenticationToken1ClassName = uniqid('authenticationToken1');
		$authenticationToken2ClassName = uniqid('authenticationToken2');
		$authenticationToken3ClassName = uniqid('authenticationToken3');
		$authenticationToken4ClassName = uniqid('authenticationToken4');
		$authenticationToken5ClassName = uniqid('authenticationToken5');
		$authenticationToken6ClassName = uniqid('authenticationToken6');
		$grantedAuthority1ClassName = uniqid('grantedAuthority1');
		$grantedAuthority11ClassName = uniqid('grantedAuthority11');
		$grantedAuthority2ClassName = uniqid('grantedAuthority2');
		$grantedAuthority3ClassName = uniqid('grantedAuthority3');
		$grantedAuthority33ClassName = uniqid('grantedAuthority33');
		$grantedAuthority4ClassName = uniqid('grantedAuthority4');
		$grantedAuthority44ClassName = uniqid('grantedAuthority44');
		$grantedAuthority6ClassName = uniqid('grantedAuthority6');

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

		$grantedAuthority1 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority1ClassName);
		$grantedAuthority11 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority11ClassName);
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority1, $grantedAuthority11)));

		$grantedAuthority2 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority2ClassName);
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority2)));

		$grantedAuthority3 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority3ClassName);
		$grantedAuthority33 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority33ClassName);
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority3, $grantedAuthority33)));

		$grantedAuthority4 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority4ClassName);
		$grantedAuthority44 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority44ClassName);
		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken4ClassName);
		$token4->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));
		$token4->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token4->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority4, $grantedAuthority44)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken5ClassName);
		$token5->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array()));

		$grantedAuthority6 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), $grantedAuthority6ClassName);
		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));
		$token6->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority6)));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->setRequest($request);

		$this->assertEquals(array($grantedAuthority1, $grantedAuthority11, $grantedAuthority2, $grantedAuthority6), $securityContext->getGrantedAuthorities());
	}
}
?>