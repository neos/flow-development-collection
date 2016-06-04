<?php
namespace TYPO3\Flow\Tests\Unit\SignalSlot;

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
 * Testcase for the Signal Aspect
 *
 */
class SignalAspectTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function forwardSignalToDispatcherForwardsTheSignalsMethodArgumentsToTheDispatcher()
    {
        $mockJoinPoint = $this->getMockBuilder('TYPO3\Flow\Aop\JoinPoint')->disableOriginalConstructor()->getMock();
        $mockJoinPoint->expects($this->any())->method('getClassName')->will($this->returnValue('SampleClass'));
        $mockJoinPoint->expects($this->any())->method('getMethodName')->will($this->returnValue('emitSignal'));
        $mockJoinPoint->expects($this->any())->method('getMethodArguments')->will($this->returnValue(array('arg1' => 'val1', 'arg2' => array('val2'))));

        $mockDispatcher = $this->createMock('TYPO3\Flow\SignalSlot\Dispatcher');
        $mockDispatcher->expects($this->once())->method('dispatch')->with('SampleClass', 'signal', array('arg1' => 'val1', 'arg2' => array('val2')));

        $aspect = $this->getAccessibleMock('TYPO3\Flow\SignalSlot\SignalAspect', array('dummy'));
        $aspect->_set('dispatcher', $mockDispatcher);
        $aspect->forwardSignalToDispatcher($mockJoinPoint);
    }
}
