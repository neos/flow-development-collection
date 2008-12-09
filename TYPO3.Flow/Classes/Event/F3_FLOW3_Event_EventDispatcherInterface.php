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
 * Contract for an Event dispatcher
 *
 * @package FLOW3
 * @subpackage Event
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface EventDispatcherInterface {

	/**
	 * Adds an Event listener method (Closure) to the internal listeners collection.
	 * Whenever an Event is dispatched, all registered listeners for the given Event type are executed.
	 *
	 * @param string $type The event type.
	 * @param Closure $listener Listener method.
	 * @return void
	 */
	public function addEventListener($type, ::Closure $listener);

	/**
	 * Executes all listener methods which are registered for the respective Event type.
	 *
	 * @param F3::FLOW3::Event::Event $event The Event to be passed on to the listeners.
	 * @return void
	 */
	public function dispatchEvent(F3::FLOW3::Event::Event $event);
}
?>