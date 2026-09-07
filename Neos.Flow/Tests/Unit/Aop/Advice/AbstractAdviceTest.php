<?php

declare(strict_types=1);

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
use Neos\Flow\Aop\Advice\AbstractAdvice;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\Unit\Aop\Advice\Fixtures\SomeClass;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

require_once('Fixtures/SomeClass.php');

/**
 * Testcase for the Abstract Method Interceptor Builder
 */
final class AbstractAdviceTest extends UnitTestCase
{
    /**
     * @return void
     */
    #[Test]
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->createStub(JoinPointInterface::class);

        $mockAspect = $this->createMock(SomeClass::class);
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->willReturn(($mockAspect));

        $mockDispatcher = $this->createStub(Dispatcher::class);

        $advice = new AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockJoinPoint = $this->createStub(JoinPointInterface::class);

        $mockAspect = $this->createMock(SomeClass::class);
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->willReturn(($mockAspect));

        $mockDispatcher = $this->createStub(Dispatcher::class);

        $advice = new AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeEmitsSignalWithAdviceAndJoinPoint()
    {
        $mockJoinPoint = $this->createStub(JoinPointInterface::class);

        $mockAspect = $this->createMock(SomeClass::class);
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint);

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->willReturn(($mockAspect));


        $advice = new AbstractAdvice('aspectObjectName', 'someMethod', $mockObjectManager);

        $mockDispatcher = $this->createMock(Dispatcher::class);
        $mockDispatcher->expects($this->once())->method('dispatch')->with(AbstractAdvice::class, 'adviceInvoked', [$mockAspect, 'someMethod', $mockJoinPoint]);
        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $advice->invoke($mockJoinPoint);
    }
}
