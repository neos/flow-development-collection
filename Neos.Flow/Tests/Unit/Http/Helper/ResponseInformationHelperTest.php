<?php
namespace Neos\Flow\Tests\Unit\Http\Helper;

use Neos\Flow\Http\Helper\ArgumentsHelper;
use Neos\Flow\Http\Helper\ResponseInformationHelper;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Response;
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
        $request = Request::createFromEnvironment();
        $response = new Response();
        $response = $response->withBody(ArgumentsHelper::createContentStreamFromString('12345'));
        self::assertFalse($response->hasHeader('Content-Length'));

        $compliantResponse = ResponseInformationHelper::makeStandardsCompliant($response, $request);
        self::assertTrue($compliantResponse->hasHeader('Content-Length'));
        $contentLengthHeaderValues = $compliantResponse->getHeader('Content-Length');
        // FIXME: After deprecation of non PSR-7 http this should always be an array.
        $contentLengthHeaderValues = is_array($contentLengthHeaderValues) ? reset($contentLengthHeaderValues) : $contentLengthHeaderValues;
        self::assertEquals(5, $contentLengthHeaderValues);
    }
}
