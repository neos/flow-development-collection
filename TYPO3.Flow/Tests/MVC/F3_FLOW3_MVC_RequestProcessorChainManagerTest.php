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

require_once(dirname(__FILE__) . '/Fixture/F3_FLOW3_MVC_Fixture_MockRequestProcessor.php');

/**
 * Testcase for the MVC Request Processor Chain Manager
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_RequestProcessorChainManagerTest extends F3_Testing_BaseTestCase {

	/**
	 * Checks if a request processor can be registered.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function newProcessorCanBeRegistered() {
		$processorFixtures = array(
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
		);
		$manager = new F3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'F3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'F3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'F3_FLOW3_MVC_Request');

		$registeredProcessors = $manager->getRegisteredRequestProcessors();
		$this->assertTrue(count($registeredProcessors) > 0, 'It seems like no request processors are registered.');

		$ok = TRUE;
		foreach ($registeredProcessors['F3_FLOW3_MVC_Request'] as $registeredProcessor) {
			if ($registeredProcessor !== array_shift($processorFixtures)) $ok = FALSE;
		}

		$this->assertTrue($ok, 'The request processors seem not to be registered correctly.');
	}

	/**
	 * Checks if a request processor can be nuregistered.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processorsCanBeUnregistered() {
		$processorFixtures = array(
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new F3_FLOW3_Fixture_MVC_MockRequestProcessor,
		);
		$manager = new F3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'F3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'F3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'F3_FLOW3_MVC_Request');

		$manager->unregisterRequestProcessor($processorFixtures[1]);

		$registeredProcessors = $manager->getRegisteredRequestProcessors();

		$found = FALSE;
		foreach ($registeredProcessors['F3_FLOW3_MVC_Request'] as $registeredProcessor) {
			if ($registeredProcessor === $processorFixtures[1]) $found = TRUE;
		}

		$this->assertFalse($found, 'The unregistered request processor is still registered.');
	}

	/**
	 * Checks if the request processors are really called.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function requestProcessorIsInvokedDependingOnRequestType() {
		$manager = new F3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor(new F3_FLOW3_Fixture_MVC_MockRequestProcessor, 'F3_FLOW3_MVC_Web_Request');

		$webRequest = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Request');
		$manager->processRequest($webRequest);
		$this->assertTrue($webRequest->hasArgument('F3_FLOW3_Fixture_MVC_MockRequestProcessor'), 'Seems like the Dummy Request Processor has not been called.');

		$cliRequest = $this->componentFactory->getComponent('F3_FLOW3_MVC_CLI_Request');
		$manager->processRequest($cliRequest);
		$this->assertFalse($cliRequest->hasArgument('F3_FLOW3_Fixture_MVC_MockRequestProcessor'), 'Seems like the Dummy Request Processor has been called although it was not registered for CLI requests.');
	}
}
?>