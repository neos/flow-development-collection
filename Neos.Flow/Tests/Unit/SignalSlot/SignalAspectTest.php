<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Signal Aspect
 */
final class SignalAspectTest extends UnitTestCase
{
    #[Test]
    public function forwardSignalToDispatcherForwardsTheSignalsMethodArgumentsToTheDispatcher()
    {
        $mockJoinPoint = $this->createMock(JoinPoint::class);
        $mockJoinPoint->method('getClassName')->willReturn(('SampleClass'));
        $mockJoinPoint->method('getMethodName')->willReturn(('emitSignal'));
        $mockJoinPoint->method('getMethodArguments')->willReturn((['arg1' => 'val1', 'arg2' => ['val2']]));

        $mockDispatcher = $this->createMock(Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with('SampleClass', 'signal', ['arg1' => 'val1', 'arg2' => ['val2']]);

        $aspect = $this->getAccessibleMock(SignalAspect::class, []);
        $aspect->_set('dispatcher', $mockDispatcher);
        $aspect->forwardSignalToDispatcher($mockJoinPoint);
    }
}
