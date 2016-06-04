<?php
namespace TYPO3\Flow\Tests\Unit\Security\Aspect;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect;

/**
 * Testcase for the security policy enforcement aspect
 *
 */
class PolicyEnforcementAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\Flow\Aop\JoinPointInterface
     */
    protected $mockJoinPoint;

    /**
     * @var \TYPO3\Flow\Aop\Advice\AdviceChain
     */
    protected $mockAdviceChain;

    /**
     * @var \TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement
     */
    protected $mockPolicyEnforcementInterceptor;

    /**
     * @var \TYPO3\Flow\Security\Context
     */
    protected $mockSecurityContext;

    /**
     * @var \TYPO3\Flow\Security\Aspect\PolicyEnforcementAspect
     */
    protected $policyEnforcementAspect;

    public function setUp()
    {
        $this->mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->disableOriginalConstructor()->getMock();
        $this->mockAdviceChain = $this->getMockBuilder('TYPO3\Flow\Aop\Advice\AdviceChain')->disableOriginalConstructor()->getMock();
        $this->mockPolicyEnforcementInterceptor = $this->getMockBuilder('TYPO3\Flow\Security\Authorization\Interceptor\PolicyEnforcement')->disableOriginalConstructor()->getMock();
        $this->mockSecurityContext = $this->createMock('TYPO3\Flow\Security\Context');
        $this->policyEnforcementAspect = new PolicyEnforcementAspect($this->mockPolicyEnforcementInterceptor, $this->mockSecurityContext);
    }

    /**
     * @test
     */
    public function enforcePolicyPassesTheGivenJoinPointOverToThePolicyEnforcementInterceptor()
    {
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        $this->mockPolicyEnforcementInterceptor->expects($this->once())->method('setJoinPoint')->with($this->mockJoinPoint);

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     */
    public function enforcePolicyCallsThePolicyEnforcementInterceptorCorrectly()
    {
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        $this->mockPolicyEnforcementInterceptor->expects($this->once())->method('invoke');

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     */
    public function enforcePolicyPassesTheGivenJoinPointOverToTheAfterInvocationInterceptor()
    {
        $this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        //$this->mockAfterInvocationInterceptor->expects($this->once())->method('setJoinPoint')->with($this->mockJoinPoint);

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     */
    public function enforcePolicyPassesTheReturnValueOfTheInterceptedMethodOverToTheAfterInvocationInterceptor()
    {
        $this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

        $someResult = 'blub';

        $this->mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        //$this->mockAfterInvocationInterceptor->expects($this->once())->method('setResult')->with($someResult);

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     */
    public function enforcePolicyCallsTheTheAfterInvocationInterceptorCorrectly()
    {
        $this->markTestSkipped('Currently the AfterInvocationInterceptor is not used.');

        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        //$this->mockAfterInvocationInterceptor->expects($this->once())->method('invoke');

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    public function enforcePolicyCallsTheAdviceChainCorrectly()
    {
        $this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint);
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));

        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }

    /**
     * @test
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    public function enforcePolicyReturnsTheResultOfTheOriginalMethodCorrectly()
    {
        $someResult = 'blub';

        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));
        $this->mockAdviceChain->expects($this->once())->method('proceed')->will($this->returnValue($someResult));
        //$this->mockAfterInvocationInterceptor->expects($this->once())->method('invoke')->will($this->returnValue($someResult));

        //
        $this->assertEquals($someResult, $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint));
    }

    /**
     * @test
     * @todo adjust when AfterInvocationInterceptor is used again
     */
    public function enforcePolicyDoesNotInvokeInterceptorIfAuthorizationChecksAreDisabled()
    {
        $this->mockAdviceChain->expects($this->once())->method('proceed')->with($this->mockJoinPoint);
        $this->mockJoinPoint->expects($this->once())->method('getAdviceChain')->will($this->returnValue($this->mockAdviceChain));

        $this->mockSecurityContext->expects($this->atLeastOnce())->method('areAuthorizationChecksDisabled')->will($this->returnValue(true));
        $this->mockPolicyEnforcementInterceptor->expects($this->never())->method('invoke');
        $this->policyEnforcementAspect->enforcePolicy($this->mockJoinPoint);
    }
}
