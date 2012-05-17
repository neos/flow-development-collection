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
 * Testcase for the Http Response class
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
	public function itIsPossibleToSetTheHttpStatusCodeAndMessage() {
		$response = new Response();
		$response->setStatus(400, 'Really Bad Request');
		$headers = $response->getHeaders();
		$this->assertEquals('HTTP/1.1 400 Really Bad Request', $headers[0]);
	}

	/**
	 * @test
	 */
	public function setStatusSetsTheHttpStatusInParentResponse() {
		$parentResponse = new Response();
		$response = new Response($parentResponse);

		$response->setStatus(418, 'I\'m a bad coffee machine');
		$this->assertEquals('418 I\'m a bad coffee machine', $parentResponse->getStatus());
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setStatusThrowsExceptionOnInvalidCode() {
		$response = new Response();
		$response->setStatus(924);
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function setStatusThrowsExceptionOnNonNumericCode() {
		$response = new Response();
		$response->setStatus('400');
	}

	/**
	 * @test
	 */
	public function getStatusCodeSolelyReturnsTheStatusCode() {
		$response = new Response();

		$response->setStatus(418);
		$this->assertEquals(418, $response->getStatusCode());
	}

	/**
	 * @test
	 */
	public function getStatusReturnsTheHttpStatusFromParentResponse() {
		$parentResponse = new Response();
		$response = new Response($parentResponse);

		$parentResponse->setStatus(418, 'I\'m a bad coffee machine');

		$this->assertEquals('418 I\'m a bad coffee machine', $response->getStatus());
		$this->assertEquals(418, $response->getStatusCode());
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
			'Content-Type: text/html; charset=UTF-8',
			'MyHeader: MyValue',
			'OtherHeader: OtherValue',
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
			'Content-Type: text/html; charset=UTF-8',
			'MyHeader: OtherValue'
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
			'Content-Type: text/html; charset=UTF-8',
			'MyHeader: MyValue',
			'MyHeader: OtherValue'
		);

		$this->assertEquals($expectedHeaders, $response->getHeaders());
	}

	/**
	 * @test
	 */
	public function setHeaderSetsHeadInParentResponse() {
		$parentResponse = new Response();
		$response = new Response($parentResponse);

		$response->setHeader('MyHeader', 'MyValue');

		$this->assertEquals('MyValue', $response->getHeader('MyHeader'));
		$this->assertEquals('MyValue', $parentResponse->getHeader('MyHeader'));
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
	public function getHeadersReturnsHeadersOfParentResponse() {
		$parentResponse = new Response();
		$response = new Response($parentResponse);

		$parentResponse->setHeader('MyHeader', 'MyValue');

		$found = FALSE;
		foreach ($response->getHeaders() as $header) {
			if ($header === 'MyHeader: MyValue') {
				$found = TRUE;
			}

		}
		$this->assertTrue($found);
	}

	/**
	 * RFC 2616 / 3.7.1
	 *
	 * @test
	 */
	public function contentTypeHeaderWithMediaTypeTextHtmlIsAddedByDefault() {
		$response = new Response();
		$this->assertEquals('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
	}

	/**
	 * RFC 2616 / 3.7.1
	 *
	 * @test
	 */
	public function setHeaderAddsCharsetToMediaTypeIfNoneWasSpecifiedAndTypeIsText() {
		$response = new Response();

		$response->setHeader('Content-Type', 'text/plain', TRUE);
		$this->assertEquals('text/plain; charset=UTF-8', $response->getHeader('Content-Type'));

		$response->setHeader('Content-Type', 'text/plain', TRUE);
		$response->setCharset('Shift_JIS');
		$this->assertEquals('text/plain; charset=Shift_JIS', $response->getHeader('Content-Type'));
		$this->assertEquals('Shift_JIS', $response->getCharset());

		$response->setHeader('Content-Type', 'image/jpeg', TRUE);
		$response->setCharset('Shift_JIS');
		$this->assertEquals('image/jpeg', $response->getHeader('Content-Type'));
	}

	/**
	 * @test
	 */
	public function theDefaultCharacterSetIsUtf8() {
		$response = new Response();

		$this->assertEquals('UTF-8', $response->getCharset());
	}

	/**
	 * (RFC 2616 / 3.7.1)
	 *
	 * @test
	 */
	public function setCharsetSetsTheCharsetAndAlsoUpdatesContentTypeHeader() {
		$response = new Response();

		$response->setCharset('UTF-16');
		$this->assertEquals('text/html; charset=UTF-16', $response->getHeader('Content-Type'));

		$response->setHeader('Content-Type', 'text/plain; charset=UTF-16');
		$response->setCharset('ISO-8859-1');
		$this->assertEquals('text/plain; charset=ISO-8859-1', $response->getHeader('Content-Type'));

		$response->setHeader('Content-Type', 'image/png');
		$response->setCharset('UTF-8');
		$this->assertEquals('image/png', $response->getHeader('Content-Type'));

		$response->setHeader('Content-Type', 'Text/Plain');
		$this->assertEquals('Text/Plain; charset=UTF-8', $response->getHeader('Content-Type'));
	}

	/**
	 * (RFC 2616 / 3.7)
	 *
	 * @test
	 */
	public function setCharsetAlsoUpdatesContentTypeHeaderIfSpaceIsMissing() {
		$response = new Response();

		$response->setHeader('Content-Type', 'text/plain;charset=UTF-16');
		$response->setCharset('ISO-8859-1');
		$this->assertEquals('text/plain; charset=ISO-8859-1', $response->getHeader('Content-Type'));
	}

	/**
	 * (RFC 2616 / 3.7)
	 *
	 * @test
	 */
	public function setCharsetUpdatesContentTypeHeaderAndLeavesAdditionalInformationIntact() {
		$response = new Response();

		$response->setHeader('Content-Type', 'text/plain; charSet=UTF-16; x-foo=bar');
		$response->setCharset('ISO-8859-1');
		$this->assertEquals('text/plain; charset=ISO-8859-1; x-foo=bar', $response->getHeader('Content-Type'));
	}

	/**
	 * @test
	 */
	public function getParentResponseReturnsResponseSetInConstructor() {
		$parentResponse = new Response();

		$response = new Response($parentResponse);
		$this->assertSame($parentResponse, $response->getParentResponse());
	}

	/**
	 * @test
	 */
	public function contentCanBeSetAppendedAndRetrieved() {
		$response = new Response();

		$response->setContent('Two households, both alike in dignity, ');
		$response->appendContent('In fair Verona, where we lay our scene');

		$this->assertEquals('Two households, both alike in dignity, In fair Verona, where we lay our scene', $response->getContent());

		$response->setContent('For never was a story of more woe, Than this of Juliet and her Romeo.');
		$this->assertEquals('For never was a story of more woe, Than this of Juliet and her Romeo.', $response->getContent());
	}
}
?>