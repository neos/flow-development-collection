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
use Neos\Flow\Aop\Advice\AdviceChain;
use Neos\Flow\Aop\Advice\AroundAdvice;
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Tests\Unit\Aop\Advice\Fixtures\SomeClass;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Abstract Method Interceptor Builder
 */
final class AroundAdviceTest extends UnitTestCase
{
    /**
     * @return void
     */
    #[Test]
    public function invokeInvokesTheAdviceIfTheRuntimeEvaluatorReturnsTrue()
    {
        $mockJoinPoint = $this->createStub(JoinPointInterface::class);

        $mockAspect = $this->createMock(SomeClass::class);
        $mockAspect->expects($this->once())->method('someMethod')->with($mockJoinPoint)->willReturn(('result'));

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->expects($this->once())->method('get')->with('aspectObjectName')->willReturn(($mockAspect));

        $mockDispatcher = $this->createStub(Dispatcher::class);

        $advice = new AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return true;
            }
        });

        $this->inject($advice, 'dispatcher', $mockDispatcher);

        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals('result', $result, 'The around advice did not return the result value as expected.');
    }

    /**
     * @return void
     */
    #[Test]
    public function invokeDoesNotInvokeTheAdviceIfTheRuntimeEvaluatorReturnsFalse()
    {
        $mockAdviceChain = $this->createMock(AdviceChain::class);
        $mockAdviceChain->expects($this->once())->method('proceed')->willReturn(('result'));

        $mockJoinPoint = $this->createMock(JoinPointInterface::class);
        $mockJoinPoint->method('getAdviceChain')->willReturn(($mockAdviceChain));

        $mockAspect = $this->createMock(SomeClass::class);
        $mockAspect->expects($this->never())->method('someMethod');

        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $mockObjectManager->method('get')->willReturn(($mockAspect));

        $advice = new AroundAdvice('aspectObjectName', 'someMethod', $mockObjectManager, function (JoinPointInterface $joinPoint) {
            if ($joinPoint !== null) {
                return false;
            }
        });
        $result = $advice->invoke($mockJoinPoint);

        self::assertEquals('result', $result, 'The around advice did not return the result value as expected.');
    }
}
