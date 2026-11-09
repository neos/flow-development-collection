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
 * An aspect for testing advices on methods with variadic parameters
 */
#[Flow\Aspect]
class VariadicParametersTestingAspect
{
    /**
     * A before advice on a method with a variadic parameter: the arguments passed to
     * the variadic parameter are recorded as one array, the method itself must still
     * receive them as individual arguments.
     */
    #[Flow\Before('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithVariadicParameters->sum())')]
    public function beforeSumAdvice(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithVariadicParameters);
        $proxy->beforeAdviceArguments = $joinPoint->getMethodArguments();
    }

    /**
     * An around advice on the very same method, which simply proceeds
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithVariadicParameters->sum())')]
    public function aroundSumAdvice(JoinPointInterface $joinPoint): int
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithVariadicParameters);
        $proxy->aroundAdviceArguments = $joinPoint->getMethodArguments();
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * An around advice which modifies the arguments of the variadic parameter before
     * proceeding: the additional argument must arrive at the original method.
     */
    #[Flow\Around('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithVariadicParameters->concatenate())')]
    public function aroundConcatenateAdvice(JoinPointInterface $joinPoint): string
    {
        $parts = $joinPoint->getMethodArgument('parts');
        $parts[] = 'and more';
        $joinPoint->setMethodArgument('parts', $parts);
        return $joinPoint->getAdviceChain()->proceed($joinPoint);
    }

    /**
     * A before advice on a method with an untyped variadic parameter
     */
    #[Flow\Before('method(Neos\Flow\Tests\Functional\Aop\Fixtures\TargetClassWithVariadicParameters->countItems())')]
    public function beforeCountItemsAdvice(JoinPointInterface $joinPoint): void
    {
        $proxy = $joinPoint->getProxy();
        assert($proxy instanceof TargetClassWithVariadicParameters);
        $proxy->beforeAdviceArguments = $joinPoint->getMethodArguments();
    }
}
