<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Tests\Unit\Security;

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
class ContextTest extends \F3\FLOW3\Tests\UnitTestCase {

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
	public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly() {
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');

		$matchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$matchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$abstainingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$abstainingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(FALSE));
		$abstainingRequestPattern->expects($this->never())->method('matchRequest');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern)));
		$token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));

		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token5Provider'));

		$token6 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token6->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token6->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($abstainingRequestPattern, $matchingRequestPattern, $matchingRequestPattern)));
		$token6->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token6Provider'));

		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($token1, $token2, $token3, $token4, $token5, $token6)));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectSettings($settings);
		$securityContext->_set('tokens', array($token1, $token3, $token4));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->initialize($mockRequest);

		$this->assertEquals(array($token1, $token2, $token4, $token6), array_values($securityContext->_get('activeTokens')));
		$this->assertEquals(array($token3, $token5), array_values($securityContext->_get('inactiveTokens')));

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
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$token1Clone = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$token2Clone = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('tokens', $tokensFromTheSession);

		$securityContext->initialize($mockRequest);

		$expectedMergedTokens = array($token1Clone, $token2Clone, $token3);

		$this->assertEquals(array_values($securityContext->_get('tokens')), $expectedMergedTokens);
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeCallsUpdateCredentialsOnAllActiveTokens() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\RequestInterface');
		$mockAuthenticationManager = $this->getMock('F3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$notMatchingRequestPattern = $this->getMock('F3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$notMatchingRequestPattern->expects($this->any())->method('canMatch')->will($this->returnValue(TRUE));
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$mockToken1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$mockToken2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$mockToken2->expects($this->atLeastOnce())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$mockToken3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->never())->method('updateCredentials');
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
	 * Data provider for authentication strategy settings
	 *
	 * @return array
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function authenticationStrategies() {
		$data = array();
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
		$data[] = array($settings, \F3\FLOW3\Security\Context::AUTHENTICATE_ALL_TOKENS);
		$settings['security']['authentication']['authenticationStrategy'] = 'oneToken';
		$data[] = array($settings, \F3\FLOW3\Security\Context::AUTHENTICATE_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'atLeastOneToken';
		$data[] = array($settings, \F3\FLOW3\Security\Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'anyToken';
		$data[] = array($settings, \F3\FLOW3\Security\Context::AUTHENTICATE_ANY_TOKEN);
		return $data;
	}

	/**
	 * @dataProvider authenticationStrategies()
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy) {
		$securityContext = new \F3\FLOW3\Security\Context();
		$securityContext->injectSettings($settings);

		$this->assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
	}

	/**
	 * @test
	 * @category unit
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getRolesReturnsTheCorrectRoles() {
		$settings = array();

		$role1 = new \F3\FLOW3\Security\Policy\Role('role1');
		$role11 = new \F3\FLOW3\Security\Policy\Role('role11');
		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($role1, $role11)));
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$role2 = new \F3\FLOW3\Security\Policy\Role('role2');
		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($role2)));
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$role3 = new \F3\FLOW3\Security\Policy\Role('role3');
		$role33 = new \F3\FLOW3\Security\Policy\Role('role33');
		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token3->expects($this->any())->method('getRoles')->will($this->returnValue(array($role3, $role33)));
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$role4 = new \F3\FLOW3\Security\Policy\Role('role4');
		$role44 = new \F3\FLOW3\Security\Policy\Role('role44');
		$token4 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token4->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token4->expects($this->any())->method('getRoles')->will($this->returnValue(array($role4, $role44)));
		$token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));

		$role5 = new \F3\FLOW3\Security\Policy\Role('role5');
		$token5 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token5->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRoles')->will($this->returnValue(array($role5)));
		$token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token6Provider'));

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));

		$everybodyRole = new \F3\FLOW3\Security\Policy\Role('Everybody');

		$securityContextProxy = $this->buildAccessibleProxy('F3\FLOW3\Security\Context');
		$securityContext = new $securityContextProxy();
		$securityContext->injectSettings($settings);
		$securityContext->injectPolicyService($mockPolicyService);
		$securityContext->_set('activeTokens', array($token1, $token2, $token3, $token4, $token5));

		$expectedResult = array($everybodyRole, $role1, $role11, $role2, $role5);

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

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$securityContext->injectPolicyService($mockPolicyService);

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

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$result = $securityContext->getRoles();

		$this->assertInstanceOf('F3\FLOW3\Security\Policy\Role', $result[0]);
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

		$mockPolicyService = $this->getMock('F3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$securityContext->injectPolicyService($mockPolicyService);

		$result = $securityContext->getRoles();

		$everybodyRoleFound = FALSE;
		foreach ($result as $resultRole) {
			$this->assertInstanceOf('F3\FLOW3\Security\Policy\Role', $resultRole);
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
		require_once(FLOW3_PATH_PACKAGES . 'Framework/FLOW3/Resources/PHP/Doctrine/Common/Collections/Collection.php');
		require_once(FLOW3_PATH_PACKAGES . 'Framework/FLOW3/Resources/PHP/Doctrine/Common/Collections/ArrayCollection.php');

		$mockParty = $this->getMockForAbstractClass('F3\Party\Domain\Model\AbstractParty');

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

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPartyByTypeReturnsTheFirstAuthenticatedPartyWithGivenType() {
		require_once(FLOW3_PATH_PACKAGES . 'Framework/FLOW3/Resources/PHP/Doctrine/Common/Collections/Collection.php');
		require_once(FLOW3_PATH_PACKAGES . 'Framework/FLOW3/Resources/PHP/Doctrine/Common/Collections/ArrayCollection.php');

		$matchingMockParty = $this->getMockForAbstractClass('F3\Party\Domain\Model\AbstractParty', array(), 'MatchingParty');
		$notMatchingMockParty = $this->getMockForAbstractClass('F3\Party\Domain\Model\AbstractParty', array(), 'NotMatchingParty');

		$mockAccount1 = $this->getMock('F3\FLOW3\Security\Account');
		$mockAccount1->expects($this->any())->method('getParty')->will($this->returnValue($notMatchingMockParty));
		$mockAccount2 = $this->getMock('F3\FLOW3\Security\Account');
		$mockAccount2->expects($this->any())->method('getParty')->will($this->returnValue($matchingMockParty));

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token3'));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$mockContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertSame($matchingMockParty, $mockContext->getPartyByType('MatchingParty'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName() {
		$mockAccount1 = $this->getMock('F3\FLOW3\Security\Account');
		$mockAccount2 = $this->getMock('F3\FLOW3\Security\Account');

		$token1 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token1'));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token2'));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), uniqid('token3'));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$mockContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$mockContext->_set('activeTokens', array('SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3));

		$this->assertSame($mockAccount2, $mockContext->getAccountByAuthenticationProviderName('MatchingProvider'));
	}

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound() {
		$mockContext = $this->getAccessibleMock('F3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$mockContext->_set('activeTokens', array());

		$this->assertSame(NULL, $mockContext->getAccountByAuthenticationProviderName('UnknownProvider'));
	}

}
?>
