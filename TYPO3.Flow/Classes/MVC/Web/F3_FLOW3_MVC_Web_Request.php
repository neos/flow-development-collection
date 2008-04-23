<?php
declare(ENCODING = 'utf-8');

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
 * @version $Id:F3_FLOW3_MVC_Web_Request.php 467 2008-02-06 19:34:56Z robert $
 */

/**
 * Represents a web request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_MVC_Web_Request.php 467 2008-02-06 19:34:56Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class F3_FLOW3_MVC_Web_Request extends F3_FLOW3_MVC_Request {

	/**
	 * @var F3_FLOW3_Utility_Environment
	 */
	protected $environment;

	/**
	 * @var F3_FLOW3_Property_DataType_URI The request URI
	 */
	protected $requestURI;

	/**
	 * @var F3_FLOW3_Property_DataType_URI The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;

	/**
	 * Injects the environment
	 *
	 * @param F3_FLOW3_Utility_Environment $environment
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectEnvironment(F3_FLOW3_Utility_Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the request URI
	 *
	 * @param F3_FLOW3_Property_DataType_URI $requestURI URI of this web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequestURI(F3_FLOW3_Property_DataType_URI $requestURI) {
		$this->requestURI = clone $requestURI;
		$this->baseURI = $this->detectBaseURI($requestURI);
	}

	/**
	 * Returns the request URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}

	/**
	 * Sets the base URI for this request.
	 *
	 * @param F3_FLOW3_Property_DataType_URI $baseURI New base URI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseURI(F3_FLOW3_Property_DataType_URI $baseURI) {
		$this->baseURI = clone $baseURI;
	}

	/**
	 * Returns the base URI
	 *
	 * @return F3_FLOW3_Property_DataType_URI Base URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURI() {
		return $this->baseURI;
	}

	/**
	 * Tries to detect the base URI of this request and returns it.
	 *
	 * @param F3_FLOW3_Property_DataType_URI $requestURI URI of this web request
	 * @return F3_FLOW3_Property_DataType_URI The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo externalize this method into a strategy
	 */
	protected function detectBaseURI(F3_FLOW3_Property_DataType_URI $requestURI) {
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