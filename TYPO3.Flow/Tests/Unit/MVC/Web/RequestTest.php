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
 * Testcase for the MVC Web Request class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * @var \F3\FLOW3\Property\DataType\Uri
	 */
	protected $requestUri;

	/**
	 * @var ArrayObject
	 */
	protected $SERVER;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->mockEnvironment = $this->getAccessibleMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$this->SERVER = array();
		$this->mockEnvironment->_set('SERVER', $this->SERVER);

		$uriString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2#anchor';
		$this->requestUri = new \F3\FLOW3\Property\DataType\Uri($uriString);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentsReturnsProperlyInitializedArgumentsArrayForNewRequest() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$this->assertInternalType('array', $request->getArguments(), 'getArguments() does not return an array for a virgin request object.');
	}

	/**
	 * Checks if the request URI is returned as expected.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestUriReturnsTheBaseUriDetectedByTheEnvironmentClass() {
		$expectedRequestUri = new \F3\FLOW3\Property\DataType\Uri('http://www.server.com/foo/bar');

		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('dummy'));
		$request->_set('requestUri', $expectedRequestUri);

		$this->assertEquals($expectedRequestUri, $request->getRequestUri());
	}

	/**
	 * Returns the base URI of the current request.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseUriReturnsTheBaseUriDetectedByTheEnvironmentClass() {
		$expectedBaseUri = new \F3\FLOW3\Property\DataType\Uri('http://www.server.com/');

		$request = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Request', array('dummy'));
		$request->_set('baseUri', $expectedBaseUri);

		$this->assertEquals($expectedBaseUri, $request->getBaseUri(), 'The returned baseUri is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRequestMethodCanBeSetAndRetrieved() {
		$request = new \F3\FLOW3\MVC\Web\Request();

		$request->setMethod('GET');
		$this->assertEquals('GET', $request->getMethod());

		$request->setMethod('POST');
		$this->assertEquals('POST', $request->getMethod());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidRequestMethodException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function requestMethodsWhichAreNotCompletelyUpperCaseAreRejected() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->setMethod('sOmEtHing');
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnNullByDefault() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$this->assertNull($request->getReferringRequest());
	}

	/**
	 * @test
	 */
	public function getReferringRequestShouldReturnCorrectlyBuiltReferringRequest() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->setArgument('__referrer', array(
			'@controller' => 'Foo',
			'@action' => 'bar'
		));
		$referringRequest = $request->getReferringRequest();
		$this->assertNotNull($referringRequest);

		$this->assertAttributeEquals('Foo', 'controllerName', $referringRequest);
		$this->assertAttributeEquals('bar', 'controllerActionName', $referringRequest);
	}
}
?>