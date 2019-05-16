<?php
namespace Neos\Flow\Tests\Unit\Http;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use GuzzleHttp\Psr7\Stream;
use Neos\Flow\Http\ContentStream;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Http;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Test case for the Http Response class
 */
class ResponseTest extends UnitTestCase
{
    /**
     * @test
     */
    public function theDefaultStatusHeaderIs200OK()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $headers = ResponseInformationHelper::prepareHeaders($response);
        $this->assertEquals('HTTP/1.1 200 OK', $headers[0]);
    }

    /**
     * Data provider
     */
    public function rawResponses()
    {
        return [
            [file_get_contents(__DIR__ . '/../Fixtures/RawResponse-1.txt'),
                [
                    'Server' => 'Apache/2.2.17 (Ubuntu)',
                    'X-Flow-Powered' => 'Flow/1.2',
                    'Cache-Control' => 'public, s-maxage=600',
                    'Vary' => 'Accept-Encoding',
                    'Content-Encoding' => 'gzip',
                    'Content-Type' => 'text/html; charset=UTF-8',
                    'Content-Length' => 3795,
                    'Date' => 'Wed, 29 Aug 2012 09:03:49 GMT',
                    'Age' => 550,
                    'Via' => '1.1 varnish',
                    'Connection' => 'keep-alive',
                    'Set-Cookie' => 'masscast=null; path=/'
                ]
            , 200],
            [file_get_contents(__DIR__ . '/../Fixtures/RawResponse-2.txt'),
                [
                    'Server' => 'Apache/2.2.17 (Ubuntu)',
                    'Location' => 'http://flow.neos.io/',
                    'Vary' => 'Accept-Encoding',
                    'Content-Encoding' => 'gzip',
                    'Content-Type' => 'text/html; charset=iso-8859-1',
                    'Content-Length' => 243,
                    'Date' => 'Wed, 29 Aug 2012 09:03:46 GMT',
                    'X-Varnish' => 1792566338,
                    'Age' => 0,
                    'Via' => '1.1 varnish',
                    'Connection' => 'keep-alive'
                ]
            , 301]
        ];
    }

    /**
     * @param $rawResponse
     * @param $expectedHeaders
     * @param $expectedStatusCode
     * @test
     * @dataProvider rawResponses
     */
    public function createFromRawSetsHeadersAndStatusCodeCorrectly($rawResponse, $expectedHeaders, $expectedStatusCode)
    {
        $response = ResponseInformationHelper::createFromRaw($rawResponse);
        $this->assertEquals('1.1', $response->getProtocolVersion());

        foreach ($expectedHeaders as $fieldName => $fieldValue) {
            $this->assertTrue($response->hasHeader($fieldName), sprintf('Response does not have expected header %s', $fieldName));
            $this->assertEquals($fieldValue, $response->getHeader($fieldName)[0]);
        }
        foreach ($response->getHeaders() as $fieldName => $fieldValue) {
            $this->assertTrue(isset($expectedHeaders[$fieldName]), sprintf('Response has unexpected header %s', $fieldName));
        }

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());

        $expectedContent = "<!DOCTYPE html>\n<html>\nthe body\n</html>";
        $this->assertEquals($expectedContent, $response->getBody()->getContents());
    }

    /**
     * @test_disabled
     */
    public function createFromRawSetsCookiesCorrectly()
    {
        $response = ResponseInformationHelper::createFromRaw(file_get_contents(__DIR__ . '/../Fixtures/RawResponse-1.txt'));
        $this->assertCount(4, $response->getHeader('Set-Cookie'));

        $cookie = Http\Cookie::createFromRawSetCookieHeader($response->getHeader('Set-Cookie')[0]);

        $this->assertInstanceOf(Http\Cookie::class, $response->getCookie('tg'));
        $this->assertEquals('426148', $response->getCookie('tg')->getValue());
        $this->assertEquals(1665942816, $response->getCookie('tg')->getExpires());

        $this->assertInstanceOf(Http\Cookie::class, $response->getCookie('dmvk'));
        $this->assertEquals('507d9f20317a5', $response->getCookie('dmvk')->getValue());
        $this->assertEquals('example.org', $response->getCookie('dmvk')->getDomain());

        $this->assertInstanceOf(Http\Cookie::class, $response->getCookie('ql_n'));
        $this->assertEquals('0', $response->getCookie('ql_n')->getValue());

        $this->assertInstanceOf(Http\Cookie::class, $response->getCookie('masscast'));
        $this->assertEquals('null', $response->getCookie('masscast')->getValue());

        foreach ($response->getCookies() as $cookie) {
            $this->assertEquals('/', $cookie->getPath());
        }
    }

    /**
     * @test
     */
    public function createFromRawThrowsExceptionOnFirstLine()
    {
        $this->expectException(\InvalidArgumentException::class);
        Response::createFromRaw('No valid response');
    }

    /**
     * @test
     */
    public function startLineEqualsStatusLine()
    {
        $this->expectException(\InvalidArgumentException::class);
        ResponseInformationHelper::createFromRaw('No valid response');
    }

    /**
     * @test
     */
    public function settingVersionHasExpectedImplications()
    {
        $response = ResponseInformationHelper::createFromRaw(file_get_contents(__DIR__ . '/../Fixtures/RawResponse-1.txt'));
        $response = $response->withProtocolVersion('1.0');

        $this->assertEquals('1.0', $response->getProtocolVersion());
    }

    /**
     * @test
     */
    public function itIsPossibleToSetTheHttpStatusCodeAndMessage()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withStatus(400, 'Really Bad Request');
        $headers = ResponseInformationHelper::prepareHeaders($response);
        $this->assertEquals('HTTP/1.1 400 Really Bad Request', $headers[0]);
    }

    /**
     * @test
     */
    public function setStatusReturnsUnknownStatusMessageOnInvalidCode()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withStatus(924);
        // FIXME: Status reason phrase is gone, do we need to take care?
        $this->assertEquals('924', trim($response->getStatusCode() . ' ' . $response->getReasonPhrase()));
    }

    /**
     * @test
     */
    public function setStatusThrowsExceptionOnNonNumericCode()
    {
        $this->expectException(\InvalidArgumentException::class);
        $response = new Response();
        $response->setStatus('400');
    }

    /**
     * @test
     */
    public function getStatusReturnsTheStatusCodeAndMessage()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withStatus(418, 'Sono Vibiemme');
        $this->assertEquals('418 Sono Vibiemme', $response->getStatusCode() .  ' ' . $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function getStatusCodeSolelyReturnsTheStatusCode()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withStatus(418);
        $this->assertEquals(418, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function additionalHeadersCanBeSetAndRetrieved()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withStatus(123, 'Custom Status');
        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        $response = $response->withHeader('MyHeader', 'MyValue');
        $response = $response->withHeader('OtherHeader', 'OtherValue');

        $expectedHeaders = [
            'HTTP/1.1 123 Custom Status',
            'Content-Type: text/html; charset=UTF-8',
            'MyHeader: MyValue',
            'OtherHeader: OtherValue',
        ];



        $this->assertEquals($expectedHeaders, ResponseInformationHelper::prepareHeaders($response));
    }

    /**
     * @test
     */
    public function multipleHeadersCanBeSetAsArray()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        $response = $response->withHeader('MyHeader', ['MyValue-1','MyValue-2','MyValue-3']);
        $response = $response->withStatus(123, 'Custom Status');

        $expectedHeaders = [
            'HTTP/1.1 123 Custom Status',
            'Content-Type: text/html; charset=UTF-8',
            'MyHeader: MyValue-1, MyValue-2, MyValue-3'
        ];

        $this->assertEquals($expectedHeaders, ResponseInformationHelper::prepareHeaders($response));
    }

    /**
     * RFC 2616 / 3.7.1
     *
     * @test
     */
    public function contentTypeHeaderWithMediaTypeTextHtmlIsAddedByDefault()
    {
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Content-Type', 'text/html; charset=UTF-8');
        $charsetHeaders = $response->getHeader('Content-Type');

        $this->assertEquals('text/html; charset=UTF-8', $charsetHeaders[0]);
    }

    /**
     * @test
     */
    public function setNowSetsTheTimeReferenceInGmt()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 +0200');

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->setTimezone(new \DateTimeZone('GMT'))->format(DATE_RFC2822));

        $this->assertEquals('Tue, 22 May 2012 10:00:00 +0000', $response->getHeaderLine('Date'));
    }

    /**
     * RFC 2616 / 13.2.3, 14.18
     *
     * @test
     */
    public function responseMustContainDateHeaderAndThusHasOneByDefault()
    {
        $now = new \DateTime();
        $now = $now->setTimezone(new \DateTimeZone('GMT'));

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));

        $date = $response->getHeaderLine('Date');
        $this->assertEquals($now->format(DATE_RFC2822), $date);
    }

    /**
     * @test
     */
    public function setDateAndGetDateSetAndGetTheDateHeader()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
        $response = new \GuzzleHttp\Psr7\Response();

        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $this->assertEquals($now->format(DATE_RFC2822), $response->getHeaderLine('Date'));

        $response = $response->withHeader('Date', 'Tue, 22 May 2012 12:00:00 +0000');
        $this->assertEquals($now->format(DATE_RFC2822), $response->getHeaderLine('Date'));
    }

    /**
     * @test
     */
    public function setAndGetLastModifiedSetsTheLastModifiedHeader()
    {
        $date = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
        $fig = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 21 May 2012 12:00:00 GMT');
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $date->format(DATE_RFC2822));

        $this->assertEmpty($response->getHeaderLine('Last-Modified'));
        $response = $response->withHeader('Last-Modified', $fig->format(DATE_RFC2822));
        $this->assertEquals($fig->format(DATE_RFC2822), $response->getHeaderLine('Last-Modified'));
    }

    /**
     * RFC 2616 / 14.21 (Expires)
     *
     * @test
     */
    public function setAndGetExpiresSetsAndRetrievesTheExpiresHeader()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
        $later = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 23 May 2012 12:00:00 GMT');

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Expires', $later->format(DATE_RFC2822));
        $this->assertEquals($later->format(DATE_RFC2822), $response->getHeaderLine('Expires'));
    }

    /**
     * @test_disabled
     */
    public function getAgeReturnsTheTimePassedSinceTimeSpecifiedInDateHeader()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
        $sixtySecondsAgo = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 11:59:00 GMT');

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response->setNow($now);
        $response->setHeader('Date', $sixtySecondsAgo);

        $this->assertEquals(60, $response->getHeader('Age'));
    }

    /**
     * @test
     */
    public function getAgeReturnsTimeSpecifiedInAgeHeaderIfExists()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Age', 123);

        $this->assertSame('123', $response->getHeaderLine('Age'));
    }

    /**
     * RFC 2616 / 14.9.1
     *
     * @test
     */
    public function setPublicSetsTheRespectiveCacheControlDirective()
    {
        $now = new \DateTime();
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Cache-Control', 'public');

        $this->assertEquals('public', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * RFC 2616 / 14.9.1
     *
     * @test
     */
    public function setPrivateSetsTheRespectiveCacheControlDirective()
    {
        $now = new \DateTime();
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Cache-Control', 'private');

        $this->assertEquals('private', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * RFC 2616 / 14.9.4
     *
     * @test
     */
    public function setAndGetMaximumAgeSetsAndReturnsTheMaxAgeCacheControlDirective()
    {
        $now = new \DateTime();
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Cache-Control', 'max-age=60');

        $this->assertEquals('max-age=60', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * RFC 2616 / 14.9.4
     *
     * @test
     */
    public function setAndGetSharedMaximumAgeSetsAndReturnsTheSMaxAgeCacheControlDirective()
    {
        $now = new \DateTime();
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Cache-Control', 's-maxage=60');

        $this->assertEquals('s-maxage=60', $response->getHeaderLine('Cache-Control'));
    }

    /**
     * RFC 2616 / 14.9.4
     *
     * @test«
     */
    public function makeStandardsCompliantRemovesMaxAgeIfNoCacheExists()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();

        $response = $response->withHeader('Cache-Control', 'no-cache, max-age=240');
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertEquals('no-cache', $response->getHeaderLine('Cache-Control'));
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
    public function makeStandardsCompliantRemovesBodyContentIfStatusCodeImpliesIt()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();

        foreach ([100, 101, 204, 304] as $statusCode) {
            $response = $response->withStatus($statusCode);
            $fileHandle = fopen('php://temp', 'r+');
            fwrite($fileHandle, 'Body Language');
            rewind($fileHandle);
            $response = $response->withBody(new Stream($fileHandle));
            $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
            $this->assertEquals('', $response->getBody()->getContents());
        }
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantRemovesTheContentLengthHeaderIfTransferLengthIsDifferent()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();

        $content = 'Pat grabbed her hat';
        $fileHandle = fopen('php://temp', 'r+');
        fwrite($fileHandle, $content);
        rewind($fileHandle);
        $response = $response->withBody(new Stream($fileHandle));

        $response = $response->withHeader('Transfer-Encoding', 'chunked');
        $response = $response->withHeader('Content-Length', strlen($content));
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertFalse($response->hasHeader('Content-Length'));
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantSetsAContentLengthHeaderIfNotPresent()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();

        $content = '
			Pat grabbed her hat
			and her fat, wooden bat
			When her friends couldn\'t play,
			Pat yelled out, "Drat!"
			But then she hit balls
			to her dog and _-at.
		';

        $fileHandle = fopen('php://temp', 'r+');
        fwrite($fileHandle, $content);
        rewind($fileHandle);
        $response = $response->withBody(new Stream($fileHandle));

        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertEquals(strlen($content), $response->getHeaderLine('Content-Length'));
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantSetsBodyAndContentLengthForHeadRequests()
    {
        $request = new \GuzzleHttp\Psr7\Request('HEAD', new Uri('http://localhost'));

        $content = '
			Pat grabbed her hat
			and her fat, wooden bat
			When her friends couldn\'t play,
			Pat yelled out, "Drat!"
			But then she hit balls
			to her dog and _-at.
		';

        $response = new \GuzzleHttp\Psr7\Response();
        $fileHandle = fopen('php://temp', 'r+');
        fwrite($fileHandle, $content);
        rewind($fileHandle);
        $response = $response->withBody(new Stream($fileHandle));

        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertEquals('', $response->getBody()->getContents());
        $this->assertEquals(strlen($content), $response->getHeaderLine('Content-Length'));

        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Content-Length', 275);
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertEquals(275, $response->getHeaderLine('Content-Length'));
    }

    /**
     * RFC 2616 / 14.21 (Expires)
     *
     * @test
     */
    public function makeStandardsCompliantRemovesMaxAgeDirectiveIfExpiresHeaderIsPresent()
    {
        $now = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 22 May 2012 12:00:00 GMT');
        $later = \DateTime::createFromFormat(DATE_RFC2822, 'Wed, 23 May 2012 12:00:00 GMT');

        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
//        $request = Request::create(new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();
        $response = $response->withHeader('Date', $now->format(DATE_RFC2822));
        $response = $response->withHeader('Age', 60);
        $response = $response->withHeader('Expires', $later->format(DATE_RFC2822));

        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertFalse(stripos($response->getHeader('Cache-Control')[0], 'max-age'));
        $this->assertEquals($later->format(DATE_RFC2822), $response->getHeader('Expires')[0]);
    }

    /**
     * RFC 2616 / 14.25 (If-Modified-Since)
     *
     * @test
     */
    public function makeStandardsCompliantReturns304ResponseIfResourceWasNotModified()
    {
        $modifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Sun, 20 May 2012 12:00:00 GMT');
        $lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Fri, 18 May 2012 12:00:00 GMT');

        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));
        $response = new \GuzzleHttp\Psr7\Response();

        $request = $request->withHeader('If-Modified-Since', $modifiedSince->format(DATE_RFC2822));
        $response = $response->withHeader('Last-Modified', $lastModified->format(DATE_RFC2822));
//        $response->setLastModified($lastModified);
        $response->withBody(ContentStream::fromContents('Some Content'));
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);

        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getBody()->getContents());
    }

    /**
     * RFC 2616 / 14.28 (If-Unmodified-Since)
     *
     * @test
     */
    public function makeStandardsCompliantReturns412StatusIfUnmodifiedSinceDoesNotMatch()
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', new Uri('http://localhost'));

        $response = new \GuzzleHttp\Psr7\Response();
        $unmodifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 09:00:00 GMT');
        $lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Sun, 20 May 2012 08:00:00 UTC');
        $request = $request->withHeader('If-Unmodified-Since', $unmodifiedSince->format(DATE_RFC2822));
        $response = $response->withHeader('Last-Modified', $lastModified->format(DATE_RFC2822));
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertSame(412, $response->getStatusCode());

        $response = new \GuzzleHttp\Psr7\Response();
        $unmodifiedSince = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 09:00:00 GMT');
        $lastModified = \DateTime::createFromFormat(DATE_RFC2822, 'Tue, 15 May 2012 08:00:00 UTC');
        $request = $request->withHeader('If-Unmodified-Since', $unmodifiedSince->format(DATE_RFC2822));
        $response = $response->withHeader('Last-Modified', $lastModified->format(DATE_RFC2822));
        $response = Http\Helper\ResponseInformationHelper::makeStandardsCompliant($response, $request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
