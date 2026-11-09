<?php

declare(strict_types=1);

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test case for the BaseUriProvider class
 */
final class BaseUriProviderTest extends UnitTestCase
{
    /**
     * @var BaseUriProvider
     */
    private $baseUriProvider;

    protected function setUp(): void
    {
        $this->baseUriProvider = new BaseUriProvider();
    }

    #[Test]
    public function getConfiguredBaseUriOrFallbackToCurrentRequestReturnsConfiguredBaseUriByDefault(): void
    {
        $configuredBaseUri = 'http://some-base.uri/';
        $this->inject($this->baseUriProvider, 'configuredBaseUri', $configuredBaseUri);

        self::assertSame($configuredBaseUri, (string)$this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest());
    }

    #[Test]
    public function getConfiguredBaseUriOrFallbackToCurrentRequestReturnsBaseUriOfCurrentlyActiveRequestIfNoBaseUriIsConfigured(): void
    {
        $mockBootstrap = $this->createMock(Bootstrap::class);
        $mockHttpRequestHandler = $this->createMock(HttpRequestHandlerInterface::class);
        $mockServerRequest = $this->createMock(ServerRequestInterface::class);
        $uri = new Uri('http://uri-from-current-request/some/path');
        $mockServerRequest->method('getUri')->willReturn($uri);
        $mockHttpRequestHandler->method('getHttpRequest')->willReturn($mockServerRequest);
        $mockBootstrap->method('getActiveRequestHandler')->willReturn($mockHttpRequestHandler);

        $this->inject($this->baseUriProvider, 'bootstrap', $mockBootstrap);

        self::assertSame('http://uri-from-current-request/', (string)$this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest());
    }

    #[Test]
    public function getConfiguredBaseUriOrFallbackToCurrentRequestReturnsBaseUriFromFallbackRequestIfNoBaseUriIsConfiguredAndCurrentHttpRequestCantBeDetermined(): void
    {
        $mockBootstrap = $this->createMock(Bootstrap::class);
        $mockNonHttpRequestHandler = $this->createStub(RequestHandlerInterface::class);
        $mockBootstrap->method('getActiveRequestHandler')->willReturn($mockNonHttpRequestHandler);

        $this->inject($this->baseUriProvider, 'bootstrap', $mockBootstrap);

        $mockFallbackRequest = $this->createMock(ServerRequestInterface::class);
        $uri = new Uri('https://uri-from-fallback-request/some/path');
        $mockFallbackRequest->method('getUri')->willReturn($uri);

        self::assertSame('https://uri-from-fallback-request/', (string)$this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest($mockFallbackRequest));
    }

    #[Test]
    public function getConfiguredBaseUriOrFallbackToCurrentRequestThrowsExceptionIfNoBaseUriIsConfiguredAndCurrentHttpRequestCantBeDeterminedAndNoFallbackRequestIsSpecified(): void
    {
        $mockBootstrap = $this->createMock(Bootstrap::class);
        $mockNonHttpRequestHandler = $this->createStub(RequestHandlerInterface::class);
        $mockBootstrap->method('getActiveRequestHandler')->willReturn($mockNonHttpRequestHandler);

        $this->inject($this->baseUriProvider, 'bootstrap', $mockBootstrap);

        $this->expectException(HttpException::class);
        $this->baseUriProvider->getConfiguredBaseUriOrFallbackToCurrentRequest();
    }
}
