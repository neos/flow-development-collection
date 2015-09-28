<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the policy enforcement interceptor
 *
 */
class PolicyEnforcementTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function invokeCallsTheAuthenticationManager()
    {
        $authenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $accessDecisionManager = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface');
        $joinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }


    /**
     * @test
     */
    public function invokeCallsTheAccessDecisionManagerToDecideOnTheCurrentJoinPoint()
    {
        $authenticationManager = $this->getMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $accessDecisionManager = $this->getMock('TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface');
        $joinPoint = $this->getMock('TYPO3\Flow\Aop\JoinPointInterface');

        $accessDecisionManager->expects($this->once())->method('decideOnJoinPoint')->with($joinPoint);

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement($authenticationManager, $accessDecisionManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }
}
