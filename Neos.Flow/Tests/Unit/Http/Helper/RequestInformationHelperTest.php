<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Http\Helper;

use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for the RequestInformationHelper
 */
final class RequestInformationHelperTest extends UnitTestCase
{
    #[Test]
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
