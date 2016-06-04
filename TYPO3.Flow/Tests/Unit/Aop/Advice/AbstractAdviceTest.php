<?php
namespace TYPO3\Flow\Tests\Unit\Aop\Advice;

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
        $mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->disableOriginalConstructor()->getMock();

        $mockAspect = $this->getMockBuilder('MockClass' . md5(uniqid(mt_rand(), true)))->setMethods(array('someMethod'))->getMock();
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->createMock('TYPO3\Flow\SignalSlot\Dispatcher');

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
        $mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->disableOriginalConstructor()->getMock();

        $mockAspect = $this->getMockBuilder('MockClass' . md5(uniqid(mt_rand(), true)))->setMethods(array('someMethod'))->getMock();
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->any())->method('get')->will($this->returnValue($mockAspect));

        $mockDispatcher = $this->createMock('TYPO3\Flow\SignalSlot\Dispatcher');

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
        $mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPointInterface')->disableOriginalConstructor()->getMock();

        $mockAspect = $this->getMockBuilder('MockClass' . md5(uniqid(mt_rand(), true)))->setMethods(array('someMethod'))->getMock();
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->getMockBuilder('TYPO3\Flow\Object\ObjectManagerInterface')->disableOriginalConstructor()->getMock();
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->will($this->returnValue($mockAspect));


        $advice = new \TYPO3\Flow\Aop\Advice\AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager);

        $mockDispatcher = $this->createMock('TYPO3\Flow\SignalSlot\Dispatcher');
        $mockDispatcher->expects($this->once())->method('dispatch')->with('TYPO3\Flow\Aop\Advice\AbstractAdvice', 'adviceInvoked', array($mockAspect, 'someMethod', $mockJoinPoint));
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }
}
