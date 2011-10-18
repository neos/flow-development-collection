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

use \TYPO3\FLOW3\Property\DataType\Uri;


/**
 * Represents a web sub request (used in plugins for example)
 *
 * @api
 */
class SubRequest extends \TYPO3\FLOW3\MVC\Web\Request {

	/**
	 * @var \TYPO3\FLOW3\MVC\Web\Request
	 */
	protected $parentRequest;

	/**
	 * @var string
	 */
	protected $argumentNamespace = '';

	/**
	 * @param \TYPO3\FLOW3\MVC\Web\Request $parentRequest
	 */
	public function __construct(\TYPO3\FLOW3\MVC\Web\Request $parentRequest) {
		$this->parentRequest = $parentRequest;
	}

	/**
	 * @return \TYPO3\FLOW3\MVC\Web\Request
	 */
	public function getParentRequest() {
		return $this->parentRequest;
	}

	/**
	 * @param string $argumentNamespace
	 * @return void
	 */
	public function setArgumentNamespace($argumentNamespace) {
		$this->argumentNamespace = $argumentNamespace;
	}

	/**
	 * @return string
	 */
	public function getArgumentNamespace() {
		return $this->argumentNamespace;
	}

	/**
	 * Sets the Request URI in the parent request
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $requestUri
	 * @return void
	 */
	public function setRequestUri(Uri $requestUri) {
		$this->parentRequest->setRequestUri($requestUri);
	}

	/**
	 * Returns the parent request URI
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI of the parent web request
	 * @api
	 */
	public function getRequestUri() {
		return $this->parentRequest->getRequestUri();
	}

	/**
	 * Sets the Base URI of the parent request
	 *
	 * @param \TYPO3\FLOW3\Property\DataType\Uri $baseUri
	 * @return void
	 */
	public function setBaseUri(Uri $baseUri) {
		$this->parentRequest->setBaseUri($baseUri);
	}

	/**
	 * Returns the base URI of the parent request
	 *
	 * @return \TYPO3\FLOW3\Property\DataType\Uri URI of the parent web request
	 * @api
	 */
	public function getBaseUri() {
		return $this->parentRequest->getBaseUri();
	}

	/**
	 * Sets the parent request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 */
	public function setMethod($method) {
		$this->parentRequest->setMethod($method);
	}

	/**
	 * Returns the name of the parent request method
	 *
	 * @return string Name of the parent request method
	 * @api
	 */
	public function getMethod() {
		return $this->parentRequest->getMethod();
	}

	/**
	 * Returns the the parent request path relative to the base URI
	 *
	 * @return string
	 * @api
	 */
	public function getRoutePath() {
		return $this->parentRequest->getRoutePath();
	}

	/**
	 * Return the top most parent request
	 *
	 * @return \TYPO3\FLOW3\MVC\Web\Request
	 */
	public function getRootRequest() {
		if ($this->parentRequest instanceof SubRequest) {
			return $this->parentRequest->getRootRequest();
		} else {
			return $this->parentRequest;
		}
	}

}
?>