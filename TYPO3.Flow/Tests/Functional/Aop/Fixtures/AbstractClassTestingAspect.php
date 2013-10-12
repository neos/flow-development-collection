<?php
namespace TYPO3\Flow\Tests\Functional\Aop\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * An aspect for testing functionality related to abstract classes
 *
 * @Flow\Aspect
 */
class AbstractClassTestingAspect {

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\SubClassOfAbstractClass->abstractMethod())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function abstractMethodInSubClassAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return $result . ' adviced';
	}

	/**
	 * @Flow\Around("method(public TYPO3\Flow\Tests\Functional\Aop\Fixtures\AbstractClass->concreteMethod())")
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function concreteMethodInAbstractClassAdvice(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return $result . ' adviced';
	}
}
