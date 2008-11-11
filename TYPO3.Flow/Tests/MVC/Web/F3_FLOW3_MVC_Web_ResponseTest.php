<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web;

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
 * @version $Id:F3::FLOW3::Object::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the MVC Web Response class
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:F3::FLOW3::Object::TransientObjectCacheTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ResponseTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theDefaultStatusHeaderIs200OK() {
		$response = new F3::FLOW3::MVC::Web::Response();
		$this->assertEquals(array('HTTP/1.1 200 OK'), $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function itIsPossibleToSetTheHTTPStatusCodeAndMessage() {
		$response = new F3::FLOW3::MVC::Web::Response();
		$response->setStatus(400, 'Really Bad Request');
		$this->assertEquals(array('HTTP/1.1 400 Really Bad Request'), $response->getHeaders());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function additionalHeadersCanBeSetAndRetrieved() {
		$response = new F3::FLOW3::MVC::Web::Response();
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
		$response = new F3::FLOW3::MVC::Web::Response();
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
		$response = new F3::FLOW3::MVC::Web::Response();
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