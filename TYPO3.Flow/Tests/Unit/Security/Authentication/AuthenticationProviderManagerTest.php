<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for authentication provider manager
 */
class AuthenticationProviderManagerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\Flow\Security\Authentication\AuthenticationProviderManager
	 */
	protected $authenticationProviderManager;

	/**
	 * @var \TYPO3\Flow\Session\SessionInterface
	 */
	protected $mockSession;

	/**
	 * Sets up this test case
	 */
	public function setUp() {
		$this->authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('dummy'), array(), '', FALSE);
		$this->mockSession = $this->getMock('TYPO3\Flow\Session\SessionInterface');
		$this->authenticationProviderManager->_set('session', $this->mockSession);
	}

	/**
	 * @test
	 */
	public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder() {
		$securityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockProvider1 = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface', array(), array(), 'mockAuthenticationProvider1');
		$mockProvider2 = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface', array(), array(), 'mockAuthenticationProvider2');
		$mockToken1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken1');
		$mockToken2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken2');

		$mockToken1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));
		$mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));

		$mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(TRUE, FALSE));
		$mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->returnValue(TRUE));

		$mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
		$mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationStrategy')->will($this->returnValue(\TYPO3\Flow\Security\Context::AUTHENTICATE_ALL_TOKENS));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2)));

		$this->authenticationProviderManager->_set('providers', array($mockProvider1, $mockProvider2));
		$this->authenticationProviderManager->_set('securityContext', $securityContext);

		$this->authenticationProviderManager->authenticate();
	}

	/**
	 * @test
	 */
	public function authenticateAuthenticatesOnlyTokensWithStatusAuthenticationNeeded() {
		$securityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockProvider = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderInterface');
		$mockToken1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken11');
		$mockToken2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken12');
		$mockToken3 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), 'mockAuthenticationToken13');

		$mockToken1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$mockToken2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$mockToken3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\TYPO3\Flow\Security\Authentication\TokenInterface::WRONG_CREDENTIALS));
		$mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN));
		$mockToken3->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(\TYPO3\Flow\Security\Authentication\TokenInterface::AUTHENTICATION_NEEDED));

		$mockProvider->expects($this->any())->method('canAuthenticate')->will($this->returnValue(TRUE));
		$mockProvider->expects($this->once())->method('authenticate')->with($mockToken3);

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationStrategy')->will($this->returnValue(\TYPO3\Flow\Security\Context::AUTHENTICATE_ONE_TOKEN));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

		$this->authenticationProviderManager->_set('providers', array($mockProvider));
		$this->authenticationProviderManager->_set('securityContext', $securityContext);

		$this->authenticationProviderManager->authenticate();
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	public function authenticateThrowsAnExceptionIfNoTokenCouldBeAuthenticated() {
		$securityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));

		$this->authenticationProviderManager->_set('providers', array());
		$this->authenticationProviderManager->_set('securityContext', $securityContext);

		$this->authenticationProviderManager->authenticate();
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 */
	public function authenticateThrowsAnExceptionIfAuthenticateAllTokensIsTrueButATokenCouldNotBeAuthenticated() {
		$securityContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);

		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface');

		$token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2)));
		$securityContext->expects($this->atLeastOnce())->method('getAuthenticationStrategy')->will($this->returnValue(\TYPO3\Flow\Security\Context::AUTHENTICATE_ALL_TOKENS));

		$this->authenticationProviderManager->_set('providers', array());
		$this->authenticationProviderManager->_set('securityContext', $securityContext);

		$this->authenticationProviderManager->authenticate();
	}

	/**
	 * @test
	 */
	public function isAuthenticatedReturnsTrueIfAnTokenCouldBeAuthenticated() {
		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockToken->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->assertTrue($this->authenticationProviderManager->isAuthenticated());
	}

	/**
	 * @test
	 */
	public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticated() {
		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));
		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->assertFalse($this->authenticationProviderManager->isAuthenticated());
	}

	/**
	 * @test
	 */
	public function isAuthenticatedReturnsTrueIfAtLeastOneTokenIsAuthenticated() {
		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));
		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->assertTrue($this->authenticationProviderManager->isAuthenticated());
	}

	/**
	 * @test
	 */
	public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticatedWithStrategyAnyToken() {
		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getAuthenticationStrategy')->will($this->returnValue(\TYPO3\Flow\Security\Context::AUTHENTICATE_ANY_TOKEN));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));
		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->assertFalse($this->authenticationProviderManager->isAuthenticated());
	}

	/**
	 * @test
	 */
	public function isAuthenticatedReturnsTrueIfOneTokenIsAuthenticatedWithStrategyAnyToken() {
		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getAuthenticationStrategy')->will($this->returnValue(\TYPO3\Flow\Security\Context::AUTHENTICATE_ANY_TOKEN));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));
		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->assertTrue($this->authenticationProviderManager->isAuthenticated());
	}

	/**
	 * @test
	 */
	public function logoutReturnsIfNoAccountIsAuthenticated() {
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->never())->method('isInitialized');
		$authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('isAuthenticated'), array(), '', FALSE);
		$authenticationProviderManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(FALSE));
		$authenticationProviderManager->setSecurityContext($mockContext);
		$authenticationProviderManager->logout();

	}

	/**
	 * @test
	 */
	public function logoutSetsTheAuthenticationStatusOfAllActiveAuthenticationTokensToNoCredentialsGiven() {
		$token1 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(TRUE));
		$token1->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);
		$token2 = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token2->expects($this->once())->method('setAuthenticationStatus')->with(\TYPO3\Flow\Security\Authentication\TokenInterface::NO_CREDENTIALS_GIVEN);

		$authenticationTokens = array($token1, $token2);

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

		$this->authenticationProviderManager->setSecurityContext($mockContext);

		$this->authenticationProviderManager->logout();
	}

	/**
	 * @test
	 */
	public function logoutDestroysSessionIfStarted() {
		$this->authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('emitLoggedOut'), array(), '', FALSE);
		$this->authenticationProviderManager->_set('session', $this->mockSession);

		$this->mockSession->expects($this->any())->method('canBeResumed')->will($this->returnValue(TRUE));
		$this->mockSession->expects($this->any())->method('isStarted')->will($this->returnValue(TRUE));

		$token = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token)));

		$this->mockSession->expects($this->once())->method('destroy');

		$this->authenticationProviderManager->setSecurityContext($mockContext);
		$this->authenticationProviderManager->logout();
	}

	/**
	 * @test
	 */
	public function logoutDoesNotDestroySessionIfNotStarted() {
		$this->authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('emitLoggedOut'), array(), '', FALSE);
		$this->authenticationProviderManager->_set('session', $this->mockSession);

		$token = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token)));

		$this->mockSession->expects($this->never())->method('destroy');

		$this->authenticationProviderManager->setSecurityContext($mockContext);
		$this->authenticationProviderManager->logout();
	}

	/**
	 * @test
	 */
	public function logoutEmitsLoggedOutSignalBeforeDestroyingSession() {
		$this->authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('emitLoggedOut'), array(), '', FALSE);
		$this->authenticationProviderManager->_set('session', $this->mockSession);

		$this->mockSession->expects($this->any())->method('canBeResumed')->will($this->returnValue(TRUE));
		$this->mockSession->expects($this->any())->method('isStarted')->will($this->returnValue(TRUE));

		$token = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(TRUE));

		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue(array($token)));

		$loggedOutEmitted = FALSE;
		$this->authenticationProviderManager->expects($this->once())->method('emitLoggedOut')->will($this->returnCallback(function() use(&$loggedOutEmitted) {
			$loggedOutEmitted = TRUE;
		}));
		$this->mockSession->expects($this->once())->method('destroy')->will($this->returnCallback(function() use(&$loggedOutEmitted) {
			if (!$loggedOutEmitted) {
				\PHPUnit_Framework_Assert::fail('emitLoggedOut was not called before destroy');
			}
		}));

		$this->authenticationProviderManager->setSecurityContext($mockContext);
		$this->authenticationProviderManager->logout();
	}

	/**
	 * @test
	 */
	public function noTokensAndProvidersAreBuiltIfTheConfigurationArrayIsEmpty() {
		$this->authenticationProviderManager->_call('buildProvidersAndTokensFromConfiguration', array());

		$providers = $this->authenticationProviderManager->_get('providers');
		$tokens = $this->authenticationProviderManager->_get('tokens');

		$this->assertEquals(array(), $providers, 'The array of providers should be empty.');
		$this->assertEquals(array(), $tokens, 'The array of tokens should be empty.');
	}

	/**
	 * @test
	 * @expectedException TYPO3\Flow\Security\Exception\InvalidAuthenticationProviderException
	 */
	public function anExceptionIsThrownIfTheConfiguredProviderDoesNotExist() {
		$providerConfiguration = array(
			'NotExistingProvider' => array(
				'providerClass' => 'NotExistingProviderClass'
			),
		);

		$mockProviderResolver = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderResolver', array(), array(), '', FALSE);
		$mockRequestPatternResolver = $this->getMock('TYPO3\Flow\Security\RequestPatternResolver', array(), array(), '', FALSE);

		$this->authenticationProviderManager = $this->getAccessibleMock('TYPO3\Flow\Security\Authentication\AuthenticationProviderManager', array('authenticate'), array($mockProviderResolver, $mockRequestPatternResolver));
		$this->authenticationProviderManager->_call('buildProvidersAndTokensFromConfiguration', $providerConfiguration);
	}
}
