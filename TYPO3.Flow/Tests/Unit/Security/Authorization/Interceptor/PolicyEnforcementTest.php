<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
