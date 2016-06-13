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
        $securityContext = $this->createMock('TYPO3\Flow\Security\Context');
        $authenticationManager = $this->createMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $privilegeManager = $this->createMock('TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface');
        $joinPoint = $this->createMock('TYPO3\Flow\Aop\JoinPointInterface');

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
        $securityContext = $this->createMock('TYPO3\Flow\Security\Context');
        $authenticationManager = $this->createMock('TYPO3\Flow\Security\Authentication\AuthenticationManagerInterface');
        $privilegeManager = $this->createMock('TYPO3\Flow\Security\Authorization\PrivilegeManagerInterface');
        $joinPoint = $this->createMock('TYPO3\Flow\Aop\JoinPointInterface');

        $privilegeManager->expects($this->once())->method('isGranted')->with('TYPO3\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface');

        $interceptor = new \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }
}
