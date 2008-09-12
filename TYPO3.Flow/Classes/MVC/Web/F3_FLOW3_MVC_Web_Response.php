<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web;

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
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Web::Response.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * A web specific response implementation
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::MVC::Web::Response.php 467 2008-02-06 19:34:56Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Response extends F3::FLOW3::MVC::Response {

	/**
	 * The HTTP headers which will be sent in the response
	 *
	 * @var array
	 */
	protected $headers = array();

	/**
	 * The HTTP status code
	 *
	 * @var integer
	 */
	protected $statusCode = 200;

	/**
	 * The HTTP status message
	 *
	 * @var string
	 */
	protected $statusMessage = 'OK';

	/**
	 * The standardized and other important HTTP Status messages
	 *
	 * @var array
	 */
	protected $statusMessages = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing', # RFC 2518
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded',
	);

	/**
	 * Sets the HTTP status code and (optionally) a customized message.
	 *
	 * @param integer $code The status code
	 * @param string $message If specified, this message is sent instead of the standard message
	 * @return void
	 * @throws InvalidArgumentException if the specified status code is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setStatus($code, $message = NULL) {
		if (!is_int($code)) throw new InvalidArgumentException('The HTTP status code must be of type integer, ' . gettype($code) . ' given.', 1220526013);
		if ($message === NULL && !key_exists($code, $this->statusMessages)) throw new InvalidArgumentException('No message found for HTTP status code "' . $code . '".', 1220526014);

		$this->statusCode = $code;
		$this->statusMessage = ($message === NULL) ? $this->statusMessages[$code] : $message;
	}

	/**
	 * Returns status code and status message.
	 *
	 * @return string The status code and status message, eg. "404 Not Found"
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStatus() {
		return $this->statusCode . ' ' . $this->statusMessage;
	}

	/**
	 * Sets the specified HTTP header
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param mixed $value The value of the given header
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHeader($name, $value, $replaceExistingHeader = TRUE) {
		if (strtoupper(substr($name, 0, 4)) == 'HTTP') throw new InvalidArgumentException('The HTTP status header must be set via setStatus().', 1220541963);
		if ($replaceExistingHeader === TRUE || !key_exists($name, $this->headers)) {
			$this->headers[$name] = array($value);
		} else {
			$this->headers[$name][] = $value;
		}
	}

	/**
	 * Returns the HTTP headers - including the status header - of this web response
	 *
	 * @return string The HTTP headers
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getHeaders() {
		$preparedHeaders = array();
		$statusHeader = 'HTTP/1.1 ' . $this->statusCode . ' ' . $this->statusMessage;

		$preparedHeaders[] = $statusHeader;
		foreach ($this->headers as $name => $values) {
			foreach ($values as $value) {
				$preparedHeaders[] = $name . ': ' . $value;
			}
		}
		return $preparedHeaders;
	}

	/**
	 * Sends the HTTP headers.
	 *
	 * If headers have already been sent, this method fails silently.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function sendHeaders() {
		if (headers_sent() === TRUE) return;
		foreach ($this->getHeaders() as $header) {
			header($header);
		}
	}

	/**
	 * Renders and sends the whole web response
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function send() {
		$this->sendHeaders();
		if ($this->content !== NULL) {
			echo $this->getContent();
		}
	}
}
?>