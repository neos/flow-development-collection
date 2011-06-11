<?php
namespace F3\FLOW3\Tests\Unit\MVC\Web;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Web Request Handler
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandlerTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequestBuildsARequestAndResponseDispatchesThemByTheDispatcherAndSendsTheResponse() {
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);

		$mockRequestBuilder = $this->getMock('F3\FLOW3\MVC\Web\RequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->once())->method('build')->will($this->returnValue($mockRequest));

		$mockDispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);
		$mockDispatcher->expects($this->once())->method('dispatch')->with($mockRequest);

		$requestHandler = new \F3\FLOW3\MVC\Web\RequestHandler($mockDispatcher, $mockRequestBuilder);
		$requestHandler->handleRequest();
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequestSetsAContentTypeHeaderAccordingToCertainFormats() {
		$this->markTestIncomplete('Needs to mock a newable!');

			// these calls need to checked but
#		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
#		$mockResponse->expects($this->at(0))->method('setHeader')->with('Content-Type', 'application/rss+xml');
#		$mockResponse->expects($this->at(2))->method('setHeader')->with('Content-Type', 'application/rss+xml');
#		$mockResponse->expects($this->at(4))->method('setHeader')->with('Content-Type', 'application/atom+xml');
#		$mockResponse->expects($this->at(6))->method('setHeader')->with('Content-Type', 'application/atom+xml');

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->at(0))->method('getFormat')->will($this->returnValue('rss.xml'));
		$mockRequest->expects($this->at(1))->method('getFormat')->will($this->returnValue('rss'));
		$mockRequest->expects($this->at(2))->method('getFormat')->will($this->returnValue('atom.xml'));
		$mockRequest->expects($this->at(3))->method('getFormat')->will($this->returnValue('atom'));

		$mockRequestBuilder = $this->getMock('F3\FLOW3\MVC\Web\RequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->exactly(4))->method('build')->will($this->returnValue($mockRequest));

		$mockDispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);

		$requestHandler = new \F3\FLOW3\MVC\Web\RequestHandler($mockDispatcher, $mockRequestBuilder);
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
		$requestHandler->handleRequest();
	}
}
?>