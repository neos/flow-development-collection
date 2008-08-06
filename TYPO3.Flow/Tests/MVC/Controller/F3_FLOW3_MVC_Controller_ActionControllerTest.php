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
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

require_once(__DIR__ . '/../Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockAction.php');

/**
 * Testcase for the MVC Action Controller
 *
 * @package FLOW3
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Controller_ActionControllerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function stringsReturnedByActionMethodAreAppendedToResponseObject() {
		$mockController = new F3_FLOW3_MVC_Fixture_Controller_MockAction($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface'));
		$mockController->injectComponentManager($this->componentManager);
		$mockController->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$request->setControllerActionName('returnSomeString');
		$mockController->processRequest($request, $response);
		$this->assertEquals('Mock Action Controller Return String', $response->getContent(), 'The response object did not contain the string returned by the action controller');
	}

	/**
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function ifNoViewCouldBeResolvedAnEmptyViewIsProvided() {
		$mockController = $this->getMock('F3_FLOW3_MVC_Controller_ActionController', array('exoticAction'), array($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface')), 'F3_FLOW3_MVC_Controller_ActionController' . uniqid());
		$mockController->injectComponentManager($this->componentManager);
		$mockController->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Default');
		$request->setControllerActionName('exotic');

		$mockController->processRequest($request, $response);
		$viewReflection = new F3_FLOW3_Reflection_Property(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('F3_FLOW3_MVC_View_AbstractView', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertTrue(get_class($view) == 'F3_FLOW3_MVC_View_Empty', 'The action controller did not provide an empty view.');
	}

	/**
	 * Views following the scheme F3_PackageName_View_ActionName will be set as $this->view
	 * automatically.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 * @test
	 */
	public function aViewMatchingTheActionNameIsProvidedAutomatically() {
		$mockController = $this->getMock('F3_FLOW3_MVC_Controller_ActionController', array('thingAction'), array($this->componentFactory, $this->componentFactory->getComponent('F3_FLOW3_Package_ManagerInterface')), 'F3_FLOW3_MVC_Controller_ActionController' . uniqid());
		$mockController->injectComponentManager($this->componentManager);
		$mockController->injectPropertyMapper($this->componentFactory->getComponent('F3_FLOW3_Property_Mapper'));

		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$request->setControllerPackageKey('TestPackage');
		$request->setControllerName('Some');
		$request->setControllerActionName('thing');

		$mockController->processRequest($request, $response);
		$viewReflection = new F3_FLOW3_Reflection_Property(get_class($mockController), 'view');
		$view = $viewReflection->getValue($mockController);

		$this->assertType('F3_FLOW3_MVC_View_AbstractView', $view, 'The view has either not been set or is not of the expected type.');
		$this->assertTrue(get_class($view) == 'F3_TestPackage_View_Some_Thing', 'The action controller did not select the "Some" "Thing" view.');
	}
}
?>