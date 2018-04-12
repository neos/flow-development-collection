<?php
namespace Neos\Flow\Tests\Unit\SignalSlot;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Aop\JoinPoint;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\SignalSlot\SignalAspect;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the Signal Aspect
 */
class SignalAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function forwardSignalToDispatcherForwardsTheSignalsMethodArgumentsToTheDispatcher()
    {
        $mockJoinPoint = $this->getMockBuilder(JoinPoint::class)->disableOriginalConstructor()->getMock();
        $mockJoinPoint->expects($this->any())->method('getClassName')->will($this->returnValue('SampleClass'));
        $mockJoinPoint->expects($this->any())->method('getMethodName')->will($this->returnValue('emitSignal'));
        $mockJoinPoint->expects($this->any())->method('getMethodArguments')->will($this->returnValue(['arg1' => 'val1', 'arg2' => ['val2']]));

        $mockDispatcher = $this->createMock(Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with('SampleClass', 'signal', ['arg1' => 'val1', 'arg2' => ['val2']]);

        $aspect = $this->getAccessibleMock(SignalAspect::class, ['dummy']);
        $aspect->_set('dispatcher', $mockDispatcher);
        $aspect->forwardSignalToDispatcher($mockJoinPoint);
    }
}
