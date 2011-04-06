<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web;

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

use \F3\FLOW3\Property\DataType\Uri;

/**
 * Represents a web request.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Request extends \F3\FLOW3\MVC\Request {

	/**
	 * Contains the request method
	 * @var string
	 */
	protected $method = 'GET';

	/**
	 * The request URI
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $requestUri;

	/**
	 * The base URI for this request - ie. the host and path leading to which all FLOW3 URI paths are relative
	 *
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $baseUri;

	/**
	 * Sets the Request URI
	 *
	 * @param \F3\FLOW3\Property\DataType\Uri $requestUri
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequestUri(Uri $requestUri) {
		$this->requestUri = $requestUri;
	}

	/**
	 * Returns the request URI
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRequestUri() {
		return $this->requestUri;
	}

	/**
	 * Sets the Base URI
	 *
	 * @param \F3\FLOW3\Property\DataType\Uri $baseUri
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseUri(Uri $baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * Returns the base URI
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\MVC\Exception\InvalidRequestMethodException if the request method is not supported
	 * @api
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new \F3\FLOW3\MVC\Exception\InvalidRequestMethodException('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Returns the the request path relative to the base URI
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRoutePath() {
		return substr($this->requestUri->getPath(), strlen($this->baseUri->getPath()));
	}

}
?>