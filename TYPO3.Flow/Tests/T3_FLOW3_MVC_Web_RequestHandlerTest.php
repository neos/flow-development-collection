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
 * Testcase for the MVC Web Request Handler class
 * 
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Web_RequestHandlerTest extends T3_Testing_BaseTestCase {
	
	protected $requestHandler;

	/**
	 * @var T3_FLOW3_Utility_MockEnvironment
	 */
	protected $environment;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->componentManager->setComponentClassName('T3_FLOW3_Utility_Environment', 'T3_FLOW3_Utility_MockEnvironment');
		$this->environment = $this->componentManager->getComponent('T3_FLOW3_Utility_Environment');

			// Inject the mock environment into Builder and Handler:
		$dispatcher = new T3_FLOW3_MVC_Dispatcher($this->componentManager);
		$requestProcessorChainManager = new T3_FLOW3_MVC_RequestProcessorChainManager();
		$requestBuilder = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_RequestBuilder', $this->componentManager, $this->environment);
		$this->requestHandler = new T3_FLOW3_MVC_Web_RequestHandler($this->componentManager, $this->environment, $dispatcher, $requestProcessorChainManager);
	}
	
	/**
	 * Checks if a mock request asking for the TestPackage default controller is handled and dispatched correctly.
	 *
	 * @test 
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function WebRequestHandlerHandlesTestPackageRequestCorrectly() {
		$realRequestURI = $this->environment->getRequestURI();
		$realBaseURI = $this->detectBaseURI($realRequestURI);

		$this->environment->SERVER['REQUEST_URI'] = $realBaseURI->getPath() . 'TestPackage';
		ob_start();
		$this->requestHandler->handleRequest();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('TestPackage Default Controller - Web Request.', $output, 'The web request handler did not handle the request correctly - at least I did not receive the expected output.');
	}

	/**
	 * Tries to detect the base URI of this request and returns it.
	 * 
	 * @param  T3_FLOW3_MVC_URI		$requestURI: URI of this web request
	 * @return T3_FLOW3_MVC_URI		The detected base URI
	 * @author Robert Lemke <robert@typo3.org>
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