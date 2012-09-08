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
 * This is the interface for a generic AOP advice. It is never implemented directly.
 * In FLOW3 all advices are implemented as interceptors.
 *
 * @see \TYPO3\FLOW3\Aop\InterceptorInterface
 */
interface AdviceInterface {

	/**
	 * Invokes the advice method
	 *
	 * @param  \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point which is passed to the advice method
	 * @return mixed Optionally the result of the advice method
	 */
	public function invoke(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint);

	/**
	 * Returns the aspect's object name which has been passed to the constructor
	 *
	 * @return string The object name of the aspect
	 */
	public function getAspectObjectName();

	/**
	 * Returns the advice's method name which has been passed to the constructor
	 *
	 * @return string The name of the advice method
	 */
	public function getAdviceMethodName();
}
?>