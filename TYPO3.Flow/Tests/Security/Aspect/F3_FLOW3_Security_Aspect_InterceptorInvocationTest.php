<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Security\Aspect;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 */

/**
 * Testcase for the security interceptor invocation aspect
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class InterceptorInvocationTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicySetsPassesTheGivenJoinPointOverToThePolicyEnforcementInterceptor() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyCallsThePolicyEnforcementInterceptorCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicySetsPassesTheGivenJoinPointOverToTheAfterInvocationInterceptor() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicySetsPassesTheReturnValueOfTheInterceptedMethodOverToTheAfterInvocationInterceptor() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setResult')->with($someResult);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyCallsTheTheAfterInvocationInterceptorCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicySetsCallsTheAdviceChainCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockAdviceChain->expects($this->once())->method('proceed')->with($mockJoinPoint);
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor,$mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyReturnsTheResultCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('invoke')->will($this->returnValue($someResult));

		$securityAdvice = new \F3\FLOW3\Security\Aspect\InterceptorInvocation($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$this->assertEquals($someResult, $securityAdvice->enforcePolicy($mockJoinPoint));
	}
}
?>