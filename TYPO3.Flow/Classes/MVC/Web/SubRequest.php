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
 * Represents a web sub request (used in plugins for example)
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class SubRequest extends \F3\FLOW3\MVC\Web\Request {

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $parentRequest;

	/**
	 * @var string
	 */
	protected $argumentNamespace = '';

	/**
	 * @param \F3\FLOW3\MVC\Web\Request $parentRequest
	 */
	public function __construct(\F3\FLOW3\MVC\Web\Request $parentRequest) {
		$this->parentRequest = $parentRequest;
	}

	/**
	 * @return \F3\FLOW3\MVC\Web\Request
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
	 * @param \F3\FLOW3\Property\DataType\Uri $requestUri
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRequestUri(Uri $requestUri) {
		$this->parentRequest->setRequestUri($requestUri);
	}

	/**
	 * Returns the parent request URI
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri URI of the parent web request
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getRequestUri() {
		return $this->parentRequest->getRequestUri();
	}

	/**
	 * Sets the Base URI of the parent request
	 *
	 * @param \F3\FLOW3\Property\DataType\Uri $baseUri
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setBaseUri(Uri $baseUri) {
		$this->parentRequest->setBaseUri($baseUri);
	}

	/**
	 * Returns the base URI of the parent request
	 *
	 * @return \F3\FLOW3\Property\DataType\Uri URI of the parent web request
	 * @author Bastian Waidelich <bastian@typo3.org>
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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setMethod($method) {
		$this->parentRequest->setMethod($method);
	}

	/**
	 * Returns the name of the parent request method
	 *
	 * @return string Name of the parent request method
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getMethod() {
		return $this->parentRequest->getMethod();
	}

	/**
	 * Returns the the parent request path relative to the base URI
	 *
	 * @return string
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getRoutePath() {
		return $this->parentRequest->getRoutePath();
	}

	/**
	 * Return the top most parent request
	 *
	 * @return \F3\FLOW3\MVC\Web\Request
	 * @author Lienhart Woitok <lienhart.woitok@netlogix.de>
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