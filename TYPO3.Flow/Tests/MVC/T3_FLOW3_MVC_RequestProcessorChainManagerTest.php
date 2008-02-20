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

require_once(dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_MVC_MockRequestProcessor.php');

/**
 * Testcase for the MVC Request Processor Chain Manager
 *
 * @package		Framework
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_MVC_RequestProcessorChainManagerTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks if a request processor can be registered.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function newProcessorCanBeRegistered() {
		$processorFixtures = array(
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
		);
		$manager = new T3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'T3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'T3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'T3_FLOW3_MVC_Request');

		$registeredProcessors = $manager->getRegisteredRequestProcessors();
		$this->assertTrue(count($registeredProcessors) > 0, 'It seems like no request processors are registered.');

		$ok = TRUE;
		foreach($registeredProcessors['T3_FLOW3_MVC_Request'] as $registeredProcessor) {
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
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
			new T3_FLOW3_Fixture_MVC_MockRequestProcessor,
		);
		$manager = new T3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'T3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'T3_FLOW3_MVC_Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'T3_FLOW3_MVC_Request');

		$manager->unregisterRequestProcessor($processorFixtures[1]);

		$registeredProcessors = $manager->getRegisteredRequestProcessors();

		$found = FALSE;
		foreach($registeredProcessors['T3_FLOW3_MVC_Request'] as $registeredProcessor) {
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
		$manager = new T3_FLOW3_MVC_RequestProcessorChainManager;
		$manager->registerRequestProcessor(new T3_FLOW3_Fixture_MVC_MockRequestProcessor, 'T3_FLOW3_MVC_Web_Request');

		$webRequest = $this->componentManager->getComponent('T3_FLOW3_MVC_Web_Request');
		$manager->processRequest($webRequest);
		$this->assertTrue($webRequest->hasArgument('T3_FLOW3_Fixture_MVC_MockRequestProcessor'), 'Seems like the Dummy Request Processor has not been called.');

		$cliRequest = $this->componentManager->getComponent('T3_FLOW3_MVC_CLI_Request');
		$manager->processRequest($cliRequest);
		$this->assertFalse($cliRequest->hasArgument('T3_FLOW3_Fixture_MVC_MockRequestProcessor'), 'Seems like the Dummy Request Processor has been called although it was not registered for CLI requests.');
	}
}
?>