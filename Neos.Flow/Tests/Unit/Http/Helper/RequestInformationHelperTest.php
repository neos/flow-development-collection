<?php
namespace Neos\Flow\Tests\Unit\Http\Helper;

use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\Request;
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
        $request = Request::createFromEnvironment()
            ->withAddedHeader('Authorization', 'Basic SomeUser:SomePassword')
            ->withAddedHeader('Authorization', 'Bearer SomeToken');

        $renderedHeaders = RequestInformationHelper::renderRequestHeaders($request);
        self::assertNotContains('SomePassword', $renderedHeaders);
        self::assertNotContains('SomeToken', $renderedHeaders);
    }
}
