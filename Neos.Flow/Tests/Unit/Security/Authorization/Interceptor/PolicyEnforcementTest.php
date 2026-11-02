<?php

declare(strict_types=1);

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
use Neos\Flow\Security\Authentication\AuthenticationManagerInterface;
use Neos\Flow\Security\Authorization\Interceptor\PolicyEnforcement;
use Neos\Flow\Security\Authorization\Privilege\Method\MethodPrivilegeInterface;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the policy enforcement interceptor
 */
final class PolicyEnforcementTest extends UnitTestCase
{
    #[Test]
    public function invokeCallsTheAuthenticationManager()
    {
        $securityContext = $this->createStub(Context::class);
        $authenticationManager = $this->createMock(AuthenticationManagerInterface::class);
        $privilegeManager = $this->createStub(PrivilegeManagerInterface::class);
        $joinPoint = $this->createStub(JoinPointInterface::class);

        $authenticationManager->expects($this->once())->method('authenticate');

        $interceptor = new PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }

    #[Test]
    public function invokeCallsThePrivilegeManagerToDecideOnTheCurrentJoinPoint()
    {
        $securityContext = $this->createStub(Context::class);
        $authenticationManager = $this->createStub(AuthenticationManagerInterface::class);
        $privilegeManager = $this->createMock(PrivilegeManagerInterface::class);
        $joinPoint = $this->createStub(JoinPointInterface::class);

        $privilegeManager->expects($this->once())->method('isGranted')->with(MethodPrivilegeInterface::class);

        $interceptor = new PolicyEnforcement($securityContext, $authenticationManager, $privilegeManager);
        $interceptor->setJoinPoint($joinPoint);
        $interceptor->invoke();
    }
}
