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
class Response extends Message implements ResponseInterface{

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
	 * The current point in time, used for comparisons
	 * @var \DateTime
	 */
	protected $now;

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
	 * @param \TYPO3\FLOW3\Http\Response $parentResponse
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
	 * @return \TYPO3\FLOW3\Http\Response the parent response, or NULL if none
	 */
	public function getParentResponse() {
		return $this->parentResponse;
	}

	/**
	 * Appends content to the already existing content.
	 *
	 * @param string $content More response content
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function appendContent($content) {
		$this->content .= $content;
		return $this;
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
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @throws \InvalidArgumentException if the specified status code is not valid
	 * @api
	 */
	public function setStatus($code, $message = NULL) {
		if (!is_int($code)) {
			throw new \InvalidArgumentException('The HTTP status code must be of type integer, ' . gettype($code) . ' given.', 1220526013);
		}
		if ($message === NULL && !isset($this->statusMessages[$code])) {
			throw new \InvalidArgumentException('No message found for HTTP status code "' . $code . '".', 1220526014);
		}
		$this->statusCode = $code;
		$this->statusMessage = ($message === NULL) ? $this->statusMessages[$code] : $message;
		return $this;
	}

	/**
	 * Returns status code and status message.
	 *
	 * @return string The status code and status message, eg. "404 Not Found"
	 * @api
	 */
	public function getStatus() {
		return $this->statusCode . ' ' . $this->statusMessage;
	}

	/**
	 * Returns the status code.
	 *
	 * @return integer The status code, eg. 404
	 * @api
	 */
	public function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * Sets the current point in time.
	 *
	 * This date / time is used internally for comparisons in order to determine the
	 * freshness of this response. By default this DateTime object is set automatically
	 * through dependency injection, configured in the Objects.yaml of the FLOW3 package.
	 *
	 * Unless you are mocking the current time in a test, there is probably no need
	 * to use this function. Also note that this method must be called before any
	 * of the Response methods are used and it must not be called a second time.
	 *
	 * @param \DateTime $now The current point in time
	 * @return
	 * @api
	 */
	public function setNow(\DateTime $now) {
		$this->now = clone $now;
		$this->now->setTimezone(new \DateTimeZone('UTC'));
		$this->headers->set('Date', $this->now);
	}

	/**
	 * Sets the Date header.
	 *
	 * The given date must either be an RFC2822 parseable date string or a DateTime
	 * object. The timezone will be converted to GMT internally, but the point in
	 * time remains the same.
	 *
	 * @param string|\DateTime $date
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setDate($date) {
		$this->headers->set('Date', $date);
		return $this;
	}

	/**
	 * Returns the date from the Date header.
	 *
	 * The returned date is configured to be in the GMT timezone.
	 *
	 * @return \DateTime The date of this response
	 * @api
	 */
	public function getDate() {
		return $this->headers->get('Date');
	}

	/**
	 * Sets the Last-Modified header.
	 *
	 * The given date must either be an RFC2822 parseable date string or a DateTime
	 * object. The timezone will be converted to GMT internally, but the point in
	 * time remains the same.
	 *
	 * @param string|\DateTime $date
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setLastModified($date) {
		$this->headers->set('Last-Modified', $date);
		return $this;
	}

	/**
	 * Returns the date from the Last-Modified header or NULL if no such header
	 * is present.
	 *
	 * The returned date is configured to be in the GMT timezone.
	 *
	 * @return \DateTime The last modification date or NULL
	 * @api
	 */
	public function getLastModified() {
		return $this->headers->get('Last-Modified');
	}

	/**
	 * Sets the Expires header.
	 *
	 * The given date must either be an RFC2822 parseable date string or a DateTime
	 * object. The timezone will be converted to GMT internally, but the point in
	 * time remains the same.
	 *
	 * In order to signal that the response has already expired, the date should
	 * be set to the same date as the Date header (that is, $now). To communicate
	 * an infinite expiration time, the date should be set to one year in the future.
	 *
	 * Expiration times should not be more than one year in the future, according
	 * to RFC 2616 / 14.21
	 *
	 * @param string|\DateTime $date
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setExpires($date) {
		$this->headers->set('Expires', $date);
		return $this;
	}

	/**
	 * Returns the date from the Expires header or NULL if no such header
	 * is present.
	 *
	 * The returned date is configured to be in the GMT timezone.
	 *
	 * @return \DateTime The expiration date or NULL
	 * @api
	 */
	public function getExpires() {
		return $this->headers->get('Expires');
	}

	/**
	 * Returns the age of this responds in seconds.
	 *
	 * The age is determined either by an explicitly set Age header or by the
	 * difference between Date and "now".
	 *
	 * Note that, according to RFC 2616 / 13.2.3, the presence of an Age header implies
	 * that the response is not first-hand. You should therefore only explicitly set
	 * an Age header if this is the case.
	 *
	 * @return integer The age in seconds
	 * @api
	 */
	public function getAge() {
		if ($this->headers->has('Age')) {
			return $this->headers->get('Age');
		} else {
			$dateTimestamp = $this->headers->get('Date')->getTimestamp();
			$nowTimestamp = $this->now->getTimestamp();
			return ($nowTimestamp > $dateTimestamp) ? ($nowTimestamp - $dateTimestamp) : 0;
		}
	}

