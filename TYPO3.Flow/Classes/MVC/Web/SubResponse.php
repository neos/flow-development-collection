<?php
namespace TYPO3\FLOW3\MVC\Web;

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
 * A web specific sub response implementation
 *
 * @api
 */
class SubResponse extends \TYPO3\FLOW3\MVC\Web\Response {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Response
	 */
	protected $parentResponse;

	/**
	 * @param \TYPO3\FLOW3\MVC\Web\Response $parentResponse
	 */
	public function __construct(\TYPO3\FLOW3\MVC\Web\Response $parentResponse) {
		$this->parentResponse = $parentResponse;
	}

	/**
	 * @return \TYPO3\FLOW3\MVC\Web\Response
	 */
	public function getParentResponse() {
		return $this->parentResponse;
	}

	/**
	 * Sets the HTTP status code and (optionally) a customized message in the parent response
	 *
	 * @param integer $code The status code
	 * @param string $message If specified, this message is sent instead of the standard message
	 * @return void
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::setStatus()
	 */
	public function setStatus($code, $message = NULL) {
		$this->parentResponse->setStatus($code, $message);
	}

	/**
	 * Returns status code and status message from the parent response
	 *
	 * @return string The status code and status message, eg. "404 Not Found"
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::getStatus()
	 */
	public function getStatus() {
		return $this->parentResponse->getStatus();
	}

	/**
	 * Sets the specified HTTP header in the parent response
	 *
	 * @param string $name Name of the header, for example "Location", "Content-Description" etc.
	 * @param mixed $value The value of the given header
	 * @param boolean $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
	 * @return void
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::setHeader()
	 */
	public function setHeader($name, $value, $replaceExistingHeader = TRUE) {
		$this->parentResponse->setHeader($name, $value, $replaceExistingHeader);
	}

	/**
	 * Returns the HTTP headers - including the status header - of the parent response
	 *
	 * @return string The HTTP headers
	 * @api
	 * @see \TYPO3\FLOW3\MVC\Web\Response::getHeaders()
	 */
	public function getHeaders() {
		return $this->parentResponse->getHeaders();
	}
}
?>