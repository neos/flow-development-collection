<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC;

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

require_once(__DIR__ . '/Fixture/F3_FLOW3_MVC_Fixture_MockRequestProcessor.php');

/**
 * Testcase for the MVC Request Processor Chain Manager
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RequestProcessorChainManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * Checks if a request processor can be registered.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function newProcessorCanBeRegistered() {
		$processorFixtures = array(
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
		);
		$manager = new F3::FLOW3::MVC::RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'F3::FLOW3::MVC::Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'F3::FLOW3::MVC::Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'F3::FLOW3::MVC::Request');

		$registeredProcessors = $manager->getRegisteredRequestProcessors();
		$this->assertTrue(count($registeredProcessors) > 0, 'It seems like no request processors are registered.');

		$ok = TRUE;
		foreach ($registeredProcessors['F3::FLOW3::MVC::Request'] as $registeredProcessor) {
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
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
			new F3::FLOW3::Fixture::MVC::MockRequestProcessor,
		);
		$manager = new F3::FLOW3::MVC::RequestProcessorChainManager;
		$manager->registerRequestProcessor($processorFixtures[0], 'F3::FLOW3::MVC::Request');
		$manager->registerRequestProcessor($processorFixtures[1], 'F3::FLOW3::MVC::Request');
		$manager->registerRequestProcessor($processorFixtures[2], 'F3::FLOW3::MVC::Request');

		$manager->unregisterRequestProcessor($processorFixtures[1]);

		$registeredProcessors = $manager->getRegisteredRequestProcessors();

		$found = FALSE;
		foreach ($registeredProcessors['F3::FLOW3::MVC::Request'] as $registeredProcessor) {
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
		$manager = new F3::FLOW3::MVC::RequestProcessorChainManager;
		$manager->registerRequestProcessor(new F3::FLOW3::Fixture::MVC::MockRequestProcessor, 'F3::FLOW3::MVC::Web::Request');

		$webRequest = $this->objectManager->getObject('F3::FLOW3::MVC::Web::Request');
		$manager->processRequest($webRequest);
		$this->assertTrue($webRequest->hasArgument('F3::FLOW3::Fixture::MVC::MockRequestProcessor'), 'Seems like the Dummy Request Processor has not been called.');

		$cliRequest = $this->objectManager->getObject('F3::FLOW3::MVC::CLI::Request');
		$manager->processRequest($cliRequest);
		$this->assertFalse($cliRequest->hasArgument('F3::FLOW3::Fixture::MVC::MockRequestProcessor'), 'Seems like the Dummy Request Processor has been called although it was not registered for CLI requests.');
	}
}
?>