<?php
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

use \F3\FLOW3\MVC\Controller\FlashMessage;
use \F3\FLOW3\Error\Message;
use \F3\FLOW3\Error\Notice;
use \F3\FLOW3\Error\Warning;
use \F3\FLOW3\Error\Error;

/**
 * This is a container for all Flash Messages. It is of scope session, thus, it is automatically persisted.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope session
 */
class FlashMessageContainer {

	/**
	 * The storage of flash messages
	 * @var \SplObjectStorage<F3\FLOW3\MVC\Controller\FlashMessage>
	 */
	protected $flashMessages;

	public function __construct() {
		$this->flashMessages = new \SplObjectStorage;
	}

	/**
	 * Add message with one of the default severity types.
	 *
	 * @param string $messageBody the body of the message to convey
	 * @param string $messageTitle optional message title
	 * @param string $severity severity of of the message (One of the FlashMessage::SEVERITY_* constants)
	 * @param array $arguments array of arguments to be replaced in the message body
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function add($messageBody, $messageTitle = '', $severity = FlashMessage::SEVERITY_OK, $messageArguments = array()) {
		if (!is_string($messageBody)) {
			throw new \InvalidArgumentException('The message body must be of type string but ' . gettype($messageBody) . ' given.', 1243258395);
		}

		switch ($severity) {
			case FlashMessage::SEVERITY_NOTICE:
				$message = new Notice($messageBody, $messageArguments);
				break;
			case FlashMessage::SEVERITY_WARNING:
				$message = new Warning($messageBody, $messageArguments);
				break;
			case FlashMessage::SEVERITY_ERROR:
				$message = new Error($messageBody, $messageArguments);
				break;
			default:
				$message = new Message($messageBody, $messageArguments);
			break;
		}
		$flashMessage = new FlashMessage($message, $messageTitle, $severity);
		$this->flashMessages->attach($flashMessage);
	}

	/**
	 * Add message object (custom)
	 *
	 * @param $message \F3\FLOW3\Error\Message
	 * @param $messageTitle optional message title
	 * @return void
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function addMessage(Message $message, $messageTitle = '') {
		if ($message instanceof Notice) {
			$severity = FlashMessage::SEVERITY_NOTICE;
		} elseif ($message instanceof Warning) {
			$severity = FlashMessage::SEVERITY_WARNING;
		} elseif ($message instanceof Error) {
			$severity = FlashMessage::SEVERITY_ERROR;
		} else {
			$severity = FlashMessage::SEVERITY_OK;
		}
		$flashMessage = new FlashMessage($message, $messageTitle, $severity);
		$this->flashMessages->attach($flashMessage);
	}

	/**
	 * Get all flash messages currently available.
	 *
	 * @return \SplObjectStorage<F3\FLOW3\MVC\Controller\FlashMessage> A SplObjectStorage of messages
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getAll() {
		return $this->flashMessages;
	}

	/**
	 * Reset all flash messages.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function flush() {
		$this->flashMessages = new \SplObjectStorage;
	}

	/**
	 * Get all flash messages currently available and delete them afterwards.
	 *
	 * @return \SplObjectStorage<F3\FLOW3\MVC\Controller\FlashMessage>
	 * @api
	 */
	public function getAllAndFlush() {
		$flashMessages = $this->flashMessages;
		$this->flush();
		return $flashMessages;
	}

	/**
	 * @param string $severity severity of of the message (One of the FlashMessage::SEVERITY_* constants)
	 * @return \SplObjectStorage<F3\FLOW3\MVC\Controller\FlashMessage>
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getBySeverity($severity) {
		$filteredFlashMessages = new \SplObjectStorage;
		foreach($this->flashMessages as $flashMessage) {
			if ($flashMessage->getSeverity() === $severity) {
				$filteredFlashMessages->attach($flashMessage);
			}
		}
		return $filteredFlashMessages;
	}

	/**
	 * @param string $severity severity of of the message (One of the FlashMessage::SEVERITY_* constants)
	 * @return \SplObjectStorage<F3\FLOW3\MVC\Controller\FlashMessage>
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getAndFlushBySeverity($severity) {
		$filteredFlashMessages = $this->getBySeverity($severity);
		$this->flashMessages->removeAll($filteredFlashMessages);
		return $filteredFlashMessages;
	}

	/**
	 * @param string $severity severity of of the message (One of the FlashMessage::SEVERITY_* constants)
	 * @return void
	 * @author Christian Müller <christian.mueller@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function flushBySeverity($severity) {
		$filteredFlashMessages = $this->getBySeverity($severity);
		$this->flashMessages->removeAll($filteredFlashMessages);
	}
}
?>