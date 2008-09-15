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
 * @subpackage Tests
 * @version $Id$
 */


/**
 * Testcase for the EventDispatcher Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class EventDispatcherTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function eventDispatcherIsPrototype() {
		$dispatcher1 = $this->componentFactory->getComponent('F3::FLOW3::Event::EventDispatcher');
		$dispatcher2 = $this->componentFactory->getComponent('F3::FLOW3::Event::EventDispatcher');
		$this->assertNotSame($dispatcher1, $dispatcher2, 'Obviously EventDispatcher is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function listenersAreNotifiedWhenMatchingEventIsDispatched() {
		$listenerExecuted = FALSE;
		$dispatcher = new F3::FLOW3::Event::EventDispatcher();
		$dispatcher->addEventListener('foo', function(F3::FLOW3::Event::Event $event) use (&$listenerExecuted) { $listenerExecuted = TRUE; });
		$event = new F3::FLOW3::Event::Event('foo');
		$dispatcher->dispatchEvent($event);
		
		$this->assertTRUE($listenerExecuted, 'Listener has not been excecuted even though it was registered for the specified Event type.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function listenersAreNotNotifiedWhenNonMatchingEventIsDispatched() {
		$listenerExecuted = FALSE;
		$dispatcher = new F3::FLOW3::Event::EventDispatcher();
		$dispatcher->addEventListener('foo', function(F3::FLOW3::Event::Event $event) use (&$listenerExecuted) { $listenerExecuted = TRUE; });
		$event = new F3::FLOW3::Event::Event('bar');
		$dispatcher->dispatchEvent($event);
		
		$this->assertFALSE($listenerExecuted, 'Listener has been excecuted even though it was registered for a different Event type.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function passesEventObjectToListener() {
		$dispatcher = new F3::FLOW3::Event::EventDispatcher();
		$originalEvent = new F3::FLOW3::Event::Event('foo');
		$passedEvent = NULL;
		$dispatcher->addEventListener('foo', function(F3::FLOW3::Event::Event $event) use (&$passedEvent) { $passedEvent = $event; });
		$dispatcher->dispatchEvent($originalEvent);
		
		$this->assertSame($originalEvent, $passedEvent, 'The EventDispatcher did not pass the original Event object to the listener method.');
	}
}
?>