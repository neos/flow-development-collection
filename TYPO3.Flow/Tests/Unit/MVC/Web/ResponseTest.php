<?php
declare(ENCODING = 'utf-8');
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
 * Testcase for the MVC Web Response class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ResponseTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultStatusHeaderIs200OK() {
		$response = new \F3\FLOW3\MVC\Web\Response();
		$this->assertEquals(array('HTTP/1.1 200 OK'), $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToSetTheHTTPStatusCodeAndMessage() {
		$response = new \F3\FLOW3\MVC\Web\Response();
		$response->setStatus(400, 'Really Bad Request');
		$this->assertEquals(array('HTTP/1.1 400 Really Bad Request'), $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function additionalHeadersCanBeSetAndRetrieved() {
		$response = new \F3\FLOW3\MVC\Web\Response();
		$response->setStatus(123, 'Custom Status');
		$response->setHeader('MyHeader', 'MyValue');
		$response->setHeader('OtherHeader', 'OtherValue');

		$expectedHeaders = array(
			'HTTP/1.1 123 Custom Status',
			'MyHeader: MyValue',
			'OtherHeader: OtherValue'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function byDefaultHeadersOfTheSameNameAreReplaced() {
		$response = new \F3\FLOW3\MVC\Web\Response();
		$response->setHeader('MyHeader', 'MyValue');
		$response->setHeader('MyHeader', 'OtherValue');

		$expectedHeaders = array(
			'HTTP/1.1 200 OK',
			'MyHeader: OtherValue'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function multipleHeadersOfTheSameNameMayBeDefined() {
		$response = new \F3\FLOW3\MVC\Web\Response();
		$response->setHeader('MyHeader', 'MyValue', FALSE);
		$response->setHeader('MyHeader', 'OtherValue', FALSE);

		$expectedHeaders = array(
			'HTTP/1.1 200 OK',
			'MyHeader: MyValue',
			'MyHeader: OtherValue'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}
}
?>