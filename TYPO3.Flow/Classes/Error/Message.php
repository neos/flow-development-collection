<?php
namespace TYPO3\FLOW3\Error;

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
 * An object representation of a generic message. Usually, you will use Error, Warning or Notice instead of this one.
 *
 * @api
 */
class Message {

	const SEVERITY_NOTICE = 'Notice';
	const SEVERITY_WARNING = 'Warning';
	const SEVERITY_ERROR = 'Error';
	const SEVERITY_OK = 'OK';

	/**
	 * The error message, could also be a key for translation.
	 * @var string
	 */
	protected $message = '';

	/**
	 * An optional title for the message (used eg. in flashMessages).
	 * @var string
	 */
	protected $title = '';

	/**
	 * The error code.
	 * @var integer
	 */
	protected $code = NULL;

	/**
	 * The message arguments. Will be replaced in the message body.
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * The severity of this message ('OK'), overwrite in your own implementation.
	 * @var string
	 */
	protected $severity = self::SEVERITY_OK;

	/**
	 * Constructs this error
	 *
	 * @param string $message An english error message which is used if no other error message can be resolved
	 * @param integer $code A unique error code
	 * @param array $arguments Array of arguments to be replaced in message
	 * @param string $title optional title for the message
	 * @api
	 */
	public function __construct($message, $code = NULL, array $arguments = array(), $title = '') {
		$this->message = $message;
		$this->code = $code;
		$this->arguments = $arguments;
		$this->title = $title;
	}

	/**
	 * Returns the error message
	 * @return string The error message
	 * @api
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * Returns the error code
	 * @return integer The error code
	 * @api
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return array
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return string
	 * @api
	 */
	public function getSeverity() {
		return $this->severity;
	}

	/**
	 * @return string
	 */
	public function render() {
		if ($this->arguments !== array()) {
			return vsprintf($this->message, $this->arguments);
		} else {
			return $this->message;
		}
	}

	/**
	 * Converts this error into a string
	 *
	 * @return string
	 * @api
	 */
	public function __toString() {
		return $this->render();
	}
}

?>