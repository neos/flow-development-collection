<?php
namespace Neos\Flow\Tests\Unit\Http\Helper;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Tests for the RequestInformationHelper
 */
class RequestInformationHelperTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderRequestHeadersWillNotDiscloseAuthorizationCredentials()
    {
        $request = ServerRequest::fromGlobals()
            ->withAddedHeader('Authorization', 'Basic SomeUser:SomePassword')
            ->withAddedHeader('Authorization', 'Bearer SomeToken');

        $renderedHeaders = RequestInformationHelper::renderRequestHeaders($request);
        self::assertStringNotContainsString('SomePassword', $renderedHeaders);
        self::assertStringNotContainsString('SomeToken', $renderedHeaders);
    }
}
