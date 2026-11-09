<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\Aspect;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Aop\Advice\AdviceChain;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Security;
use Neos\Flow\Security\Aspect\PolicyEnforcementAspect;
use Neos\Flow\Security\Authorization\Interceptor\PolicyEnforcement;
use Neos\Flow\Security\Context;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the security policy enforcement aspect
 */
final class PolicyEnforcementAspectTest extends UnitTestCase
{
    /**
     * @var JoinPointInterface
     */
    protected $mockJoinPoint;

    /**
     * @var AdviceChain
     */
    protected $mockAdviceChain;

    /**
     * @var Security\Authorization\Interceptor\PolicyEnforcement
     */
    protected $mockPolicyEnforcementInterceptor;

    /**
     * @var Security\Context
     */
    protected $mockSecurityContext;

    /**
     * @var PolicyEnforcementAspect
     */
    protected $policyEnforcementAspect;

    protected function setUp(): void
    {
        $this->mockJoinPoint = $this->createMock(JoinPointInterface::class);
        $this->mockAdviceChain = $this->createMock(AdviceChain::class);
        $this->mockPolicyEnforcementInterceptor = $this->createMock(PolicyEnforcement::class);
        $this->mockSecurityContext = $this->createMock(Context::class);
        $this->policyEnforcementAspect = new PolicyEnforcementAspect($this->mockPolicyEnforcementInterceptor, $this->mockSecurityContext);
    }

    #[Test]
    public function enforcePolicyPassesTheGivenJoinPointOverToThePolicyEnforcementInterceptor()
    {
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->willReturn(($this->mockAdviceChain));
        $this->mockPolicyEnforcementInterceptor->expects($this->once())->method('setJoinPoint')->with($this->mockJoinPoint);

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    #[Test]
    public function enforcePolicyCallsThePolicyEnforcementInterceptorCorrectly()
    {
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->willReturn(($this->mockAdviceChain));
        $this->mockPolicyEnforcementInterceptor->expects($this->once())->method('invoke');

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    #[Test]
    public function enforcePolicyCallsTheAdviceChainCorrectly()
    {
        $this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint);
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->willReturn(($this->mockAdviceChain));

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    #[Test]
    public function enforcePolicyReturnsTheResultOfTheOriginalMethodCorrectly()
    {
        $someResult = 'blub';

        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->willReturn(($this->mockAdviceChain));
        $this->mockAdviceChain->expects($this->once())->method('proceed')->willReturn(($someResult));
        // $this->mockAfterInvocationInterceptor->expects($this->once())->method('invoke')->willReturn(($someResult));

        self::assertEquals($someResult, $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint));
    }

    /**
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    #[Test]
    public function enforcePolicyDoesNotInvokeInterceptorIfAuthorizationChecksAreDisabled()
    {
        $this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint);
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->willReturn(($this->mockAdviceChain));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->willReturn((true));
        $this->mockPolicyEnforcementInterceptor->expects($this->never())->method('invoke');
        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }
}
