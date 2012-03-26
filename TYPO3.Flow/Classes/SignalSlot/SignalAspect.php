<?php
namespace TYPO3\FLOW3\SignalSlot;

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
 * Aspect which connects signal methods with the Signal Dispatcher
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Aspect
 */
class SignalAspect {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Passes the signal over to the Dispatcher
	 *
	 * @FLOW3\AfterReturning("methodAnnotatedWith(TYPO3\FLOW3\Annotations\Signal)")
	 * @param \TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 */
	public function forwardSignalToDispatcher(\TYPO3\FLOW3\Aop\JoinPointInterface $joinPoint) {
		$signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));
		$this->dispatcher->dispatch($joinPoint->getClassName(), $signalName, $joinPoint->getMethodArguments());
	}
}
?>