<?php
namespace TYPO3\Flow\Tests\Unit\Security;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\Policy\Role;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Testcase for the security context
 */
class ContextTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function currentRequestIsSetInTheSecurityContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->setRequest($request);

		$securityContext->_call('initialize');

		$this->assertSame($request, $securityContext->_get('request'));
	}

	/**
	 * @test
	 */
	public function securityContextIsSetToInitialized() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$mockRequestHandler = $this->getMock('TYPO3\Flow\Mvc\ActionRequestHandler', array(), array(), '', FALSE);
		$mockRequestHandler->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$bootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE);
		$bootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('bootstrap', $bootstrap);
		$securityContext->setRequest($request);

		$this->assertFalse($securityContext->isInitialized());
		$securityContext->_call('initialize');
		$this->assertTrue($securityContext->isInitialized());
	}

	/**
	 * initialize() might be called multiple times during one request. This might override
	 * roles and other data acquired from tokens / accounts, which have been initialized
	 * in a previous initialize() call. Therefore - and in order to save some processor
	 * cycles - initialization should only by executed once for a Context instance.
	 *
	 * @test
	 */
	public function securityContextIsNotInitializedAgainIfItHasBeenInitializedAlready() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('canBeInitialized'));
		$securityContext->expects($this->never())->method('canBeInitialized');
		$securityContext->_set('initialized', TRUE);

		$securityContext->initialize();
	}

	/**
	 * @test
	 */
	public function initializeSeparatesActiveAndInactiveTokens() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));

		$mockRequestHandler = $this->getMock('TYPO3\Flow\Mvc\ActionRequestHandler', array(), array(), '', FALSE);
		$mockRequestHandler->expects($this->any())->method('getRequest')->will($this->returnValue($request));

		$bootstrap = $this->getMock('TYPO3\Flow\Core\Bootstrap', array(), array(), '', FALSE);
		$bootstrap->expects($this->any())->method('getActiveRequestHandler')->will($this->returnValue($mockRequestHandler));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('separateActiveAndInactiveTokens'));
		$securityContext->expects($this->once())->method('separateActiveAndInactiveTokens');
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->_set('bootstrap', $bootstrap);
		$securityContext->setRequest($request);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly() {
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'allTokens';

		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$matchingRequestPattern = $this->getMock('TYPO3\Flow\Security\RequestPatternInterface');
		$matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(TRUE));

		$notMatchingRequestPattern = $this->getMock('TYPO3\Flow\Security\RequestPatternInterface');
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(FALSE));
		$token2->expects($this->never())->method('getRequestPatterns');
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$token4 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array()));
		$token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));

		$token5 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern, $matchingRequestPattern)));
		$token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token5Provider'));

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($token1, $token2, $token3, $token4, $token5)));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->injectSettings($settings);
		$securityContext->setRequest($request);
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

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->setRequest($request);
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$token1Clone = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token1Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$token2Clone = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token2Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$tokensFromTheManager = array($token1, $token2, $token3);
		$tokensFromTheSession = array($token1Clone, $token2Clone);

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->injectAuthenticationManager($mockAuthenticationManager);
		$securityContext->setRequest($request);
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

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$notMatchingRequestPattern = $this->getMock('TYPO3\Flow\Security\RequestPatternInterface');
		$notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(FALSE));

		$mockToken1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
		$mockToken2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
		$mockToken2->expects($this->atLeastOnce())->method('hasRequestPatterns')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
		$mockToken3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

		$mockToken1->expects($this->once())->method('updateCredentials');
		$mockToken2->expects($this->never())->method('updateCredentials');
		$mockToken3->expects($this->once())->method('updateCredentials');

		$mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$securityContext->_call('initialize');
	}

	/**
	 * @test
	 */
	public function injectAuthenticationManagerSetsAReferenceToTheSecurityContextInTheAuthenticationManager() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
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
		$data[] = array($settings, \TYPO3\Flow\Security\Context::AUTHENTICATE_ALL_TOKENS);
		$settings['security']['authentication']['authenticationStrategy'] = 'oneToken';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::AUTHENTICATE_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'atLeastOneToken';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN);
		$settings['security']['authentication']['authenticationStrategy'] = 'anyToken';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::AUTHENTICATE_ANY_TOKEN);
		return $data;
	}

	/**
	 * @dataProvider authenticationStrategies()
	 * @test
	 */
	public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy) {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->injectSettings($settings);

		$this->assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
	}

	/**
	 * @expectedException \TYPO3\Flow\Exception
	 * @test
	 */
	public function invalidAuthenticationStrategyFromConfigurationThrowsException() {
		$settings = array();
		$settings['security']['authentication']['authenticationStrategy'] = 'fizzleGoesHere';

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->injectSettings($settings);
	}

	/**
	 * Data provider for CSRF protection strategy settings
	 *
	 * @return array
	 */
	public function csrfProtectionStrategies() {
		$data = array();
		$settings = array();
		$settings['security']['csrf']['csrfStrategy'] = 'onePerRequest';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::CSRF_ONE_PER_REQUEST);
		$settings['security']['csrf']['csrfStrategy'] = 'onePerSession';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::CSRF_ONE_PER_SESSION);
		$settings['security']['csrf']['csrfStrategy'] = 'onePerUri';
		$data[] = array($settings, \TYPO3\Flow\Security\Context::CSRF_ONE_PER_URI);
		return $data;
	}

	/**
	 * @dataProvider csrfProtectionStrategies()
	 * @test
	 */
	public function csrfProtectionStrategyIsSetCorrectlyFromConfiguration($settings, $expectedCsrfProtectionStrategy) {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->injectSettings($settings);

		$this->assertEquals($expectedCsrfProtectionStrategy, $securityContext->_get('csrfProtectionStrategy'));
	}

	/**
	 * @expectedException \TYPO3\Flow\Exception
	 * @test
	 */
	public function invalidCsrfProtectionStrategyFromConfigurationThrowsException() {
		$settings = array();
		$settings['security']['csrf']['csrfStrategy'] = 'fizzleGoesHere';

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('dummy'));
		$securityContext->injectSettings($settings);
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheCorrectRoles() {
		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$testRole = new Role('Acme.Demo:TestRole');

		$account = new \TYPO3\Flow\Security\Account();
		$account->setRoles(array($testRole));

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole', 'initializeRolesFromPolicy'));
		$mockPolicyService->expects($this->any())->method('getRole')->with('Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
		$securityContext->_set('activeTokens', array($mockToken));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$expectedResult = array('Everybody' => $everybodyRole, 'Acme.Demo:TestRole' => $testRole);
		$this->assertEquals($expectedResult, $securityContext->getRoles());
	}

	/**
	 * @test
	 */
	public function getRolesTakesInheritanceOfRolesIntoAccount() {
		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$testRole1 = new Role('Acme.Demo:TestRole1');
		$testRole2 = new Role('Acme.Demo:TestRole2');
		$testRole3 = new Role('Acme.Demo:TestRole3');
		$testRole4 = new Role('Acme.Demo:TestRole4');
		$testRole5 = new Role('Acme.Demo:TestRole5');
		$testRole6 = new Role('Acme.Demo:TestRole6');
		$testRole7 = new Role('Acme.Demo:TestRole7');

			// Set parents
		$testRole1->setParentRoles(array($testRole2, $testRole3));
		$testRole2->setParentRoles(array($testRole4, $testRole5));
		$testRole3->setParentRoles(array($testRole6, $testRole7));

		$account = new \TYPO3\Flow\Security\Account();
		$account->setRoles(array($testRole1));

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole', 'initializeRolesFromPolicy'));
		$mockPolicyService->expects($this->any())->method('getRole')->with('Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
		$securityContext->_set('activeTokens', array($mockToken));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$expectedResult = array(
			'Acme.Demo:TestRole1' => $testRole1,
			'Acme.Demo:TestRole2' => $testRole2,
			'Acme.Demo:TestRole3' => $testRole3,
			'Acme.Demo:TestRole4' => $testRole4,
			'Acme.Demo:TestRole5' => $testRole5,
			'Acme.Demo:TestRole6' => $testRole6,
			'Acme.Demo:TestRole7' => $testRole7,
			'Everybody' => $everybodyRole);
		$result = $securityContext->getRoles();

		ksort($expectedResult);
		ksort($result);

		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$anonymousRole = new Role('Anonymous', Role::SOURCE_SYSTEM);
		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole'));
		$mockPolicyService->expects($this->any())->method('getRole')->will($this->returnValueMap(array(array('Anonymous', $anonymousRole), array('Everybody', $everybodyRole))));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$result = $securityContext->getRoles();
		$this->assertInstanceOf('TYPO3\Flow\Security\Policy\Role', $result['Everybody']);
		$this->assertEquals('Everybody', $result['Everybody']->getIdentifier());
	}

	/**
	 * @test
	 */
	public function getRolesReturnsTheAnonymousRoleIfNoTokenIsAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$anonymousRole = new Role('Anonymous', Role::SOURCE_SYSTEM);
		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole'));
		$mockPolicyService->expects($this->any())->method('getRole')->will($this->returnValueMap(array(array('Anonymous', $anonymousRole), array('Everybody', $everybodyRole))));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$result = $securityContext->getRoles();
		$this->assertInstanceOf('TYPO3\Flow\Security\Policy\Role', $result['Anonymous']);
		$this->assertEquals('Anonymous', (string)($result['Anonymous']));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsTrueForEverybodyRole() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));

		$this->assertTrue($securityContext->hasRole('Everybody'));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsTrueForAnonymousRoleIfNotAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$this->assertTrue($securityContext->hasRole('Anonymous'));
	}

	/**
	 * @test
	 */
	public function hasRoleReturnsFalseForAnonymousRoleIfAuthenticated() {
		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$this->assertFalse($securityContext->hasRole('Anonymous'));
	}

	/**
	 * @test
	 */
	public function hasRoleWorks() {
		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$testRole = new Role('Acme.Demo:TestRole');

		$account = new \TYPO3\Flow\Security\Account();
		$account->setRoles(array($testRole));

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole', 'initializeRolesFromPolicy'));
		$mockPolicyService->expects($this->any())->method('getRole')->with('Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize', 'getAccount'));
		$securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
		$securityContext->_set('activeTokens', array($mockToken));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$this->assertTrue($securityContext->hasRole('Acme.Demo:TestRole'));
		$this->assertFalse($securityContext->hasRole('Foo.Bar:Baz'));
	}

	/**
	 * @test
	 */
	public function hasRoleWorksWithRecursiveRoles() {
		$everybodyRole = new Role('Everybody', Role::SOURCE_SYSTEM);
		$testRole1 = new Role('Acme.Demo:TestRole1');
		$testRole2 = new Role('Acme.Demo:TestRole2');

			// Set parents
		$testRole1->setParentRoles(array($testRole2));

		$account = new \TYPO3\Flow\Security\Account();
		$account->setRoles(array($testRole1));

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
		$mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

		$mockPolicyService = $this->getAccessibleMock('TYPO3\Flow\Security\Policy\PolicyService', array('getRole', 'initializeRolesFromPolicy'));
		$mockPolicyService->expects($this->any())->method('getRole')->with('Everybody')->will($this->returnValue($everybodyRole));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize', 'getAccount'));
		$securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
		$securityContext->_set('activeTokens', array($mockToken));
		$securityContext->_set('policyService', $mockPolicyService);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);

		$this->assertTrue($securityContext->hasRole('Acme.Demo:TestRole2'));
	}

	/**
	 * @test
	 */
	public function getPartyAsksTheCorrectAuthenticationTokenAndReturnsItsParty() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$mockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty');

		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccount->expects($this->once())->method('getParty')->will($this->returnValue($mockParty));

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockParty, $securityContext->getParty());
	}

	/**
	 * @test
	 */
	public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$mockAccount = $this->getMock('TYPO3\Flow\Security\Account');

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->never())->method('getAccount');

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertEquals($mockAccount, $securityContext->getAccount());
	}

	/**
	 * @test
	 */
	public function getPartyByTypeReturnsTheFirstAuthenticatedPartyWithGivenType() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$matchingMockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty', array(), 'MatchingParty');
		$notMatchingMockParty = $this->getMockForAbstractClass('TYPO3\Party\Domain\Model\AbstractParty', array(), 'NotMatchingParty');

		$mockAccount1 = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccount1->expects($this->any())->method('getParty')->will($this->returnValue($notMatchingMockParty));
		$mockAccount2 = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccount2->expects($this->any())->method('getParty')->will($this->returnValue($matchingMockParty));

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

		$this->assertSame($matchingMockParty, $securityContext->getPartyByType('MatchingParty'));
	}

	/**
	 * @test
	 */
	public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$mockAccount1 = $this->getMock('TYPO3\Flow\Security\Account');
		$mockAccount2 = $this->getMock('TYPO3\Flow\Security\Account');

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token1' . md5(uniqid(mt_rand(), TRUE)));
		$token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token1->expects($this->never())->method('getAccount');

		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token2' . md5(uniqid(mt_rand(), TRUE)));
		$token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

		$token3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'token3' . md5(uniqid(mt_rand(), TRUE)));
		$token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('activeTokens', array('SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3));

		$this->assertSame($mockAccount2, $securityContext->getAccountByAuthenticationProviderName('MatchingProvider'));
	}

	/**
	 * @test
	 */
	public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('activeTokens', array());

		$this->assertSame(NULL, $securityContext->getAccountByAuthenticationProviderName('UnknownProvider'));
	}

	/**
	 * @test
	 */
	public function getCsrfProtectionTokenReturnsANewTokenIfNoneIsPresentInTheContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', array());

		$this->assertNotEmpty($securityContext->getCsrfProtectionToken());
	}

	/**
	 * @test
	 */
	public function getCsrfProtectionTokenReturnsANewTokenIfTheCsrfStrategyIsOnePerUri() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('token1' => TRUE, 'token2' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('getAuthenticationTokens'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfTokens', $existingTokens);
		$securityContext->_set('csrfStrategy', \TYPO3\Flow\Security\Context::CSRF_ONE_PER_URI);

		$this->assertFalse(array_key_exists($securityContext->getCsrfProtectionToken(), $existingTokens));
	}

	/**
	 * @test
	 */
	public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContext() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('csrfToken12345' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfProtectionTokens', $existingTokens);

		$this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
		$this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken'));
	}

	/**
	 * @test
	 */
	public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContextAndUnsetsItIfTheCsrfStrategyIsOnePerUri() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();

		$mockAuthenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');

		$existingTokens = array('csrfToken12345' => TRUE);

		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->setRequest($request);
		$securityContext->_set('authenticationManager', $mockAuthenticationManager);
		$securityContext->_set('csrfProtectionTokens', $existingTokens);
		$securityContext->_set('csrfProtectionStrategy', \TYPO3\Flow\Security\Context::CSRF_ONE_PER_URI);

		$this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
		$this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
	}

	/**
	 * @test
	 */
	public function authorizationChecksAreEnabledByDefault() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$this->assertFalse($securityContext->areAuthorizationChecksDisabled());
	}

	/**
	 * @test
	 */
	public function withoutAuthorizationChecksDisabledAuthorizationChecks() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$self = $this;
		$securityContext->withoutAuthorizationChecks(function() use ($securityContext, $self) {
			$self->assertTrue($securityContext->areAuthorizationChecksDisabled());
		});
	}

	/**
	 * @test
	 */
	public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocation() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		$securityContext->withoutAuthorizationChecks(function(){});
		$this->assertFalse($securityContext->areAuthorizationChecksDisabled());
	}

	/**
	 * @test
	 */
	public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocationIfClosureThrowsException() {
		$securityContext = $this->getAccessibleMock('TYPO3\Flow\Security\Context', array('initialize'));
		try {
			$securityContext->withoutAuthorizationChecks(function() {
				throw new \Exception('Test Exception');
			});
		} catch (\Exception $exception) {
		}
		$this->assertFalse($securityContext->areAuthorizationChecksDisabled());
	}
}
?>
