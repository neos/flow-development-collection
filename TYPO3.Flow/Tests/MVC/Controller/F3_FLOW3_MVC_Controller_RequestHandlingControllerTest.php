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
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

require_once(__DIR__ . '/../Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockRequestHandling.php');

/**
 * Testcase for the MVC Request Handling Controller
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_RequestHandlingControllerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function onlySupportedRequestTypesAreAccepted() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');
		$controller = new F3_FLOW3_MVC_Fixture_Controller_MockRequestHandling($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
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
		$mockArguments = $this->getMock('F3_FLOW3_MVC_Controller_Arguments', array(), array(), '', FALSE);
		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface', array('getComponent'));
		$mockComponentFactory->expects($this->once())->method('getComponent')->will($this->returnValue($mockArguments));
		$mockPackageManager = $this->getMock('F3_FLOW3_Package_ManagerInterface');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($mockComponentFactory, $mockPackageManager);
		$this->assertType('F3_FLOW3_MVC_Controller_Arguments', $controller->getArguments(), 'getArguments() did not return an arguments object.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processRequestSetsTheDispatchedFlagOfTheRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$this->assertFalse($request->isDispatched());
		$controller->processRequest($request, $response);
		$this->assertTrue($request->isDispatched());
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_MVC_Exception_StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardThrowsAStopActionException() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$controller->processRequest($request, $response);
		$controller->forward('default');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardResetsTheDispatchedFlagOfTheRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$controller->processRequest($request, $response);
		$this->assertTrue($request->isDispatched());
		try {
			$controller->forward('default');
		} catch(F3_FLOW3_MVC_Exception_StopAction $exception) {
		}
		$this->assertFalse($request->isDispatched());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function forwardSetsTheSpecifiedControllerActionAndArgumentsInToTheRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$controller->processRequest($request, $response);
		try {
			$controller->forward('some', 'Alternative', 'TestPackage');
		} catch(F3_FLOW3_MVC_Exception_StopAction $exception) {
		}

		$this->assertEquals('some', $request->getControllerActionName());
		$this->assertEquals('Alternative', $request->getControllerName());
		$this->assertEquals('TestPackage', $request->getControllerPackageKey());
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_MVC_Exception_StopAction
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function redirectThrowsAStopActionException() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$controller = new F3_FLOW3_MVC_Controller_RequestHandlingController($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$controller->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$controller->processRequest($request, $response);
		$controller->redirect('http://typo3.org');
	}
}
?>