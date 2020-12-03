<?php
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

use Neos\Flow\Exception;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Security\Account;
use Neos\Flow\Security\Authentication\Token\TestingToken;
use Neos\Flow\Security\Authentication\TokenAndProviderFactory;
use Neos\Flow\Security\Authentication\TokenAndProviderFactoryInterface;
use Neos\Flow\Security\Authentication\TokenInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy;
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
class ContextTest extends UnitTestCase
{
    /**
     * @var Context
     */
    protected $securityContext;

    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var TokenAndProviderFactoryInterface
     */
    protected $mockTokenAndProviderFactory;

    /**
     * @var ObjectManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockObjectManager;

    /**
     * @var SessionDataContainer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSessionDataContainer;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        $this->mockSessionDataContainer = $this->createMock(SessionDataContainer::class);

        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->mockObjectManager->expects(self::any())->method('get')->with(SessionDataContainer::class)->willReturn($this->mockSessionDataContainer);

        $this->securityContext = $this->getAccessibleMock(Context::class, ['separateActiveAndInactiveTokens']);
        $this->inject($this->securityContext, 'objectManager', $this->mockObjectManager);

        $this->mockTokenAndProviderFactory = $this->getMockBuilder(TokenAndProviderFactoryInterface::class)->setMethods(['getTokens', 'getProviders'])->getMock();
        $this->securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->securityContext->setRequest($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function currentRequestIsSetInTheSecurityContext()
    {
        $this->securityContext->initialize();
        self::assertSame($this->mockActionRequest, $this->securityContext->_get('request'));
    }

    /**
     * @test
     */
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
     *
     * @test
     */
    public function securityContextIsNotInitializedAgainIfItHasBeenInitializedAlready()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['canBeInitialized']);
        $securityContext->expects(self::never())->method('canBeInitialized');
        $securityContext->_set('initialized', true);

        $securityContext->initialize();
    }

    /**
     * @test
     */
    public function initializeSeparatesActiveAndInactiveTokens()
    {
        $this->securityContext->expects(self::once())->method('separateActiveAndInactiveTokens');
        $this->securityContext->initialize();
    }

    /**
     * @test
     */
    public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);

        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $securityContext->injectSettings($settings);

        $matchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeRequestPattern')->getMock();
        $matchingRequestPattern->expects(self::any())->method('matchRequest')->will(self::returnValue(true));

        $notMatchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeOtherRequestPattern')->getMock();
        $notMatchingRequestPattern->expects(self::any())->method('matchRequest')->will(self::returnValue(false));

        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue(true));
        $token1->expects(self::once())->method('getRequestPatterns')->will(self::returnValue([$matchingRequestPattern]));
        $token1->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token1Provider'));
        $token1->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue(false));
        $token2->expects(self::never())->method('getRequestPatterns');
        $token2->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token2Provider'));
        $token2->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->createMock(TokenInterface::class);
        $token3->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue(true));
        $token3->expects(self::once())->method('getRequestPatterns')->will(self::returnValue([$notMatchingRequestPattern]));
        $token3->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token3Provider'));
        $token3->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token4 = $this->createMock(TokenInterface::class);
        $token4->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue(true));
        $token4->expects(self::once())->method('getRequestPatterns')->will(self::returnValue([]));
        $token4->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token4Provider'));
        $token4->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token5 = $this->createMock(TokenInterface::class);
        $token5->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue(true));
        $token5->expects(self::once())->method('getRequestPatterns')->will(self::returnValue([$notMatchingRequestPattern, $matchingRequestPattern]));
        $token5->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token5Provider'));
        $token5->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $this->mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactoryInterface::class);
        $this->mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->will(self::returnValue([
            $token1,
            $token2,
            $token3,
            $token4,
            $token5
        ]));
