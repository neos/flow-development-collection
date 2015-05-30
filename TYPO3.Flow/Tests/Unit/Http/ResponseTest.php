<?php
namespace TYPO3\Flow\Tests\Unit\Http;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;

/**
 * Test case for the Http Response class
 */
class ResponseTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theDefaultStatusHeaderIs200OK() {
		$response = new Response();
		$headers = $response->renderHeaders();
		$this->assertEquals('HTTP/1.1 200 OK', $headers[0]);
	}

	/**
	 * Data provider
	 */
	public function rawResponses() {
		return array(
			array(file_get_contents(__DIR__ . '/../Fixtures/RawResponse-1.txt'),
				array(
					'Server' => 'Apache/2.2.17 (Ubuntu)',
					'X-Powered-By' => 'PHP/5.3.5-1ubuntu7.2',
					'X-Flow-Powered' => 'Flow/1.2',
					'Cache-Control' => 'public, s-maxage=600',
					'Vary' => 'Accept-Encoding',
					'Content-Encoding' => 'gzip',
					'Content-Type' => 'text/html; charset=UTF-8',
					'Content-Length' => 3795,
					'Date' => \DateTime::createFromFormat(DATE_RFC2822, 'Wed, 29 Aug 2012 09:03:49 GMT'),
					'Age' => 550,
					'Via' => '1.1 varnish',
					'Connection' => 'keep-alive'
				)
			, 200),
			array(file_get_contents(__DIR__ . '/../Fixtures/RawResponse-2.txt'),
				array(
					'Server' => 'Apache/2.2.17 (Ubuntu)',
					'Location' => 'http://flow.typo3.org/',
					'Vary' => 'Accept-Encoding',
					'Content-Encoding' => 'gzip',
					'Content-Type' => 'text/html; charset=iso-8859-1',
					'Content-Length' => 243,
					'Date' => \DateTime::createFromFormat(DATE_RFC2822, 'Wed, 29 Aug 2012 09:03:46 GMT'),
					'X-Varnish' => 1792566338,
					'Age' => 0,
					'Via' => '1.1 varnish',
					'Connection' => 'keep-alive'
				)
			, 301)
		);
	}

	/**
	 * @param $rawResponse
	 * @param $expectedHeaders
	 * @param $expectedStatusCode
	 * @test
	 * @dataProvider rawResponses
	 */
	public function createFromRawSetsHeadersAndStatusCodeCorrectly($rawResponse, $expectedHeaders, $expectedStatusCode) {
		$response = Response::createFromRaw($rawResponse);

		foreach ($expectedHeaders as $fieldName => $fieldValue) {
			$this->assertTrue($response->hasHeader($fieldName), sprintf('Response does not have expected header %s', $fieldName));
			$this->assertEquals($fieldValue, $response->getHeader($fieldName));
		}
		foreach ($response->getHeaders()->getAll() as $fieldName => $fieldValue) {
			$this->assertTrue(isset($expectedHeaders[$fieldName]), sprintf('Response has unexpected header %s', $fieldName));
		}

		$this->assertEquals($expectedStatusCode, $response->getStatusCode());

		$expectedContent = "<!DOCTYPE html>\n<html>\nthe body\n</html>";
		$this->assertEquals($expectedContent, $response->getContent());
	}

	/**
	 * @test
	 */
	public function createFromRawSetsCookiesCorrectly() {
		$response = Response::createFromRaw(file_get_contents(__DIR__ . '/../Fixtures/RawResponse-1.txt'));
		$this->assertCount(4, $response->getCookies());

		$this->assertInstanceOf('TYPO3\Flow\Http\Cookie', $response->getCookie('tg'));
		$this->assertEquals('426148', $response->getCookie('tg')->getValue());
		$this->assertEquals(1665942816, $response->getCookie('tg')->getExpires());

		$this->assertInstanceOf('TYPO3\Flow\Http\Cookie', $response->getCookie('dmvk'));
		$this->assertEquals('507d9f20317a5', $response->getCookie('dmvk')->getValue());
		$this->assertEquals('example.org', $response->getCookie('dmvk')->getDomain());

		$this->assertInstanceOf('TYPO3\Flow\Http\Cookie', $response->getCookie('ql_n'));
		$this->assertEquals('0', $response->getCookie('ql_n')->getValue());

		$this->assertInstanceOf('TYPO3\Flow\Http\Cookie', $response->getCookie('masscast'));
		$this->assertEquals('null', $response->getCookie('masscast')->getValue());

		foreach ($response->getCookies() as $cookie) {
			$this->assertEquals('/', $cookie->getPath());
		}
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function createFromRawThrowsExceptionOnFirstLine() {
		Response::createFromRaw('No valid response');
	}

	/**
	 * @test
	 */
	public function itIsPossibleToSetTheHttpStatusCodeAndMessage() {
		$response = new Response();
		$response->setStatus(400, 'Really Bad Request');
		$headers = $response->renderHeaders();
		$this->assertEquals('HTTP/1.1 400 Really Bad Request', $headers[0]);
	}

	/**
	 * @test
	 */
	public function setStatusReturnsUnknownStatusMessageOnInvalidCode() {
		$response = new Response();
		$response->setStatus(924);
		$this->assertEquals('924 Unknown Status', $response->getStatus());
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
	public function getStatusReturnsTheStatusCodeAndMessage() {
		$response = new Response();
		$response->setStatus(418);
		$this->assertEquals('418 Sono Vibiemme', $response->getStatus());
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
	public function additionalHeadersCanBeSetAndRetrieved() {
		$response = new Response();
		$response->setStatus(123, 'Custom Status');
		$response->setHeader('MyHeader', 'MyValue');
		$response->setHeader('OtherHeader', 'OtherValue');

		$expectedHeaders = array(
			'HTTP/1.1 123 Custom Status',
			'X-Flow-Powered: Flow/' . FLOW_VERSION_BRANCH,
			'Content-Type: text/html; charset=UTF-8',
			'MyHeader: MyValue',
			'OtherHeader: OtherValue',
		);

		$this->assertEquals($expectedHeaders, $response->renderHeaders());
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
	 * @test
	 */
	public function setNowSetsTheTimeReferenceInGmt() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 +0200');

		$response = new Response();
		$response->setNow($now);

		$this->assertEquals('Tue, 22 May 2012 10:00:00 +0000', $response->getHeader('Date')->format(DATE_RFC2822));
	}

	/**
	 * RFC 2616 / 13.2.3, 14.18
	 *
	 * @test
	 */
	public function responseMustContainDateHeaderAndThusHasOneByDefault() {
		$now = new \DateTime();
		$response = new Response();
		$response->setNow($now);

		$date = $response->getHeader('Date');
		$this->assertEquals($now->getTimestamp(), $date->getTimestamp());
	}

	/**
	 * @test
	 */
	public function setDateAndGetDateSetAndGetTheDateHeader() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
		$response = new Response();

		$response->setDate($now);
		$this->assertEquals($now, $response->getDate());

		$response->setDate('Tue, 22 May 2012 12:00:00 GMT');
		$this->assertEquals($now, $response->getDate());
	}

	/**
	 * @test
	 */
	public function setAndGetLastModifiedSetsTheLastModifiedHeader() {
		$date = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
		$fig = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 21 May 2012 12:00:00 GMT');
		$response = new Response();
		$response->setNow($date);

		$this->assertNull($response->getLastModified());
		$response->setLastModified($fig);
		$this->assertEquals($fig, $response->getLastModified());
	}

	/**
	 * RFC 2616 / 14.21 (Expires)
	 *
	 * @test
	 */
	public function setAndGetExpiresSetsAndRetrievesTheExpiresHeader() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
		$later = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 23 May 2012 12:00:00 GMT');

		$response = new Response();
		$response->setNow($now);
		$response->setExpires($later);
		$this->assertEquals($later, $response->getExpires());
	}

	/**
	 * @test
	 */
	public function getAgeReturnsTheTimePassedSinceTimeSpecifiedInDateHeader() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
		$sixtySecondsAgo = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 11:59:00 GMT');

		$response = new Response();
		$response->setNow($now);
		$response->setHeader('Date', $sixtySecondsAgo);

		$this->assertEquals(60, $response->getAge());
	}

	/**
	 * @test
	 */
	public function getAgeReturnsTimeSpecifiedInAgeHeaderIfExists() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');

		$response = new Response();
		$response->setNow($now);
		$response->setHeader('Age', 123);

		$this->assertSame(123, $response->getAge());
	}

	/**
	 * RFC 2616 / 14.9.1
	 *
	 * @test
	 */
	public function setPublicSetsTheRespectiveCacheControlDirective() {
		$response = new Response();
		$response->setNow(new \DateTime());

		$response->setPublic();
		$this->assertEquals('public', $response->getHeader('Cache-Control'));
	}

	/**
	 * RFC 2616 / 14.9.1
	 *
	 * @test
	 */
	public function setPrivateSetsTheRespectiveCacheControlDirective() {
		$response = new Response();
		$response->setNow(new \DateTime());

		$response->setPrivate();
		$this->assertEquals('private', $response->getHeader('Cache-Control'));
	}

	/**
	 * RFC 2616 / 14.9.4
	 *
	 * @test
	 */
	public function setAndGetMaximumAgeSetsAndReturnsTheMaxAgeCacheControlDirective() {
		$response = new Response();
		$response->setNow(new \DateTime());

		$response->setMaximumAge(60);
		$this->assertEquals('max-age=60', $response->getHeader('Cache-Control'));
		$this->assertSame(60, $response->getMaximumAge());
	}

	/**
	 * RFC 2616 / 14.9.4
	 *
	 * @test
	 */
	public function setAndGetSharedMaximumAgeSetsAndReturnsTheSMaxAgeCacheControlDirective() {
		$response = new Response();
		$response->setNow(new \DateTime());

		$response->setSharedMaximumAge(60);
		$this->assertEquals('s-maxage=60', $response->getHeader('Cache-Control'));
		$this->assertSame(60, $response->getSharedMaximumAge());
	}

	/**
	 * RFC 2616 / 14.9.4
	 *
	 * @test
	 */
	public function makeStandardsCompliantRemovesMaxAgeIfNoCacheExists() {
		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();

		$response->setHeader('Cache-Control', 'no-cache, max-age=240');
		$response->makeStandardsCompliant($request);
		$this->assertEquals('no-cache', $response->getHeader('Cache-Control'));
	}

	/**
	 * RFC 2616 / 4.3 (Message Body)
	 *
	 * 10.1.1 (100 Continue)
	 * 10.1.2 (101 Switching Protocols)
	 * 10.2.5 (204 No Content)
	 * 10.3.5 (304 Not Modified)
	 *
	 * @test
	 */
	public function makeStandardsCompliantRemovesBodyContentIfStatusCodeImpliesIt() {
		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();

		foreach (array(100, 101, 204, 304) as $statusCode) {
			$response->setStatus($statusCode);
			$response->setContent('Body Language');
			$response->makeStandardsCompliant($request);
			$this->assertEquals('', $response->getContent());
		}
	}

	/**
	 * RFC 2616 / 4.4 (Message Length)
	 *
	 * @test
	 */
	public function makeStandardsCompliantRemovesTheContentLengthHeaderIfTransferLengthIsDifferent() {
		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();

		$content = 'Pat grabbed her hat';

		$response->setContent($content);
		$response->setHeader('Transfer-Encoding', 'chunked');
		$response->setHeader('Content-Length', strlen($content));
		$response->makeStandardsCompliant($request);
		$this->assertFalse($response->hasHeader('Content-Length'));
	}

	/**
	 * RFC 2616 / 4.4 (Message Length)
	 *
	 * @test
	 */
	public function makeStandardsCompliantSetsAContentLengthHeaderIfNotPresent() {
		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();

		$content = '
			Pat grabbed her hat
			and her fat, wooden bat
			When her friends couldn\'t play,
			Pat yelled out, "Drat!"
			But then she hit balls
			to her dog and _-at.
		';

		$response->setContent($content);
		$response->makeStandardsCompliant($request);
		$this->assertEquals(strlen($content), $response->getHeader('Content-Length'));
	}

	/**
	 * RFC 2616 / 4.4 (Message Length)
	 *
	 * @test
	 */
	public function makeStandardsCompliantSetsBodyAndContentLengthForHeadRequests() {
		$request = Request::create(new Uri('http://localhost'), 'HEAD');

		$content = '
			Pat grabbed her hat
			and her fat, wooden bat
			When her friends couldn\'t play,
			Pat yelled out, "Drat!"
			But then she hit balls
			to her dog and _-at.
		';

		$response = new Response();
		$response->setContent($content);
		$response->makeStandardsCompliant($request);
		$this->assertEquals('', $response->getContent());
		$this->assertEquals(strlen($content), $response->getHeader('Content-Length'));

		$response = new Response();
		$response->setHeader('Content-Length', 275);
		$response->makeStandardsCompliant($request);
		$this->assertEquals(275, $response->getHeader('Content-Length'));
	}

	/**
	 * RFC 2616 / 14.21 (Expires)
	 *
	 * @test
	 */
	public function makeStandardsCompliantRemovesMaxAgeDireciveIfExpiresHeaderIsPresent() {
		$now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
		$later = \DateTime::createFromFormat(DATE_RFC2822, 'Wed, 23 May 2012 12:00:00 GMT');

		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();
		$response->setNow($now);

		$response->setMaximumAge(60);
		$response->setExpires($later);
		$response->makeStandardsCompliant($request);
		$this->assertSame(NULL, $response->getHeaders()->getCacheControlDirective('max-age'));
		$this->assertEquals($later, $response->getExpires());
	}

	/**
	 * RFC 2616 / 14.25 (If-Modified-Since)
	 *
	 * @test
	 */
	public function makeStandardsCompliantReturns304ResponseIfResourceWasNotModified() {
		$modifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Sun, 20 May 2012 12:00:00 GMT');
		$lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Fr, 18 May 2012 12:00:00 GMT');

		$request = Request::create(new Uri('http://localhost'));
		$response = new Response();

		$request->setHeader('If-Modified-Since', $modifiedSince);
		$response->setLastModified($lastModified);
		$response->setContent('Some Content');
		$response->makeStandardsCompliant($request);

		$this->assertSame(304, $response->getStatusCode());
		$this->assertSame('', $response->getContent());
	}

	/**
	 * RFC 2616 / 14.28 (If-Unmodified-Since)
	 *
	 * @test
	 */
	public function makeStandardsCompliantReturns412StatusIfUnmodifiedSinceDoesNotMatch() {
		$request = Request::create(new Uri('http://localhost'));

		$response = new Response();
		$unmodifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 09:00:00 GMT');
		$lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Sun, 20 May 2012 08:00:00 UTC');
		$request->setHeader('If-Unmodified-Since', $unmodifiedSince);
		$response->setHeader('Last-Modified', $lastModified);
		$response->makeStandardsCompliant($request);
		$this->assertSame(412, $response->getStatusCode());

		$response = new Response();
		$unmodifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 09:00:00 GMT');
		$lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 08:00:00 UTC');
		$request->setHeader('If-Unmodified-Since', $unmodifiedSince);
		$response->setHeader('Last-Modified', $lastModified);
		$response->makeStandardsCompliant($request);

		$this->assertSame(200, $response->getStatusCode());
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
		$this->assertEquals('For never was a story of more woe, Than this of Juliet and her Romeo.', (string)$response);
	}

	/**
	 * @test
	 */
	public function setterMethodsAreChainable() {
		$response = new Response();
		$this->assertSame($response,
			$response->setContent('Foo')
				->appendContent('Bar')
				->setStatus(404)
				->setPublic()
				->setPrivate()
				->setDate(new \DateTime())
				->setMaximumAge(60)
				->setSharedMaximumAge(60)
				->setLastModified(new \DateTime())
				->setExpires(new \DateTime())
		);
	}

	/**
	 * @return array
	 */
	public function contentAndExpectedStringRepresentation() {
		return array(
			array('foo bar', 'foo bar'),
			array(2556, '2556'),
			array(TRUE, '1'),
			array(FALSE, ''),
			array(new \stdClass(), '')
		);
	}

	/**
	 * @test
	 * @dataProvider contentAndExpectedStringRepresentation()
	 */
	public function toStringAlwaysReturnsAStringRepresentationOfContent($content, $expectedString) {
		$response = new Response();
		$response->setContent($content);
		$this->assertSame($expectedString, (string)$response);

	}
}
