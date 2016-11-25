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

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\RequestInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security;
use Neos\Flow\Tests\UnitTestCase;

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
     * @var  SystemLoggerInterface
     */
    protected $mockSystemLogger;

    public function setUp()
    {
        parent::setUp();

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $this->mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsTaggedWithSkipCsrfProtection()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(true));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsNotMentionedInThePolicy()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButNoCsrfTokenHasBeenSent()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue([]));
        $this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButTheCsrfTokenIsInvalid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(['__csrfToken' => 'invalidCsrfToken']));
        $this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('invalidCsrfToken')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertTrue($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsMentionedInThePolicyAndTheCsrfTokenIsValid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(['__csrfToken' => 'validToken']));
        $this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('validToken')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheCsrfTokenIsPassedThroughAnHttpHeader()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');
        $httpRequest->setHeader('X-Flow-Csrftoken', 'validToken');

        $this->mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $this->mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $this->mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue([]));
        $this->mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($this->mockActionRequest));
        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->createMock(ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->createMock(MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->createMock(Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue([$mockPrivilege]));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('validToken')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfNobodyIsAuthenticated()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfRequestMethodIsSafe()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'GET');

        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfRequestIsNoActionRequest()
    {
        $mockRequest = $this->createMock(RequestInterface::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfAuthorizationChecksAreDisabled()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $this->mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMockBuilder(AuthenticationManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockSecurityContext = $this->createMock(Security\Context::class);
        $mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(Security\RequestPattern\CsrfProtection::class, ['dummy']);
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('systemLogger', $this->mockSystemLogger);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($this->mockActionRequest));
    }
}
