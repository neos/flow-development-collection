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
 * @version $Id$
 */

require_once(__DIR__ . '/Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController.php');
require_once(__DIR__ . '/Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockExceptionThrowingController.php');

/**
 * Testcase for the MVC Dispatcher
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_DispatcherTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_MVC_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$securityContextHolder = $this->getMock('F3_FLOW3_Security_ContextHolderInterface');
		$firewall = $this->getMock('F3_FLOW3_Security_Authorization_FirewallInterface');
		$settings = new F3_FLOW3_Configuration_Container();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getSettings'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));

		$this->dispatcher = new F3_FLOW3_MVC_Dispatcher($this->componentManager, $this->componentFactory);
		$this->dispatcher->injectSecurityContextHolder($securityContextHolder);
		$this->dispatcher->injectFirewall($firewall);
		$this->dispatcher->injectConfigurationManager($configurationManager);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidControllersResultInException() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectComponentManager($this->componentManager);
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		if (!class_exists('F3_FLOW3_MVC_Fixture_Controller_Invalid')) $this->getMock('stdclass', array(), array(), 'F3_FLOW3_MVC_Fixture_Controller_Invalid');
		$this->componentManager->registerComponent('F3_FLOW3_MVC_Fixture_Controller_Invalid');

		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Fixture_Controller_@controller');
		$request->setControllerName('Invalid');

		try {
			$this->dispatcher->dispatch($request, $response);
			$this->fail('The dispatcher accepted an invalid controller.');
		} catch (F3_FLOW3_MVC_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function aStopActionExceptionThrownByTheControllerIsCatchedByTheDispatcherAndBreaksTheDispatchLoop() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectComponentManager($this->componentManager);
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$this->componentManager->registerComponent('F3_FLOW3_MVC_Fixture_Controller_MockExceptionThrowingController');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Fixture_Controller_@controller');
		$request->setControllerName('MockExceptionThrowingController');

		$request->setControllerActionName('stopAction');
		$this->dispatcher->dispatch($request, $response);

		$request->setDispatched(FALSE);
		$request->setControllerActionName('throwGeneralException');
		try {
			$this->dispatcher->dispatch($request, $response);
			$this->fail('The exception thrown by the second action was catched somewhere or the action was not called.');
		} catch (F3_FLOW3_MVC_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatcherCallsProcessRequestMethodOfController() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectComponentManager($this->componentManager);
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$this->componentManager->registerComponent('F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController');
		$controller = $this->componentFactory->getComponent('F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Fixture_Controller_@controller');
		$request->setControllerName('MockRequestHandlingController');

		$this->dispatcher->dispatch($request, $response);
		$this->assertTrue($controller->requestHasBeenProcessed, 'It seems like the controller has not been called by the dispatcher.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDispatcherInjectsThePackageSettingsIntoTheController() {
		$settings = new F3_FLOW3_Configuration_Container();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getSettings'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getSettings')->will($this->returnValue($settings));

		$this->dispatcher->injectConfigurationManager($configurationManager);

		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->injectComponentManager($this->componentManager);
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$this->componentManager->registerComponent('F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController');
		$controller = $this->componentFactory->getComponent('F3_FLOW3_MVC_Fixture_Controller_MockRequestHandlingController');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Fixture_Controller_@controller');
		$request->setControllerName('MockRequestHandlingController');

		$this->dispatcher->dispatch($request, $response);
		$this->assertSame($settings, $controller->getSettings());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theDispatcherInitializesTheSecurityContextWithTheGivenRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Controller_@controller');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$securityContextHolder = $this->getMock('F3_FLOW3_Security_ContextHolderInterface', array('initializeContext', 'setContext', 'getContext', 'clearContext'));
		$this->dispatcher->injectSecurityContextHolder($securityContextHolder);

		$securityContextHolder->expects($this->any())->method('initializeContext');
		$this->dispatcher->dispatch($request, $response);
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function theDispatcherCallsTheFirewallWithTheGivenRequest() {
		$request = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$request->setControllerPackageKey('FLOW3');
		$request->setControllerComponentNamePattern('F3_@package_MVC_Controller_@controller');
		$response = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Response');

		$firewall = $this->getMock('F3_FLOW3_Security_Authorization_FirewallInterface');
		$this->dispatcher->injectFirewall($firewall);

		$firewall->expects($this->any())->method('blockIllegalRequests');
		$this->dispatcher->dispatch($request, $response);
	}
}
?>