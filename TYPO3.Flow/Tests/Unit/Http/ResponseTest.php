<?php
namespace TYPO3\FLOW3\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Http\Response;

/**
 * Testcase for the MVC Web Response class
 */
class ResponseTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theDefaultStatusHeaderIs200OK() {
		$response = new Response();
		$headers = $response->getHeaders();
		$this->assertEquals('HTTP/1.1 200 OK', $headers[0]);
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetTheHTTPStatusCodeAndMessage() {
		$response = new Response();
		$response->setStatus(400, 'Really Bad Request');
		$headers = $response->getHeaders();
		$this->assertEquals('HTTP/1.1 400 Really Bad Request', $headers[0]);
	}

	/**
	 * @test
	 */
	public function additionalHeadersCanBeSetAndRetrieved() {
		$response = new Response();
		$response->setStatus(123, 'Custom Status');
		$response->setHeader('MyHeader', 'MyValue');
		$response->setHeader('OtherHeader', 'OtherValue');

		$expectedHeaders = array(
			'HTTP/1.1 123 Custom Status',
			'X-FLOW3-Powered: FLOW3/' . FLOW3_VERSION_BRANCH,
			'MyHeader: MyValue',
			'OtherHeader: OtherValue',
			'Content-Type: text/html; charset=UTF-8'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function byDefaultHeadersOfTheSameNameAreReplaced() {
		$response = new Response();
		$response->setHeader('MyHeader', 'MyValue');
		$response->setHeader('MyHeader', 'OtherValue');

		$expectedHeaders = array(
			'HTTP/1.1 200 OK',
			'X-FLOW3-Powered: FLOW3/' . FLOW3_VERSION_BRANCH,
			'MyHeader: OtherValue',
			'Content-Type: text/html; charset=UTF-8'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function multipleHeadersOfTheSameNameMayBeDefined() {
		$response = new Response();
		$response->setHeader('MyHeader', 'MyValue', FALSE);
		$response->setHeader('MyHeader', 'OtherValue', FALSE);

		$expectedHeaders = array(
			'HTTP/1.1 200 OK',
			'X-FLOW3-Powered: FLOW3/' . FLOW3_VERSION_BRANCH,
			'MyHeader: MyValue',
			'MyHeader: OtherValue',
			'Content-Type: text/html; charset=UTF-8'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function getHeaderReturnsAStringOrAnArray() {
		$response = new Response();

		$response->setHeader('MyHeader', 'MyValue');
		$this->assertEquals('MyValue', $response->getHeader('MyHeader'));

		$response->setHeader('MyHeader', 'OtherValue', FALSE);
		$this->assertEquals(array('MyValue', 'OtherValue'), $response->getHeader('MyHeader'));
	}

	/**
	 * @test
	 */
	public function getParentResponseReturnsResponseSetInConstructor() {
		$parentResponse = new Response();

		$response = new Response($parentResponse);
		$this->assertSame($parentResponse, $response->getParentResponse());
	}
}
?>