<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Advice;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * Testcase for the Abstract Method Interceptor Builder
 *
 */
class AbstractAdviceTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class, array(), array(), '', false);

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->getMock(\TYPO3\Flow\SignalSlot\Dispatcher::class);

        $advice = new \TYPO3\Flow\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
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
        $mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class, array(), array(), '', false);

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->getMock(\TYPO3\Flow\SignalSlot\Dispatcher::class);

        $advice = new \TYPO3\Flow\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
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
        $mockJoinPoint = $this->getMock(\TYPO3\Flow\Aop\JoinPointInterface::class, array(), array(), '', false);

        $mockAspect = $this->getMock('MockClass' . md5(uniqid(mt_rand(), true)), array('someMethod'));
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMock(\TYPO3\Flow\Object\ObjectManagerInterface::class, array(), array(), '', false);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $advice = new \TYPO3\Flow\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager);

        $mockDispatcher = $this->getMock(\TYPO3\Flow\SignalSlot\Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with(\TYPO3\Flow\Aop\Advice\AbstractAdvice::class, 'adviceInvoked', array($mockAspect, 'someMethod', $mockJoinPoint));
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }
}
