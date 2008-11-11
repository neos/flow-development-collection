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
 * Testcase for the Event Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class EventTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function eventIsPrototype() {
		$event1 = $this->objectManager->getObject('F3::FLOW3::Event::Event');
		$event2 = $this->objectManager->getObject('F3::FLOW3::Event::Event');
		$this->assertNotSame($event1, $event2, 'Obviously Event is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function eventTypeIsSet() {
		$event = new F3::FLOW3::Event::Event('testEventType');
		$this->assertEquals($event->getType(), 'testEventType');
	}

}
?>