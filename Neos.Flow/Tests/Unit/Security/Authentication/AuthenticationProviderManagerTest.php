<?php
namespace Neos\Flow\Tests\Unit\Security\Authentication;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Security\Authentication\AuthenticationProviderInterface;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Exception\AuthenticationRequiredException;
use Neos\Flow\Session\SessionManager;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\AuthenticationProviderManager;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Session\SessionInterface;

/**
 * Test case for authentication provider manager
 */
class AuthenticationProviderManagerTest extends UnitTestCase
{
    /**
     * @var AuthenticationProviderManager
     */
    protected $authenticationProviderManager;

    /**
     * @var TokenAndProviderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenAndProviderFactory;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSession;

    /**
     * @var SessionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSessionManager;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSecurityContext;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->tokenAndProviderFactory = $this->getMockBuilder(TokenAndProviderFactoryInterface::class)->getMock();
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['dummy'], [$this->tokenAndProviderFactory], '', true);
        $this->mockSession = $this->getMockBuilder(SessionInterface::class)->getMock();
        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockSessionManager = $this->getMockBuilder(SessionManager::class)->getMock();
        $this->mockSessionManager->expects(self::any())->method('getCurrentSession')->willReturn($this->mockSession);

        $this->inject($this->authenticationProviderManager, 'sessionManager', $this->mockSessionManager);
        $this->inject($this->authenticationProviderManager, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->authenticationProviderManager, 'isInitialized', true);
    }

    /**
     * @test
     */
    public function authenticateDelegatesAuthenticationToTheCorrectProvidersInTheCorrectOrder()
    {
        $mockProvider1 = $this->createMock(AuthenticationProviderInterface::class);
        $mockProvider2 = $this->createMock(AuthenticationProviderInterface::class);
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken2 = $this->createMock(TokenInterface::class);

        $mockToken1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));
        $mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(true, false));
        $mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->returnValue(true));

        $mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
        $mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$mockToken1, $mockToken2]));

        $this->tokenAndProviderFactory->expects(self::any())->method('getProviders')->willReturn([
            $mockProvider1,
            $mockProvider2
        ]);

        $this->inject($this->authenticationProviderManager, 'authenticationStrategy', Context::AUTHENTICATE_ALL_TOKENS);

        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function authenticateTagsSessionWithAccountIdentifier()
    {
        $account = new Account();
        $account->setAccountIdentifier('admin');

        $securityContext = $this->getMockBuilder(Context::class)->setMethods(['getAuthenticationStrategy', 'getAuthenticationTokens', 'refreshTokens', 'refreshRoles'])->getMock();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getAccount')->will($this->returnValue($account));

        $token->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $this->mockSession->expects($this->once())->method('addTag')->with('Neos-Flow-Security-Account-21232f297a57a5a743894a0e4a801fc3');

        $this->inject($this->authenticationProviderManager, 'securityContext', $securityContext);

        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function authenticateAuthenticatesOnlyTokensWithStatusAuthenticationNeeded()
    {
        $mockProvider = $this->createMock(AuthenticationProviderInterface::class);
        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken3 = $this->createMock(TokenInterface::class);

        $mockToken1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $mockToken2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $mockToken3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockToken1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::WRONG_CREDENTIALS));
        $mockToken2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::NO_CREDENTIALS_GIVEN));
        $mockToken3->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $mockProvider->expects($this->any())->method('canAuthenticate')->will($this->returnValue(true));
        $mockProvider->expects($this->once())->method('authenticate')->with($mockToken3);

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$mockToken1, $mockToken2, $mockToken3]));

        $this->tokenAndProviderFactory->expects(self::any())->method('getProviders')->willReturn([
            $mockProvider
        ]);

        $this->inject($this->authenticationProviderManager, 'authenticationStrategy', Context::AUTHENTICATE_ONE_TOKEN);

        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function authenticateThrowsAnExceptionIfNoTokenCouldBeAuthenticated()
    {
        $this->expectException(AuthenticationRequiredException::class);
        $token1 = $this->createMock(TokenInterface::class);
        $token2 = $this->createMock(TokenInterface::class);

        $token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));
        $token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$token1, $token2]));

        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function authenticateThrowsAnExceptionIfAuthenticateAllTokensIsTrueButATokenCouldNotBeAuthenticated()
    {
        $this->expectException(AuthenticationRequiredException::class);
        $token1 = $this->createMock(TokenInterface::class);
        $token2 = $this->createMock(TokenInterface::class);

        $token1->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $token2->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue([$token1, $token2]));

        $this->inject($this->authenticationProviderManager, 'authenticationStrategy', Context::AUTHENTICATE_ALL_TOKENS);
        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAnTokenCouldBeAuthenticated()
    {
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->once())->method('isAuthenticated')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue([$mockToken]));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticated()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

        self::assertFalse($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAtLeastOneTokenIsAuthenticated()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(true));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticatedWithStrategyAnyToken()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationStrategy')->will($this->returnValue(Context::AUTHENTICATE_ANY_TOKEN));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

        self::assertFalse($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfOneTokenIsAuthenticatedWithStrategyAnyToken()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->will($this->returnValue(true));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationStrategy')->will($this->returnValue(Context::AUTHENTICATE_ANY_TOKEN));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function logoutReturnsIfNoAccountIsAuthenticated()
    {
        $this->mockSecurityContext->expects($this->never())->method('isInitialized');
        /** @var AuthenticationProviderManager|\PHPUnit_Framework_MockObject_MockObject $authenticationProviderManager */
        $authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['isAuthenticated'], [], '', false);
        $authenticationProviderManager->expects($this->once())->method('isAuthenticated')->will($this->returnValue(false));
        $authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutSetsTheAuthenticationStatusOfAllActiveAuthenticationTokensToNoCredentialsGiven()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->will($this->returnValue(true));
        $token1->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue($authenticationTokens));

        $this->authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutDestroysSessionIfStarted()
    {
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['emitLoggedOut'], [$this->tokenAndProviderFactory], '', true);
        $this->inject($this->authenticationProviderManager, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->authenticationProviderManager, 'sessionManager', $this->mockSessionManager);
        $this->inject($this->authenticationProviderManager, 'isInitialized', true);

        $this->mockSession->expects($this->any())->method('canBeResumed')->will($this->returnValue(true));
        $this->mockSession->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $this->mockSession->expects($this->once())->method('destroy');

        $this->authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutDoesNotDestroySessionIfNotStarted()
    {
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['emitLoggedOut'], [$this->tokenAndProviderFactory], '', true);
        $this->inject($this->authenticationProviderManager, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->authenticationProviderManager, 'sessionManager', $this->mockSessionManager);
        $this->inject($this->authenticationProviderManager, 'isInitialized', true);

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $this->mockSession->expects($this->never())->method('destroy');

        $this->authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutEmitsLoggedOutSignalBeforeDestroyingSession()
    {
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['emitLoggedOut'], [$this->tokenAndProviderFactory], '', true);
        $this->inject($this->authenticationProviderManager, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->authenticationProviderManager, 'sessionManager', $this->mockSessionManager);
        $this->inject($this->authenticationProviderManager, 'isInitialized', true);

        $this->mockSession->expects($this->any())->method('canBeResumed')->will($this->returnValue(true));
        $this->mockSession->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $loggedOutEmitted = false;
        $this->authenticationProviderManager->expects($this->once())->method('emitLoggedOut')->will($this->returnCallback(function () use (&$loggedOutEmitted) {
            $loggedOutEmitted = true;
        }));
        $this->mockSession->expects($this->once())->method('destroy')->will($this->returnCallback(function () use (&$loggedOutEmitted) {
            if (!$loggedOutEmitted) {
                \PHPUnit\Framework\Assert::fail('emitLoggedOut was not called before destroy');
            }
        }));

        $this->authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutRefreshesTokensInSecurityContext()
    {
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['emitLoggedOut'], [$this->tokenAndProviderFactory], '', true);
        $this->inject($this->authenticationProviderManager, 'securityContext', $this->mockSecurityContext);
        $this->inject($this->authenticationProviderManager, 'sessionManager', $this->mockSessionManager);
        $this->inject($this->authenticationProviderManager, 'isInitialized', true);

        $this->mockSession->expects($this->any())->method('canBeResumed')->will($this->returnValue(true));
        $this->mockSession->expects($this->any())->method('isStarted')->will($this->returnValue(true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->will($this->returnValue([$token]));

        $this->authenticationProviderManager->expects(self::once())->method('emitLoggedOut');

        $this->authenticationProviderManager->logout();
    }
}
