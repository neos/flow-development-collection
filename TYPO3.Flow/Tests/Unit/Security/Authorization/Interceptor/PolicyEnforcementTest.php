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

use TYPO3\Flow\Security\Authorization\Privilege\GenericPrivilegeSubject;

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
        $securityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $authenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $privilegeManager = $this->getMock(\TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface::class);
        $joinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }


    /**
     * @test
     */
    public function invokeCallsThePrivilegeManagerToDecideOnTheCurrentJoinPoint()
    {
        $securityContext = $this->getMock(\TYPO3\Flow\Security\Context::class);
        $authenticationManager = $this->getMock(\TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface::class);
        $privilegeManager = $this->getMock(\TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface::class);
        $joinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class);

        $privilegeManager->expects($this->once())->method('isGranted')->with(\TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }
}
