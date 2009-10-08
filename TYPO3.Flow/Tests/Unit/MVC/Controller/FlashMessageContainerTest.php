<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Controller;

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
 * Testcase for the Flash Messages Container
 *
 * @version $Id: ActionControllerTest.php 3131 2009-09-07 14:05:04Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FlashMessageContainerTest extends \F3\Testing\BaseTestCase {

	/**
	 *
	 * @var F3\FLOW3\MVC\Controller\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	public function setUp() {
		$this->flashMessageContainer = new \F3\FLOW3\MVC\Controller\FlashMessageContainer();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function addedFlashMessageCanBeReadOutAgain() {
		$message1 = 'This is a test message';
		$message2 = 'This is another test message';
		$this->flashMessageContainer->add($message1);
		$this->flashMessageContainer->add($message2);
		$this->assertEquals(array($message1, $message2), $this->flashMessageContainer->getAll());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function addingSomethingDifferentThanStringsThrowsException() {
		$this->flashMessageContainer->add(new \stdClass());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function flushResetsFlashMessage() {
		$message1 = 'This is a test message';
		$this->flashMessageContainer->add($message1);
		$this->flashMessageContainer->flush();
		$this->assertEquals(array(), $this->flashMessageContainer->getAll());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAllAndFlushFetchesAllEntriesAndFlushesTheFlashMessages() {
		$message1 = 'This is a test message';
		$message2 = 'This is another test message';
		$this->flashMessageContainer->add($message1);
		$this->flashMessageContainer->add($message2);
		$this->assertEquals(array($message1, $message2), $this->flashMessageContainer->getAllAndFlush());
		$this->assertEquals(array(), $this->flashMessageContainer->getAll());
	}

}
?>