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

    #[Test]
    public function extractHeadersFromServerVariablesCastsScalarValuesToString()
    {
        $headers = RequestInformationHelper::extractHeadersFromServerVariables([
            'HTTP_X_FORWARDED_PORT' => 8080,
            'CONTENT_LENGTH' => 54,
            'REDIRECT_REMOTE_AUTHORIZATION' => null,
        ]);

        self::assertSame(['X-Forwarded-Port' => '8080', 'Content-Length' => '54', 'Authorization' => ''], $headers);
    }

    #[Test]
    public function serverRequestCanBeCreatedWithHeadersExtractedFromNonStringServerVariables()
    {
        $headers = RequestInformationHelper::extractHeadersFromServerVariables(['HTTP_X_FORWARDED_PORT' => 8080]);
        $request = new ServerRequest('GET', 'http://localhost', $headers);

        self::assertSame('8080', $request->getHeaderLine('X-Forwarded-Port'));
    }
}
