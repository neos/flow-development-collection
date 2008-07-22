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
 * Testcase for the MVC CLI Request Handler class
 *
 * @package FLOW3
 * @version $Id:F3_FLOW3_Component_TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_CLI_RequestHandlerTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_MVC_CLI_RequestHandler
	 */
	protected $requestHandler;

	/**
	 * @var F3_FLOW3_Utility_MockEnvironment
	 */
	protected $environment;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$configuration = $this->componentFactory->getComponent('F3_FLOW3_Configuration_Manager')->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3);
		$this->environment = new F3_FLOW3_Utility_MockEnvironment($configuration->utility->environment);

			// Inject the mock environment into Builder and Handler:
		$this->componentFactory->getComponent('F3_FLOW3_MVC_CLI_RequestBuilder', $this->componentManager, $this->componentFactory, $this->environment);
		$this->requestHandler = $this->componentFactory->getComponent('F3_FLOW3_MVC_CLI_RequestHandler', $this->componentFactory, $this->environment);
	}

	/**
	 * Checks if a mock request asking for the TestPackage default controller is handled and dispatched correctly.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function CLIRequestHandlerHandlesTestPackageRequestCorrectly() {
		$this->environment->SAPIName = 'cli';
		$this->environment->SERVER['argc'] = 2;
		$this->environment->SERVER['argv'][0] = 'index.php';
		$this->environment->SERVER['argv'][1] = 'TestPackage';
		$this->environment->SERVER['argv'][2] = 'Default';

		ob_start();
		$this->requestHandler->handleRequest();
		$output = ob_get_contents();
		ob_end_clean();

		$this->assertEquals('TestPackage Default Controller - CLI Request.', $output, 'The CLI request handler did not handle the request correctly - at least I did not receive the expected output.');
	}
}
?>