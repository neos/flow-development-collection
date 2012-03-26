<?php
namespace TYPO3\FLOW3\Mvc;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * This is a container for all Flash Messages.
 *
 * @FLOW3\Scope("session")
 * @api
 */
class FlashMessageContainer {

	/**
	 * @var array
	 */
	protected $messages = array();

	/**
	 * Add a flash message object.
	 *
	 * @param \TYPO3\FLOW3\Error\Message $message
	 * @return void
	 * @FLOW3\Session(autoStart=true)
	 * @api
	 */
	public function addMessage(\TYPO3\FLOW3\Error\Message $message) {
		$this->messages[] = $message;
	}

	/**
	 * Returns all currently stored flash messages.
	 *
	 * @param string $severity severity of messages (from \TYPO3\FLOW3\Error\Message::SEVERITY_* constants) to return.
	 * @return array<\TYPO3\FLOW3\Error\Message>
	 * @api
	 */
	public function getMessages($severity = NULL) {
		if ($severity === NULL) {
			return $this->messages;
		}

		$messages = array();
		foreach($this->messages as $message) {
			if ($message->getSeverity() === $severity) {
				$messages[] = $message;
			}
		}
		return $messages;
	}

	/**
	 * Remove messages from this container.
	 *
	 * @param string $severity severity of messages (from \TYPO3\FLOW3\Error\Message::SEVERITY_* constants) to remove.
	 * @return void
	 * @FLOW3\Session(autoStart=true)
	 * @api
	 */
	public function flush($severity = NULL) {
		if ($severity === NULL) {
			$this->messages = array();
		} else {
			foreach($this->messages as $index => $message) {
				if ($message->getSeverity() === $severity) {
					unset($this->messages[$index]);
				}
			}
		}
	}

	/**
	 * Get all flash messages (with given severity) currently available and remove them from the container.
	 *
	 * @param string $severity severity of the messages (One of the \TYPO3\FLOW3\Error\Message::SEVERITY_* constants)
	 * @return array<\TYPO3\FLOW3\Error\Message>
	 * @api
	 */
	public function getMessagesAndFlush($severity = NULL) {
		$messages = $this->getMessages($severity);
		if (count($messages) > 0) {
			$this->flush($severity);
		}
		return $messages;
	}

}
?>