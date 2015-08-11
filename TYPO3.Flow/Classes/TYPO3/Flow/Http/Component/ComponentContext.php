<?php
namespace TYPO3\Flow\Http\Component;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;

/**
 * The component context
 *
 * An instance of this class will be passed to each component of the chain allowing them to read/write parameters to/from it.
 * Besides handling of the chain is interrupted as soon as the "cancelled" flag is set.
 *
 * @api
 */
class ComponentContext {

	/**
	 * The current HTTP request
	 *
	 * @var Request
	 */
	protected $httpRequest;

	/**
	 * The current HTTP response
	 *
	 * @var Response
	 */
	protected $httpResponse;

	/**
	 * Two-dimensional array storing an parameter dictionary (containing variables that can be read/written by all components)
	 * The first dimension is the fully qualified Component name, the second dimension is the identifier for the parameter.
	 *
	 * @var array
	 */
	protected $parameters = array();

	/**
	 * @param Request $httpRequest
	 * @param Response $httpResponse
	 */
	public function __construct(Request $httpRequest, Response $httpResponse) {
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
	}

	/**
	 * @return Request
	 * @api
	 */
	public function getHttpRequest() {
		return $this->httpRequest;
	}

	/**
	 * @param Request $httpRequest
	 * @return void
	 * @api
	 */
	public function replaceHttpRequest(Request $httpRequest) {
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @return Response
	 * @api
	 */
	public function getHttpResponse() {
		return $this->httpResponse;
	}

	/**
	 * @param Response $httpResponse
	 * @return void
	 * @api
	 */
	public function replaceHttpResponse(Response $httpResponse) {
		$this->httpResponse = $httpResponse;
	}

	/**
	 * @param string $componentClassName
	 * @param string $parameterName
	 * @return mixed
	 * @api
	 */
	public function getParameter($componentClassName, $parameterName) {
		return isset($this->parameters[$componentClassName][$parameterName]) ? $this->parameters[$componentClassName][$parameterName] : NULL;
	}

	/**
	 * @param string $componentClassName
	 * @param string $parameterName
	 * @param mixed $value
	 * @api
	 */
	public function setParameter($componentClassName, $parameterName, $value) {
		if (!isset($this->parameters[$componentClassName])) {
			$this->parameters[$componentClassName] = array();
		}
		$this->parameters[$componentClassName][$parameterName] = $value;
	}

}