<?php
declare(encoding = 'utf-8');

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
 * Represents a web request.
 * 
 * @package		FLOW3
 * @subpackage	MVC
 * @version 	$Id:T3_FLOW3_MVC_Web_Request.php 467 2008-02-06 19:34:56Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * 
 * @scope prototype
 */
class T3_FLOW3_MVC_Web_Request extends T3_FLOW3_MVC_Request {

	/**
	 * @var T3_FLOW3_Utility_Environment
	 */
	protected $environment;
	
	/**
	 * @var T3_FLOW3_MVC_URI The request URI
	 */
	protected $requestURI;
	
	/**
	 * @var T3_FLOW3_MVC_URI The base URI for this request - ie. the host and path leading to the index.php
	 */
	protected $baseURI;	
	
	/**
	 * Constructs the web request
	 *
	 * @param  T3_FLOW3_Utility_Environment	$environment
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(T3_FLOW3_Utility_Environment $environment) {
		parent::__construct();
		$this->environment = $environment;
	}
	
	/**
	 * Sets the request URI
	 * 
	 * @param  T3_FLOW3_MVC_URI		URI of this web request
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRequestURI(T3_FLOW3_MVC_URI $requestURI) {
		$this->requestURI = clone $requestURI;
		$this->baseURI = $this->detectBaseURI($requestURI);
	}
	
	/**
	 * Returns the request URI
	 *
	 * @return T3_FLOW3_MVC_URI		URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}
	
	/**
	 * Sets the base URI for this request.
	 * 
	 * @param  T3_FLOW3_MVC_URI		New base URI
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setBaseURI(T3_FLOW3_MVC_URI $baseURI) {
		$this->baseURI = clone $baseURI;
	}
	
	/**
	 * Returns the base URI
	 * 
	 * @return T3_FLOW3_MVC_URI		Base URI of this web request
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURI() {
		return $this->baseURI;
	}
	
	/**
	 * Tries to detect the base URI of this request and returns it.
	 * 
	 * @param  T3_FLOW3_MVC_URI		$requestURI: URI of this web request
	 * @return T3_FLOW3_MVC_URI		The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo   externalize this method into a strategy
	 */
	protected function detectBaseURI(T3_FLOW3_MVC_URI $requestURI) {
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