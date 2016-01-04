<?php
namespace TYPO3\Flow\Tests\Unit\Security;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface;
use TYPO3\Flow\Security\Account;
use TYPO3\Flow\Security\Authentication\TokenInterface;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Security\Policy\Role;
use TYPO3\Flow\Tests\UnitTestCase;

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
     * @var AuthenticationManagerInterface
     */
    protected $mockAuthenticationManager;

    /**
     * Sets up this test case
     */
    public function setUp()
    {
        $this->securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('separateActiveAndInactiveTokens'));

        $this->mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $this->mockAuthenticationManager->expects($this->any())->method('getTokens')->will($this->returnValue(array()));
        $this->securityContext->injectAuthenticationManager($this->mockAuthenticationManager);

        $this->mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->securityContext->setRequest($this->mockActionRequest);
    }

    /**
     * @test
     */
    public function currentRequestIsSetInTheSecurityContext()
    {
        $this->securityContext->initialize();
        $this->assertSame($this->mockActionRequest, $this->securityContext->_get('request'));
    }

    /**
     * @test
     */
    public function securityContextIsSetToInitialized()
    {
        $this->assertFalse($this->securityContext->isInitialized());
        $this->securityContext->initialize();
        $this->assertTrue($this->securityContext->isInitialized());
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
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('canBeInitialized'));
        $securityContext->expects($this->never())->method('canBeInitialized');
        $securityContext->_set('initialized', true);

        $securityContext->initialize();
    }

    /**
     * @test
     */
    public function initializeSeparatesActiveAndInactiveTokens()
    {
        $this->securityContext->expects($this->once())->method('separateActiveAndInactiveTokens');
        $this->securityContext->initialize();
    }

    /**
     * @test
     */
    public function initializeUpdatesAndSeparatesActiveAndInactiveTokensCorrectly()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));

        $settings = array();
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $securityContext->injectSettings($settings);

        $matchingRequestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $matchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(true));

        $notMatchingRequestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(false));

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token1->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(true));
        $token1->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($matchingRequestPattern)));
        $token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
        $token1->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token2->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(false));
        $token2->expects($this->never())->method('getRequestPatterns');
        $token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
        $token2->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token3->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(true));
        $token3->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
        $token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));
        $token3->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token4 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token4->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(true));
        $token4->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array()));
        $token4->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token4Provider'));
        $token4->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token5 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token5->expects($this->once())->method('hasRequestPatterns')->will($this->returnValue(true));
        $token5->expects($this->once())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern, $matchingRequestPattern)));
        $token5->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token5Provider'));
        $token5->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($token1, $token2, $token3, $token4, $token5)));

        $mockSession = $this->getMock(\TYPO3\Flow\Session\SessionInterface::class);
        $mockSessionManager = $this->getMock(\TYPO3\Flow\Session\SessionManagerInterface::class);
        $mockSessionManager->expects($this->any())->method('getCurrentSession')->will($this->returnValue($mockSession));
        $mockSecurityLogger = $this->getMock(\TYPO3\Flow\Log\SecurityLoggerInterface::class);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->injectSettings($settings);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->injectAuthenticationManager($mockAuthenticationManager);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);
        $securityContext->_set('tokens', array($token1, $token3, $token4));

        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('tokens', array($token1, $token3, $token4));
        $securityContext->initialize();

        $this->assertEquals(array($token1, $token2, $token4), array_values($securityContext->_get('activeTokens')));
        $this->assertEquals(array($token3, $token5), array_values($securityContext->_get('inactiveTokens')));
    }

    /**
     * @test
     */
    public function securityContextCallsTheAuthenticationManagerToSetItsTokens()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array()));
        $securityContext->injectAuthenticationManager($mockAuthenticationManager);
        $securityContext->setRequest($this->mockActionRequest);

        $securityContext->initialize();
    }

    /**
     * @test
     */
    public function tokenFromAnAuthenticationManagerIsReplacedIfThereIsOneOfTheSameTypeInTheSession()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
        $token1Clone = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token1Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
        $token1Clone->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
        $token2Clone = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token2Clone->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
        $token2Clone->expects($this->any())->method('getAuthenticationStatus')->will($this->returnValue(TokenInterface::AUTHENTICATION_NEEDED));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $token3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

        $tokensFromTheManager = array($token1, $token2, $token3);
        $tokensFromTheSession = array($token1Clone, $token2Clone);

        $mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue($tokensFromTheManager));

        $mockSession = $this->getMock(\TYPO3\Flow\Session\SessionInterface::class);
        $mockSessionManager = $this->getMock(\TYPO3\Flow\Session\SessionManagerInterface::class);
        $mockSessionManager->expects($this->any())->method('getCurrentSession')->will($this->returnValue($mockSession));
        $mockSecurityLogger = $this->getMock(\TYPO3\Flow\Log\SecurityLoggerInterface::class);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->injectAuthenticationManager($mockAuthenticationManager);
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('tokens', $tokensFromTheSession);
        $securityContext->_set('sessionManager', $mockSessionManager);
        $securityContext->_set('securityLogger', $mockSecurityLogger);

        $securityContext->_call('initialize');

        $expectedMergedTokens = array($token1Clone, $token2Clone, $token3);
        $this->assertEquals(array_values($securityContext->_get('tokens')), $expectedMergedTokens);
    }

    /**
     * @test
     */
    public function initializeCallsUpdateCredentialsOnAllActiveTokens()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $notMatchingRequestPattern = $this->getMock(\TYPO3\Flow\Security\RequestPatternInterface::class);
        $notMatchingRequestPattern->expects($this->any())->method('matchRequest')->will($this->returnValue(false));

        $mockToken1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken1->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token1Provider'));
        $mockToken2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken2->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token2Provider'));
        $mockToken2->expects($this->atLeastOnce())->method('hasRequestPatterns')->will($this->returnValue(true));
        $mockToken2->expects($this->atLeastOnce())->method('getRequestPatterns')->will($this->returnValue(array($notMatchingRequestPattern)));
        $mockToken3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken3->expects($this->any())->method('getAuthenticationProviderName')->will($this->returnValue('token3Provider'));

        $mockToken1->expects($this->once())->method('updateCredentials');
        $mockToken2->expects($this->never())->method('updateCredentials');
        $mockToken3->expects($this->once())->method('updateCredentials');

        $mockAuthenticationManager->expects($this->once())->method('getTokens')->will($this->returnValue(array($mockToken1, $mockToken2, $mockToken3)));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $securityContext->_call('initialize');
    }

    /**
     * @test
     */
    public function injectAuthenticationManagerSetsAReferenceToTheSecurityContextInTheAuthenticationManager()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->once())->method('setSecurityContext')->with($securityContext);

        $securityContext->injectAuthenticationManager($mockAuthenticationManager);
    }

    /**
     * Data provider for authentication strategy settings
     *
     * @return array
     */
    public function authenticationStrategies()
    {
        $data = array();
        $settings = array();
        $settings['security']['authentication']['authenticationStrategy'] = 'allTokens';
        $data[] = array($settings, Context::AUTHENTICATE_ALL_TOKENS);
        $settings['security']['authentication']['authenticationStrategy'] = 'oneToken';
        $data[] = array($settings, Context::AUTHENTICATE_ONE_TOKEN);
        $settings['security']['authentication']['authenticationStrategy'] = 'atLeastOneToken';
        $data[] = array($settings, Context::AUTHENTICATE_AT_LEAST_ONE_TOKEN);
        $settings['security']['authentication']['authenticationStrategy'] = 'anyToken';
        $data[] = array($settings, Context::AUTHENTICATE_ANY_TOKEN);
        return $data;
    }

    /**
     * @dataProvider authenticationStrategies()
     * @test
     */
    public function authenticationStrategyIsSetCorrectlyFromConfiguration($settings, $expectedAuthenticationStrategy)
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->injectSettings($settings);

        $this->assertEquals($expectedAuthenticationStrategy, $securityContext->getAuthenticationStrategy());
    }

    /**
     * @expectedException \TYPO3\Flow\Exception
     * @test
     */
    public function invalidAuthenticationStrategyFromConfigurationThrowsException()
    {
        $settings = array();
        $settings['security']['authentication']['authenticationStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->injectSettings($settings);
    }

    /**
     * Data provider for CSRF protection strategy settings
     *
     * @return array
     */
    public function csrfProtectionStrategies()
    {
        $data = array();
        $settings = array();
        $settings['security']['csrf']['csrfStrategy'] = 'onePerRequest';
        $data[] = array($settings, Context::CSRF_ONE_PER_REQUEST);
        $settings['security']['csrf']['csrfStrategy'] = 'onePerSession';
        $data[] = array($settings, Context::CSRF_ONE_PER_SESSION);
        $settings['security']['csrf']['csrfStrategy'] = 'onePerUri';
        $data[] = array($settings, Context::CSRF_ONE_PER_URI);
        return $data;
    }

    /**
     * @dataProvider csrfProtectionStrategies()
     * @test
     */
    public function csrfProtectionStrategyIsSetCorrectlyFromConfiguration($settings, $expectedCsrfProtectionStrategy)
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->injectSettings($settings);

        $this->assertEquals($expectedCsrfProtectionStrategy, $securityContext->_get('csrfProtectionStrategy'));
    }

    /**
     * @expectedException \TYPO3\Flow\Exception
     * @test
     */
    public function invalidCsrfProtectionStrategyFromConfigurationThrowsException()
    {
        $settings = array();
        $settings['security']['csrf']['csrfStrategy'] = 'fizzleGoesHere';

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('dummy'));
        $securityContext->injectSettings($settings);
    }

    /**
     * @test
     */
    public function getRolesReturnsTheCorrectRoles()
    {
        $everybodyRole = new Role('TYPO3.Flow:Everybody');
        $authenticatedUserRole = new Role('TYPO3.Flow:AuthenticatedUser');
        $testRole = new Role('Acme.Demo:TestRole');

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));

        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole', 'initializeRolesFromPolicy'));
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->will($this->returnCallback(
            function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole) {
                switch ($roleIdentifier) {
                    case 'TYPO3.Flow:Everybody':
                        return $everybodyRole;
                    case 'TYPO3.Flow:AuthenticatedUser':
                        return $authenticatedUserRole;
                }
            }
        ));

        $account = $this->getAccessibleMock(\TYPO3\Flow\Security\Account::class, array('dummy'));
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles(array($testRole));

        $mockToken = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
        $securityContext->_set('activeTokens', array($mockToken));
        $securityContext->_set('policyService', $mockPolicyService);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $expectedResult = array('TYPO3.Flow:Everybody' => $everybodyRole, 'TYPO3.Flow:AuthenticatedUser' => $authenticatedUserRole, 'Acme.Demo:TestRole' => $testRole);
        $this->assertEquals($expectedResult, $securityContext->getRoles());
    }

    /**
     * @test
     */
    public function getRolesTakesInheritanceOfRolesIntoAccount()
    {
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $everybodyRole */
        $everybodyRole = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('TYPO3.Flow:Everybody'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $authenticatedUserRole */
        $authenticatedUserRole = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('TYPO3.Flow:AuthenticatedUser'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole1 */
        $testRole1 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole1'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole2 */
        $testRole2 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole2'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole3 */
        $testRole3 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole3'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole4 */
        $testRole4 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole4'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole5 */
        $testRole5 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole5'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole6 */
        $testRole6 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole6'));
        /** @var Role|\PHPUnit_Framework_MockObject_MockObject $testRole7 */
        $testRole7 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole7'));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));

        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole'));
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->will($this->returnCallback(
            function ($roleIdentifier) use ($everybodyRole, $authenticatedUserRole, $testRole1, $testRole2, $testRole3, $testRole4, $testRole5, $testRole6, $testRole7) {
                switch ($roleIdentifier) {
                    case 'TYPO3.Flow:Everybody':
                        return $everybodyRole;
                    case 'TYPO3.Flow:AuthenticatedUser':
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
        $testRole1->setParentRoles(array($testRole2, $testRole3));
        $testRole2->setParentRoles(array($testRole4, $testRole5));
        $testRole3->setParentRoles(array($testRole6, $testRole7));

        /** @var Account|\PHPUnit_Framework_MockObject_MockObject $account */
        $account = $this->getAccessibleMock(\TYPO3\Flow\Security\Account::class, array('dummy'));
        $this->inject($account, 'policyService', $mockPolicyService);
        $account->setRoles(array($testRole1));

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $mockToken */
        $mockToken = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
        $this->inject($securityContext, 'activeTokens', array($mockToken));
        $this->inject($securityContext, 'policyService', $mockPolicyService);
        $this->inject($securityContext, 'authenticationManager', $mockAuthenticationManager);

        $expectedResult = array(
            'Acme.Demo:TestRole1' => $testRole1,
            'Acme.Demo:TestRole2' => $testRole2,
            'Acme.Demo:TestRole3' => $testRole3,
            'Acme.Demo:TestRole4' => $testRole4,
            'Acme.Demo:TestRole5' => $testRole5,
            'Acme.Demo:TestRole6' => $testRole6,
            'Acme.Demo:TestRole7' => $testRole7,
            'TYPO3.Flow:Everybody' => $everybodyRole,
            'TYPO3.Flow:AuthenticatedUser' => $authenticatedUserRole
        );
        $result = $securityContext->getRoles();

        ksort($expectedResult);
        ksort($result);

        $this->assertSame(array_keys($expectedResult), array_keys($result));
        // $this->assertSame($expectedResult, $result);
    }

    /**
     * @test
     */
    public function getRolesReturnsTheEverybodyRoleEvenIfNoTokenIsAuthenticated()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));

        $everybodyRole = new Role('TYPO3.Flow:Everybody');
        $anonymousRole = new Role('TYPO3.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole'));
        $mockPolicyService->expects($this->any())->method('getRole')->will($this->returnValueMap(array(array('TYPO3.Flow:Anonymous', $anonymousRole), array('TYPO3.Flow:Everybody', $everybodyRole))));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->_set('policyService', $mockPolicyService);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $result = $securityContext->getRoles();
        $this->assertInstanceOf(\TYPO3\Flow\Security\Policy\Role::class, $result['TYPO3.Flow:Everybody']);
        $this->assertEquals('TYPO3.Flow:Everybody', $result['TYPO3.Flow:Everybody']->getIdentifier());
    }

    /**
     * @test
     */
    public function getRolesReturnsTheAnonymousRoleIfNoTokenIsAuthenticated()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(false));

        $everybodyRole = new Role('TYPO3.Flow:Everybody');
        $anonymousRole = new Role('TYPO3.Flow:Anonymous');
        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole'));
        $mockPolicyService->expects($this->any())->method('getRole')->will($this->returnValueMap(array(array('TYPO3.Flow:Anonymous', $anonymousRole), array('TYPO3.Flow:Everybody', $everybodyRole))));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->_set('policyService', $mockPolicyService);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $result = $securityContext->getRoles();
        $this->assertInstanceOf(\TYPO3\Flow\Security\Policy\Role::class, $result['TYPO3.Flow:Anonymous']);
        $this->assertEquals('TYPO3.Flow:Anonymous', (string)($result['TYPO3.Flow:Anonymous']));
    }

    /**
     * @test
     */
    public function getRolesReturnsTheAuthenticatedUserRoleIfATokenIsAuthenticated()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));

        $everybodyRole = new Role('TYPO3.Flow:Everybody');
        $authenticatedUserRole = new Role('TYPO3.Flow:AuthenticatedUser');
        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole'));
        $mockPolicyService->expects($this->any())->method('getRole')->will($this->returnValueMap(array(array('TYPO3.Flow:AuthenticatedUser', $authenticatedUserRole), array('Everybody', $everybodyRole))));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->_set('policyService', $mockPolicyService);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $result = $securityContext->getRoles();
        $this->assertInstanceOf(\TYPO3\Flow\Security\Policy\Role::class, $result['TYPO3.Flow:AuthenticatedUser']);
        $this->assertEquals('TYPO3.Flow:AuthenticatedUser', (string)($result['TYPO3.Flow:AuthenticatedUser']));
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForEverybodyRole()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));

        $this->assertTrue($securityContext->hasRole('TYPO3.Flow:Everybody'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsTrueForAnonymousRoleIfNotAuthenticated()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $this->assertTrue($securityContext->hasRole('TYPO3.Flow:Anonymous'));
    }

    /**
     * @test
     */
    public function hasRoleReturnsFalseForAnonymousRoleIfAuthenticated()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $this->assertFalse($securityContext->hasRole('TYPO3.Flow:Anonymous'));
    }

    /**
     * @test
     */
    public function hasRoleWorks()
    {
        $everybodyRole = new Role('TYPO3.Flow:Everybody');
        $testRole = new Role('Acme.Demo:TestRole');

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));

        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole', 'initializeRolesFromPolicy'));
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->will($this->returnCallback(
            function ($roleIdentifier) use ($everybodyRole) {
                switch ($roleIdentifier) {
                    case 'TYPO3.Flow:Everybody':
                        return $everybodyRole;
                }
            }
        ));

        $account = $this->getAccessibleMock(\TYPO3\Flow\Security\Account::class, array('dummy'));
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles(array($testRole));

        $mockToken = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize', 'getAccount'));
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
    public function hasRoleWorksWithRecursiveRoles()
    {
        $everybodyRole = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('TYPO3.Flow:Everybody'));
        $testRole1 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole1'));
        $testRole2 = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\Role::class, array('dummy'), array('Acme.Demo:TestRole2'));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $mockAuthenticationManager->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));

        $mockPolicyService = $this->getAccessibleMock(\TYPO3\Flow\Security\Policy\PolicyService::class, array('getRole', 'initializeRolesFromPolicy'));
        $mockPolicyService->expects($this->atLeastOnce())->method('getRole')->will($this->returnCallback(
            function ($roleIdentifier) use ($everybodyRole, $testRole1, $testRole2) {
                switch ($roleIdentifier) {
                    case 'TYPO3.Flow:Everybody':
                        return $everybodyRole;
                    case 'Acme.Demo:TestRole1':
                        return $testRole1;
                    case 'Acme.Demo:TestRole2':
                        return $testRole2;
                }
            }
        ));

        $everybodyRole->_set('policyService', $mockPolicyService);
        $testRole1->_set('policyService', $mockPolicyService);
        $testRole2->_set('policyService', $mockPolicyService);

        // Set parents
        $testRole1->setParentRoles(array($testRole2));

        $account = $this->getAccessibleMock(\TYPO3\Flow\Security\Account::class, array('dummy'));
        $account->_set('policyService', $mockPolicyService);
        $account->setRoles(array($testRole1));

        $mockToken = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class);
        $mockToken->expects($this->atLeastOnce())->method('isAuthenticated')->will($this->returnValue(true));
        $mockToken->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($account));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize', 'getAccount'));
        $securityContext->expects($this->any())->method('getAccount')->will($this->returnValue($account));
        $securityContext->_set('activeTokens', array($mockToken));
        $securityContext->_set('policyService', $mockPolicyService);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);

        $this->assertTrue($securityContext->hasRole('Acme.Demo:TestRole2'));
    }

    /**
     * @test
     */
    public function getPartyAsksTheCorrectAuthenticationTokenAndReturnsItsParty()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $mockParty = $this->getMockForAbstractClass(\TYPO3\Party\Domain\Model\AbstractParty::class);

        $mockAccount = $this->getMock(\TYPO3\Flow\Security\Account::class);
        $mockAccount->expects($this->once())->method('getParty')->will($this->returnValue($mockParty));

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token2->expects($this->atLeastOnce())->method('getAccount')->will($this->returnValue($mockAccount));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token3->expects($this->never())->method('getAccount');

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

        $this->assertEquals($mockParty, $securityContext->getParty());
    }

    /**
     * @test
     */
    public function getAccountReturnsTheAccountAttachedToTheFirstAuthenticatedToken()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $mockAccount = $this->getMock(\TYPO3\Flow\Security\Account::class);

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token2->expects($this->once())->method('getAccount')->will($this->returnValue($mockAccount));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token3->expects($this->never())->method('getAccount');

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

        $this->assertEquals($mockAccount, $securityContext->getAccount());
    }

    /**
     * @test
     */
    public function getPartyByTypeReturnsTheFirstAuthenticatedPartyWithGivenType()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $matchingMockParty = $this->getMockForAbstractClass(\TYPO3\Party\Domain\Model\AbstractParty::class, array(), 'MatchingParty');
        $notMatchingMockParty = $this->getMockForAbstractClass(\TYPO3\Party\Domain\Model\AbstractParty::class, array(), 'NotMatchingParty');

        $mockAccount1 = $this->getMock(\TYPO3\Flow\Security\Account::class);
        $mockAccount1->expects($this->any())->method('getParty')->will($this->returnValue($notMatchingMockParty));
        $mockAccount2 = $this->getMock(\TYPO3\Flow\Security\Account::class);
        $mockAccount2->expects($this->any())->method('getParty')->will($this->returnValue($matchingMockParty));

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->expects($this->once())->method('getAuthenticationTokens')->will($this->returnValue(array($token1, $token2, $token3)));

        $this->assertSame($matchingMockParty, $securityContext->getPartyByType('MatchingParty'));
    }

    /**
     * @test
     */
    public function getAccountByAuthenticationProviderNameReturnsTheAuthenticatedAccountWithGivenProviderName()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $mockAccount1 = $this->getMock(\TYPO3\Flow\Security\Account::class);
        $mockAccount2 = $this->getMock(\TYPO3\Flow\Security\Account::class);

        $token1 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token1' . md5(uniqid(mt_rand(), true)));
        $token1->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));
        $token1->expects($this->never())->method('getAccount');

        $token2 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token2' . md5(uniqid(mt_rand(), true)));
        $token2->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token2->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount1));

        $token3 = $this->getMock(\TYPO3\Flow\Security\Authentication\TokenInterface::class, array(), array(), 'token3' . md5(uniqid(mt_rand(), true)));
        $token3->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));
        $token3->expects($this->any())->method('getAccount')->will($this->returnValue($mockAccount2));

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('activeTokens', array('SomeOhterProvider' => $token1, 'SecondProvider' => $token2, 'MatchingProvider' => $token3));

        $this->assertSame($mockAccount2, $securityContext->getAccountByAuthenticationProviderName('MatchingProvider'));
    }

    /**
     * @test
     */
    public function getAccountByAuthenticationProviderNameReturnsNullIfNoAccountFound()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('activeTokens', array());

        $this->assertSame(null, $securityContext->getAccountByAuthenticationProviderName('UnknownProvider'));
    }

    /**
     * @test
     */
    public function getCsrfProtectionTokenReturnsANewTokenIfNoneIsPresentInTheContext()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('csrfTokens', array());

        $this->assertNotEmpty($securityContext->getCsrfProtectionToken());
    }

    /**
     * @test
     */
    public function getCsrfProtectionTokenReturnsANewTokenIfTheCsrfStrategyIsOnePerUri()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $existingTokens = array('token1' => true, 'token2' => true);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('getAuthenticationTokens'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('csrfTokens', $existingTokens);
        $securityContext->_set('csrfStrategy', Context::CSRF_ONE_PER_URI);

        $this->assertFalse(array_key_exists($securityContext->getCsrfProtectionToken(), $existingTokens));
    }

    /**
     * @test
     */
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContext()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $existingTokens = array('csrfToken12345' => true);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('csrfProtectionTokens', $existingTokens);

        $this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        $this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken'));
    }

    /**
     * @test
     */
    public function isCsrfProtectionTokenValidChecksIfTheGivenTokenIsExistingInTheContextAndUnsetsItIfTheCsrfStrategyIsOnePerUri()
    {
        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);

        $existingTokens = array('csrfToken12345' => true);

        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->setRequest($this->mockActionRequest);
        $securityContext->_set('authenticationManager', $mockAuthenticationManager);
        $securityContext->_set('csrfProtectionTokens', $existingTokens);
        $securityContext->_set('csrfProtectionStrategy', Context::CSRF_ONE_PER_URI);

        $this->assertTrue($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
        $this->assertFalse($securityContext->isCsrfProtectionTokenValid('csrfToken12345'));
    }

    /**
     * @test
     */
    public function authorizationChecksAreEnabledByDefault()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $this->assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    /**
     * @test
     */
    public function withoutAuthorizationChecksDisabledAuthorizationChecks()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
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
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $securityContext->withoutAuthorizationChecks(function () {});
        $this->assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    /**
     * @test
     */
    public function withoutAuthorizationChecksReactivatesAuthorizationChecksAfterClosureInvocationIfClosureThrowsException()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        try {
            $securityContext->withoutAuthorizationChecks(function () {
                throw new \Exception('Test Exception');
            });
        } catch (\Exception $exception) {
        }
        $this->assertFalse($securityContext->areAuthorizationChecksDisabled());
    }

    /**
     * @test
     */
    public function withoutAuthorizationChecksReactivatesAuthorizationCheckCorrectlyWhenCalledNested()
    {
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize'));
        $self = $this;
        $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
            $securityContext->withoutAuthorizationChecks(function () use ($securityContext, $self) {
                $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
            });
            $self->assertTrue($securityContext->areAuthorizationChecksDisabled());
        });
        $this->assertFalse($securityContext->areAuthorizationChecksDisabled());
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
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize', 'canBeInitialized', 'getRoles'));
        $securityContext->expects($this->at(0))->method('canBeInitialized')->will($this->returnValue(true));
        $securityContext->expects($this->at(1))->method('initialize');
        $securityContext->expects($this->any())->method('getRoles')->will($this->returnValue(array()));

        $securityContext->getContextHash();
    }

    /**
     * @test
     */
    public function getContextHashReturnsAHashOverAllAuthenticatedRoles()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('isInitialized', 'getRoles'));
        $securityContext->expects($this->any())->method('isInitialized')->will($this->returnValue(true));

        $mockRole1 = $this->getMockBuilder(Role::class)->disableOriginalConstructor()->getMock();
        $mockRole2 = $this->getMockBuilder(Role::class)->disableOriginalConstructor()->getMock();
        $mockRoles = ['Acme.Role1' => $mockRole1, 'Acme.Role2' => $mockRole2];
        $securityContext->expects($this->atLeastOnce())->method('getRoles')->will($this->returnValue($mockRoles));

        $expectedHash = md5(implode('|', array_keys($mockRoles)));
        $this->assertSame($expectedHash, $securityContext->getContextHash());
    }

    /**
     * @test
     */
    public function getContextHashReturnsStaticStringIfSecurityContextCantBeInitialized()
    {
        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $securityContext */
        $securityContext = $this->getAccessibleMock(\TYPO3\Flow\Security\Context::class, array('initialize', 'canBeInitialized'));
        $securityContext->expects($this->atLeastOnce())->method('canBeInitialized')->will($this->returnValue(false));
        $securityContext->expects($this->never())->method('initialize');
        $this->assertSame(Context::CONTEXT_HASH_UNINITIALIZED, $securityContext->getContextHash());
    }
}
