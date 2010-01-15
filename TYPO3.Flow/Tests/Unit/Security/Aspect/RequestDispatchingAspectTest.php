<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestDispatchingAspectTest extends \F3\Testing\BaseTestCase {

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

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$mockRequestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService');

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockSecurityContextHolder->expects($this->any())->method('initializeContext')->with($request);

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContextHolder, $mockFirewall, $mockRequestHashService);
		$dispatchingAspect->initializeSecurity($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function blockIllegalRequestsCallsTheFirewallWithTheGivenRequest() {
		$request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$exception = new \F3\FLOW3\Security\Exception\AuthenticationRequiredException();

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$mockRequestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService');

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockFirewall->expects($this->any())->method('blockIllegalRequests')->with($request);

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContextHolder, $mockFirewall, $mockRequestHashService);
		$dispatchingAspect->blockIllegalRequests($mockJoinPoint);
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
			$args = func_get_args();

			return $exception;
		};

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$mockRequestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);
		$mockEntryPoint = $this->getMock('F3\FLOW3\Security\Authentication\EntryPointInterface', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockSecurityContextHolder->expects($this->once())->method('getContext')->will($this->returnValue($mockContext));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue($mockEntryPoint));
		$mockEntryPoint->expects($this->once())->method('canForward')->will($this->returnValue(TRUE));
		$mockEntryPoint->expects($this->once())->method('startAuthentication')->with($this->equalTo($request), $this->equalTo($response));

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContextHolder, $mockFirewall, $mockRequestHashService);
		$dispatchingAspect->forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPoint($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @expectedException \F3\FLOW3\Security\Exception\AuthenticationRequiredException
	 */
	public function forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPointThrowsTheOriginalExceptionIfNoEntryPointIsAvailable() {
		$request = $request = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$response = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$exception = new \F3\FLOW3\Security\Exception\AuthenticationRequiredException('AuthenticationRequired Exception! Bad...', 1237212410);

		$getMethodArgumentCallback = function() use (&$request, &$response) {
			$args = func_get_args();

			if ($args[0] === 'request') return $request;
			elseif ($args[0] === 'response') return $response;
		};

		$getExceptionCallback = function() use (&$exception) {
			$args = func_get_args();

			return $exception;
		};

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockFirewall = $this->getMock('F3\FLOW3\Security\Authorization\FirewallInterface');
		$mockSecurityContextHolder = $this->getMock('F3\FLOW3\Security\ContextHolderInterface');
		$mockRequestHashService = $this->getMock('F3\FLOW3\Security\Channel\RequestHashService');
		$mockContext = $this->getMock('F3\FLOW3\Security\Context', array(), array(), '', FALSE);
		$mockToken = $this->getMock('F3\FLOW3\Security\Authentication\TokenInterface', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->any())->method('getMethodArgument')->will($this->returnCallback($getMethodArgumentCallback));
		$mockJoinPoint->expects($this->any())->method('getException')->will($this->returnCallback($getExceptionCallback));
		$mockSecurityContextHolder->expects($this->once())->method('getContext')->will($this->returnValue($mockContext));
		$mockContext->expects($this->atLeastOnce())->method('getAuthenticationTokens')->will($this->returnValue(array($mockToken)));
		$mockToken->expects($this->once())->method('getAuthenticationEntryPoint')->will($this->returnValue(NULL));

		$dispatchingAspect = new \F3\FLOW3\Security\Aspect\RequestDispatchingAspect($mockSecurityContextHolder, $mockFirewall, $mockRequestHashService);
		$dispatchingAspect->forwardAuthenticationRequiredExceptionsToAnAuthenticationEntryPoint($mockJoinPoint);
	}
}
?>