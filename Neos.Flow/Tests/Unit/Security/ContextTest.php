<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Neos\Flow\Security\Policy\PolicyService;
use PHPUnit\Framework\MockObject\MockObject;
use Neos\Flow\Exception;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenAndProviderFactory;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\RequestPatternInterface;
use Neos\Flow\Security\SessionDataContainer;
use Neos\Flow\Session\SessionInterface;
use Neos\Flow\Session\SessionManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security\Policy\Role;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the security context
 */
final class ContextTest extends UnitTestCase
{
    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * @var TokenAndProviderFactoryInterface
     */
    protected $mockTokenAndProviderFactory;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $mockObjectManager;

    /**
     * @var SessionDataContainer|MockObject
     */
    protected $mockSessionDataContainer;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->mockSessionDataContainer = $this->createMock(SessionDataContainer::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->method('get')->with(SessionDataContainer::class)->willReturn($this->mockSessionDataContainer);

        $this->securityContext = $this->getAccessibleMock(Context::class, ['separateActiveAndInactiveTokens']);
        $this->inject($this->securityContext, 'objectManager', $this->mockObjectManager);

        $this->mockTokenAndProviderFactory = $this->getMockBuilder(TokenAndProviderFactoryInterface::class)->onlyMethods(['getTokens', 'getProviders'])->getMock();
        $this->securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $this->mockActionRequest = $this->createStub(ActionRequest::class);
        $this->securityContext->setRequest($this->mockActionRequest);
    }

    #[Test]
    public function currentRequestIsSetInTheSecurityContext()
    {
        $this->securityContext->initialize();
        self::assertSame($this->mockActionRequest, $this->securityContext->_get('request'));
    }

    #[Test]
    public function securityContextIsSetToInitialized()
    {
        self::assertFalse($this->securityContext->isInitialized());
        $this->securityContext->initialize();
        self::assertTrue($this->securityContext->isInitialized());
    }

    /**
     * initialize() might be called multiple times during one request. This might override
     * roles and other data acquired from tokens / accounts, which have been initialized
     * in a previous initialize() call. Therefore - and in order to save some processor
     * cycles - initialization should only by executed once for a Context instance.
     */
    #[Test]
    public function securityContextIsNotInitializedAgainIfItHasBeenInitializedAlready()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['canBeInitialized']);
        $securityContext->expects($this->never())->method('canBeInitialized');
        $securityContext->_set('initialized', true);

        $securityContext->initialize();
    }

    #[Test]
    public function initializeSeparatesActiveAndInactiveTokens()
    {
        $this->securityContext->expects($this->once())->method('separateActiveAndInactiveTokens');
        $this->securityContext->initialize();
    }

    #[Test]
    public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly()
    {
        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);

        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $securityContext->injectSettings($settings);

        $matchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeRequestPattern')->getMock();
        $matchingRequestPattern->method('matchRequest')->willReturn((true));

        $notMatchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeOtherRequestPattern')->getMock();
        $notMatchingRequestPattern->method('matchRequest')->willReturn((false));

        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects($this->once())->method('hasRequestPatterns')->willReturn((true));
        $token1->expects($this->once())->method('getRequestPatterns')->willReturn(([$matchingRequestPattern]));
        $token1->method('getAuthenticationProviderName')->willReturn(('token1Provider'));
        $token1->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects($this->once())->method('hasRequestPatterns')->willReturn((false));
        $token2->expects($this->never())->method('getRequestPatterns');
        $token2->method('getAuthenticationProviderName')->willReturn(('token2Provider'));
        $token2->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->createMock(TokenInterface::class);
        $token3->expects($this->once())->method('hasRequestPatterns')->willReturn((true));
        $token3->expects($this->once())->method('getRequestPatterns')->willReturn(([$notMatchingRequestPattern]));
        $token3->method('getAuthenticationProviderName')->willReturn(('token3Provider'));
        $token3->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token4 = $this->createMock(TokenInterface::class);
        $token4->expects($this->once())->method('hasRequestPatterns')->willReturn((true));
        $token4->expects($this->once())->method('getRequestPatterns')->willReturn(([]));
        $token4->method('getAuthenticationProviderName')->willReturn(('token4Provider'));
        $token4->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token5 = $this->createMock(TokenInterface::class);
        $token5->expects($this->once())->method('hasRequestPatterns')->willReturn((true));
        $token5->expects($this->once())->method('getRequestPatterns')->willReturn(([$notMatchingRequestPattern, $matchingRequestPattern]));
        $token5->method('getAuthenticationProviderName')->willReturn(('token5Provider'));
        $token5->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $this->mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactoryInterface::class);
        $this->mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn(([
            $token1,
            $token2,
            $token3,
            $token4,
            $token5
        ]));
