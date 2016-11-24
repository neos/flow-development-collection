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
 * An aspect for testing functionality related to abstract classes
 *
 * @Flow\Aspect
 */
class AbstractClassTestingAspect
{
    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\SubClassOfAbstractClass->abstractMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function abstractMethodInSubClassAdvice(JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' adviced';
    }

    /**
     * @Flow\Around("method(public Neos\Flow\Tests\Functional\Aop\Fixtures\AbstractClass->concreteMethod())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function concreteMethodInAbstractClassAdvice(JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' adviced';
    }
}
