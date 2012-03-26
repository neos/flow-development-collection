<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the policy enforcement interceptor
 *
 */
class PolicyEnforcementTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function invokeCallsTheAuthenticationManager() {
		$authenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$accessDecisionManager = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$authenticationManager->expects($this->once())->method('authenticate');

		$interceptor = new \TYPO3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}


	/**
	 * @test
	 */
	public function invokeCallsTheAccessDecisionManagerToDecideOnTheCurrentJoinPoint() {
		$authenticationManager = $this->getMock('TYPO3\FLOW3\Security\Authentication\AuthenticationManagerInterface');
		$accessDecisionManager = $this->getMock('TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface');
		$joinPoint = $this->getMock('TYPO3\FLOW3\Aop\JoinPointInterface');

		$accessDecisionManager->expects($this->once())->method('decideOnJoinPoint')->with($joinPoint);

		$interceptor = new \TYPO3\FLOW3\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
		$interceptor->setJoinPoint($joinPoint);
		$interceptor->invoke();
	}
}

?>