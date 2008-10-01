<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Controller;
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
 * @version $Id:F3::FLOW3::Component::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

require_once(__DIR__ . '/../Fixture/Controller/F3_FLOW3_MVC_Fixture_Controller_MockRESTController.php');

/**
 * Testcase for the MVC REST Controller
 *
 * @package FLOW3
 * @version $Id:F3::FLOW3::Component::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RESTControllerTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::MVC::Controller::RESTController
	 */
	protected $mockController;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setUp() {
		$this->mockController = new F3::FLOW3::MVC::Fixture::Controller::MockRESTController($this->componentFactory, $this->componentFactory->getComponent('F3::FLOW3::Package::ManagerInterface'));
		$this->mockController->injectComponentManager($this->componentManager);
		$this->mockController->injectPropertyMapper($this->componentFactory->getComponent('F3::FLOW3::Property::Mapper'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionCallsTheListActionOnGETRequestsWithoutIdentifier() {
		$request = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Request');
		$response = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Response');

		$this->mockController->processRequest($request, $response);
		$this->assertEquals('list action called', $response->getContent());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function callActionCallsTheShowActionOnGETRequestsWithIdentifier() {
		$request = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Request');
		$response = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Response');

		$request->setArgument('id', '6499348f-f8fd-48de-9979-24e1edc2fbe7');

		$this->mockController->processRequest($request, $response);
		$this->assertEquals('show action called', $response->getContent());
	}
}
?>