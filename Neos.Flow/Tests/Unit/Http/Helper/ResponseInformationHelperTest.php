<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http\Helper;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Tests\UnitTestCase;
use function GuzzleHttp\Psr7\stream_for;

/**
 * Tests for the ResponseInformationHelper
 */
class ResponseInformationHelperTest extends UnitTestCase
{
    /**
     * RFC 2616 / 14.9.4
     *
     * @test
     */
    public function makeStandardsCompliantRemovesMaxAgeIfNoCacheIsSet()
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response(200, ['Cache-Control' => 'no-cache, max-age=240']);

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertEquals('no-cache', $compliantResponse->getHeaderLine('Cache-Control'));
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantRemovesTheContentLengthHeaderIfTransferLengthIsDifferent()
    {
        $content = 'Pat grabbed her hat';

        $request = ServerRequest::fromGlobals();
        $response = new Response(200, ['Transfer-Encoding' => 'chunked', 'Content-Length' => strlen($content)], $content);

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertFalse($compliantResponse->hasHeader('Content-Length'));
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantSetsAContentLengthHeaderIfNotPresent()
    {
        $content = '
            Pat grabbed her hat
            and her fat, wooden bat
            When her friends couldn\'t play,
            Pat yelled out, "Drat!"
            But then she hit balls
            to her dog and _-at.
        ';

        $request = ServerRequest::fromGlobals();
        $response = new Response(200, [], $content);
        self::assertFalse($response->hasHeader('Content-Length'));

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertTrue($compliantResponse->hasHeader('Content-Length'));
        $this->assertEquals(strlen($content), $compliantResponse->getHeaderLine('Content-Length'));
    }

    /**
     * RFC 2616 / 4.4 (Message Length)
     *
     * @test
     */
    public function makeStandardsCompliantEnsuresEmptyBodyForHeadRequests()
    {
        $request = ServerRequest::fromGlobals()->withMethod('HEAD');
        $response = new Response(200, ['X-FOO' => 'bar', 'Content-Length' => 5], '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertEmpty((string)$compliantResponse->getBody());
        self::assertSame($response->getHeaders(), $compliantResponse->getHeaders());
    }

    public function makeStandardsCompliantEnsures304BasedOnLastModificationDataProvider(): array
    {
        return [
            ['GET', [], 200, [], 200],
            ['HEAD', [], 200, [], 200],
            // last modification was same as client value
            ['GET', ['If-Modified-Since' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 304],
            ['HEAD', ['If-Modified-Since' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 304],
            // last modification was before client value
            ['GET', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 304],
            ['HEAD', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 304],
            // last modification was after client value
            ['GET', ['If-Modified-Since' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 20 Nov 1994 12:45:26 GMT'], 200],
            ['HEAD', ['If-Modified-Since' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 20 Nov 1994 12:45:26 GMT'], 200],
            // methods other than get and head are ignored
            ['PUT', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200],
            ['POST', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200],
            ['DELETE', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 200, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 200],
            // status codes other tan 200 are ignored
            ['GET', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 203, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 203],
            ['HEAD', ['If-Modified-Since' => 'Tue, 10 Nov 1994 12:45:26 GMT'], 203, ['Last-Modified' => 'Tue, 15 Nov 1994 12:45:26 GMT'], 203]
        ];
    }

    /**
     * RFC 2616 / 14.25 (If-Modified-Since)
     *
     * @test
     * @dataProvider makeStandardsCompliantEnsures304BasedOnLastModificationDataProvider
     */
    public function makeStandardsCompliantEnsures304BasedOnLastModification($requestMethod, $requestHeaders, $responseStatus, $responseHeaders, $expoectedStatus)
    {
        $request = ServerRequest::fromGlobals()->withMethod($requestMethod);
        if ($requestHeaders) {
            foreach ($requestHeaders as $headeName => $headerValue) {
                $request = $request->withHeader($headeName, $headerValue);
            }
        }
        $response = new Response($responseStatus, $responseHeaders, '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertSame($expoectedStatus, $compliantResponse->getStatusCode());
    }

    public function makeStandardsCompliantEnsures304BasedOnEtagDataProvider(): array
    {
        return [
            ['GET', [], 200, [], 200],
            ['HEAD', [], 200, [], 200],
            // when etag matches a 304 result is created
            ['GET', ['If-None-Match' => '"12345"'], 200, ['ETag' => '"12345"'], 304],
            ['HEAD', ['If-None-Match' => '"12345"'], 200, ['ETag' => '"12345"'], 304],
            // multiple if-none-match headers
            ['HEAD', ['If-None-Match' => ['"abcd"', '"12345"', '"5678"']], 200, ['ETag' => '"12345"'], 304],
            ['HEAD', ['If-None-Match' => ['"abcd"', '"56789"', '"defg"']], 200, ['ETag' => '"12345"'], 200],
            // etags comparison ignores weakness indicator
            ['GET', ['If-None-Match' => 'W/"12345"'], 200, ['ETag' => '"12345"'], 304],
            ['GET', ['If-None-Match' => '"12345"'], 200, ['ETag' => 'W/"12345"'], 304],
            ['GET', ['If-None-Match' => 'W/"12345"'], 200, ['ETag' => 'W/"12345"'], 304],
            ['HEAD', ['If-None-Match' => 'W/"12345"'], 200, ['ETag' => '"12345"'], 304],
            ['HEAD', ['If-None-Match' => '"12345"'], 200, ['ETag' => 'W/"12345"'], 304],
            ['HEAD', ['If-None-Match' => 'W/"12345"'], 200, ['ETag' => 'W/"12345"'], 304],
            // other http methods are ignored
            ['PUT', ['If-None-Match' => '"12345"'], 200, ['ETag' => '"12345"'], 200],
            ['POST', ['If-None-Match' => '"12345"'], 200, ['ETag' => '"12345"'], 200],
            ['DELETE', ['If-None-Match' => '"12345"'], 200, ['ETag' => '"12345"'], 200],
            // non 200 status responses are ignored
            ['GET', ['If-None-Match' => '"12345"'], 203, ['ETag' => '"12345"'], 203],
            ['HEAD', ['If-None-Match' => '"12345"'], 203, ['ETag' => '"12345"'], 203]
        ];
    }

    /**
     * @test
     * @dataProvider makeStandardsCompliantEnsures304BasedOnEtagDataProvider
     */
    public function makeStandardsCompliantEnsures304BasedOnEtag($requestMethod, $requestHeaders, $responseStatus, $responseHeaders, $expoectedStatus)
    {
        $request = ServerRequest::fromGlobals()->withMethod($requestMethod);
        if ($requestHeaders) {
            foreach ($requestHeaders as $headerName => $headerValue) {
                $request = $request->withHeader($headerName, $headerValue);
            }
        }
        $response = new Response($responseStatus, $responseHeaders, '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertSame($expoectedStatus, $compliantResponse->getStatusCode());
    }

    /**
     * RFC 2616 / 14.28 (If-Unmodified-Since)
     *
     * @test
     */
    public function makeStandardsCompliantReturns412StatusIfUnmodifiedSinceDoesNotMatch()
    {
        $unmodifiedSince = 'Tue, 15 May 2012 09:00:00 GMT';
        $request = ServerRequest::fromGlobals()->withHeader('If-Unmodified-Since', $unmodifiedSince);
        $lastModified = 'Sun, 20 May 2012 08:00:00 UTC';
        $response = new Response(200, ['Last-Modified' => $lastModified]);
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertSame(412, $compliantResponse->getStatusCode());

        $unmodifiedSince = $lastModified = 'Tue, 15 May 2012 09:00:00 GMT';
        $request = ServerRequest::fromGlobals()->withHeader('If-Unmodified-Since', $unmodifiedSince);
        $response = new Response(200, ['Last-Modified' => $lastModified]);
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertSame(200, $compliantResponse->getStatusCode(), 'Status code should have been left unchanged at 200');
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
        foreach ([100, 101, 204, 304] as $statusCode) {
            $request = ServerRequest::fromGlobals();
            $response = new Response($statusCode, [], '12345');
            $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
            self::assertEmpty((string)$compliantResponse->getBody());
        }
    }

    /**
     * RFC 2616 / 14.21 (Expires)
     *
     * @test
     */
    public function makeStandardsCompliantRemovesMaxAgeDireciveIfExpiresHeaderIsPresent()
    {
        $expires = 'Tue, 19 Jan 2038 03:14:07 GMT';

        $request = ServerRequest::fromGlobals();
        $response = new Response(200, ['Expires' => $expires, 'Cache-Control' => 'public, max-age=12345']);

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        $this->assertSame($compliantResponse->getHeaderLine('Cache-Control'), 'public');
        $this->assertSame($expires, $response->getHeaderLine('Expires'));
    }

    /**
     * @test
     */
    public function makeStandardCompliantEnsuresCorrectCacheControlHeader()
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response(200, ['Cache-Control' => 'must-revalidate']);
        $response = $response->withBody(stream_for(''));
        self::assertTrue($response->hasHeader('Cache-Control'));

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertTrue($compliantResponse->hasHeader('Cache-Control'));
        $cacheControlHeaderValue = $compliantResponse->getHeaderLine('Cache-Control');

        self::assertEquals('must-revalidate', $cacheControlHeaderValue);
    }
}
