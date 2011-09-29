<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Controller;

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
 * Testcase for the Flash Messages Container
 *
 */
class FlashMessageContainerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 *
	 * @var TYPO3\FLOW3\MVC\Controller\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	public function setUp() {
		$this->flashMessageContainer = new \TYPO3\FLOW3\MVC\Controller\FlashMessageContainer();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addedFlashMessageCanBeReadOutAgain() {
		$messages = array (
			0 => new \TYPO3\FLOW3\Error\Notice('This is a test message', 1),
			1 => new \TYPO3\FLOW3\Error\Warning('This is another test message', 1)
		);
		$this->flashMessageContainer->addMessage($messages[0]);
		$this->flashMessageContainer->add('This is another test message', '', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING, array(), 1);
		$returnedFlashMessages = $this->flashMessageContainer->getAll();

		$this->assertInstanceOf('SplObjectStorage', $returnedFlashMessages);
		$this->assertEquals(count($returnedFlashMessages), 2);

		$i = 0;
		foreach($returnedFlashMessages as $flashMessage) {
			$this->assertEquals($flashMessage->getMessage(), $messages[$i++]);
		}
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @expectedException \InvalidArgumentException
	 */
	public function addingSomethingDifferentThanMessagesThrowsException() {
		$this->flashMessageContainer->add(new \stdClass());
		$this->flashMessageContainer->addMessage('not an object');
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function flushResetsFlashMessage() {
		$message1 = 'This is a test message';
		$this->flashMessageContainer->add($message1);
		$this->flashMessageContainer->flush();
		$this->assertEquals(new \SplObjectStorage, $this->flashMessageContainer->getAll());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getAllAndFlushFetchesAllEntriesAndFlushesTheFlashMessages() {
		$messages = array (
			0 => new \TYPO3\FLOW3\Error\Notice('This is a test message', 1),
			1 => new \TYPO3\FLOW3\Error\Warning('This is another test message', 1)
		);
		$this->flashMessageContainer->addMessage($messages[0]);
		$this->flashMessageContainer->add('This is another test message', '', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING, array(), 1);
		$returnedFlashMessages = $this->flashMessageContainer->getAllAndFlush();

		$this->assertInstanceOf('SplObjectStorage', $returnedFlashMessages);
		$this->assertEquals(count($returnedFlashMessages), 2);

		$i = 0;
		foreach($returnedFlashMessages as $flashMessage) {
			$this->assertEquals($flashMessage->getMessage(), $messages[$i++]);
		}

		$this->assertEquals(new \SplObjectStorage, $this->flashMessageContainer->getAll());
	}

	/**
	 * @test
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getBySeverityFetchesOnlyFilteredFlashMessages() {
		$messages = array (
			0 => new \TYPO3\FLOW3\Error\Notice('This is a test message', 1),
			1 => new \TYPO3\FLOW3\Error\Warning('This is another test message', 1)
		);

		foreach ($messages as $message) {
			$this->flashMessageContainer->addMessage($message);
		}

		$filteredFlashMessages = $this->flashMessageContainer->getBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE);

		$this->assertInstanceOf('SplObjectStorage', $filteredFlashMessages);
		$this->assertEquals(count($filteredFlashMessages), 1);

		$filteredFlashMessages->rewind();
		$flashMessage = $filteredFlashMessages->current();
		$this->assertEquals($messages[0], $flashMessage->getMessage());
	}

	/**
	 * @test
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getAndFlushBySeverityFetchesAndFlushesForOneSeverityOnly() {
		$messages = array (
			0 => new \TYPO3\FLOW3\Error\Notice('This is a test message', 1),
			1 => new \TYPO3\FLOW3\Error\Warning('This is another test message', 1)
		);

		foreach ($messages as $message) {
			$this->flashMessageContainer->addMessage($message);
		}

		$filteredFlashMessages = $this->flashMessageContainer->getAndFlushBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE);

		$this->assertInstanceOf('SplObjectStorage', $filteredFlashMessages);
		$this->assertEquals(count($filteredFlashMessages), 1);

		$filteredFlashMessages->rewind();
		$flashMessage = $filteredFlashMessages->current();
		$this->assertEquals($messages[0], $flashMessage->getMessage());

		$this->assertEquals(new \SplObjectStorage, $this->flashMessageContainer->getBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE));
	}

	/**
	 * @test
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function flushBySeverityLeavesOtherMessagesIntact() {
		$messages = array (
			0 => new \TYPO3\FLOW3\Error\Notice('This is a test message', 1),
			1 => new \TYPO3\FLOW3\Error\Warning('This is another test message', 1)
		);

		foreach ($messages as $message) {
			$this->flashMessageContainer->addMessage($message);
		}

		$this->flashMessageContainer->flushBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE);
		$this->assertEquals(0, count($this->flashMessageContainer->getBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE)));
		$this->assertEquals(1, count($this->flashMessageContainer->getBySeverity(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING)));
	}

	/**
	 * DataProvider for addCreatesMessagesDependingOnTheSeverity()
	 *
	 * @return array
	 */
	public function addCreatesMessagesDependingOnTheSeverityDataProvider() {
		return array(
			array(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE, 'TYPO3\FLOW3\Error\Notice'),
			array(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING, 'TYPO3\FLOW3\Error\Warning'),
			array(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_ERROR, 'TYPO3\FLOW3\Error\Error'),
			array(\TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_OK, 'TYPO3\FLOW3\Error\Message'),
			array('unknown Severity', 'TYPO3\FLOW3\Error\Message'),
			array('', 'TYPO3\FLOW3\Error\Message'),
			array(NULL, 'TYPO3\FLOW3\Error\Message'),
		);
	}

	/**
	 * @test
	 * @dataProvider addCreatesMessagesDependingOnTheSeverityDataProvider
	 */
	public function addCreatesMessagesDependingOnTheSeverity($severity, $expectedMessageType) {
		$messageBody = 'Message body';
		$messageArguments = array('Foo', 'Bar');

		$this->flashMessageContainer->add($messageBody, 'Message title', $severity, $messageArguments, 1);
		$flashMessages = $this->flashMessageContainer->getAll();
		$this->assertEquals(1, count($flashMessages));
		$flashMessages->rewind();
		$flashMessage = $flashMessages->current();
		$message = $flashMessage->getMessage();
		$this->assertInstanceOf($expectedMessageType, $message);
		$this->assertEquals($messageBody, $message->getMessage());
		$this->assertEquals($messageArguments, $message->getArguments());
	}

	/**
	 * DataProvider for addMessageSetsSeverityDependingOnMessageType()
	 *
	 * @return array
	 */
	public function addMessageSetsSeverityDependingOnMessageTypeDataProvider() {
		return array(
			array('TYPO3\FLOW3\Error\Notice', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_NOTICE),
			array('TYPO3\FLOW3\Error\Warning', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_WARNING),
			array('TYPO3\FLOW3\Error\Error', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_ERROR),
			array('TYPO3\FLOW3\Error\Message', \TYPO3\FLOW3\MVC\Controller\FlashMessage::SEVERITY_OK),
		);
	}

	/**
	 * @test
	 * @dataProvider addMessageSetsSeverityDependingOnMessageTypeDataProvider
	 */
	public function addMessageSetsSeverityDependingOnMessageType($messageType, $expectedSeverity) {
		$mockMessage = $this->getMock($messageType, array(), array(), '', FALSE);
		$messageTitle = 'Some message title';

		$this->flashMessageContainer->addMessage($mockMessage, $messageTitle);
		$flashMessages = $this->flashMessageContainer->getAll();
		$this->assertEquals(1, count($flashMessages));
		$flashMessages->rewind();
		$flashMessage = $flashMessages->current();
		$message = $flashMessage->getMessage();
		$this->assertSame($mockMessage, $message);
		$this->assertEquals($messageTitle, $flashMessage->getMessageTitle());
	}
}
?>