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

/**
 * Aspect which connects signal methods with the Signal Dispatcher
 *
 * @scope singleton
 * @aspect
 */
class SignalAspect {

	/**
	 * @inject
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Passes the signal over to the Dispatcher
	 *
	 * @afterreturning methodTaggedWith(signal)
	 * @param \TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSignalToDispatcher(\TYPO3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));
		$this->dispatcher->dispatch($joinPoint->getClassName(), $signalName, $joinPoint->getMethodArguments());
	}
}
?>