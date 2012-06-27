<?php
namespace TYPO3\FLOW3\Aop\Advice;

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
 * The advice chain holds a number of subsequent advices that
 * match a given join point and calls the advices in the right order.
 *
 */
class AdviceChain {

	/**
	 * An array of \TYPO3\FLOW3\Aop\Advice objects which form the advice chain
	 * @var array
	 */
	protected $advices;

	/**
	 * The number of the next advice which will be invoked on a proceed() call
	 * @var integer
	 */
	protected $adviceIndex = -1;

	/**
	 * Initializes the advice chain
	 *
	 * @param array $advices An array of \TYPO3\FLOW3\Aop\Advice\AdviceInterface compatible objects which form the chain of advices
	 */
	public function __construct($advices) {
		$this->advices = $advices;
	}

	/**
	 * An advice usually calls (but doesn't have to necessarily) this method
	 * in order to proceed with the next advice in the chain. If no advice is
	 * left in the chain, the proxy classes' method invokeJoinpoint() will finally
	 * be called.
	 *
	 * @param  \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point (ie. the context)
	 * @return mixed Result of the advice or the original method of the target class
	 */
	public function proceed(\TYPO3\FLOW3\Aop\JoinPointInterface &$joinPoint) {
		$this->adviceIndex++;
		if ($this->adviceIndex < count($this->advices)) {
			$result = $this->advices[$this->adviceIndex]->invoke($joinPoint);
		} else {
			$result = $joinPoint->getProxy()->FLOW3_Aop_Proxy_invokeJoinpoint($joinPoint);
		}
		return $result;
	}

	/**
	 * Re-initializes the index to start a new run through the advice chain
	 *
	 * @return void
	 */
	public function rewind() {
		$this->adviceIndex = -1;
	}
}
?>