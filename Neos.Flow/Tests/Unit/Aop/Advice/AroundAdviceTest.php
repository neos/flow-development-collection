<?php
namespace Neos\Flow\Tests\Unit\Aop\Advice;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\SignalSlot;
use Neos\Flow\Aop;

/**
 * Testcase for the Abstract Method Interceptor Builder
 */
class AroundAdviceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->willReturn(('result'));

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->willReturn(($mockAspect));

        $mockDispatcher = $this->createMock(SignalSlot\Dispatcher::class);

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });

        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }

    /**
     * @test
     * @return void
     */
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockAdviceChain = $this->getMockBuilder(Aop\Advice\AdviceChain::class)->disableOriginalConstructor()->getMock();
        $mockAdviceChain->expects($this->once())->method('proceed')->willReturn(('result'));

        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();
        $mockJoinPoint->expects($this->any())->method('getAdviceChain')->willReturn(($mockAdviceChain));

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('get')->willReturn(($mockAspect));

        $advice = new Aop\Advice\AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals($result, 'result', 'The around advice did not return the result value as expected.');
    }
}
