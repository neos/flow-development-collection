<?php
namespace TYPO3\FLOW3\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Mvc\ResponseInterface;

/**
 * Represents a HTTP Response
 *
 * @api
 */
class Response implements ResponseInterface{

	/**
	 * @var \TYPO3\FLOW3\Http\Headers
	 */
	protected $headers;

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var \TYPO3\FLOW3\Http\Response
	 */
	protected $parentResponse;

	/**
	 * The HTTP status code
	 * @var integer
	 */
	protected $statusCode = 200;

	/**
	 * The HTTP status message
	 * @var string
	 */
	protected $statusMessage = 'OK';

	/**
	 * @var string
	 */
	protected $charset = 'UTF-8';

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
		418 => 'Sono Vibiemme',
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
	 * Construct this Response
	 *
	 * @param Response $parentResponse
	 */
	public function __construct(Response $parentResponse = NULL) {
		$this->headers = new Headers();
		$this->headers->set('X-FLOW3-Powered', 'FLOW3/' . FLOW3_VERSION_BRANCH);
		$this->headers->set('Content-Type', 'text/html; charset=' . $this->charset);
		$this->parentResponse = $parentResponse;
	}

	/**
	 * Return the parent response or NULL if none exists.
	 *
	 * @return Response the parent response, or NULL if none
	 */
	public function getParentResponse() {
		return $this->parentResponse;
	}

	/**
	 * Overrides and sets the content of the response
	 *
	 * @param string $content The response content
	 * @return void
	 * @api
	 */
	public function setContent($content) {
		$this->content = $content;
	}

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return void
	 * @api
	 */
	public function appendContent($content) {
		$this->content .= $content;
	}

	/**
	 * Returns the response content without sending it.
	 *
	 * @return string The response content
	 * @api
	 */
	public function getContent() {
		return $this->content;
	}
	/**
	 * Sets the HTTP status code and (optionally) a customized message.
	 *
	 * @param integer $code The status code
	 * @param string $message If specified, this message is sent instead of the standard message
	 * @return void
	 * @throws \InvalidArgumentException if the specified status code is not valid
	 * @api
	 */
	public function setStatus($code, $message = NULL) {
		if ($this->parentResponse !== NULL) {
			$this->parentResponse->setStatus($code, $message);
		} else {
			if (!is_int($code)) {
				throw new \InvalidArgumentException('The HTTP status code must be of type integer, ' . gettype($code) . ' given.', 1220526013);
			}
			if ($message === NULL && !isset($this->statusMessages[$code])) {
				throw new \InvalidArgumentException('No message found for HTTP status code "' . $code . '".', 1220526014);
			}
			$this->statusCode = $code;
			$this->statusMessage = ($message === NULL) ? $this->statusMessages[$code] : $message;
		}
	}

	/**
	 * Returns status code and status message.
	 *
	 * @return string The status code and status message, eg. "404 Not Found"
	 * @api
	 */
	public function getStatus() {
		if ($this->parentResponse !== NULL) {
			return $this->parentResponse->getStatus();
		}
		return $this->statusCode . ' ' . $this->statusMessage;
	}

	/**
	 * Returns the status code.
	 *
	 * @return integer The status code, eg. 404
	 * @api
	 */
	public function getStatusCode() {
		if ($this->parentResponse !== NULL) {
			return $this->parentResponse->getStatusCode();
		}
		return $this->statusCode;
	}

	/**
	 * Sets the character set for this response.
	 *
	 * If the content type of this response is a text/* media type, the character
	 * set in the respective Content-Type header will be updated by this method.
	 *
	 * @param string $charset A valid IANA character set identifier
	 * @return void
	 * @see http://www.iana.org/assignments/character-sets
	 * @api
	 */
	public function setCharset($charset) {
		$this->charset = $charset;

		$contentType = $this->getHeader('Content-Type');
		if (stripos($contentType, 'text/') === 0) {
			$matches = array();
			if (preg_match('/(?P<contenttype>.*); ?charset[^;]+(?P<extra>;.*)?/iu', $contentType, $matches)) {
				$contentType = $matches['contenttype'];
			}
			$contentType .= '; charset=' . $this->charset . (isset($matches['extra']) ? $matches['extra'] : '');
			$this->setHeader('Content-Type', $contentType, TRUE);
		}
	}

	/**
	 * Returns the character set of this response.
	 *
	 * Note that the default character in FLOW3 is UTF-8.
	 *
	 * @return string An IANA character set identifier
	 * @api
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Sets the specified HTTP header
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param mixed $value The value of the given header
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function setHeader($name, $value, $replaceExistingHeader = TRUE) {
		switch ($name) {
			case 'Content-Type' :
				if (stripos($value, 'charset') === FALSE && stripos($value, 'text/') === 0) {
					$value .= '; charset=' . $this->charset;
				}
			break;
		}

		if ($this->parentResponse !== NULL) {
			$this->parentResponse->setHeader($name, $value, $replaceExistingHeader);
		} else {
			if (strtoupper(substr($name, 0, 4)) === 'HTTP') throw new \InvalidArgumentException('The "HTTP" status header must be set via setStatus().', 1220541963);
			$this->headers->set($name, $value, $replaceExistingHeader);
		}
	}

	/**
	 * Returns the HTTP headers - including the status header - of this web response
	 *
	 * @return array The HTTP headers
	 * @api
	 */
	public function getHeaders() {
		if ($this->parentResponse !== NULL) {
			return $this->parentResponse->getHeaders();
		}
		$preparedHeaders = array();
		$statusHeader = 'HTTP/1.1 ' . $this->statusCode . ' ' . $this->statusMessage;

		$preparedHeaders[] = $statusHeader;
		foreach ($this->headers->getAll() as $name => $values) {
			foreach ($values as $value) {
				$preparedHeaders[] = $name . ': ' . $value;
			}
		}

		return $preparedHeaders;
	}

	/**
	 * Returns the value(s) of the specified header
	 *
	 * If one such header exists, the value is returned as a single string.
	 * If multiple headers of that name exist, the values are returned as an array.
	 * If no such header exists, NULL is returned.
	 *
	 * @param string $name Name of the header
	 * @return array|string An array of field values if multiple headers of that name exist, a string value if only one value exists and NULL if there is no such header.
	 * @api
	 */
	public function getHeader($name) {
		if ($this->parentResponse !== NULL) {
			return $this->parentResponse->getHeader($name);
		}
		return $this->headers->get($name);
	}

	/**
	 * Sends the HTTP headers.
	 *
	 * If headers have been sent previously, this method fails silently.
	 *
	 * @return void
	 * @codeCoverageIgnore
	 * @api
	 */
	public function sendHeaders() {
		if ($this->parentResponse !== NULL) {
			$this->sendHeaders();
			return;
		}
		if (headers_sent() === TRUE) {
			return;
		}
		foreach ($this->getHeaders() as $header) {
			header($header);
		}
		foreach ($this->headers->getCookies() as $cookie) {
			setcookie($cookie->getName(), $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), $cookie->isHttpOnly());
		}
	}

	/**
	 * Renders and sends the whole web response
	 *
	 * @return void
	 * @codeCoverageIgnore
	 * @api
	 */
	public function send() {
		if ($this->parentResponse !== NULL) {
			$this->parentResponse->send();
			return;
		}
		$this->sendHeaders();
		if ($this->content !== NULL) {
			echo $this->getContent();
		}
	}

	/**
	 * Cast the response to a string: return the content part of this response
	 *
	 * @return string The same as getContent()
	 * @api
	 */
	public function __toString() {
		return $this->getContent();
	}

}
?>