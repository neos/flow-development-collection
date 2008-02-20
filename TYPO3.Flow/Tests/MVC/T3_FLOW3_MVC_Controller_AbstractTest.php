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
 * Testcase for the MVC Abstract Controller
 * 
 * @package		FLOW3
 * @version 	$Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Controller_AbstractTest extends T3_Testing_BaseTestCase {
	
	/**
	 * Checks if the TestPackage controller handles a web request
	 * 
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestCanProcessWebRequest() {
		$dispatcher = $this->componentManager->getComponent('T3_FLOW3_MVC_Dispatcher');

		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$request->setControllerName('T3_TestPackage_Controller_Default');
		$request->lock();		

		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Response');
		
		$dispatcher->dispatch($request, $response);		
		$this->assertEquals('TestPackage Default Controller - Web Request.', (string)$response->getContent(), 'The response returned by the TestPackage controller was not as expected.');
	}

	/**
	 * Checks if the TestPackage controller handles a CLI request
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestCanProcessCLIRequest() {
		$dispatcher = $this->componentManager->getComponent('T3_FLOW3_MVC_Dispatcher');

		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_CLI_Request');
		$request->setControllerName('T3_TestPackage_Controller_Default');
		$request->lock();		

		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_CLI_Response');

		$dispatcher->dispatch($request, $response);
		$this->assertEquals('TestPackage Default Controller - CLI Request.', (string)$response->getContent(), 'The response returned by the TestPackage controller was not as expected.');
	}
}
?>