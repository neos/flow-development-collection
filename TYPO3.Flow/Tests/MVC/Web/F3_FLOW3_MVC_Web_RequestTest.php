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
 * Testcase for the MVC Web Request class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\MVC\Web\Request
	 */
	protected $request;

	/**
	 * @var \F3\FLOW3\Property\DataType\URI
	 */
	protected $requestURI;

	/**
	 * Sets up this test case
	 *
	 * @author  Robert Lemke <robert@typo3.org>
	 */
	protected function setUp() {
		$this->environment = new \F3\FLOW3\Utility\MockEnvironment();
		$this->environment->SERVER['ORIG_SCRIPT_NAME'] = '/path1/path2/index.php';
		$this->environment->SERVER['SCRIPT_NAME'] = '/path1/path2/index.php';

		$URIString = 'http://username:password@subdomain.domain.com:8080/path1/path2/index.php?argument1=value1&argument2=value2#anchor';
		$this->requestURI = new \F3\FLOW3\Property\DataType\URI($URIString);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentsReturnsProperlyInitializedArgumentsArrayObjectForNewRequest() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->injectEnvironment($this->environment);
		$this->assertType('ArrayObject', $request->getArguments(), 'getArguments() does not return an ArrayObject for a virgin request object.');
	}

	/**
	 * Checks if the request URI is returned as expected.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRequestURIreturnsCorrectURI() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($this->requestURI);

		$this->assertEquals($this->requestURI, $request->getRequestURI(), 'request->getRequestURI() did not return the expected URI.');
		$this->assertNotSame($this->requestURI, $request->getRequestURI(), 'request->getRequestURI() returned the same URI which is dangerous ...');
	}

	/**
	 * Checks if the test URI is detected correctly as the base URI
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURIdetectsSimpleURICorrectly() {
		$this->environment->SERVER['ORIG_SCRIPT_NAME'] = NULL;
		$this->environment->SERVER['SCRIPT_NAME'] = '/';

		$requestURI = new \F3\FLOW3\Property\DataType\URI('http://www.server.com/index.php');
		$expectedBaseURI = new \F3\FLOW3\Property\DataType\URI('http://www.server.com/');

		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($requestURI);

		$this->assertEquals($expectedBaseURI, $request->getBaseURI(), 'The returned baseURI is not as expected.');
	}

	/**
	 * Checks if the base URI is detected correctly when TYPO3 resides in a subdirectory of the web root.
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getBaseURIdetectsURIWithSubDirectoryCorrectly() {
		$this->environment->SERVER['ORIG_SCRIPT_NAME'] = NULL;
		$this->environment->SERVER['SCRIPT_NAME'] = '/path1/path2/index.php';

		$requestURI = new \F3\FLOW3\Property\DataType\URI('http://www.server.com/path1/path2/index.php');
		$expectedBaseURI = new \F3\FLOW3\Property\DataType\URI('http://www.server.com/path1/path2/');

		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->injectEnvironment($this->environment);
		$request->setRequestURI($requestURI);

		$this->assertEquals($expectedBaseURI, $request->getBaseURI(), 'The returned baseURI is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRequestMethodCanBeSetAndRetrieved() {
		$request = new \F3\FLOW3\MVC\Web\Request();

		$request->setMethod(\F3\FLOW3\Utility\Environment::REQUEST_METHOD_GET);
		$this->assertEquals(\F3\FLOW3\Utility\Environment::REQUEST_METHOD_GET, $request->getMethod());

		$request->setMethod(\F3\FLOW3\Utility\Environment::REQUEST_METHOD_POST);
		$this->assertEquals(\F3\FLOW3\Utility\Environment::REQUEST_METHOD_POST, $request->getMethod());
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidRequestMethod
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function invalidRequestMethodsAreRejected() {
		$request = new \F3\FLOW3\MVC\Web\Request();
		$request->setMethod('SOMETHING');
	}
}
?>