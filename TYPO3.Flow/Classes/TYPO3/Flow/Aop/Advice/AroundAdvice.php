<?php
namespace TYPO3\Flow\Aop\Advice;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Implementation of the Around Advice.
 *
 */
class AroundAdvice extends \TYPO3\Flow\Aop\Advice\AbstractAdvice implements \TYPO3\Flow\Aop\Advice\AdviceInterface {

	/**
	 * Invokes the advice method
	 *
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point which is passed to the advice method
	 * @return mixed Result of the advice method
	 */
	public function invoke(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if ($this->runtimeEvaluator !== NULL && $this->runtimeEvaluator->__invoke($joinPoint) === FALSE) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		}

		$adviceObject = $this->objectManager->get($this->aspectObjectName);
		$methodName = $this->adviceMethodName;
		return $adviceObject->$methodName($joinPoint);
	}
}
