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
 * Testcase for the security policy enforcement aspect
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class PolicyEnforcementAspectTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyPassesTheGivenJoinPointOverToThePolicyEnforcementInterceptor() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyCallsThePolicyEnforcementInterceptorCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyPassesTheGivenJoinPointOverToTheAfterInvocationInterceptor() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyPassesTheReturnValueOfTheInterceptedMethodOverToTheAfterInvocationInterceptor() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setResult')->with($someResult);

		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function enforcePolicyCallsTheTheAfterInvocationInterceptorCorrectly() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo adjust when AfterInvocationInterceptor is used again
	 */
	public function enforcePolicyCallsTheAdviceChainCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		// $mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockAdviceChain->expects($this->once())->method('proceed')->with($mockJoinPoint);
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		// $securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @todo adjust when AfterInvocationInterceptor is used again
	 */
	public function enforcePolicyReturnsTheResultOfTheOriginalMethodCorrectly() {
		$mockJoinPoint = $this->getMock('F3\FLOW3\AOP\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('F3\FLOW3\AOP\Advice\AdviceChain', array(), array(), '', FALSE);
		// $mockAfterInvocationInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('F3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
		//$mockAfterInvocationInterceptor->expects($this->once())->method('invoke')->will($this->returnValue($someResult));

		// $securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice = new \F3\FLOW3\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor);
		$this->assertEquals($someResult, $securityAdvice->enforcePolicy($mockJoinPoint));
	}
}
?>