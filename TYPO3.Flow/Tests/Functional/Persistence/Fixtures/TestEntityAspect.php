<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Aop\JoinPointInterface;

/**
 * An aspect for testing aop within entities
 *
 * @Flow\Aspect
 */
class TestEntityAspect
{
    /**
     * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity->sayHello())")
     * @param JoinPointInterface $joinPoint
     * @return string
     */
    public function concreteMethodInAbstractClassAdvice(JoinPointInterface $joinPoint)
    {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        return $result . ' Andi!';
    }
}
