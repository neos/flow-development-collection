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

    /**
     * @test
     */
    public function makeStandardCompliantEnsuresEmptyBodyOn304Responses()
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response(304, [], '12345');
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertFalse($compliantResponse->hasHeader('Content-Length'));
        self::assertEmpty((string)$compliantResponse->getBody());
    }

    public function makeStandardCompliantRemovesNotAllowedHeadersFrom304ResponseDataProvider()
    {
        return [
            ['Cache-Control' , 'max-age=60' , true],
            ['Content-Location', '/example', true],
            ['Date', 'Wed, 21 Oct 2015 07:28:00 GMT', true],
            ['ETag', '"33a64df551425fcc55e4d42a148795d9f25f89d4"', true],
            ['Expires', 'Wed, 21 Oct 2015 07:28:00 GMT', true],
            ['Vary', 'Accept-Encoding,Cookie', true],
            ['Content-Length', 5, false],
            ['X-Foo', 'bar', false]
        ];
    }

    /**
     * @test
     * @dataProvider makeStandardCompliantRemovesNotAllowedHeadersFrom304ResponseDataProvider
     */
    public function makeStandardCompliantRemovesNotAllowedHeadersFrom304Response($headerName, $headerValue, $keepHeader)
    {
        $request = ServerRequest::fromGlobals();
        $response = new Response(304);
        $response = $response->withHeader($headerName, $headerValue);
        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        if ($keepHeader) {
            self::assertTrue($compliantResponse->hasHeader($headerName));
            self::assertSame($response->getHeader($headerName), $compliantResponse->getHeader($headerName));
        } else {
            self::assertFalse($compliantResponse->hasHeader('Content-Length'));
        }
    }
}
