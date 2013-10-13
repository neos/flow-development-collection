<?php
namespace TYPO3\Flow\Tests\Unit\Security\Aspect;

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
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;

/**
 * Testcase for the request dispatching aspect
 */
class RequestDispatchingAspectTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 * @return void
	 */
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsCallsTheFirewallWithTheGivenRequest() {
		$actionRequest = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$response = new Response();

		$getMethodArgumentCallback = function() use (&$actionRequest, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $actionRequest;
			elseif ($args[0] === 'response') return $response;
		};

		$mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockFirewall->expects($this->once())->method('blockIllegalRequests')->with($actionRequest);

		$dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}

	/**
	 * @test
	 * @return void
	 */
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsOnlyInterceptsActionRequests() {
		$httpRequest = Request::create(new Uri('http://wwwision.de'));
		$response = new Response();

		$getMethodArgumentCallback = function() use (&$httpRequest, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $httpRequest;
			elseif ($args[0] === 'response') return $response;
		};

		$mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
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
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsDoesNotBlockRequestsIfAuthorizationChecksAreDisabled() {
		$request = Request::create(new Uri('http://wwwision.de'))->createActionRequest();
		$response = new Response();

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
		$mockSecurityContext = $this->getMock('TYPO3\Flow\Security\Context');
		$mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->will($this->returnValue(TRUE));

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
	public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointBasicallyWorks() {
		$request = Request::create(new Uri('http://robertlemke.com/admin'))->createActionRequest();
		$response = new Response();
		$exception = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') {
				return $request;
			} elseif ($args[0] === 'response') {
				return $response;
			}
		};

		$getExceptionCallback = function() use (&$exception) {
			return $exception;
		};

		$mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockEntryPoint = $this->getMock('TYPO3\Flow\Security\Authentication\EntryPointInterface', array(), array(), '', FALSE);

		$authenticationRequiredException = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException();

		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($authenticationRequiredException));

		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
		$mockEntryPoint->expects($this->once())->method('startAuthentication')->with($this->equalTo($request->getHttpRequest()), $this->equalTo($response));

		$dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Security\Exception\AuthenticationRequiredException
	 * @return void
	 */
	public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointThrowsTheOriginalExceptionIfNoEntryPointIsAvailable() {
		$request = $this->getMock('TYPO3\Flow\Mvc\ActionRequest', array(), array(), '', FALSE);
		$response = $this->getMock('TYPO3\Flow\Http\Response', array(), array(), '', FALSE);
		$exception = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') {
				return $request;
			} elseif ($args[0] === 'response') {
				return $response;
			}
		};

		$getExceptionCallback = function() use (&$exception) {
			return $exception;
		};

		$mockSecurityLogger = $this->getMock('TYPO3\Flow\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('TYPO3\Flow\Security\Authorization\FirewallInterface');
		$mockContext = $this->getMock('TYPO3\Flow\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('TYPO3\Flow\Security\Authentication\TokenInterface', array(), array(), '', FALSE);

		$authenticationRequiredException = new \TYPO3\Flow\Security\Exception\AuthenticationRequiredException();

		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($authenticationRequiredException));

		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));

		$dispatchingAspect = new \TYPO3\Flow\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}
}
