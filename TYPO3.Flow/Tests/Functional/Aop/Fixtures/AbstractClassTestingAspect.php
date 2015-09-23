<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect for testing functionality related to abstract classes
 *
 * @Flow\Aspect
 */
class AbstractClassTestingAspect
{
    /**
     * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\SubClassOfAbstractClass->abstractMethod())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
     * @return string
     */
    public function abstractMethodInSubClassAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' adviced';
    }

    /**
     * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\AbstractClass->concreteMethod())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
     * @return string
     */
    public function concreteMethodInAbstractClassAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' adviced';
    }
}
