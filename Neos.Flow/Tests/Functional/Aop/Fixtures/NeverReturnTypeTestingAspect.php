<?php
namespace Neos\Flow\Tests\Functional\Aop\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing methods with never return type
 *
 * @Flow\Aspect
 */
class NeverReturnTypeTestingAspect
{
    /**
     * A before advice should work with never return type
     *
     * @Flow\Before("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithNeverReturnType->methodThatThrows())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function beforeNeverReturningMethod(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithNeverReturnType);
        $proxy->beforeAdviceWasInvoked = true;
    }

    /**
     * An after throwing advice should work with never return type
     *
     * @Flow\AfterThrowing("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithNeverReturnType->methodThatThrows())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function afterThrowingNeverReturningMethod(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithNeverReturnType);
        $proxy->afterThrowingAdviceWasInvoked = true;
    }

    /**
     *
     * @Flow\Around("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithNeverReturnType->aroundAdvicedMethodThatThrows())")
     * @param JoinPointInterface $joinPoint
     * @return void
     */
    public function aroundNeverReturningMethod(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithNeverReturnType);
        try {
            $joinPoint->getAdviceChain()->proceed($joinPoint);
        } catch (\RuntimeException $exception) {
            $proxy->aroundAdviceWasInvoked = true;
            throw $exception;
        }
    }

    /**
     * An after returning advice makes no sense for never return type - but let's test what happens
     *
     * @Flow\AfterReturning("method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithNeverReturnType->methodThatExits())")
     * @return void
     */
    public function afterReturningNeverReturningMethod(): void
    {
        throw new \LogicException('AfterReturning advice should not be invoked for never-returning methods');
    }
}
