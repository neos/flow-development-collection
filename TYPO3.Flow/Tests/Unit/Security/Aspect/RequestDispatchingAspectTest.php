<?php
namespace F3\FLOW3\Tests\Unit\Security\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the request dispatching aspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestDispatchingAspectTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeSecurityInitializesTheSecurityContextWithTheGivenRequest() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);

		$getMethodArgumentCallback = function() use (&$request) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
		};

		$mockSecurityLogger = $this->getMock('F3\FLOW3\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context');

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockSecurityContext->expects($this->once())->method('initialize')->with($request);

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->initializeSecurity($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function blockIllegalRequestsAndForwardToAuthenticationEntryPointsCallsTheFirewallWithTheGivenRequest() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$mockSecurityLogger = $this->getMock('F3\FLOW3\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContext = $this->getMock('F3\FLOW3\Security\Context');

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockFirewall->expects($this->any())->method('blockIllegalRequests')->with($request);

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointBasicallyWorks() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$exception = new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$getExceptionCallback = function() use (&$exception) {
			return $exception;
		};

		$mockSecurityLogger = $this->getMock('F3\FLOW3\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockEntryPoint = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointInterface', array(), array(), '', FALSE);

		$mockException = $this->getMock('F3\FLOW3\Security\Exception\AuthenticationRequiredException', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($mockException));

		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
		$mockEntryPoint->expects($this->once())->method('canForward')->will($this->returnValue(TRUE));
		$mockEntryPoint->expects($this->once())->method('startAuthentication')->with($this->equalTo($request), $this->equalTo($response));

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointThrowsTheOriginalExceptionIfNoEntryPointIsAvailable() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$exception = new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$getExceptionCallback = function() use (&$exception) {
			return $exception;
		};

		$mockSecurityLogger = $this->getMock('F3\FLOW3\Log\SecurityLoggerInterface', array(), array(), '', FALSE);
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);

		$mockException = $this->getMock('F3\FLOW3\Security\Exception\AuthenticationRequiredException', array(), array(), '', FALSE);

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($mockException));

		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockContext, $mockFirewall, $mockSecurityLogger);
		$dispatchingAspect->blockIllegalRequestsAndForwardToAuthenticationEntryPoints($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAccessDeniedResponseHeaderSetsTheResponseContentToAccessDeniedIfAnAccessDeniedExceptionHasBeenThrown() {
		$response = $this->getMock('F3\FLOW3\MVC\Response', array(), array(), '', FALSE);
		$response->expects($this->once())->method('setContent')->with('Access denied!');
		$response->expects($this->never())->method('setStatus');

		$exception = new \F3\FLOW3\Security\Exception\AccessDeniedException('AccessDenied Exception! Bad...', 1237212411);

		$getMethodArgumentCallback = function() use (&$response, &$exception) {
			$args = func_get_args();

			if ($args[0] === 'exception') return $exception;
			elseif ($args[0] === 'response') return $response;
		};

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($exception));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('response')->will($this->returnCallback($getMethodArgumentCallback));

		$dispatchingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\RequestDispatchingAspect', array('dummy'), array(), '', FALSE);
		$dispatchingAspect->setAccessDeniedResponseHeader($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setAccessDeniedResponseHeaderSetsTheResponseStatusTo403IfAnAccessDeniedExceptionHasBeenThrownWhileExecutingAWebRequest() {
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$response->expects($this->once())->method('setContent')->with('Access denied!');
		$response->expects($this->once())->method('setStatus')->with(403);

		$exception = new \F3\FLOW3\Security\Exception\AccessDeniedException('AccessDenied Exception! Bad...', 1237212411);

		$getMethodArgumentCallback = function() use (&$response, &$exception) {
			$args = func_get_args();

			if ($args[0] === 'exception') return $exception;
			elseif ($args[0] === 'response') return $response;
		};

		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->throwException($exception));

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockJoinPoint->expects($this->any())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->once())->method('getMethodArgument')->with('response')->will($this->returnCallback($getMethodArgumentCallback));

		$dispatchingAspect = $this->getAccessibleMock('F3\FLOW3\Security\Aspect\RequestDispatchingAspect', array('dummy'), array(), '', FALSE);
		$dispatchingAspect->setAccessDeniedResponseHeader($mockJoinPoint);
	}
}
?>
