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
		$headers = $response->renderHeaders();
		$this->assertEquals('HTTP/1.1 200 OK', $headers[0]);
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
			'X-FLOW3-Powered: FLOW3/' . FLOW3_VERSION_BRANCH,
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
}
?>