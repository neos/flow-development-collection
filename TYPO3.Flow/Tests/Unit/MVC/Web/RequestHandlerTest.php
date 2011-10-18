<?php
namespace TYPO3\FLOW3\Tests\Unit\MVC\Web;

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
		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);

		$mockRequestBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\RequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->once())->method('build')->will($this->returnValue($mockRequest));

		$mockDispatcher = $this->getMock('TYPO3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);
		$mockDispatcher->expects($this->once())->method('dispatch')->with($mockRequest);

		$requestHandler = new \TYPO3\FLOW3\MVC\Web\RequestHandler($mockDispatcher, $mockRequestBuilder);
		$requestHandler->handleRequest();
	}

	/**
	 * @test
	 */
	public function handleRequestSetsAContentTypeHeaderAccordingToCertainFormats() {
		$this->markTestIncomplete('Needs to mock a newable!');

			// these calls need to checked but
#		$mockResponse = $this->getMock('TYPO3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
#		$mockResponse->expects($this->at(0))->method('setHeader')->with('Content-Type', 'application/rss+xml');
#		$mockResponse->expects($this->at(2))->method('setHeader')->with('Content-Type', 'application/rss+xml');
#		$mockResponse->expects($this->at(4))->method('setHeader')->with('Content-Type', 'application/atom+xml');
#		$mockResponse->expects($this->at(6))->method('setHeader')->with('Content-Type', 'application/atom+xml');

		$mockRequest = $this->getMock('TYPO3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getFormat')->will($this->returnValue('rss.xml'));
		$mockRequest->expects($this->at(1))->method('getFormat')->will($this->returnValue('rss'));
		$mockRequest->expects($this->at(2))->method('getFormat')->will($this->returnValue('atom.xml'));
		$mockRequest->expects($this->at(3))->method('getFormat')->will($this->returnValue('atom'));

		$mockRequestBuilder = $this->getMock('TYPO3\FLOW3\MVC\Web\RequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->exactly(4))->method('build')->will($this->returnValue($mockRequest));

		$mockDispatcher = $this->getMock('TYPO3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);

		$requestHandler = new \TYPO3\FLOW3\MVC\Web\RequestHandler($mockDispatcher, $mockRequestBuilder);
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
	}
}
?>