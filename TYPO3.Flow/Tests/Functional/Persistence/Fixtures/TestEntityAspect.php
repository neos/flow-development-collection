<?php
namespace TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * An aspect for testing aop within entities
 *
 * @FLOW3\Aspect
 */
class TestEntityAspect {

	/**
	 * @FLOW3\Around("method(public TYPO3\FLOW3\Tests\Functional\Persistence\Fixtures\TestEntity->sayHello())")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function concreteMethodInAbstractClassAdvice(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return $result . ' Andi!';
	}
}
?>