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
	public function getRolesReturnsTheCorrectAuthorities() {
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

		$role1 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role1ClassName, FALSE);
		$role11 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role11ClassName, FALSE);
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken1ClassName);
		$token1->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($role1, $role11)));

		$role2 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role2ClassName, FALSE);
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken2ClassName);
		$token2->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($role2)));

		$role3 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role3ClassName, FALSE);
		$role33 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role33ClassName, FALSE);
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken3ClassName);
		$token3->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getRoles')->will($this->returnValue(array($role3, $role33)));

		$role4 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role4ClassName, FALSE);
		$role44 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role44ClassName, FALSE);
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

		$role6 = $this->getMock('F3\FLOW3\Security\ACL\Role', array(), array(), $role6ClassName, FALSE);
		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), $authenticationToken6ClassName);
		$token6->expects($this->any())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));
		$token6->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token6->expects($this->any())->method('getRoles')->will($this->returnValue(array($role6)));

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token2, $token3, $token4, $token5, $token6));
		$securityContext->setRequest($request);

		$this->assertEquals(array($role1, $role11, $role2, $role6), $securityContext->getRoles());
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
}
?>