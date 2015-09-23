<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

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

/**
 * Testcase for the CsrfProtection request pattern
 *
 * Hint: don't try to refactor into using  a real object manager, action request
 * or the like ... too many dependencies to work with the real objects.
 *
 */
class CsrfProtectionTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsTaggedWithSkipCsrfProtection()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(true));

        $mockPrivilege = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array($mockPrivilege)));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsNotMentionedInThePolicy()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array()));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButNoCsrfTokenHasBeenSent()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(array()));
        $mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($mockActionRequest));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array($mockPrivilege)));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertTrue($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsTrueIfTheTargetActionIsMentionedInThePolicyButTheCsrfTokenIsInvalid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(array('__csrfToken' => 'invalidCsrfToken')));
        $mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($mockActionRequest));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array($mockPrivilege)));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('invalidCsrfToken')->will($this->returnValue(false));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertTrue($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfTheTargetActionIsMentionedInThePolicyAndTheCsrfTokenIsValid()
    {
        $controllerObjectName = 'SomeControllerObjectName';
        $controllerActionName = 'list';

        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(array('__csrfToken' => 'validToken')));
        $mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($mockActionRequest));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array($mockPrivilege)));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('validToken')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
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

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->atLeastOnce())->method('getControllerObjectName')->will($this->returnValue($controllerObjectName));
        $mockActionRequest->expects($this->any())->method('getControllerActionName')->will($this->returnValue($controllerActionName));
        $mockActionRequest->expects($this->any())->method('getInternalArguments')->will($this->returnValue(array()));
        $mockActionRequest->expects($this->any())->method('getMainRequest')->will($this->returnValue($mockActionRequest));
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('getClassNameByObjectName')->with($controllerObjectName)->will($this->returnValue($controllerObjectName));

        $mockReflectionService = $this->getMock(\TYPO3\Flow\Reflection\ReflectionService::class);
        $mockReflectionService->expects($this->once())->method('isMethodTaggedWith')->with($controllerObjectName, $controllerActionName . 'Action', 'skipcsrfprotection')->will($this->returnValue(false));

        $mockPrivilege = $this->getMock(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);
        $mockPrivilege->expects($this->once())->method('matchesMethod')->with($controllerObjectName, $controllerActionName . 'Action')->will($this->returnValue(true));

        $mockPolicyService = $this->getMock(\TYPO3\Flow\Security\Policy\PolicyService::class);
        $mockPolicyService->expects($this->once())->method('getAllPrivilegesByType')->will($this->returnValue(array($mockPrivilege)));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->any())->method('isCsrfProtectionTokenValid')->with('validToken')->will($this->returnValue(true));
        $mockSecurityContext->expects($this->any())->method('hasCsrfProtectionTokens')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('objectManager', $mockObjectManager);
        $mockCsrfProtectionPattern->_set('reflectionService', $mockReflectionService);
        $mockCsrfProtectionPattern->_set('policyService', $mockPolicyService);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfNobodyIsAuthenticated()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(false));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfRequestMethodIsSafe()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'GET');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfRequestIsNoActionRequest()
    {
        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockRequest = $this->getMock(\TYPO3\Flow\Mvc\RequestInterface::class, array(), array(), '', false);

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockRequest));
    }

    /**
     * @test
     */
    public function matchRequestReturnsFalseIfAuthorizationChecksAreDisabled()
    {
        $httpRequest = Request::create(new Uri('http://localhost'), 'POST');

        $mockSystemLogger = $this->getMock(\TYPO3\Flow\Log\SystemLoggerInterface::class);

        $mockActionRequest = $this->getMock(\TYPO3\Flow\Mvc\ActionRequest::class, array(), array(), '', false);
        $mockActionRequest->expects($this->any())->method('getHttpRequest')->will($this->returnValue($httpRequest));

        $mockAuthenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class, array(), array(), '', false);
        $mockAuthenticationManager->expects($this->any())->method('isAuthenticated')->will($this->returnValue(true));

        $mockSecurityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));

        $mockCsrfProtectionPattern = $this->getAccessibleMock(\TYPO3\Flow\Security\RequestPattern\CsrfProtection::class, array('dummy'));
        $mockCsrfProtectionPattern->_set('authenticationManager', $mockAuthenticationManager);
        $mockCsrfProtectionPattern->_set('systemLogger', $mockSystemLogger);
        $mockCsrfProtectionPattern->_set('securityContext', $mockSecurityContext);

        $this->assertFalse($mockCsrfProtectionPattern->matchRequest($mockActionRequest));
    }
}
