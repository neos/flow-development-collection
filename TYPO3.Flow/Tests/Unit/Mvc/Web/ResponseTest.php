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
 * Testcase for the MVC Web Response class
 *
 */
class ResponseTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theDefaultStatusHeaderIs200OK() {
		$response = new \TYPO3\FLOW3\Mvc\Web\Response();
		$this->assertEquals(array('HTTP/1.1 200 OK'), $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetTheHTTPStatusCodeAndMessage() {
		$response = new \TYPO3\FLOW3\Mvc\Web\Response();
		$response->setStatus(400, 'Really Bad Request');
		$this->assertEquals(array('HTTP/1.1 400 Really Bad Request'), $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function additionalHeadersCanBeSetAndRetrieved() {
		$response = new \TYPO3\FLOW3\Mvc\Web\Response();
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
	 */
	public function byDefaultHeadersOfTheSameNameAreReplaced() {
		$response = new \TYPO3\FLOW3\Mvc\Web\Response();
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
	 */
	public function multipleHeadersOfTheSameNameMayBeDefined() {
		$response = new \TYPO3\FLOW3\Mvc\Web\Response();
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