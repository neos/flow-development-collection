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

/**
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Represents a web request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 *
 * @scope prototype
 */
class Request extends \F3\FLOW3\MVC\Request {

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'html';

	/**
	 * @var string Contains the request method
	 */
	protected $method = 'GET';

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Property\DataType\URI The request URI
	 */
	protected $requestURI;

	/**
	 * @var \F3\FLOW3\Property\DataType\URI The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the request method
	 *
	 * @param string $method Name of the request method
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\MVC\Exception\InvalidRequestMethod if the request method is not supported
	 */
	public function setMethod($method) {
		if ($method === '' || (strtoupper($method) !== $method)) throw new \F3\FLOW3\MVC\Exception\InvalidRequestMethod('The request method "' . $method . '" is not supported.', 1217778382);
		$this->method = $method;
	}

	/**
	 * Returns the name of the request method
	 *
	 * @return string Name of the request method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethod() {
		return $this->method;
	}

	/**
	 * Sets the request URI
	 *
	 * @param \F3\FLOW3\Property\DataType\URI $requestURI URI of this web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequestURI(\F3\FLOW3\Property\DataType\URI $requestURI) {
		$this->requestURI = clone $requestURI;
		$this->baseURI = $this->detectBaseURI($requestURI);
	}

	/**
	 * Returns the request URI
	 *
	 * @return \F3\FLOW3\Property\DataType\URI URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param \F3\FLOW3\Property\DataType\URI $baseURI New base URI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseURI(\F3\FLOW3\Property\DataType\URI $baseURI) {
		$this->baseURI = clone $baseURI;
	}

	/**
	 * Returns the base URI
	 *
	 * @return \F3\FLOW3\Property\DataType\URI Base URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURI() {
		return $this->baseURI;
	}

	/**
	 * Tries to detect the base URI of this request and returns it.
	 *
	 * @param \F3\FLOW3\Property\DataType\URI $requestURI URI of this web request
	 * @return \F3\FLOW3\Property\DataType\URI The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function detectBaseURI(\F3\FLOW3\Property\DataType\URI $requestURI) {
		$baseURI = clone $requestURI;
		$baseURI->setQuery(NULL);
		$baseURI->setFragment(NULL);

		$requestPathSegments = explode('/', $this->environment->getScriptRequestPathAndName());
		array_pop($requestPathSegments);
		$baseURI->setPath(implode('/', $requestPathSegments) . '/');
		return $baseURI;
	}
}
?>