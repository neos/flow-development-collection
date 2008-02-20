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

require_once(dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_MVC_MockActionController.php');

/**
 * Testcase for the MVC Action Controller
 *
 * @package   FLOW3
 * @version   $Id:T3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license   http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_Controller_ActionControllerTest extends T3_Testing_BaseTestCase {

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo Currently this doesn't really check if someAction was called because I could get this feature of PHPunit to work
	 * @test
	 */
	public function processRequestCallsActionMethodAccordingToRequestObject() {
		$mockController = $this->getMock('T3_FLOW3_MVC_Controller_ActionController', array('someAction'), array($this->componentManager, $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface')), '');
		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Response');

		$request->setActionName('some');
		$mockController->processRequest($request, $response);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringsReturnedByActionMethodAreAppendedToResponseObject() {
		$mockController = new T3_FLOW3_Fixture_MVC_MockActionController($this->componentManager, $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface'));
		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Response');

		$request->setActionName('returnSomeString');
		$mockController->processRequest($request, $response);
		$this->assertEquals('Mock Action Controller Return String', $response->getContent(), 'The response object did not contain the string returned by the action controller');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function ifNoViewCouldBeResolvedAnEmptyViewIsProvided() {
		$mockController = $this->getMock('T3_FLOW3_MVC_Controller_ActionController', array('exoticAction'), array($this->componentManager, $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface')), '');

		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Response');

		$request->setControllerName('T3_TestPackage_Controller_Default');
		$request->setActionName('exotic');

		$mockController->processRequest($request, $response);
		$viewReflection = new T3_FLOW3_Reflection_Property(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('T3_FLOW3_MVC_View_Abstract', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertTrue(get_class($view) == 'T3_FLOW3_MVC_View_Empty', 'The action controller did not provide an empty view.');
	}

	/**
	 * Views following the scheme T3_PackageName_View_ActionName will be set as $this->view
	 * automatically.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function aViewMatchingTheActionNameIsProvidedAutomatically() {
		$mockController = $this->getMock('T3_FLOW3_MVC_Controller_ActionController', array('thingAction'), array($this->componentManager, $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface')), '');

		$request = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Response');

		$request->setControllerName('T3_TestPackage_Controller_Some');
		$request->setActionName('thing');

		$mockController->processRequest($request, $response);
		$viewReflection = new T3_FLOW3_Reflection_Property(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('T3_FLOW3_MVC_View_Abstract', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertTrue(get_class($view) == 'T3_TestPackage_View_Some_Thing', 'The action controller did not select the "Some" "Thing" view.');
	}
}
?>