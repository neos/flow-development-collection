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
        $contentLengthHeaderValues = $compliantResponse->getHeader('Content-Length');
        // FIXME: After deprecation of non PSR-7 http this should always be an array.
        $contentLengthHeaderValues = is_array($contentLengthHeaderValues) ? reset($contentLengthHeaderValues) : $contentLengthHeaderValues;
        self::assertEquals(5, $contentLengthHeaderValues);
    }
}
