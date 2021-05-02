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
        $mockJoinPoint->expects(self::any())->method('getClassName')->will(self::returnValue('SampleClass'));
        $mockJoinPoint->expects(self::any())->method('getMethodName')->will(self::returnValue('emitSignal'));
        $mockJoinPoint->expects(self::any())->method('getMethodArguments')->will(self::returnValue(['arg1' => 'val1', 'arg2' => ['val2']]));

        $mockDispatcher = $this->createMock(Dispatcher::class);
        $mockDispatcher->expects(self::once())->method('dispatch')->with('SampleClass', 'signal', ['arg1' => 'val1', 'arg2' => ['val2']]);

        $aspect = $this->getAccessibleMock(SignalAspect::class, ['dummy']);
        $aspect->_set('dispatcher', $mockDispatcher);
        $aspect->forwardSignalToDispatcher($mockJoinPoint);
    }
}
