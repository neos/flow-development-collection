<?php

declare(strict_types=1);

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
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\RequestPattern\CsrfProtection;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the CsrfProtection request pattern
 *
 * Hint: don't try to refactor into using  a real object manager, action request
 * or the like ... too many dependencies to work with the real objects.
 */
final class CsrfProtectionTest extends UnitTestCase
{
    /**
     * @var ActionRequest
     */
    protected $mockActionRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockActionRequest = $this->createMock(ActionRequest::class);
    }

    #[Test]
    public function matchRequestReturnsFalseIfTheTargetActionIsAnnotatedWithSkipCsrfProtection()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects(self::once())->method('isMethodAnnotatedWith')->with($controllerObjectName, $controllerActionName . 'Action', Flow\SkipCsrfProtection::class)->willReturn(true);

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->willReturn((true));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([$mockPrivilege]));

        $mockSecurityContext = $this->createStub(Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfTheTargetActionIsNotMentionedInThePolicy()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([]));

        $mockSecurityContext = $this->createStub(Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButNoCsrfTokenHasBeenSent()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getInternalArguments')->willReturn(([]));
        $this->mockActionRequest->method('getMainRequest')->willReturn(($this->mockActionRequest));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->willReturn((false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->willReturn((true));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([$mockPrivilege]));

        $mockSecurityContext = $this->createStub(Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButTheCsrfTokenIsInvalid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getInternalArguments')->willReturn((['__csrfToken' => 'invalidCsrfToken']));
        $this->mockActionRequest->method('getMainRequest')->willReturn(($this->mockActionRequest));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->willReturn((false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->willReturn((true));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('isCsrfProtectionTokenValid')->with('invalidCsrfToken')->willReturn((false));
        $mockSecurityContext->method('hasCsrfProtectionTokens')->willReturn((true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfTheTargetActionIsMentionedInThePolicyAndTheCsrfTokenIsValid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getInternalArguments')->willReturn((['__csrfToken' => 'validToken']));
        $this->mockActionRequest->method('getMainRequest')->willReturn(($this->mockActionRequest));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->willReturn((false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->willReturn((true));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('isCsrfProtectionTokenValid')->with('validToken')->willReturn((true));
        $mockSecurityContext->method('hasCsrfProtectionTokens')->willReturn((true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfTheCsrfTokenIsPassedThroughAnHttpHeader()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));
        $httpRequest = $httpRequest->withHeader('X-Flow-Csrftoken', 'validToken');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->willReturn(($controllerObjectName));
        $this->mockActionRequest->method('getControllerActionName')->willReturn(($controllerActionName));
        $this->mockActionRequest->method('getInternalArguments')->willReturn(([]));
        $this->mockActionRequest->method('getMainRequest')->willReturn(($this->mockActionRequest));
        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->willReturn(($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->willReturn((false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->willReturn((true));

        $mockPolicyService = $this->createMock(PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->willReturn(([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->method('isCsrfProtectionTokenValid')->with('validToken')->willReturn((true));
        $mockSecurityContext->method('hasCsrfProtectionTokens')->willReturn((true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfNobodyIsAuthenticated()
    {
        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((false));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfRequestMethodIsSafe()
    {
        $httpRequest = new ServerRequest('GET', new Uri('http://localhost'));

        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    #[Test]
    public function matchRequestReturnsFalseIfAuthorizationChecksAreDisabled()
    {
        $httpRequest = new ServerRequest('POST', new Uri('http://localhost'));

        $this->mockActionRequest->method('getHttpRequest')->willReturn(($httpRequest));

        $mockAuthenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $mockAuthenticationManager->method('isAuthenticated')->willReturn((true));

        $mockSecurityContext = $this->createMock(Context::class);
        $mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->willReturn((true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(CsrfProtection::class, []);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('logger', $this->createStub(LoggerInterface::class));
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

        self::assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }
}
