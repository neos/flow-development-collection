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

/**
 * Testcase for the security policy enforcement aspect
 *
 */
class PolicyEnforcementAspectTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function enforcePolicyPassesTheGivenJoinPointOverToThePolicyEnforcementInterceptor() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function enforcePolicyCallsThePolicyEnforcementInterceptorCorrectly() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockPolicyEnforcementInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function enforcePolicyPassesTheGivenJoinPointOverToTheAfterInvocationInterceptor() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setJoinPoint')->with($mockJoinPoint);

		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function enforcePolicyPassesTheReturnValueOfTheInterceptedMethodOverToTheAfterInvocationInterceptor() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('setResult')->with($someResult);

		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 */
	public function enforcePolicyCallsTheTheAfterInvocationInterceptorCorrectly() {
		$this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		$mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAfterInvocationInterceptor->expects($this->once())->method('invoke');

		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @todo adjust when AfterInvocationInterceptor is used again
	 */
	public function enforcePolicyCallsTheAdviceChainCorrectly() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		// $mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$mockAdviceChain->expects($this->once())->method('proceed')->with($mockJoinPoint);
		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));

		// $securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor);
		$securityAdvice->enforcePolicy($mockJoinPoint);
	}

	/**
	 * @test
	 * @todo adjust when AfterInvocationInterceptor is used again
	 */
	public function enforcePolicyReturnsTheResultOfTheOriginalMethodCorrectly() {
		$mockJoinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface', array(), array(), '', FALSE);
		$mockAdviceChain = $this->getMock('TYPO3\Flow\Aop\Advice\AdviceChain', array(), array(), '', FALSE);
		// $mockAfterInvocationInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\AfterInvocation', array(), array(), '', FALSE);
		$mockPolicyEnforcementInterceptor = $this->getMock('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement', array(), array(), '', FALSE);

		$someResult = 'blub';

		$mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($mockAdviceChain));
		$mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
		//$mockAfterInvocationInterceptor->expects($this->once())->method('invoke')->will($this->returnValue($someResult));

		// $securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor, $mockAfterInvocationInterceptor);
		$securityAdvice = new \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect($mockPolicyEnforcementInterceptor);
		$this->assertEquals($someResult, $securityAdvice->enforcePolicy($mockJoinPoint));
	}
}
?>