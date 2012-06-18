<?php
namespace TYPO3\FLOW3\Tests\Unit\Security;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Request;
use TYPO3\FLOW3\Http\Uri;
use TYPO3\FLOW3\Security\Policy\Role;

/**
 * Testcase for the security context
 */
class ContextTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function currentRequestIsSetInTheSecurityContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->injectRequest($request);

		$securityContext->_call('initialize');

		$this->assertSame($request, $securityContext->_get('request'));
	}

	/**
	 * @test
	 */
	public function securityContextIsSetToInitialized() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$mockRequestHandler = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequestHandler', array(), array(), '', FALSE);
		$mockRequestHandler->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$bootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);
		$bootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('bootstrap', $bootstrap);

		$this->assertFalse($securityContext->isInitialized());
		$securityContext->_call('initialize');
		$this->assertTrue($securityContext->isInitialized());
	}

	/**
	 * @test
	 */
	public function initializeSeparatesActiveAndInactiveTokens() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$mockRequestHandler = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequestHandler', array(), array(), '', FALSE);
		$mockRequestHandler->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$bootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);
		$bootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->expects($this->once())->method('separateActiveAndInactiveTokens');
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('bootstrap', $bootstrap);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly() {
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'allTokens';

		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$matchingRequestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$token4 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array()));
		$token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));

		$token5 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token5Provider'));

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($token1, $token2, $token3, $token4, $token5)));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectSettings($settings);
		$securityContext->injectRequest($request);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('tokens', array($token1, $token3, $token4));

		$securityContext->_call('initialize');

		$this->assertEquals(array($token1, $token2, $token4), array_values($securityContext->_get('activeTokens')));
		$this->assertEquals(array($token3, $token5), array_values($securityContext->_get('inactiveTokens')));

	}

	/**
	 * @test
	 */
	public function securityContextCallsTheAuthenticationManagerToSetItsTokens() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$token1Clone = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$token2Clone = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->injectRequest($request);
		$securityContext->_set('tokens', $tokensFromTheSession);

		$securityContext->_call('initialize');

		$expectedMergedTokens = array($token1Clone, $token2Clone, $token3);

		$this->assertEquals(array_values($securityContext->_get('tokens')), $expectedMergedTokens);
	}

	/**
	 * @test
	 */
	public function initializeCallsUpdateCredentialsOnAllActiveTokens() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$notMatchingRequestPattern = $this->getMock('TYPO3\FLOW3\Security\RequestPatternInterface', array(), array(), '', FALSE);
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$mockToken1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$mockToken2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$mockToken2->expects($this->atLeastOnce())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$mockToken3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->never())->method('updateCredentials');
		$mockToken3->expects($this->once())->method('updateCredentials');

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'));
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function injectAuthenticationManagerSetsAReferenceToTheSecurityContextInTheAuthenticationManager() {
		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'));
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('setSecurityContext')->with($securityContext);

		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
	}

	/**
	 * Data provider for authentication strategy settings
	 *
	 * @return array
	 */
	public function authenticationStrategies() {
		$data = array();
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
		$data[] = array($settings, \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ALL_TOKENS);
		$settings['security']['authentication']['authenticationStrategy'] = 'oneToken';
		$data[] = array($settings, \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'atLeastOneToken';
		$data[] = array($settings, \TYPO3\FLOW3\Security\Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'anyToken';
		$data[] = array($settings, \TYPO3\FLOW3\Security\Context::AUTHENTICATE_ANY_TOKEN);
		return $data;
	}

	/**
	 * @dataProvider authenticationStrategies()
	 * @test
	 */
	public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy) {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectSettings($settings);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$this->assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheCorrectRoles() {
		$settings = array();

		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$role1 = new Role('role1');
		$role11 = new Role('role11');
		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($role1, $role11)));
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$role2 = new Role('role2');
		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($role2)));
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$role3 = new Role('role3');
		$role33 = new Role('role33');
		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token3->expects($this->any())->method('getRoles')->will($this->returnValue(array($role3, $role33)));
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$role4 = new Role('role4');
		$role44 = new Role('role44');
		$token4 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token4->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token4->expects($this->any())->method('getRoles')->will($this->returnValue(array($role4, $role44)));
		$token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));

		$role5 = new Role('role5');
		$token5 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token5->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token5->expects($this->any())->method('getRoles')->will($this->returnValue(array($role5)));
		$token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token6Provider'));

		$everybodyRole = new Role('Everybody');

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));

		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array(
			$everybodyRole, $role1, $role11, $role2, $role5
		)));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('settings', $settings);
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('activeTokens', array($token1, $token2, $token3, $token4, $token5));
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$expectedResult = array($everybodyRole, $role1, $role11, $role2, $role5);

		$this->assertEquals($expectedResult, $securityContext->getRoles());
	}

	/**
	 * @test
	 */
	public function getRolesTakesInheritanceOfRolesIntoAccount() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$role1 = new Role('role1');
		$role2 = new Role('role2');
		$role3 = new Role('role3');
		$role4 = new Role('role4');
		$role5 = new Role('role5');
		$role6 = new Role('role6');
		$role7 = new Role('role7');
		$role8 = new Role('role8');
		$role9 = new Role('role9');
		$everybodyRole = new Role('Everybody');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRoles')->will($this->returnValue(array($role1, $role2, $role3)));

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getRoles')->will($this->returnValue(array($role2, $role4, $role5)));

		$policyServiceCallback = function() use (&$role1, &$role2, &$role5, &$role6, &$role7, &$role8, &$role9) {
			$args = func_get_args();

			if ((string)$args[0] === 'role1') return array($role6);
			if ((string)$args[0] === 'role2') return array($role6, $role7);
			if ((string)$args[0] === 'role5') return array($role8, $role9);

			return array();
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnCallback($policyServiceCallback));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array(
			$everybodyRole, $role1, $role2, $role3, $role4, $role5, $role6, $role7, $role8, $role9
		)));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$securityContext->injectRequest($request);
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$expectedResult = array($everybodyRole, $role1, $role2, $role3, $role4, $role5, $role6, $role7, $role8, $role9);
		$result = $securityContext->getRoles();

		sort($expectedResult);
		sort($result);

		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$result = $securityContext->getRoles();

		$this->assertInstanceOf('TYPO3\FLOW3\Security\Policy\Role', $result[0]);
		$this->assertEquals('Everybody', (string)($result[0]));
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheAnonymousRoleIfNoTokenIsAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$result = $securityContext->getRoles();

		$this->assertInstanceOf('TYPO3\FLOW3\Security\Policy\Role', $result[1]);
		$this->assertEquals('Anonymous', (string)($result[1]));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function getRolesAddsTheEverybodyRoleToTheRolesFromTheAuthenticatedTokens() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$role1 = new Role('Role1');
		$role2 = new Role('Role2');
		$role3 = new Role('Role3');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRoles')->will($this->returnValue(array($role1)));

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getRoles')->will($this->returnValue(array($role2, $role3)));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array(new Role('Everybody'))));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$securityContext->_set('policyService', $mockPolicyService);

		$result = $securityContext->getRoles();

		$everybodyRoleFound = FALSE;
		foreach ($result as $resultRole) {
			$this->assertInstanceOf('TYPO3\FLOW3\Security\Policy\Role', $resultRole);
			if ('Everybody' === (string)($resultRole)) $everybodyRoleFound = TRUE;
		}

		$this->assertTrue($everybodyRoleFound, 'The Everybody role could not be found as expected.');
	}

	/**
	 * @test
	 */
	public function hasRoleWorks() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$roleAdministrator = new Role('Administrator');
		$roleLicenseToKill = new Role('LicenseToKill');
		$roleCustomer = new Role('Customer');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleLicenseToKill)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleCustomer)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleLicenseToKill, $roleCustomer)));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('policyService', $mockPolicyService);

		$securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(TRUE));
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$this->assertTrue($securityContext->hasRole('LicenseToKill'));
		$this->assertFalse($securityContext->hasRole('Customer'));
	}

	/**
	 * @test
	 */
	public function hasRoleWorksWithRecursiveRoles() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$roleAdministrator = new Role('Administrator');
		$roleLicenseToKill = new Role('LicenseToKill');
		$roleCustomer = new Role('Customer');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleLicenseToKill)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleCustomer)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$policyServiceCallback = function() {
			$args = func_get_args();

			if ((string)$args[0] === 'Administrator') return array(new Role('Customer'));

			return array();
		};

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnCallback($policyServiceCallback));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleLicenseToKill, $roleCustomer)));

		$securityContext->_set('policyService', $mockPolicyService);

		$this->assertTrue($securityContext->hasRole('Customer'));
	}

	/**
	 * @test
	 */
	public function getRolesDoesNotReturnRolesThePolicyServiceDoesNotKnowAbout() {
		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$roleAdministrator = new Role('Administrator');
		$roleLicenseToKill = new Role('LicenseToKill');
		$roleCustomer = new Role('Customer');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleLicenseToKill)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleCustomer)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array($roleAdministrator, $roleCustomer)));

		$securityContext->_set('policyService', $mockPolicyService);

		$this->assertTrue($securityContext->hasRole('Administrator'));
		$this->assertTrue($securityContext->hasRole('Customer'));
		$this->assertFalse($securityContext->hasRole('LicenseToKill'));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsTrueForTheEverybodyRoleEvenIfNoOtherRoleIsAuthenticated() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$mockPolicyService = $this->getMock('TYPO3\FLOW3\Security\Policy\PolicyService', array(), array(), '', FALSE);
		$mockPolicyService->expects($this->any())->method('getAllParentRoles')->will($this->returnValue(array()));
		$mockPolicyService->expects($this->any())->method('getRoles')->will($this->returnValue(array()));
		$securityContext->_set('policyService', $mockPolicyService);

		$this->assertTrue($securityContext->hasRole('Everybody'));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsTrueForTheEverybodyRoleInAnyCase() {
		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('dummy'), array(), '', FALSE);
		$this->assertTrue($securityContext->hasRole('Everybody'));
	}

	/**
	 * @test
	 */
	public function getPartyAsksTheCorrectAuthenticationTokenAndReturnsItsParty() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty');

		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account');
		$mockAccount->expects($this->once())->method('getParty')->will($this->returnValue($mockParty));

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockParty, $securityContext->getParty());
	}

	/**
	 * @test
	 */
	public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAccount = $this->getMock('TYPO3\FLOW3\Security\Account');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockAccount, $securityContext->getAccount());
	}

	/**
	 * @test
	 */
	public function getPartyByTypeReturnsTheFirstAuthenticatedPartyWithGivenType() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$matchingMockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty', array(), 'MatchingParty');
		$notMatchingMockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty', array(), 'NotMatchingParty');

		$mockAccount1 = $this->getMock('TYPO3\FLOW3\Security\Account');
		$mockAccount1->expects($this->any())->method('getParty')->will($this->returnValue($notMatchingMockParty));
		$mockAccount2 = $this->getMock('TYPO3\FLOW3\Security\Account');
		$mockAccount2->expects($this->any())->method('getParty')->will($this->returnValue($matchingMockParty));

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertSame($matchingMockParty, $securityContext->getPartyByType('MatchingParty'));
	}

	/**
	 * @test
	 */
	public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$mockAccount1 = $this->getMock('TYPO3\FLOW3\Security\Account');
		$mockAccount2 = $this->getMock('TYPO3\FLOW3\Security\Account');

		$token1 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('TYPO3\FLOW3\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('activeTokens', array('SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3));

		$this->assertSame($mockAccount2, $securityContext->getAccountByAuthenticationProviderName('MatchingProvider'));
	}

	/**
	 * @test
	 */
	public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('activeTokens', array());

		$this->assertSame(NULL, $securityContext->getAccountByAuthenticationProviderName('UnknownProvider'));
	}

	/**
	 * @test
	 */
	public function getCsrfProtectionTokenReturnsANewTokenIfNoneIsPresentInTheContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', array());

		$this->assertNotEmpty($securityContext->getCsrfProtectionToken());
	}

	/**
	 * @test
	 */
	public function getCsrfProtectionTokenReturnsANewTokenIfTheCsrfStrategyIsOnePerUri() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('token1' => TRUE, 'token2' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('getAuthenticationTokens'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', $existingTokens);
		$securityContext->_set('csrfStrategy', \TYPO3\FLOW3\Security\Context::CSRF_ONE_PER_URI);

		$this->assertFalse(array_key_exists($securityContext->getCsrfProtectionToken(), $existingTokens));
	}

	/**
	 * @test
	 */
	public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('csrfToken12345' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('initialize'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', $existingTokens);

		$this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
		$this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken'));
	}

	/**
	 * @test
	 */
	public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContextAndUnsetsItIfTheCsrfStrategyIsOnePerUri() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('csrfToken12345' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\FLOW3\Security\Context', array('initialize'), array(), '', FALSE);
		$securityContext->injectRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', $existingTokens);
		$securityContext->_set('csrfStrategy', \TYPO3\FLOW3\Security\Context::CSRF_ONE_PER_URI);

		$this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
		$this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
	}
}
?>
