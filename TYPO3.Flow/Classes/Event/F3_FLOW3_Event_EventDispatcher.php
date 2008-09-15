<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Event;

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
 * Simple Event dispatcher
 *
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class EventDispatcher implements F3::FLOW3::Event::EventDispatcherInterface {

	/**
	 * The registered event listeners.
	 *
	 * @var array Array of registered listeners. Key = Event type, value = Closure to execute when Event occurs.
	 */
	protected $listeners;

	/**
	 * Adds an Event listener method (Closure) to the internal listeners collection.
	 * Whenever an Event is dispatched, all registered listeners for the given Event type are executed.
	 *
	 * @param string $type The event type.
	 * @param Closure $listener Listener method.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addEventListener($type, Closure $listener) {
		$this->listeners[$type][] = $listener;
	}

	/**
	 * Executes all listener methods which are registered for the respective Event type.
	 *
	 * @param F3::FLOW3::Event::Event $event The Event to be passed on to the listeners.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dispatchEvent(F3::FLOW3::Event::Event $event) {
		$type = $event->getType();
		if (!key_exists($type, $this->listeners) || !is_array($this->listeners[$type])) {
			return;
		}
		foreach($this->listeners[$type] as $listener) {
			$listener($event);
		}
	}
}
?>