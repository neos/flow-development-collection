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
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;
		$request = $this->getMock('F3\FLOW3\MVC\Request');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'matchingRequestPattern');
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'notMatchingRequestPattern');
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'abstainingRequestPattern');
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken1');
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken2');
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken3');
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken4');
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken5');
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken6');
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
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;

		$request = $this->getMock('F3\FLOW3\MVC\Request');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'matchingRequestPattern11');
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'notMatchingRequestPattern11');
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'abstainingRequestPattern11');
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken21');
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken22');
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken23');
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken24');
		$token4->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken25');
		$token5->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken26');
		$token6->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->setRequest($request);

		$this->assertEquals(array($token1), $securityContext->getAuthenticationTokensOfType('F3\FLOW3\Security\Authentication\authenticationToken21'));
		$this->assertEquals(array(), $securityContext->getAuthenticationTokensOfType('F3\FLOW3\Security\Authentication\authenticationToken23'));
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getGrantedAuthoritiesReturnsTheCorrectAuthorities() {
		$settings = array();
		$settings['security']['authentication']['authenticateAllTokens'] = FALSE;

		$request = $this->getMock('F3\FLOW3\MVC\Request');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'matchingRequestPattern111');
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'notMatchingRequestPattern111');
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), 'abstainingRequestPattern111');
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$grantedAuthority1 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority1');
		$grantedAuthority11 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority11');
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken221');
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority1, $grantedAuthority11)));

		$grantedAuthority2 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority2');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken222');
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority2)));

		$grantedAuthority3 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority3');
		$grantedAuthority33 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority33');
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken223');
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority3, $grantedAuthority33)));

		$grantedAuthority4 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority4');
		$grantedAuthority44 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority44');
		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken224');
		$token4->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));
		$token4->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token4->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array($grantedAuthority4, $grantedAuthority44)));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken225');
		$token5->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getGrantedAuthorities')->will($this->returnValue(array()));

		$grantedAuthority6 = $this->getMock('F3\FLOW3\Security\Authentication\GrantedAuthorityInterface', array(), array(), 'grantedAuthority6');
		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'authenticationToken226');
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