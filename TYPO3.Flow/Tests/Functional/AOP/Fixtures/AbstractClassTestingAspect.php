<?php
namespace TYPO3\FLOW3\Tests\Functional\AOP\Fixtures;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * An aspect for testing functionality related to abstract classes
 *
 * @aspect
 */
class AbstractClassTestingAspect {

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\SubClassOfAbstractClass->abstractMethod())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function abstractMethodInSubClassAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return $result . ' adviced';
	}

	/**
	 * @around method(public TYPO3\FLOW3\Tests\Functional\AOP\Fixtures\AbstractClass->concreteMethod())
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint
	 * @return void
	 */
	public function concreteMethodInAbstractClassAdvice(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$result = $joinPoint->getAdviceChain()->proceed($joinPoint);
		return $result . ' adviced';
	}
}
?>