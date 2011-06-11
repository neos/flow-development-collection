<?php
namespace F3\FLOW3\SignalSlot;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Aspect which connects signal methods with the Signal Dispatcher
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @aspect
 */
class SignalAspect {

	/**
	 * @var \F3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Injects the Signal Dispatcher
	 *
	 * @param \F3\FLOW3\SignalSlot\Dispatcher $dispatcher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectDispatcher(\F3\FLOW3\SignalSlot\Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Passes the signal over to the Dispatcher
	 *
	 * @afterreturning methodTaggedWith(signal)
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSignalToDispatcher(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$signalName = lcfirst(str_replace('emit', '', $joinPoint->getMethodName()));
		$this->dispatcher->dispatch($joinPoint->getClassName(), $signalName, $joinPoint->getMethodArguments());
	}
}
?>