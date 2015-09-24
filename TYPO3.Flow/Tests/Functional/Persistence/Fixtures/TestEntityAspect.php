<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect for testing aop within entities
 *
 * @Flow\Aspect
 */
class TestEntityAspect
{
    /**
     * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity->sayHello())")
     * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
     * @return string
     */
    public function concreteMethodInAbstractClassAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' Andi!';
    }
}
