<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web;

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
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the MVC Web Request Handler
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandlerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequestBuildsARequestAndResponseDispatchesThemByTheDispatcherAndSendsTheResponse() {
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment');

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockResponse = $this->getMock('F3\FLOW3\MVC\Web\Response', array(), array(), '', FALSE);
		$mockResponse->expects($this->once())->method('send');

		$mockRequestBuilder = $this->getMock('F3\FLOW3\MVC\Web\RequestBuilder', array(), array(), '', FALSE);
		$mockRequestBuilder->expects($this->once())->method('build')->will($this->returnValue($mockRequest));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->once())->method('create')->with('F3\FLOW3\MVC\Web\Response')->will($this->returnValue($mockResponse));

		$mockDispatcher = $this->getMock('F3\FLOW3\MVC\Dispatcher', array(), array(), '', FALSE);
		$mockDispatcher->expects($this->once())->method('dispatch')->with($mockRequest, $mockResponse);

		$requestHandler = new \F3\FLOW3\MVC\Web\RequestHandler($mockObjectFactory, $mockEnvironment, $mockDispatcher, $mockRequestBuilder);
		$requestHandler->handleRequest();
	}
}
?>