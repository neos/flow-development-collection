<?php
namespace TYPO3\Flow\Tests\Unit\Security\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the request dispatching aspect
 */
class RequestDispatchingAspectTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsCallsTheFirewallWithTheGivenRequest()
    {
        $mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->getMock();
        $getMethodArgumentCallback = function () use (&$mockActionRequest, &$mockResponse) {
            $args = func_get_args();

            if ($args[0] === 'request') {
                return $mockActionRequest;
            } elseif ($args[0] === 'response') {
                return $mockResponse;
            }
        };

        $mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');

        $mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
        $mockFirewall->expects($this->once())->method('blockIllegalRequests')->with($mockActionRequest);

        $dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
        $dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
    }

    /**
     * @test
     * @return void
     */
    public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsOnlyInterceptsActionRequests()
    {
        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->getMock();
        $getMethodArgumentCallback = function () use (&$mockHttpRequest, &$mockResponse) {
            $args = func_get_args();

            if ($args[0] === 'request') {
                return $mockHttpRequest;
            } elseif ($args[0] === 'response') {
                return $mockResponse;
            }
        };

        $mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');

        $mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
        $mockFirewall->expects($this->never())->method('blockIllegalRequests');

        $dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
        $dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
    }

    /**
     * @test
     * @return void
     */
    public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsDoesNotBlockRequestsIfAuthorizationChecksAreDisabled()
    {
        $mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->getMock();

        $getMethodArgumentCallback = function () use (&$mockActionRequest, &$mockResponse) {
            $args = func_get_args();

            if ($args[0] === 'request') {
                return $mockActionRequest;
            } elseif ($args[0] === 'response') {
                return $mockResponse;
            }
        };

        $mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
        $mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
        $mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));

        $mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
        $mockFirewall->expects($this->never())->method('blockIllegalRequests');

        $dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
        $dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
    }

    /**
     * @test
     * @return void
     */
    public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointBasicallyWorks()
    {
        $mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
        $mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));
        $mockResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->getMock();
        $exception = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

        $getMethodArgumentCallback = function () use (&$mockActionRequest, &$mockResponse) {
            $args = func_get_args();

            if ($args[0] === 'request') {
                return $mockActionRequest;
            } elseif ($args[0] === 'response') {
                return $mockResponse;
            }
        };

        $getExceptionCallback = function () use (&$exception) {
            return $exception;
        };

        $mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', false);
        $mockEntryPoint = $this->getMock('TYPO3\Flow\Security\Authentication\EntryPointInterface', array(), array(), '', false);

        $authenticationRequiredException = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException();

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($authenticationRequiredException));

        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
        $mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
        $mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
        $mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
        $mockEntryPoint->expects($this->once())->method('startAuthentication')->with($this->equalTo($mockActionRequest->getHttpRequest()), $this->equalTo($mockResponse));

        $dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
        $dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
     * @return void
     */
    public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointThrowsTheOriginalExceptionIfNoEntryPointIsAvailable()
    {
        $mockActionRequest = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array(), '', false);
        $mockResponse = $this->getMock('TYPO3\Flow\Http\Response', array(), array(), '', false);
        $exception = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

        $getMethodArgumentCallback = function () use (&$mockActionRequest, &$mockResponse) {
            $args = func_get_args();

            if ($args[0] === 'request') {
                return $mockActionRequest;
            } elseif ($args[0] === 'response') {
                return $mockResponse;
            }
        };

        $getExceptionCallback = function () use (&$exception) {
            return $exception;
        };

        $mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', false);
        $mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', false);
        $mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
        $mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', false);
        $mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', false);

        $authenticationRequiredException = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException();

        $mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', false);
        $mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($authenticationRequiredException));

        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
        $mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
        $mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
        $mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
        $mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(null));

        $dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
        $dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
    }
}
