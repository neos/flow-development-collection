<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Web Request Handler
 *
 */
class RequestHandlerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function handleRequestBuildsARequestAndResponseDispatchesThemByTheDispatcherAndSendsTheResponse() {
		$mockBootstrap = $this->getMock('TYPO3\FLOW3\Core\Bootstrap', array(), array(), '', FALSE);

		$sequence = new \TYPO3\FLOW3\Core\Booting\Sequence();
		$mockBootstrap->expects($this->once())->method('buildRuntimeSequence')->will($this->returnValue($sequence));
		$mockBootstrap->expects($this->once())->method('shutdown');

		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$mockBootstrap->expects($this->once())->method('getObjectManager')->will($this->returnValue($mockObjectManager));

			// For we don't have to mock the Dispatcher, the request is set to "dispatched"
		$request = new \TYPO3\FLOW3\Mvc\ActionRequest();
		$request->setDispatched(TRUE);

		$mockRequestBuilder = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->atLeastOnce())->method('build')->will($this->returnValue($request));

		$mockObjectManager->expects($this->exactly(2))->method('get')->will($this->onConsecutiveCalls($mockRequestBuilder, new \TYPO3\FLOW3\Mvc\Dispatcher()));

		$requestHandler = new \TYPO3\FLOW3\Mvc\ActionRequestHandler($mockBootstrap);
		$requestHandler->exit = function() {};
		$requestHandler->handleRequest();

		$this->assertSame($request, $requestHandler->getRequest());
	}

}
?>