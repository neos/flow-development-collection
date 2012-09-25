<?php
namespace TYPO3\Flow\Aop;

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
 * An advisor is the combination of a single advice and the pointcut where the
 * advice will become active.
 *
 */
class Advisor {

	/**
	 * The advisor's advice
	 * @var \TYPO3\Flow\Aop\Advice\AdviceInterface
	 */
	protected $advice;

	/**
	 * The pointcut for the advice
	 * @var \TYPO3\Flow\Aop\Pointcut\Pointcut
	 */
	protected $pointcut;

	/**
	 * Initializes the advisor with an advice and a pointcut
	 *
	 * @param \TYPO3\Flow\Aop\Advice\AdviceInterface $advice The advice to weave in
	 * @param \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut The pointcut where the advice should be inserted
	 */
	public function __construct(\TYPO3\Flow\Aop\Advice\AdviceInterface $advice, \TYPO3\Flow\Aop\Pointcut\Pointcut $pointcut) {
		$this->advice = $advice;
		$this->pointcut = $pointcut;
	}

	/**
	 * Returns the advisor's advice
	 *
	 * @return \TYPO3\Flow\Aop\Advice\AdviceInterface The advice
	 */
	public function getAdvice() {
		return $this->advice;
	}

	/**
	 * Returns the advisor's pointcut
	 *
	 * @return \TYPO3\Flow\Aop\Pointcut\Pointcut The pointcut
	 */
	public function getPointcut() {
		return $this->pointcut;
	}
}
?>