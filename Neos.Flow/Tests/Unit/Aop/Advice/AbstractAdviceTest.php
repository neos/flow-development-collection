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
class AbstractAdviceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();

        $mockAspect = $this->getMockBuilder(Fixtures\SomeClass::class)->getMock();
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->createMock(SignalSlot\Dispatcher::class);

        $advice = new Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }

    /**
     * @test
     * @return void
     */
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();

        $mockAspect = $this->createMock(Fixtures\SomeClass::class);
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->createMock(SignalSlot\Dispatcher::class);

        $advice = new Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (Aop\JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }

    /**
     * @test
     * @return void
     */
    public function invokeEmitsSignalWithAdviceAndJoinPoint()
    {
        $mockJoinPoint = $this->getMockBuilder(Aop\JoinPointInterface::class)->disableOriginalConstructor()->getMock();

        $mockAspect = $this->getMockBuilder(Fixtures\SomeClass::class)->getMock();
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMockBuilder(ObjectManagerInterface::class)->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));


        $advice = new Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager);

        $mockDispatcher = $this->createMock(SignalSlot\Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with(Aop\Advice\AbstractAdvice::class, 'adviceInvoked', [$mockAspect, 'someMethod', $mockJoinPoint]);
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }
}
