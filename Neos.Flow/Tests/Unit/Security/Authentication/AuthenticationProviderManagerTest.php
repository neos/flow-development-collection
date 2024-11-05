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
     * @var TokenAndProviderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenAndProviderFactory;

    /**
     * @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSession;

    /**
     * @var SessionManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSessionManager;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSecurityContext;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->tokenAndProviderFactory = $this->getMockBuilder(TokenAndProviderFactoryInterface::class)->getMock();
        $this->authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, [], [$this->tokenAndProviderFactory], '', true);
        $this->mockSession = $this->getMockBuilder(SessionInterface::class)->getMock();
        $this->mockSecurityContext = $this->getMockBuilder(Context::class)->disableOriginalConstructor()->getMock();

        $this->mockSessionManager = $this->getMockBuilder(SessionManager::class)->getMock();
        $this->mockSessionManager->expects($this->any())->method('getCurrentSession')->willReturn($this->mockSession);

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

        $mockToken1->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken2->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken1->expects($this->any())->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));
        $mockToken2->expects($this->any())->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $mockProvider1->expects($this->atLeastOnce())->method('canAuthenticate')->will($this->onConsecutiveCalls(true, false));
        $mockProvider2->expects($this->atLeastOnce())->method('canAuthenticate')->willReturn((true));

        $mockProvider1->expects($this->once())->method('authenticate')->with($mockToken1);
        $mockProvider2->expects($this->once())->method('authenticate')->with($mockToken2);

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(([$mockToken1, $mockToken2]));

        $this->tokenAndProviderFactory->expects($this->any())->method('getProviders')->willReturn([
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

        $securityContext = $this->getMockBuilder(Context::class)->onlyMethods(['getAuthenticationStrategy', 'getAuthenticationTokens', 'refreshTokens', 'refreshRoles'])->getMock();

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())->method('getAccount')->willReturn(($account));

        $token->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $securityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(([$token]));

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

        $mockToken1->expects($this->any())->method('isAuthenticated')->willReturn((false));
        $mockToken2->expects($this->any())->method('isAuthenticated')->willReturn((false));
        $mockToken3->expects($this->any())->method('isAuthenticated')->willReturn((true));

        $mockToken1->expects($this->any())->method('getAuthenticationStatus')->willReturn((TokenInterface::WRONG_CREDENTIALS));
        $mockToken2->expects($this->any())->method('getAuthenticationStatus')->willReturn((TokenInterface::NO_CREDENTIALS_GIVEN));
        $mockToken3->expects($this->any())->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $mockProvider->expects($this->any())->method('canAuthenticate')->willReturn((true));
        $mockProvider->expects($this->once())->method('authenticate')->with($mockToken3);

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(([$mockToken1, $mockToken2, $mockToken3]));

        $this->tokenAndProviderFactory->expects($this->any())->method('getProviders')->willReturn([
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

        $token1->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((false));
        $token2->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((false));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(([$token1, $token2]));

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

        $token1->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $token2->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((false));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(([$token1, $token2]));

        $this->inject($this->authenticationProviderManager, 'authenticationStrategy', Context::AUTHENTICATE_ALL_TOKENS);
        $this->authenticationProviderManager->authenticate();
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAnTokenCouldBeAuthenticated()
    {
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->once())->method('isAuthenticated')->willReturn((true));

        $this->mockSecurityContext->expects($this->once())->method('getAuthenticationTokens')->willReturn(([$mockToken]));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticated()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->willReturn((false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->willReturn((false));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(($authenticationTokens));

        self::assertFalse($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfAtLeastOneTokenIsAuthenticated()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->willReturn((false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->willReturn((true));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(($authenticationTokens));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsFalseIfNoTokenIsAuthenticatedWithStrategyAnyToken()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->willReturn((false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->willReturn((false));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationStrategy')->willReturn((Context::AUTHENTICATE_ANY_TOKEN));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(($authenticationTokens));

        self::assertFalse($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function isAuthenticatedReturnsTrueIfOneTokenIsAuthenticatedWithStrategyAnyToken()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->willReturn((false));
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('isAuthenticated')->willReturn((true));

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationStrategy')->willReturn((Context::AUTHENTICATE_ANY_TOKEN));
        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(($authenticationTokens));

        self::assertTrue($this->authenticationProviderManager->isAuthenticated());
    }

    /**
     * @test
     */
    public function logoutReturnsIfNoAccountIsAuthenticated()
    {
        $this->mockSecurityContext->expects($this->never())->method('isInitialized');
        /** @var AuthenticationProviderManager|\PHPUnit\Framework\MockObject\MockObject $authenticationProviderManager */
        $authenticationProviderManager = $this->getAccessibleMock(AuthenticationProviderManager::class, ['isAuthenticated'], [], '', false);
        $authenticationProviderManager->expects($this->once())->method('isAuthenticated')->willReturn((false));
        $authenticationProviderManager->logout();
    }

    /**
     * @test
     */
    public function logoutSetsTheAuthenticationStatusOfAllActiveAuthenticationTokensToNoCredentialsGiven()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('isAuthenticated')->willReturn((true));
        $token1->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);
        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('setAuthenticationStatus')->with(TokenInterface::NO_CREDENTIALS_GIVEN);

        $authenticationTokens = [$token1, $token2];

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->willReturn(($authenticationTokens));

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

        $this->mockSession->expects($this->any())->method('canBeResumed')->willReturn((true));
        $this->mockSession->expects($this->any())->method('isStarted')->willReturn((true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->willReturn((true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->willReturn(([$token]));

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
        $token->expects($this->any())->method('isAuthenticated')->willReturn((true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->willReturn(([$token]));

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

        $this->mockSession->expects($this->any())->method('canBeResumed')->willReturn((true));
        $this->mockSession->expects($this->any())->method('isStarted')->willReturn((true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->willReturn((true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->willReturn(([$token]));

        $loggedOutEmitted = false;
        $this->authenticationProviderManager->expects($this->once())->method('emitLoggedOut')->will(self::returnCallBack(function () use (&$loggedOutEmitted) {
            $loggedOutEmitted = true;
        }));
        $this->mockSession->expects($this->once())->method('destroy')->will(self::returnCallBack(function () use (&$loggedOutEmitted) {
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

        $this->mockSession->expects($this->any())->method('canBeResumed')->willReturn((true));
        $this->mockSession->expects($this->any())->method('isStarted')->willReturn((true));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $token->expects($this->any())->method('isAuthenticated')->willReturn((true));

        $this->mockSecurityContext->expects($this->any())->method('getAuthenticationTokens')->willReturn(([$token]));

        $this->authenticationProviderManager->expects($this->once())->method('emitLoggedOut');

        $this->authenticationProviderManager->logout();
    }
}
