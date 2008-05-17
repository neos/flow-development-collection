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

require_once(dirname(__FILE__) . '/../../Fixtures/F3_FLOW3_Fixture_MVC_MockRequestHandlingController.php');

/**
 * Testcase for the MVC Request Handling Controller
 *
 * @package		FLOW3
 * @version 	$Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_RequestHandlingControllerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlySupportedRequestTypesAreAccepted() {
		$request = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Response');
		$controller = new F3_FLOW3_Fixture_MVC_MockRequestHandlingController($this->componentManager, $this->componentManager->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->supportedRequestTypes = array('F3_Something_Request');

		try {
			$controller->processRequest($request, $response);
			$this->fail('The request handling controller accepted an unsupported request type.');
		} catch (F3_FLOW3_MVC_Exception_UnsupportedRequestType $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentsReturnsAnArgumentsObject() {
		$controller = $this->componentManager->getComponent('F3_FLOW3_MVC_Controller_RequestHandlingController');
		$this->assertType('F3_FLOW3_MVC_Controller_Arguments', $controller->getArguments(), 'getArguments() did not return an arguments object.');
	}
}
?>