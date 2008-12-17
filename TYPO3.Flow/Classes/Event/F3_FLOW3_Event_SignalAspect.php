<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Event;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 */

/**
 * Aspect which connects signal methods with the Signal Dispatcher
 *
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @aspect
 */
class SignalAspect {

	/**
	 * @var \F3\FLOW3\Event\SignalDispatcher
	 */
	protected $signalDispatcher;

	/**
	 * Injects the Signal Dispatcher
	 *
	 * @param \F3\FLOW3\Event\SignalDispatcher $signalDispatcher
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSignalDispatcher(\F3\FLOW3\Event\SignalDispatcher $signalDispatcher) {
		$this->signalDispatcher = $signalDispatcher;
	}

	/**
	 * Passes the signal over to the Signal Dispatcher
	 *
	 * @afterreturning methodTaggedWith(signal)
	 * @param F3\FLOW3\AOP\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSignalToDispatcher(\F3\FLOW3\AOP\JoinPointInterface $joinPoint) {
		$this->signalDispatcher->dispatch($joinPoint->getClassName(), $joinPoint->getMethodName(), $joinPoint->getMethodArguments());
	}
}
?>