	/**
	 * Sets the maximum age in seconds before this response becomes stale.
	 *
	 * This method sets the "max-age" directive in the Cache-Control header.
	 *
	 * @param integer $age The maximum age in seconds
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setMaximumAge($age) {
		$this->headers->setCacheControlDirective('max-age', $age);
		return $this;
	}

	/**
	 * Returns the maximum age in seconds before this response becomes stale.
	 *
	 * This method returns the value from the "max-age" directive in the
	 * Cache-Control header.
	 *
	 * @return integer The maximum age in seconds, or NULL if none has been defined
	 * @api
	 */
	public function getMaximumAge() {
		return $this->headers->getCacheControlDirective('max-age');
	}

	/**
	 * Sets the maximum age in seconds before this response becomes stale in shared
	 * caches, such as proxies.
	 *
	 * This method sets the "s-maxage" directive in the Cache-Control header.
	 *
	 * @param integer $maximumAge The maximum age in seconds
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setSharedMaximumAge($maximumAge) {
		$this->headers->setCacheControlDirective('s-maxage', $maximumAge);
		return $this;
	}

	/**
	 * Returns the maximum age in seconds before this response becomes stale in shared
	 * caches, such as proxies.
	 *
	 * This method returns the value from the "s-maxage" directive in the
	 * Cache-Control header.
	 *
	 * @return integer The maximum age in seconds, or NULL if none has been defined
	 * @api
	 */
	public function getSharedMaximumAge() {
		return $this->headers->getCacheControlDirective('s-maxage');
	}

	/**
	 * Renders the HTTP headers - including the status header - of this response
	 *
	 * @return array The HTTP headers
	 * @api
	 */
	public function renderHeaders() {
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
	 * Sets the respective directive in the Cache-Control header.
	 *
	 * A response flagged as "public" may be cached by any cache, even if it normally
	 * wouldn't be cacheable in a shared cache.
	 *
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setPublic() {
		$this->headers->setCacheControlDirective('public');
		return $this;
	}

	/**
	 * Sets the respective directive in the Cache-Control header.
	 *
	 * A response flagged as "private" tells that it is intended for a specific
	 * user and must not be cached by a shared cache.
	 *
	 * @return \TYPO3\FLOW3\Http\Response This response, for method chaining
	 * @api
	 */
	public function setPrivate() {
		$this->headers->setCacheControlDirective('private');
		return $this;
	}

	/**
	 * Analyzes this response, considering the given request and makes additions
	 * or removes certain headers in order to make the response compliant to
	 * RFC 2616 and related standards.
	 *
	 * It is recommended to call this method before the response is sent and FLOW3
	 * does so by default in its built-in HTTP request handler.
	 *
	 * @param \TYPO3\FLOW3\Http\Request $request The corresponding request
	 * @return void
	 * @api
	 */
	public function makeStandardsCompliant(Request $request) {
		if ($request->hasHeader('If-Modified-Since') && $this->headers->has('Last-Modified') && $this->statusCode === 200) {
			$ifModifiedSinceDate = $request->getHeader('If-Modified-Since');
			$lastModifiedDate = $this->headers->get('Last-Modified');
			if ($lastModifiedDate <= $ifModifiedSinceDate) {
				$this->setStatus(304);
				$this->content = '';
			}
		} elseif ($request->hasHeader('If-Unmodified-Since') && $this->headers->has('Last-Modified')
				&& (($this->statusCode >= 200 && $this->statusCode <= 299) || $this->statusCode === 412)) {
			$unmodifiedSinceDate = $request->getHeader('If-Unmodified-Since');
			$lastModifiedDate = $this->headers->get('Last-Modified');
			if ($lastModifiedDate > $unmodifiedSinceDate) {
				$this->setStatus(412);
			}
		}

		if (in_array($this->statusCode, array(100, 101, 204, 304))) {
			$this->content = '';
		}

		if ($this->headers->getCacheControlDirective('no-cache') !== NULL
				|| $this->headers->has('Expires')) {
			$this->headers->removeCacheControlDirective('max-age');
		}

		if ($request->getMethod() === 'HEAD') {
			if (!$this->headers->has('Content-Length')) {
				$this->headers->set('Content-Length', strlen($this->content));
			}
			$this->content = '';
		}

		if (!$this->headers->has('Content-Length')) {
			$this->headers->set('Content-Length', strlen($this->content));
		}

		if ($this->headers->has('Transfer-Encoding')) {
			$this->headers->remove('Content-Length');
		}
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
		if (headers_sent() === TRUE) {
			return;
		}
		foreach ($this->renderHeaders() as $header) {
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