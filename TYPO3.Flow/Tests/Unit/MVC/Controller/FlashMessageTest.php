<?php
namespace F3\FLOW3\Tests\Unit\MVC\Controller;

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
 * Testcase for the Flash Message
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FlashMessageTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\Error\Message
	 */
	protected $mockMessage;

	public function setUp() {
		$this->mockMessage = $this->getMock('F3\FLOW3\Error\Message', array(), array(), '', FALSE);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsMessage() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage);
		$this->assertSame($this->mockMessage, $flashMessage->getMessage());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsMessageTitleIfSpecified() {
		$messageTitle = 'Some message title';
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage, $messageTitle);
		$this->assertEquals($messageTitle, $flashMessage->getMessageTitle());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function defaultSeverityIsOk() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage);
		$this->assertEquals(\F3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_OK, $flashMessage->getSeverity());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function constructorSetsSeverityIfSpecified() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage, '', \F3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING);
		$this->assertEquals(\F3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING, $flashMessage->getSeverity());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasMessageTitleReturnsFalseIfMessageTitleIsNotSpecified() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage);
		$this->assertFalse($flashMessage->hasMessageTitle());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasMessageTitleReturnsFalseIfMessageTitleIsEmpty() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage, '');
		$this->assertFalse($flashMessage->hasMessageTitle());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasMessageTitleReturnsTrueIfMessageTitleIsSet() {
		$flashMessage = new \F3\FLOW3\MVC\Controller\FlashMessage($this->mockMessage, 'Message title');
		$this->assertTrue($flashMessage->hasMessageTitle());
	}
}
?>