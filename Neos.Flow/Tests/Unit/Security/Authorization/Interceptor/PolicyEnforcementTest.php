<?php
namespace Neos\Flow\Tests\Unit\Security\Authorization\Interceptor;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Security;

/**
 * Testcase for the policy enforcement interceptor
 */
class PolicyEnforcementTest extends UnitTestCase
{
    /**
     * @test
     */
    public function invokeCallsTheAuthenticationManager()
    {
        $securityContext = $this->createMock(Security\Context::class);
        $authenticationManager = $this->createMock(Security\Authentication\AuthenticationManagerInterface::class);
        $privilegeManager = $this->createMock(Security\Authorization\PrivilegeManagerInterface::class);
        $joinPoint = $this->createMock(JoinPointInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new Security\Authorization\Interceptor\PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }


    /**
     * @test
     */
    public function invokeCallsThePrivilegeManagerToDecideOnTheCurrentJoinPoint()
    {
        $securityContext = $this->createMock(Security\Context::class);
        $authenticationManager = $this->createMock(Security\Authentication\AuthenticationManagerInterface::class);
        $privilegeManager = $this->createMock(Security\Authorization\PrivilegeManagerInterface::class);
        $joinPoint = $this->createMock(JoinPointInterface::class);

        $privilegeManager->expects($this->once())->method('isGranted')->with(Security\Authorization\Privilege\Method\MethodPrivilegeInterface::class);

        $interceptor = new Security\Authorization\Interceptor\PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }
}
