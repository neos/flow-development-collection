<?php
namespace TYPO3\FLOW3\MVC\Controller;

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

use \TYPO3\FLOW3\Error\Message;
use \TYPO3\FLOW3\Error\Notice;
use \TYPO3\FLOW3\Error\Warning;
use \TYPO3\FLOW3\Error\Error;

/**
 * Flash Message
 * @see \TYPO3\FLOW3\MVC\Controller\FlashMessageContainer
 *
 * @api
 */
class FlashMessage {

	const SEVERITY_NOTICE = 'Notice';
	const SEVERITY_INFO = 'Information';
	const SEVERITY_OK = 'Ok';
	const SEVERITY_WARNING = 'Warning';
	const SEVERITY_ERROR = 'Error';

	/**
	 * Severity of this flash message (One of the SEVERITY_* constants)
	 * @var string
	 */
	protected $severity;

	/**
	 * @var string
	 */
	protected $messageTitle = '';

	/**
	 * @var \TYPO3\FLOW3\Error\Message
	 */
	protected $message;

	/**
	 * @param \TYPO3\FLOW3\Error\Message $message message body
	 * @param string $messageTitle optional message title
	 * @param string $severity one of the SEVERITY_* constants
	 */
	public function __construct(Message $message, $messageTitle = '', $severity = self::SEVERITY_OK) {
		$this->message = $message;
		$this->messageTitle = $messageTitle;
		$this->severity = $severity;
	}

	/**
	 * @return string
	 */
	public function getMessageTitle() {
		return $this->messageTitle;
	}

	/**
	 * @return boolean TRUE if the message title is not empty
	 */
	public function hasMessageTitle() {
		return strlen($this->messageTitle) > 0;
	}

	/**
	 * @return string
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * @return \TYPO3\FLOW3\Error\Message
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Renders the body of this flash message
	 * @see \TYPO3\FLOW3\Error\Message::render()
	 * @return string
	 */
	public function renderMessage() {
		return $this->message->render();
	}

	/**
	 * Renders the body of this flash message
	 * @return string
	 * @see renderMessage()
	 */
	public function __toString() {
		return $this->renderMessage();
	}
}
?>