//        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
//        $mockAuthenticationManager->expects(self::once())->method('getTokens')->will(self::returnValue([$token1, $token2, $token3, $token4, $token5]));

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->expects(self::any())->method('getCurrentSession')->will(self::returnValue($mockSession));
        $mockSecurityLogger = $this->createMock(LoggerInterface::class);

        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);
        $securityContext->_set('tokens', [$token1, $token3, $token4]);

        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('tokens', [$token1, $token3, $token4]);
        $securityContext->initialize();

        self::assertEquals([$token1, $token2, $token4], array_values($securityContext->_get('activeTokens')));
        self::assertEquals([$token3, $token5], array_values($securityContext->_get('inactiveTokens')));
    }

    /**
     * @test
     */
    public function initializeStoresSessionCompatibleTokensInSessionDataContainer()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);

        $securityContext->injectSettings(['security' => ['authentication' => ['authenticationStrategy' => 'allTokens']]]);

        $matchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeRequestPattern')->getMock();
        $matchingRequestPattern->method('matchRequest')->willReturn(true);

        $notMatchingRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('SomeOtherRequestPattern')->getMock();
        $notMatchingRequestPattern->method('matchRequest')->willReturn(false);

        $inactiveToken = $this->createMock(TokenInterface::class);
        $inactiveToken->expects(self::once())->method('hasRequestPatterns')->willReturn(true);
        $inactiveToken->expects(self::once())->method('getRequestPatterns')->willReturn([$notMatchingRequestPattern]);
        $inactiveToken->method('getAuthenticationProviderName')->willReturn('inactiveTokenProvider');
        $inactiveToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $activeToken = $this->createMock(TokenInterface::class);
        $activeToken->expects(self::once())->method('hasRequestPatterns')->willReturn(false);
        $activeToken->method('getAuthenticationProviderName')->willReturn('activeTokenProvider');
        $activeToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $sessionlessToken = $this->createMock(TestingToken::class);
        $sessionlessToken->expects(self::once())->method('hasRequestPatterns')->willReturn(false);
        $sessionlessToken->method('getAuthenticationProviderName')->willReturn('sessionlessTokenProvider');
        $sessionlessToken->method('getAuthenticationStatus')->willReturn(TokenInterface::AUTHENTICATION_NEEDED);

        $this->mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactoryInterface::class);
        $this->mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->willReturn([
            $inactiveToken,
            $activeToken,
            $sessionlessToken,
        ]);
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->setRequest($this->mockActionRequest);

        $expectedTokens = ['inactiveTokenProvider' => $inactiveToken, 'activeTokenProvider' => $activeToken];
        $this->mockSessionDataContainer->expects(self::once())->method('setSecurityTokens')->with($expectedTokens);

        $securityContext->initialize();
    }

    /**
     * @return array
     */
    public function separateActiveAndInactiveTokensDataProvider()
    {
        return [
            [
                'patterns' => [
                ],
                'expectedActive' => true
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => true],
                ],
                'expectedActive' => true
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => false],
                ],
                'expectedActive' => false
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => true],
                    ['type' => 'type2', 'matchesRequest' => true],
                ],
                'expectedActive' => true
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => true],
                    ['type' => 'type2', 'matchesRequest' => false],
                ],
                'expectedActive' => false
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => true],
                    ['type' => 'type2', 'matchesRequest' => false],
                    ['type' => 'type2', 'matchesRequest' => true],
                ],
                'expectedActive' => true
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => false],
                    ['type' => 'type2', 'matchesRequest' => false],
                    ['type' => 'type2', 'matchesRequest' => true],
                    ['type' => 'type1', 'matchesRequest' => true],
                ],
                'expectedActive' => true
            ],
            [
                'patterns' => [
                    ['type' => 'type1', 'matchesRequest' => true],
                    ['type' => 'type2', 'matchesRequest' => true],
                    ['type' => 'type1', 'matchesRequest' => false],
                    ['type' => 'type2', 'matchesRequest' => false],
                ],
                'expectedActive' => true
            ],
        ];
    }

    /**
     * @param array $patterns
     * @param bool $expectedActive
     * @test
     * @dataProvider separateActiveAndInactiveTokensDataProvider
     */
    public function separateActiveAndInactiveTokensTests(array $patterns, $expectedActive)
    {
        $mockRequestPatterns = [];
        foreach ($patterns as $pattern) {
            $mockRequestPattern = $this->getMockBuilder(RequestPatternInterface::class)->setMockClassName('RequestPattern_' . $pattern['type'])->getMock();
            $mockRequestPattern->expects(self::any())->method('matchRequest')->with($this->mockActionRequest)->will(self::returnValue($pattern['matchesRequest']));
            $mockRequestPatterns[] = $mockRequestPattern;
        }

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects(self::once())->method('hasRequestPatterns')->will(self::returnValue($mockRequestPatterns !== []));
        $mockToken->expects(self::any())->method('getRequestPatterns')->will(self::returnValue($mockRequestPatterns));

        $this->mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->willReturn([$mockToken]);

        $this->securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($this->securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($this->securityContext, 'tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $this->securityContext->injectSettings($settings);
        $this->securityContext->setRequest($this->mockActionRequest);

        $this->securityContext->initialize();
        if ($expectedActive) {
            self::assertContains($mockToken, $this->securityContext->_get('activeTokens'));
        } else {
            self::assertContains($mockToken, $this->securityContext->_get('inactiveTokens'));
        }
    }

    /**
     * @test
     */
    public function securityContextCallsTokenAndProviderFactoryToGetItsTokens()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($securityContext, 'tokenAndProviderFactory', $this->mockTokenAndProviderFactory);

        $this->mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->willReturn([]);

        $securityContext->setRequest($this->mockActionRequest);

        $securityContext->initialize();
    }

    /**
     * @test
     */
    public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession()
    {
        $token1 = $this->createMock(TokenInterface::class);
        $token1->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token1Provider'));
        $token1Clone = $this->createMock(TokenInterface::class);
        $token1Clone->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token1Provider'));
        $token1Clone->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->createMock(TokenInterface::class);
        $token2->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token2Provider'));
        $token2Clone = $this->createMock(TokenInterface::class);
        $token2Clone->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token2Provider'));
        $token2Clone->expects(self::any())->method('getAuthenticationStatus')->will(self::returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->createMock(TokenInterface::class);
        $token3->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token3Provider'));

        $tokensFromTheFactory = [$token1, $token2, $token3];
        $tokensFromTheSession = [$token1Clone, $token2Clone];

        $mockSession = $this->createMock(SessionInterface::class);
        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->expects(self::any())->method('getCurrentSession')->will(self::returnValue($mockSession));
        $mockSecurityLogger = $this->createMock(LoggerInterface::class);

        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);

        $this->mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->willReturn($tokensFromTheFactory);

        $this->mockSessionDataContainer->expects(self::once())->method('getSecurityTokens')->willReturn($tokensFromTheSession);

        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('tokenAndProviderFactory', $this->mockTokenAndProviderFactory);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);

        $result = $securityContext->initialize();
//        $securityContext->_call('initialize');

        $expectedMergedTokens = [$token1Clone, $token2Clone, $token3];
        self::assertEquals($expectedMergedTokens, array_values($securityContext->_get('activeTokens')));
    }

    /**
     * @test
     */
    public function initializeCallsUpdateCredentialsOnAllActiveTokens()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);

        $notMatchingRequestPattern = $this->createMock(RequestPatternInterface::class);
        $notMatchingRequestPattern->expects(self::any())->method('matchRequest')->will(self::returnValue(false));

        $mockToken1 = $this->createMock(TokenInterface::class);
        $mockToken1->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token1Provider'));
        $mockToken2 = $this->createMock(TokenInterface::class);
        $mockToken2->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token2Provider'));
        $mockToken2->expects(self::atLeastOnce())->method('hasRequestPatterns')->will(self::returnValue(true));
        $mockToken2->expects(self::atLeastOnce())->method('getRequestPatterns')->will(self::returnValue([$notMatchingRequestPattern]));
        $mockToken3 = $this->createMock(TokenInterface::class);
        $mockToken3->expects(self::any())->method('getAuthenticationProviderName')->will(self::returnValue('token3Provider'));

        $mockToken1->expects(self::once())->method('updateCredentials');
        $mockToken2->expects(self::never())->method('updateCredentials');
        $mockToken3->expects(self::once())->method('updateCredentials');

        $mockTokenAndProviderFactory = $this->createMock(TokenAndProviderFactory::class);
        $mockTokenAndProviderFactory->expects(self::once())->method('getTokens')->willReturn([$mockToken1, $mockToken2, $mockToken3]);
        $securityContext->_set('tokenAndProviderFactory', $mockTokenAndProviderFactory);

        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);

        $securityContext->_call('initialize');
    }

    /**
     * Data provider for authentication strategy settings
     *
     * @return array
     */
    public function authenticationStrategies()
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

    /**
     * @dataProvider authenticationStrategies()
     * @test
     */
    public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy)
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);

        self::assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
    }

    /**
     * @test
     */
    public function invalidAuthenticationStrategyFromConfigurationThrowsException()
    {
        $this->expectException(Exception::class);
        $settings = [];
        $settings['security']['authentication']['authenticationStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
    }

    /**
     * Data provider for CSRF protection strategy settings
     *
     * @return array
     */
    public function csrfProtectionStrategies()
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

    /**
     * @dataProvider csrfProtectionStrategies()
     * @test
     */
    public function csrfProtectionStrategyIsSetCorrectlyFromConfiguration($settings, $expectedCsrfProtectionStrategy)
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);

        self::assertEquals($expectedCsrfProtectionStrategy, $securityContext->_get('csrfProtectionStrategy'));
    }

    /**
     * @test
     */
    public function invalidCsrfProtectionStrategyFromConfigurationThrowsException()
    {
        $this->expectException(Exception::class);
        $settings = [];
        $settings['security']['csrf']['csrfStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(Context::class, ['dummy']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->injectSettings($settings);
    }

    /**
     * @test
     */
    public function getRolesReturnsTheCorrectRoles()
    {
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $authenticatedUserRole = new Policy\Role('Neos.Flow:AuthenticatedUser');
        $testRole = new Policy\Role('Acme.Demo:TestRole');

        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole', 'initializeRolesFromPolicy']);
        $mockPolicyService->expects(self::atLeastOnce())->method('getRole')->will(self::returnCallBack(
            function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole) {
                switch ($roleIdentifier) {
                    case 'Neos.Flow:Everybody':
                        return $everybodyRole;
                    case 'Neos.Flow:AuthenticatedUser':
                        return $authenticatedUserRole;
                }
            }
        ));

        $account = $this->getAccessibleMock(Account::class, ['dummy']);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects(self::atLeastOnce())->method('isAuthenticated')->will(self::returnValue(true));
        $mockToken->expects(self::atLeastOnce())->method('getAccount')->will(self::returnValue($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->expects(self::any())->method('getAccount')->will(self::returnValue($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        $expectedResult = ['Neos.Flow:Everybody' => $everybodyRole, 'Neos.Flow:AuthenticatedUser' => $authenticatedUserRole, 'Acme.Demo:TestRole' => $testRole];
        self::assertEquals($expectedResult, $securityContext->getRoles());
    }

    /**
     * @test
     */
    public function getRolesTakesInheritanceOfRolesIntoAccount()
    {
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $everybodyRole */
        $everybodyRole = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Neos.Flow:Everybody']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $authenticatedUserRole */
        $authenticatedUserRole = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Neos.Flow:AuthenticatedUser']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole1 */
        $testRole1 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole1']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole2 */
        $testRole2 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole2']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole3 */
        $testRole3 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole3']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole4 */
        $testRole4 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole4']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole5 */
        $testRole5 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole5']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole6 */
        $testRole6 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole6']);
        /** @var Role|\PHPUnit\Framework\MockObject\MockObject $testRole7 */
        $testRole7 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole7']);

        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::atLeastOnce())->method('getRole')->will(self::returnCallBack(
            function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole, $testRole1, $testRole2, $testRole3, $testRole4, $testRole5, $testRole6, $testRole7) {
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
            }
        ));

        // Set parents
        $testRole1->setParentRoles([$testRole2, $testRole3]);
        $testRole2->setParentRoles([$testRole4, $testRole5]);
        $testRole3->setParentRoles([$testRole6, $testRole7]);

        /** @var Account|\PHPUnit\Framework\MockObject\MockObject $account */
        $account = $this->getAccessibleMock(Account::class, ['dummy']);
        $this->inject($account, 'policyService', $mockPolicyService);
        $account->setRoles([$testRole1]);

        /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject $mockToken */
        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects(self::atLeastOnce())->method('isAuthenticated')->will(self::returnValue(true));
        $mockToken->expects(self::atLeastOnce())->method('getAccount')->will(self::returnValue($account));

        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->expects(self::any())->method('getAccount')->will(self::returnValue($account));
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

    /**
     * @test
     */
    public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated()
    {
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $anonymousRole = new Policy\Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->will($this->returnValueMap([['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]]));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Policy\Role::class, $result['Neos.Flow:Everybody']);
        self::assertEquals('Neos.Flow:Everybody', $result['Neos.Flow:Everybody']->getIdentifier());
    }

    /**
     * @test
     */
    public function getRolesReturnsTheAnonymousRoleIfNoTokenIsAuthenticated()
    {
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $anonymousRole = new Policy\Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->will($this->returnValueMap([['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]]));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Policy\Role::class, $result['Neos.Flow:Anonymous']);
        self::assertEquals('Neos.Flow:Anonymous', (string)($result['Neos.Flow:Anonymous']));
    }

    /**
     * @test
     */
    public function getRolesReturnsTheAuthenticatedUserRoleIfATokenIsAuthenticated(): void
    {
        $mockToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockToken->expects(self::any())->method('isAuthenticated')->willReturn(true);

        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $authenticatedUserRole = new Policy\Role('Neos.Flow:AuthenticatedUser');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->willReturnMap([['Neos.Flow:AuthenticatedUser', $authenticatedUserRole], ['Neos.Flow:Everybody', $everybodyRole]]);

        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->expects(self::any())->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        $result = $securityContext->getRoles();
        self::assertInstanceOf(Policy\Role::class, $result['Neos.Flow:AuthenticatedUser']);
        self::assertEquals('Neos.Flow:AuthenticatedUser', (string)($result['Neos.Flow:AuthenticatedUser']));
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->will($this->returnValueMap([
            ['Neos.Flow:Everybody', $everybodyRole]
        ]));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Neos.Flow:Everybody'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForAnonymousRoleIfNotAuthenticated(): void
    {
        $mockToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockToken->expects(self::any())->method('isAuthenticated')->willReturn(false);

        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $anonymousRole = new Policy\Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole]
        ]);

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->expects(self::any())->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Neos.Flow:Anonymous'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseForAnonymousRoleIfAuthenticated(): void
    {
        $mockToken = $this->getMockBuilder(TokenInterface::class)->getMock();
        $mockToken->expects(self::any())->method('isAuthenticated')->willReturn(true);

        $authenticatedUserRole = new Policy\Role('Neos.Flow:AuthenticatedUser');
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $anonymousRole = new Policy\Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::any())->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole], ['Neos.Flow:AuthenticatedUser', $authenticatedUserRole]
        ]);

        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAuthenticationTokens']);
        $securityContext->expects(self::any())->method('getAuthenticationTokens')->willReturn([$mockToken]);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $this->inject($securityContext, 'policyService', $mockPolicyService);

        self::assertFalse($securityContext->hasRole('Neos.Flow:Anonymous'));
    }

    /**
     * @test
     */
    public function hasRoleWorks(): void
    {
        $testRole = new Policy\Role('Acme.Demo:TestRole');

        $authenticatedUserRole = new Policy\Role('Neos.Flow:AuthenticatedUser');
        $everybodyRole = new Policy\Role('Neos.Flow:Everybody');
        $anonymousRole = new Policy\Role('Neos.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole']);
        $mockPolicyService->expects(self::atLeastOnce())->method('getRole')->willReturnMap([
            ['Neos.Flow:Anonymous', $anonymousRole], ['Neos.Flow:Everybody', $everybodyRole], ['Neos.Flow:AuthenticatedUser', $authenticatedUserRole]
        ]);

        $account = $this->getAccessibleMock(Account::class, ['dummy']);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects(self::atLeastOnce())->method('isAuthenticated')->will(self::returnValue(true));
        $mockToken->expects(self::atLeastOnce())->method('getAccount')->will(self::returnValue($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->expects(self::any())->method('getAccount')->will(self::returnValue($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Acme.Demo:TestRole'));
        self::assertFalse($securityContext->hasRole('Foo.Bar:Baz'));
    }

    /**
     * @test
     */
    public function hasRoleWorksWithRecursiveRoles()
    {
        $everybodyRole = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Neos.Flow:Everybody']);
        $testRole1 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole1']);
        $testRole2 = $this->getAccessibleMock(Policy\Role::class, ['dummy'], ['Acme.Demo:TestRole2']);
        $authenticatedUserRole = new Policy\Role('Neos.Flow:AuthenticatedUser');

        $mockPolicyService = $this->getAccessibleMock(Policy\PolicyService::class, ['getRole', 'initializeRolesFromPolicy']);
        $mockPolicyService->expects(self::atLeastOnce())->method('getRole')->will(self::returnCallBack(
            function ($roleIdentifier) use ($everybodyRole, $testRole1, $testRole2, $authenticatedUserRole) {
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
            }
        ));

        $everybodyRole->_set('policyService', $mockPolicyService);
        $testRole1->_set('policyService', $mockPolicyService);
        $testRole2->_set('policyService', $mockPolicyService);

        // Set parents
        $testRole1->setParentRoles([$testRole2]);

        $account = $this->getAccessibleMock(Account::class, ['dummy']);
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles([$testRole1]);

        $mockToken = $this->createMock(TokenInterface::class);
        $mockToken->expects(self::atLeastOnce())->method('isAuthenticated')->will(self::returnValue(true));
        $mockToken->expects(self::atLeastOnce())->method('getAccount')->will(self::returnValue($account));

        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'getAccount']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->expects(self::any())->method('getAccount')->will(self::returnValue($account));
        $securityContext->_set('activeTokens', [$mockToken]);
        $securityContext->_set('policyService', $mockPolicyService);

        self::assertTrue($securityContext->hasRole('Acme.Demo:TestRole2'));
    }

    /**
     * @test
     */
    public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken()
    {
        $mockAccount = $this->createMock(Account::class);

        $token1 = $this->createMock(TokenInterface::class, [], [], 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects(self::any())->method('isAuthenticated')->will(self::returnValue(false));
        $token1->expects(self::never())->method('getAccount');

        $token2 = $this->createMock(TokenInterface::class, [], [], 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));
        $token2->expects(self::once())->method('getAccount')->will(self::returnValue($mockAccount));

        $token3 = $this->createMock(TokenInterface::class, [], [], 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));
        $token3->expects(self::never())->method('getAccount');

        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('initialized', true);
        $securityContext->expects(self::once())->method('getAuthenticationTokens')->will(self::returnValue([$token1, $token2, $token3]));

        self::assertEquals($mockAccount, $securityContext->getAccount());
    }

    /**
     * @test
     */
    public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName()
    {
        $mockAccount1 = $this->createMock(Account::class);
        $mockAccount2 = $this->createMock(Account::class);

        $token1 = $this->createMock(TokenInterface::class, [], [], 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects(self::any())->method('isAuthenticated')->will(self::returnValue(false));
        $token1->expects(self::never())->method('getAccount');

        $token2 = $this->createMock(TokenInterface::class, [], [], 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));
        $token2->expects(self::any())->method('getAccount')->will(self::returnValue($mockAccount1));

        $token3 = $this->createMock(TokenInterface::class, [], [], 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));
        $token3->expects(self::any())->method('getAccount')->will(self::returnValue($mockAccount2));

        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('activeTokens', ['SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3]);
        $securityContext->_set('initialized', true);

        self::assertSame($mockAccount2, $securityContext->getAccountByAuthenticationProviderName('MatchingProvider'));
    }

    /**
     * @test
     */
    public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('activeTokens', []);
        $securityContext->_set('initialized', true);

        self::assertSame(null, $securityContext->getAccountByAuthenticationProviderName('UnknownProvider'));
    }

    /**
     * @test
     */
    public function getCsrfProtectionTokenReturnsANewTokenIfNoneIsPresentInTheContext()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['getAuthenticationTokens']);
        $this->inject($securityContext, 'objectManager', $this->mockObjectManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('csrfTokens', []);
        $securityContext->_set('initialized', true);

        self::assertNotEmpty($securityContext->getCsrfProtectionToken());
    }

    /**
     * @test
     */
    public function getCsrfProtectionTokenReturnsANewTokenIfTheCsrfStrategyIsOnePerUri()
    {
        $existingTokens = ['token1' => true, 'token2' => true];

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->mockActionRequest);
        $this->securityContext->_set('csrfTokens', $existingTokens);
        $this->securityContext->_set('csrfStrategy', Context::CSRF_ONE_PER_URI);

        self::assertFalse(array_key_exists($this->securityContext->getCsrfProtectionToken(), $existingTokens));
    }

    /**
     * @test
     */
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContext()
    {
        $existingTokens = ['csrfToken12345' => true];
        $this->mockSessionDataContainer->expects(self::any())->method('getCsrfProtectionTokens')->willReturn($existingTokens);

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->mockActionRequest);
        $this->securityContext->_set('objectManager', $this->mockObjectManager);
        $this->securityContext->_set('csrfProtectionTokens', $existingTokens);

        self::assertTrue($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        self::assertFalse($this->securityContext->isCsrfProtectionTokenValid('csrfToken'));
    }

    /**
     * @test
     */
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContextAndUnsetsItIfTheCsrfStrategyIsOnePerUri()
    {
        $existingTokens = ['csrfToken12345' => true];

        $sessionDataContainer = new SessionDataContainer();
        $sessionDataContainer->setCsrfProtectionTokens($existingTokens);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::any())->method('get')->with(SessionDataContainer::class)->willReturn($sessionDataContainer);

        /** @var Context $securityContext */
        $this->securityContext->setRequest($this->mockActionRequest);
        $this->securityContext->_set('objectManager', $mockObjectManager);
        $this->securityContext->_set('initialized', true);
        $this->securityContext->_set('csrfProtectionStrategy', Context::CSRF_ONE_PER_URI);

        self::assertTrue($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        self::assertFalse($this->securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
    }

    /**
     * @test
     */
    public function authorizationChecksAreEnabledByDefault()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    /**
     * @test
     */
    public function withoutAuthorizationChecksDisabledAuthorizationChecks()
    {
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
    }

    /**
     * @test
     */
    public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocation()
    {
        /** @var Context $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize']);
        $securityContext->withoutAuthorizationChecks(function () {
        });
        self::assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function getContextHashReturnsStaticStringIfAuthorizationChecksAreDisabled()
    {
        $self = $this;
        $this->securityContext->withoutAuthorizationChecks(function () use ($self) {
            $self->assertSame(Context::CONTEXT_HASH_UNINITIALIZED, $self->securityContext->getContextHash());
        });
    }

    /**
     * @test
     */
    public function getContextHashInitializesSecurityContext()
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'canBeInitialized', 'getRoles']);
        $securityContext->expects(self::atLeastOnce())->method('canBeInitialized')->willReturn(true);
        $securityContext->expects(self::once())->method('initialize');
        $securityContext->expects(self::any())->method('getRoles')->willReturn([]);

        $securityContext->getContextHash();
    }

    /**
     * @test
     */
    public function getContextHashReturnsAHashOverAllAuthenticatedRoles()
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['isInitialized', 'getRoles']);
        $securityContext->expects(self::any())->method('isInitialized')->will(self::returnValue(true));

        $mockRole1 = $this->getMockBuilder(Policy\Role::class)->disableOriginalConstructor()->getMock();
        $mockRole2 = $this->getMockBuilder(Policy\Role::class)->disableOriginalConstructor()->getMock();
        $mockRoles = ['Acme.Role1' => $mockRole1, 'Acme.Role2' => $mockRole2];
        $securityContext->expects(self::atLeastOnce())->method('getRoles')->will(self::returnValue($mockRoles));

        $expectedHash = md5(implode('|', array_keys($mockRoles)));
        self::assertSame($expectedHash, $securityContext->getContextHash());
    }

    /**
     * @test
     */
    public function getContextHashReturnsStaticStringIfSecurityContextCantBeInitialized()
    {
        /** @var Context|\PHPUnit\Framework\MockObject\MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(Context::class, ['initialize', 'canBeInitialized']);
        $securityContext->expects(self::atLeastOnce())->method('canBeInitialized')->will(self::returnValue(false));
        $securityContext->expects(self::never())->method('initialize');
        self::assertSame(Context::CONTEXT_HASH_UNINITIALIZED, $securityContext->getContextHash());
    }

    /**
     * @test
     */
    public function getSessionTagForAccountCreatesUniqueTagsPerAccount()
    {
        $account1 = $this->createMock(Account::class);
        $account1->expects(self::any())->method('getAccountIdentifier')->willReturn('Account1');
        $account2 = $this->createMock(Account::class);
        $account2->expects(self::any())->method('getAccountIdentifier')->willReturn('Account2');

        self::assertNotSame($this->securityContext->getSessionTagForAccount($account1), $this->securityContext->getSessionTagForAccount($account2));
    }

    /**
     * @test
     */
    public function destroySessionsForAccountWillDestroySessionsByAccountTag()
    {
        $account = $this->createMock(Account::class);
        $account->expects(self::any())->method('getAccountIdentifier')->willReturn('Account');
        $accountTag = $this->securityContext->getSessionTagForAccount($account);

        $mockSessionManager = $this->createMock(SessionManagerInterface::class);
        $mockSessionManager->expects(self::once())->method('destroySessionsByTag')->with($accountTag);
        $this->securityContext->_set('sessionManager', $mockSessionManager);

        $this->securityContext->destroySessionsForAccount($account);
    }
}
