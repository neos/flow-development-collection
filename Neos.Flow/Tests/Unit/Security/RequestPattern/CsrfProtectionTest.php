<?php
namespace Neos\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the CsrfProtection request pattern
 *
 * Hint: don't try to refactor into using  a real object manager, action request
 * or the like ... too many dependencies to work with the real objects.
 */
class CsrfProtectionTest extends UnitTestCase
{
    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    /**
     * @var  LoggerInterface
     */
    protected $mockSystemLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsTaggedWithSkipCsrfProtection()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::once())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will(self::returnValue(true));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects(self::once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will(self::returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsNotMentionedInThePolicy()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::once())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButNoCsrfTokenHasBeenSent()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getInternalArguments')->will(self::returnValue([]));
        $this->mockActionRequest->expects(self::any())->method('getMainRequest')->will(self::returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will(self::returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects(self::once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will(self::returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButTheCsrfTokenIsInvalid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getInternalArguments')->will(self::returnValue(['__csrfToken' => 'invalidCsrfToken']));
        $this->mockActionRequest->expects(self::any())->method('getMainRequest')->will(self::returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will(self::returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects(self::once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will(self::returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('isCsrfProtectionTokenValid')->with('invalidCsrfToken')->will(self::returnValue(false));
        $mockSecurityContext->expects(self::any())->method('hasCsrfProtectionTokens')->will(self::returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsMentionedInThePolicyAndTheCsrfTokenIsValid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getInternalArguments')->will(self::returnValue(['__csrfToken' => 'validToken']));
        $this->mockActionRequest->expects(self::any())->method('getMainRequest')->will(self::returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will(self::returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects(self::once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will(self::returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('isCsrfProtectionTokenValid')->with('validToken')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::any())->method('hasCsrfProtectionTokens')->will(self::returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheCsrfTokenIsPassedThroughAnHttpHeader()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));
        $httpRequest = $httpRequest->withHeader('X-Flow-Csrftoken', 'validToken');

        $this->mockActionRequest->expects(self::atLeastOnce())->method('getControllerObjectName')->will(self::returnValue($controllerObjectName));
        $this->mockActionRequest->expects(self::any())->method('getControllerActionName')->will(self::returnValue($controllerActionName));
        $this->mockActionRequest->expects(self::any())->method('getInternalArguments')->will(self::returnValue([]));
        $this->mockActionRequest->expects(self::any())->method('getMainRequest')->will(self::returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects(self::once())->method('getClassNameByObjectName')->with($controllerObjectName)->will(self::returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will(self::returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects(self::once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will(self::returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects(self::once())->method('getAllPrivilegesByType')->will(self::returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects(self::any())->method('isCsrfProtectionTokenValid')->with('validToken')->will(self::returnValue(true));
        $mockSecurityContext->expects(self::any())->method('hasCsrfProtectionTokens')->will(self::returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfNobodyIsAuthenticated()
    {
        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(false));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfRequestMethodIsSafe()
    {
        $httpRequest = new ServerRequest('GET', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfAuthorizationChecksAreDisabled()
    {
        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects(self::any())->method('getHttpRequest')->will(self::returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects(self::any())->method('isAuthenticated')->will(self::returnValue(true));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects(self::atLeastOnce())->method('areAuthorizationChecksDisabled')->will(self::returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('logger', $this->mockSystemLogger);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }
}
