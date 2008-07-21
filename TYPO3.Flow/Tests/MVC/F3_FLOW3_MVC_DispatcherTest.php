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
 * @subpackage Tests
 * @version $Id$
 */

require_once(dirname(__FILE__) . '/../Fixtures/F3_FLOW3_Fixture_DummyClass.php');

/**
 * Testcase for the MVC Dispatcher
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_DispatcherTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidControllersResultInException() {
		$securityContextHolder = $this->getMock('F3_FLOW3_Security_ContextHolderInterface');
		$firewall = $this->getMock('F3_FLOW3_Security_Authorization_FirewallInterface');
		$settings = new F3_FLOW3_Configuration_Container();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($settings));

		$request = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Response');

		$dispatcher = new F3_FLOW3_MVC_Dispatcher($this->componentManager);
		$dispatcher->injectSecurityContextHolder($securityContextHolder);
		$dispatcher->injectFirewall($firewall);
		$dispatcher->injectConfigurationManager($configurationManager);

		$this->componentManager->registerComponent('F3_FLOW3_Fixture_DummyClass');
		$request->setControllerName('F3_FLOW3_Fixture_DummyClass');

		try {
			$dispatcher->dispatch($request, $response);
			$this->fail('The dispatcher accepted an invalid controller.');
		} catch (F3_FLOW3_MVC_Exception $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function dispatcherCallsProcessRequestMethodOfController() {
		$securityContextHolder = $this->getMock('F3_FLOW3_Security_ContextHolderInterface');
		$firewall = $this->getMock('F3_FLOW3_Security_Authorization_FirewallInterface');
		$settings = new F3_FLOW3_Configuration_Container();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($settings));

		$request = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Response');

		$dispatcher = new F3_FLOW3_MVC_Dispatcher($this->componentManager);
		$dispatcher->injectSecurityContextHolder($securityContextHolder);
		$dispatcher->injectFirewall($firewall);
		$dispatcher->injectConfigurationManager($configurationManager);

		$this->componentManager->registerComponent('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');
		$controller = $this->componentManager->getComponent('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');
		$request->setControllerName('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');

		$dispatcher->dispatch($request, $response);
		$this->assertTrue($controller->requestHasBeenProcessed, 'It seems like the controller has not been called by the dispatcher.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDispatcherInjectsThePackageSettingsIntoTheController() {
		$securityContextHolder = $this->getMock('F3_FLOW3_Security_ContextHolderInterface');
		$firewall = $this->getMock('F3_FLOW3_Security_Authorization_FirewallInterface');
		$settings = new F3_FLOW3_Configuration_Container();
		$configurationManager = $this->getMock('F3_FLOW3_Configuration_Manager', array('getConfiguration'), array(), '', FALSE);
		$configurationManager->expects($this->any())->method('getConfiguration')->will($this->returnValue($settings));

		$request = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Request');
		$response = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Response');

		$dispatcher = new F3_FLOW3_MVC_Dispatcher($this->componentManager);
		$dispatcher->injectSecurityContextHolder($securityContextHolder);
		$dispatcher->injectFirewall($firewall);
		$dispatcher->injectConfigurationManager($configurationManager);

		$this->componentManager->registerComponent('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');
		$controller = $this->componentManager->getComponent('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');
		$request->setControllerName('F3_FLOW3_Fixture_MVC_MockRequestHandlingController');

		$dispatcher->dispatch($request, $response);
		$this->assertSame($settings, $controller->getSettings());
	}
}
?>