<?php
namespace Neos\Flow\Tests\Unit\Http\Helper;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use function GuzzleHttp\Psr7\stream_for;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for the ResponseInformationHelper
 */
class ResponseInformationHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function makeStandardCompliantAddsContentLengthHeader()
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response();
        $response = $response->withBody(stream_for('12345'));
        self::assertFalse($response->hasHeader('Content-Length'));

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertTrue($compliantResponse->hasHeader('Content-Length'));
        $contentLengthHeaderValues = $compliantResponse->getHeaderLine('Content-Length');

        self::assertEquals('5', $contentLengthHeaderValues);
    }

    /**
     * @test
     */
    public function makeStandardCompliantEnsuresEmptyBodyForHeadRequests()
    {
        $request = ServerRequest::fromGlobals()->withMethod('HEAD');
        $response = new Response(200, ['X-FOO' => 'bar', 'Content-Length' => 5], '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertEmpty((string)$compliantResponse->getBody());
        self::assertSame($response->getHeaders(), $compliantResponse->getHeaders());
    }

    public function makeStandardCompliantEnsures304BasedOnLastModificationDataProvider()
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
     * @test
     * @dataProvider makeStandardCompliantEnsures304BasedOnLastModificationDataProvider
     */
    public function makeStandardCompliantEnsures304BasedOnLastModification($requestMethod, $requestHeaders, $responseStatus, $responseHeaders, $expoectedStatus)
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

    public function makeStandardCompliantEnsures304BasedOnEtagDataProvider()
    {
        return [
            ['GET', [], 200, [], 200],
            ['HEAD', [], 200, [], 200],
            // when etag matches a 304 result is creatd
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
            // non 200 status responses are ignred
            ['GET', ['If-None-Match' => '"12345"'], 203, ['ETag' => '"12345"'], 203],
            ['HEAD', ['If-None-Match' => '"12345"'], 203, ['ETag' => '"12345"'], 203]
        ];
    }

    /**
     * @test
     * @dataProvider makeStandardCompliantEnsures304BasedOnEtagDataProvider
     */
    public function makeStandardCompliantEnsures304BasedOnEtag($requestMethod, $requestHeaders, $responseStatus, $responseHeaders, $expoectedStatus)
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

    /**
     * @test
     */
    public function makeStandardCompliantEnsuresEmptyBodyOn304Responses()
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response(304, [], '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertEmpty((string)$compliantResponse->getBody());
    }
}