//        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
//        $mockAuthenticationManager->expects($this->once())->method('getTokens')->willReturn(([$token1, $token2, $token3, $token4, $token5]));

        $mockSession = $this->createStub(SessionInterface::class);
        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->method('getCurrentSession')->willReturn(($mockSession));
        $mockSecurityLogger = $this->createStub(LoggerInterface::class);

        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);
        $securityContext->_set('tokens', [$token1, $token3, $token4]);

        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('tokens', [$token1, $token3, $token4]);
        $securityContext->initialize();

        self::assertEquals([$token1, $token2, $token4], array_values($securityContext->_get('activeTokens')));
        self::assertEquals([$token3, $token5], array_values($securityContext->_get('inactiveTokens')));
    }

    #[Test]
    public function initializeStoresSessionCompatibleTokensInSessionDataContainer()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);

        $securityContext->injectSettings(['security' => ['authentication' => ['authenticationStrategy' => 'allTokens']]]);

        $matchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeRequestPattern' . uniqid())->getMock();
        $matchingRequestPattern->method('matchRequest')->willReturn(true);

        $notMatchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeOtherRequestPattern' . uniqid())->getMock();
        $notMatchingRequestPattern->method('matchRequest')->willReturn(false);

        $inactiveToken = $this->createMock(TokenInterface::class);
        $inactiveToken->expects($this->once())->method('hasRequestPatterns')->willReturn(true);
        $inactiveToken->expects($this->once())->method('getRequestPatterns')->willReturn([$notMatchingRequestPattern]);
        $inactiveToken->method('getAuthenticationProviderName')->willReturn('inactiveTokenProvider');
        $inactiveToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $activeToken = $this->createMock(TokenInterface::class);
        $activeToken->expects($this->once())->method('hasRequestPatterns')->willReturn(false);
        $activeToken->method('getAuthenticationProviderName')->willReturn('activeTokenProvider');
        $activeToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $sessionlessToken = $this->createMock(TestingToken::class);
        $sessionlessToken->expects($this->once())->method('hasRequestPatterns')->willReturn(false);
        $sessionlessToken->method('getAuthenticationProviderName')->willReturn('sessionlessTokenProvider');
        $sessionlessToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $this->mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactoryInterface::class);
        $this->mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn([
            $inactiveToken,
            $activeToken,
            $sessionlessToken,
        ]);
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->setRequest($this->createStub(ActionRequest::class));

        $expectedTokens = ['inactiveTokenProvider' => $inactiveToken, 'activeTokenProvider' => $activeToken];
        $this->mockSessionDataContainer->expects($this->once())->method('setSecurityTokens')->with($expectedTokens);

        $securityContext->initialize();
    }

    /**
     * @return \Iterator<(int | string), mixed>
     */
    public static function separateActiveAndInactiveTokensDataProvider(): \Iterator
    {
        yield [
            'patterns' => [
            ],
            'expectedActive' => true
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => true],
            ],
            'expectedActive' => true
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => false],
            ],
            'expectedActive' => false
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => true],
                ['type' => 'type2', 'matchesRequest' => true],
            ],
            'expectedActive' => true
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => true],
                ['type' => 'type2', 'matchesRequest' => false],
            ],
            'expectedActive' => false
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => true],
                ['type' => 'type2', 'matchesRequest' => false],
                ['type' => 'type2', 'matchesRequest' => true],
            ],
            'expectedActive' => true
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => false],
                ['type' => 'type2', 'matchesRequest' => false],
                ['type' => 'type2', 'matchesRequest' => true],
                ['type' => 'type1', 'matchesRequest' => true],
            ],
            'expectedActive' => true
        ];
        yield [
            'patterns' => [
                ['type' => 'type1', 'matchesRequest' => true],
                ['type' => 'type2', 'matchesRequest' => true],
                ['type' => 'type1', 'matchesRequest' => false],
                ['type' => 'type2', 'matchesRequest' => false],
            ],
            'expectedActive' => true
        ];
    }

    /**
     * @param array $patterns
     * @param bool $expectedActive
     */
    #[DataProvider('separateActiveAndInactiveTokensDataProvider')]
    #[Test]
    public function separateActiveAndInactiveTokensTests(array $patterns, $expectedActive)
    {
        // Patterns sharing a logical type must share a PHP class, because
        // Context::isTokenActive() groups patterns by their class name. Pre-declare
        // one anonymous-style class per type and create mock instances from it.
        $patternClasses = [];
        foreach ($patterns as $pattern) {
            if (!isset($patternClasses[$pattern['type']])) {
                $className = 'RequestPattern_' . $pattern['type'] . '_' . md5(uniqid('', true));
                eval('class ' . $className . ' implements \\' . RequestPatternInterface::class . ' { public function matchRequest(\\Neos\\Flow\\Mvc\\ActionRequest $request) { return false; } }');
                $patternClasses[$pattern['type']] = $className;
            }
        }

        $mockRequestPatterns = [];
        foreach ($patterns as $pattern) {
            $mockRequestPattern = $this->createMock($patternClasses[$pattern['type']]);
            $mockRequestPattern->method('matchRequest')->willReturn($pattern['matchesRequest']);
            $mockRequestPatterns[] = $mockRequestPattern;
        }

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->once())->method('hasRequestPatterns')->willReturn(($mockRequestPatterns !== []));
        $mockToken->method('getRequestPatterns')->willReturn(($mockRequestPatterns));

        $this->mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn([$mockToken]);

        $this->securityContext = $this->getAccessibleMock(Context::class);
        $this->inject($this->securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($this->securityContext, 'tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $this->securityContext->injectSettings($settings);
        $this->securityContext->setRequest($this->createStub(ActionRequest::class));

        $this->securityContext->initialize();
        if ($expectedActive) {
            self::assertContains($mockToken, $this->securityContext->_get('activeTokens'));
        } else {
            self::assertContains($mockToken, $this->securityContext->_get('inactiveTokens'));
        }
    }

    #[Test]
    public function securityContextCallsTokenAndProviderFactoryToGetItsTokens()
    {
        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($securityContext, 'tokenAndProviderFactory', $this->mockTokenAndProviderFactory);

        $this->mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn([]);

        $securityContext->setRequest($this->createStub(ActionRequest::class));

        $securityContext->initialize();
    }

    #[Test]
    public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->method('getAuthenticationProviderName')->willReturn(('token1Provider'));
        $token1Clone = $this->createMock(TokenInterface::class);
        $token1Clone->method('getAuthenticationProviderName')->willReturn(('token1Provider'));
        $token1Clone->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->createMock(TokenInterface::class);
        $token2->method('getAuthenticationProviderName')->willReturn(('token2Provider'));
        $token2Clone = $this->createMock(TokenInterface::class);
        $token2Clone->method('getAuthenticationProviderName')->willReturn(('token2Provider'));
        $token2Clone->method('getAuthenticationStatus')->willReturn((TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->createMock(TokenInterface::class);
        $token3->method('getAuthenticationProviderName')->willReturn(('token3Provider'));

        $tokensFromTheFactory = [$token1, $token2, $token3];
        $tokensFromTheSession = [$token1Clone, $token2Clone];

        $mockSession = $this->createStub(SessionInterface::class);
        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->method('getCurrentSession')->willReturn(($mockSession));
        $mockSecurityLogger = $this->createStub(LoggerInterface::class);

        $securityContext = $this->getAccessibleMock(Context::class, []);

        $this->mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn($tokensFromTheFactory);

        $this->mockSessionDataContainer->expects($this->once())->method('getSecurityTokens')->willReturn($tokensFromTheSession);

        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);

        $result = $securityContext->initialize();
//        $securityContext->_call('initialize');

        $expectedMergedTokens = [$token1Clone, $token2Clone, $token3];
        self::assertEquals($expectedMergedTokens, array_values($securityContext->_get('activeTokens')));
    }

    #[Test]
    public function initializeCallsUpdateCredentialsOnAllActiveTokens()
    {
        $securityContext = $this->getAccessibleMock(Context::class, []);

        $notMatchingRequestPattern = $this->createMock(RequestPatternInterface::class);
        $notMatchingRequestPattern->method('matchRequest')->willReturn((false));

        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->method('getAuthenticationProviderName')->willReturn(('token1Provider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->method('getAuthenticationProviderName')->willReturn(('token2Provider'));
        $mockToken2->expects($this->atLeastOnce())->method('hasRequestPatterns')->willReturn((true));
        $mockToken2->expects($this->atLeastOnce())->method('getRequestPatterns')->willReturn(([$notMatchingRequestPattern]));
        $mockToken3 = $this->createMock(TokenInterface::class);
        $mockToken3->method('getAuthenticationProviderName')->willReturn(('token3Provider'));

        $mockToken1->expects($this->once())->method('updateCredentials');
        $mockToken2->expects($this->never())->method('updateCredentials');
        $mockToken3->expects($this->once())->method('updateCredentials');

        $mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactory::class);
        $mockTokenAndProviderFactory->expects($this->once())->method('getTokens')->willReturn([$mockToken1, $mockToken2, $mockToken3]);
        $securityContext->_set('tokenAndProviderFactory', $mockTokenAndProviderFactory);

        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));

        $securityContext->_call('initialize');
    }

    /**
     * Data provider for authentication strategy settings
     *
     * @return array
     */
    public static function authenticationStrategies()
    {
        $data = [];
        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $data[] = [$settings, Context::AUTHENTICATE_ALL_TOKENS];
        $settings['security']['authentication']['authenticationStrategy'] = 'oneToken';
        $data[] = [$settings, Context::AUTHENTICATE_ONE_TOKEN];
        $settings['security']['authentication']['authenticationStrategy'] = 'atLeastOneToken';
        $data[] = [$settings, Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN];
        $settings['security']['authentication']['authenticationStrategy'] = 'anyToken';
        $data[] = [$settings, Context::AUTHENTICATE_ANY_TOKEN];
        return $data;
    }

    #[DataProvider('authenticationStrategies')]
    #[Test]
    public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy)
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);

        self::assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
    }

    #[Test]
    public function invalidAuthenticationStrategyFromConfigurationThrowsException()
    {
        $this->expectException(Exception::class);
        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
    }

    /**
     * Data provider for CSRF protection strategy settings
     *
     * @return array
     */
    public static function csrfProtectionStrategies()
    {
        $data = [];
        $settings = [];
        $settings['security']['csrf']['csrfStrategy'] = 'onePerRequest';
        $data[] = [$settings, Context::CSRF_ONE_PER_REQUEST];
        $settings['security']['csrf']['csrfStrategy'] = 'onePerSession';
        $data[] = [$settings, Context::CSRF_ONE_PER_SESSION];
        $settings['security']['csrf']['csrfStrategy'] = 'onePerUri';
        $data[] = [$settings, Context::CSRF_ONE_PER_URI];
        return $data;
    }

    #[DataProvider('csrfProtectionStrategies')]
    #[Test]
    public function csrfProtectionStrategyIsSetCorrectlyFromConfiguration($settings, $expectedCsrfProtectionStrategy)
    {
        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);

        self::assertEquals($expectedCsrfProtectionStrategy, $securityContext->_get('csrfProtectionStrategy'));
    }

    #[Test]
    public function invalidCsrfProtectionStrategyFromConfigurationThrowsException()
    {
        $this->expectException(Exception::class);
        $settings = [];
        $settings['security']['csrf']['csrfStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(Context::class, []);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
    }

    #[Test]
    public function getRolesReturnsTheCorrectRoles()
    {
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $authenticatedUserRole = new Role('Neos.Flow:AuthenticatedUser');
        $testRole = new Role('Acme.Demo:TestRole');

        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole', 'initializeRolesFromPolicy']);
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->willReturnCallback(function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Everybody':
                    return $everybodyRole;
                case 'Neos.Flow:AuthenticatedUser':
                    return $authenticatedUserRole;
            }
        });

        $account = $this->getAccessibleMock(Account::class, []);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->willReturn(($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->method('getAccount')->willReturn(($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        $expectedResult = ['Neos.Flow:Everybody' => $everybodyRole, 'Neos.Flow:AuthenticatedUser' => $authenticatedUserRole, 'Acme.Demo:TestRole' => $testRole];
        self::assertEquals($expectedResult, $securityContext->getRoles());
    }

    #[Test]
    public function getRolesTakesInheritanceOfRolesIntoAccount()
    {
        /** @var Role|MockObject $everybodyRole */
        $everybodyRole = $this->getAccessibleMock(Role::class, [], ['Neos.Flow:Everybody']);
        /** @var Role|MockObject $authenticatedUserRole */
        $authenticatedUserRole = $this->getAccessibleMock(Role::class, [], ['Neos.Flow:AuthenticatedUser']);
        /** @var Role|MockObject $testRole1 */
        $testRole1 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole1']);
        /** @var Role|MockObject $testRole2 */
        $testRole2 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole2']);
        /** @var Role|MockObject $testRole3 */
        $testRole3 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole3']);
        /** @var Role|MockObject $testRole4 */
        $testRole4 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole4']);
        /** @var Role|MockObject $testRole5 */
        $testRole5 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole5']);
        /** @var Role|MockObject $testRole6 */
        $testRole6 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole6']);
        /** @var Role|MockObject $testRole7 */
        $testRole7 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole7']);

        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->willReturnCallback(function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole, $testRole1, $testRole2, $testRole3, $testRole4, $testRole5, $testRole6, $testRole7) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Everybody':
                    return $everybodyRole;
                case 'Neos.Flow:AuthenticatedUser':
                    return $authenticatedUserRole;
                case 'Acme.Demo:TestRole1':
                    return $testRole1;
                case 'Acme.Demo:TestRole2':
                    return $testRole2;
                case 'Acme.Demo:TestRole3':
                    return $testRole3;
                case 'Acme.Demo:TestRole4':
                    return $testRole4;
                case 'Acme.Demo:TestRole5':
                    return $testRole5;
                case 'Acme.Demo:TestRole6':
                    return $testRole6;
                case 'Acme.Demo:TestRole7':
                    return $testRole7;
            }
        });

        // Set parents
        $testRole1->setParentRoles([$testRole2, $testRole3]);
        $testRole2->setParentRoles([$testRole4, $testRole5]);
        $testRole3->setParentRoles([$testRole6, $testRole7]);

        /** @var Account|MockObject $account */
        $account = $this->getAccessibleMock(Account::class, []);
        $this->inject($account, 'policyService', $mockPolicyService);
        $account->setRoles([$testRole1]);

        /** @var TokenInterface|MockObject $mockToken */
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->willReturn(($account));

        /** @var Context|MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->method('getAccount')->willReturn(($account));
        $this->inject($securityContext, 'activeTokens', [$mockToken]);
        $this->inject($securityContext, 'policyService', $mockPolicyService);

        $expectedResult = [
            'Acme.Demo:TestRole1' => $testRole1,
            'Acme.Demo:TestRole2' => $testRole2,
            'Acme.Demo:TestRole3' => $testRole3,
            'Acme.Demo:TestRole4' => $testRole4,
            'Acme.Demo:TestRole5' => $testRole5,
            'Acme.Demo:TestRole6' => $testRole6,
            'Acme.Demo:TestRole7' => $testRole7,
            'Neos.Flow:Everybody' => $everybodyRole,
            'Neos.Flow:AuthenticatedUser' => $authenticatedUserRole
        ];
        $result = $securityContext->getRoles();

        ksort($expectedResult);
        ksort($result);

        self::assertSame(array_keys($expectedResult), array_keys($result));
    }

    #[Test]
    public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated()
    {
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $anonymousRole = new Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->exactly(2))->method('getRole')->willReturnMap([['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]]);

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Role::class, $result['Neos.Flow:Everybody']);
        self::assertEquals('Neos.Flow:Everybody', $result['Neos.Flow:Everybody']->getIdentifier());
    }

    #[Test]
    public function getRolesReturnsTheAnonymousRoleIfNoTokenIsAuthenticated()
    {
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $anonymousRole = new Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->exactly(2))->method('getRole')->willReturnMap([['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]]);

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Role::class, $result['Neos.Flow:Anonymous']);
        self::assertSame('Neos.Flow:Anonymous', (string)($result['Neos.Flow:Anonymous']));
    }

    #[Test]
    public function getRolesReturnsTheAuthenticatedUserRoleIfATokenIsAuthenticated(): void
    {
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->method('isAuthenticated')->willReturn(true);

        $everybodyRole = new Role('Neos.Flow:Everybody');
        $authenticatedUserRole = new Role('Neos.Flow:AuthenticatedUser');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->exactly(2))->method('getRole')->willReturnMap([['Neos.Flow:AuthenticatedUser', $authenticatedUserRole], ['Neos.Flow:Everybody', $everybodyRole]]);

        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Role::class, $result['Neos.Flow:AuthenticatedUser']);
        self::assertSame('Neos.Flow:AuthenticatedUser', (string)($result['Neos.Flow:AuthenticatedUser']));
    }

    #[Test]
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->method('getRole')->willReturnMap([
            ['Neos.Flow:Everybody', $everybodyRole]
        ]);

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Neos.Flow:Everybody'));
    }

    #[Test]
    public function hasRoleReturnsTrueForAnonymousRoleIfNotAuthenticated(): void
    {
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->method('isAuthenticated')->willReturn(false);

        $everybodyRole = new Role('Neos.Flow:Everybody');
        $anonymousRole = new Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->exactly(2))->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]
        ]);

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Neos.Flow:Anonymous'));
    }

    #[Test]
    public function hasRoleReturnsFalseForAnonymousRoleIfAuthenticated(): void
    {
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->method('isAuthenticated')->willReturn(true);

        $authenticatedUserRole = new Role('Neos.Flow:AuthenticatedUser');
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $anonymousRole = new Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole], ['Neos.Flow:AuthenticatedUser', $authenticatedUserRole]
        ]);

        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($securityContext, 'policyService', $mockPolicyService);

        self::assertFalse($securityContext->hasRole('Neos.Flow:Anonymous'));
    }

    #[Test]
    public function hasRoleWorks(): void
    {
        $testRole = new Role('Acme.Demo:TestRole');

        $authenticatedUserRole = new Role('Neos.Flow:AuthenticatedUser');
        $everybodyRole = new Role('Neos.Flow:Everybody');
        $anonymousRole = new Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole']);
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole], ['Neos.Flow:AuthenticatedUser', $authenticatedUserRole]
        ]);

        $account = $this->getAccessibleMock(Account::class, []);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->willReturn(($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->method('getAccount')->willReturn(($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Acme.Demo:TestRole'));
        self::assertFalse($securityContext->hasRole('Foo.Bar:Baz'));
    }

    #[Test]
    public function hasRoleWorksWithRecursiveRoles()
    {
        $everybodyRole = $this->getAccessibleMock(Role::class, [], ['Neos.Flow:Everybody']);
        $testRole1 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole1']);
        $testRole2 = $this->getAccessibleMock(Role::class, [], ['Acme.Demo:TestRole2']);
        $authenticatedUserRole = new Role('Neos.Flow:AuthenticatedUser');

        $mockPolicyService = $this->getAccessibleMock(PolicyService::class, ['getRole', 'initializeRolesFromPolicy']);
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->willReturnCallback(function ($roleIdentifier) use ($everybodyRole, $testRole1, $testRole2, $authenticatedUserRole) {
            switch ($roleIdentifier) {
                case 'Neos.Flow:Everybody':
                    return $everybodyRole;
                case 'Acme.Demo:TestRole1':
                    return $testRole1;
                case 'Acme.Demo:TestRole2':
                    return $testRole2;
                case 'Neos.Flow:AuthenticatedUser':
                    return $authenticatedUserRole;
            }
        });

        $everybodyRole->_set('policyService', $mockPolicyService);
        $testRole1->_set('policyService', $mockPolicyService);
        $testRole2->_set('policyService', $mockPolicyService);

        // Set parents
        $testRole1->setParentRoles([$testRole2]);

        $account = $this->getAccessibleMock(Account::class, []);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole1]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->willReturn((true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->willReturn(($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->method('getAccount')->willReturn(($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Acme.Demo:TestRole2'));
    }

    #[Test]
    public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken()
    {
        $mockAccount = $this->createStub(Account::class);

        $token1 = $this->createMock(TokenInterface::class, [], [], 'token1' . md5(uniqid((string)mt_rand(), true)));
        $token1->method('isAuthenticated')->willReturn((false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->createMock(TokenInterface::class, [], [], 'token2' . md5(uniqid((string)mt_rand(), true)));
        $token2->method('isAuthenticated')->willReturn((true));
        $token2->expects($this->once())->method('getAccount')->willReturn(($mockAccount));

        $token3 = $this->createMock(TokenInterface::class, [], [], 'token3' . md5(uniqid((string)mt_rand(), true)));
        $token3->method('isAuthenticated')->willReturn((true));
        $token3->expects($this->never())->method('getAccount');

        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('initialized', true);
        $securityContext->expects($this->once())->method('getAuthenticationTokens')->willReturn(([$token1, $token2, $token3]));

        self::assertEquals($mockAccount, $securityContext->getAccount());
    }

    #[Test]
    public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName()
    {
        $mockAccount1 = $this->createStub(Account::class);
        $mockAccount2 = $this->createStub(Account::class);

        $token1 = $this->createMock(TokenInterface::class, [], [], 'token1' . md5(uniqid((string)mt_rand(), true)));
        $token1->method('isAuthenticated')->willReturn((false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->createMock(TokenInterface::class, [], [], 'token2' . md5(uniqid((string)mt_rand(), true)));
        $token2->method('isAuthenticated')->willReturn((true));
        $token2->method('getAccount')->willReturn(($mockAccount1));

        $token3 = $this->createMock(TokenInterface::class, [], [], 'token3' . md5(uniqid((string)mt_rand(), true)));
        $token3->method('isAuthenticated')->willReturn((true));
        $token3->method('getAccount')->willReturn(($mockAccount2));

        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('activeTokens', ['SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3]);
        $securityContext->_set('initialized', true);

        self::assertSame($mockAccount2, $securityContext->getAccountByAuthenticationProviderName('MatchingProvider'));
    }

    #[Test]
    public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('activeTokens', []);
        $securityContext->_set('initialized', true);

        self::assertNull($securityContext->getAccountByAuthenticationProviderName('UnknownProvider'));
    }

    #[Test]
    public function getCsrfProtectionTokenReturnsANewTokenIfNoneIsPresentInTheContext()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->createStub(ActionRequest::class));
        $securityContext->_set('csrfTokens', []);
        $securityContext->_set('initialized', true);

        self::assertNotEmpty($securityContext->getCsrfProtectionToken());
    }

    #[Test]
    public function getCsrfProtectionTokenReturnsANewTokenIfTheCsrfStrategyIsOnePerUri()
    {
        $existingTokens = ['token1' => true, 'token2' => true];

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->createStub(ActionRequest::class));
        $this->securityContext->_set('csrfTokens', $existingTokens);
        $this->securityContext->_set('csrfStrategy', Context::CSRF_ONE_PER_URI);

        self::assertArrayNotHasKey($this->securityContext->getCsrfProtectionToken(), $existingTokens);
    }

    #[Test]
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContext()
    {
        $existingTokens = ['csrfToken12345' => true];
        $this->mockSessionDataContainer->method('getCsrfProtectionTokens')->willReturn($existingTokens);

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->createStub(ActionRequest::class));
        $this->securityContext->_set('objectManager', $this->mockObjectManager);
        $this->securityContext->_set('csrfProtectionTokens', $existingTokens);

        self::assertTrue($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        self::assertFalse($this->securityContext->isCsrfProtectionTokenValid('csrfToken'));
    }

    #[Test]
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContextAndUnsetsItIfTheCsrfStrategyIsOnePerUri()
    {
        $existingTokens = ['csrfToken12345' => true];

        $sessionDataContainer = new SessionDataContainer();
        $sessionDataContainer->setCsrfProtectionTokens($existingTokens);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->with(SessionDataContainer::class)->willReturn($sessionDataContainer);

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->createStub(ActionRequest::class));
        $this->securityContext->_set('objectManager', $mockObjectManager);
        $this->securityContext->_set('initialized', true);
        $this->securityContext->_set('csrfProtectionStrategy', Context::CSRF_ONE_PER_URI);

        self::assertTrue($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        self::assertFalse($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
    }

    #[Test]
    public function authorizationChecksAreEnabledByDefault()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function withoutAuthorizationChecksDisabledAuthorizationChecks()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
    }

    #[Test]
    public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocation()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $securityContext->withoutAuthorizationChecks(function () {
        });
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocationIfClosureThrowsException()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        try {
            $securityContext->withoutAuthorizationChecks(function () {
                throw new \Exception('Test Exception');
            });
        } catch (\Exception $exception) {
        }
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function withoutAuthorizationChecksReactivatesAuthorizationCheckCorrectlyWhenCalledNested()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
                $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
            });
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function withoutAuthorizationChecksKeepsAuthorizationCheckCorrectlyWhenCalledNestedAndExceptionFromInnerClosureIsCaught()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            try {
                $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
                    $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
                    throw new \Exception('Some exception');
                });
            } catch (\Exception $exception) {
            }
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function withoutAuthorizationChecksKeepsAuthorizationCheckCorrectlyWhenCalledNestedAndExceptionFromOuterClosureIsCaught()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            try {
                $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
                    $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
                });

                throw new \Exception('Some exception');
            } catch (\Exception $exception) {
            }
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    #[Test]
    public function getContextHashReturnsStaticStringIfAuthorizationChecksAreDisabled()
    {
        $self = $this;
        $this->securityContext->withoutAuthorizationChecks(function () use ($self) {
            $self->assertSame(Context::CONTEXT_HASH_UNINITIALIZED, $self->securityContext->getContextHash());
        });
    }

    #[Test]
    public function getContextHashInitializesSecurityContext()
    {
        /** @var Context|MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'canBeInitialized', 'getRoles']);
        $securityContext->expects($this->atLeastOnce())->method('canBeInitialized')->willReturn(true);
        $securityContext->expects($this->once())->method('initialize');
        $securityContext->method('getRoles')->willReturn([]);

        $securityContext->getContextHash();
    }

    #[Test]
    public function getContextHashReturnsAHashOverAllAuthenticatedRoles()
    {
        /** @var Context|MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['isInitialized', 'getRoles']);
        $securityContext->method('isInitialized')->willReturn((true));

        $mockRole1 = $this->createStub(Role::class);
        $mockRole2 = $this->createStub(Role::class);
        $mockRoles = ['Acme.Role1' => $mockRole1, 'Acme.Role2' => $mockRole2];
        $securityContext->expects($this->atLeastOnce())->method('getRoles')->willReturn(($mockRoles));

        $expectedHash = md5(implode('|', array_keys($mockRoles)));
        self::assertSame($expectedHash, $securityContext->getContextHash());
    }

    #[Test]
    public function getContextHashReturnsStaticStringIfSecurityContextCantBeInitialized()
    {
        /** @var Context|MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'canBeInitialized']);
        $securityContext->expects($this->atLeastOnce())->method('canBeInitialized')->willReturn((false));
        $securityContext->expects($this->never())->method('initialize');
        self::assertSame(Context::CONTEXT_HASH_UNINITIALIZED, $securityContext->getContextHash());
    }

    #[Test]
    public function getSessionTagForAccountCreatesUniqueTagsPerAccount()
    {
        $account1 = $this->createMock(Account::class);
        $account1->method('getAccountIdentifier')->willReturn('Account1');
        $account2 = $this->createMock(Account::class);
        $account2->method('getAccountIdentifier')->willReturn('Account2');

        self::assertNotSame($this->securityContext->getSessionTagForAccount($account1), $this->securityContext->getSessionTagForAccount($account2));
    }

    #[Test]
    public function destroySessionsForAccountWillDestroySessionsByAccountTag()
    {
        $account = $this->createMock(Account::class);
        $account->method('getAccountIdentifier')->willReturn('Account');
        $accountTag = $this->securityContext->getSessionTagForAccount($account);

        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->expects($this->once())->method('destroySessionsByTag')->with($accountTag);
        $this->securityContext->_set('sessionManager', $mockSessionManager);

        $this->securityContext->destroySessionsForAccount($account);
    }
}
