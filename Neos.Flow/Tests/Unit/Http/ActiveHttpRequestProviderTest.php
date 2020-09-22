<?php
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

use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\RequestHandlerInterface;
use Neos\Flow\Http\ActiveHttpRequestProvider;
use Neos\Flow\Http\BaseUriProvider;
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Test cases for the ActiveHttpRequestProvider class
 */
class ActiveHttpRequestProviderTest extends UnitTestCase
{

    /**
     * @var ActiveHttpRequestProvider
     */
    private $activeHttpRequestProvider;

    /**
     * @var Bootstrap|MockObject
     */
    private $mockBootstrap;

    /**
     * @var BaseUriProvider|MockObject
     */
    private $mockBaseUriProvider;

    /**
     * @var ServerRequestFactoryInterface|MockObject
     */
    private $mockServerRequestFactory;


    public function setUp(): void
    {
        $this->activeHttpRequestProvider = new ActiveHttpRequestProvider();

        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->activeHttpRequestProvider, 'bootstrap', $this->mockBootstrap);

        $this->mockBaseUriProvider = $this->getMockBuilder(BaseUriProvider::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->activeHttpRequestProvider, 'baseUriProvider', $this->mockBaseUriProvider);

        $this->mockServerRequestFactory = $this->getMockBuilder(ServerRequestFactoryInterface::class)->getMock();
        $this->inject($this->activeHttpRequestProvider, 'serverRequestFactory', $this->mockServerRequestFactory);
    }

    /**
     * @test
     */
    public function getActiveServerRequestReturnsInstanceFromActiveHttpRequestHandler(): void
    {
        $mockHttpRequestHandler = $this->getMockBuilder(HttpRequestHandlerInterface::class)->getMock();

        $mockServerRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $mockHttpRequestHandler->method('getHttpRequest')->willReturn($mockServerRequest);

        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockHttpRequestHandler);

        $this->mockServerRequestFactory->expects(self::never())->method('createServerRequest');
        self::assertSame($mockServerRequest, $this->activeHttpRequestProvider->getActiveHttpRequest());
    }

    /**
     * @test
     */
    public function getActiveServerRequestCreatesANewInstanceIfTheCurrentRequestHandlerIsNotAHttpHandler(): void
    {
        $mockOtherRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockOtherRequestHandler);

        $mockServerRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $this->mockServerRequestFactory->expects(self::once())->method('createServerRequest')->willReturn($mockServerRequest);
        self::assertSame($mockServerRequest, $this->activeHttpRequestProvider->getActiveHttpRequest());
    }

    /**
     * @test
     */
    public function getActiveServerRequestSetsConfiguredBaseUriIfTheCurrentRequestHandlerIsNotAHttpHandler(): void
    {
        $mockOtherRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockOtherRequestHandler);

        $mockServerRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $mockBaseUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockBaseUriProvider->expects(self::once())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willReturn($mockBaseUri);

        $this->mockServerRequestFactory->expects(self::once())->method('createServerRequest')->with('GET', $mockBaseUri)->willReturn($mockServerRequest);
        self::assertSame($mockServerRequest, $this->activeHttpRequestProvider->getActiveHttpRequest());
    }

    /**
     * @test
     */
    public function getActiveServerRequestDefaultBaseUriIfTheCurrentRequestHandlerIsNotAHttpHandlerAndTheBaseUriIsNotConfigured(): void
    {
        $mockOtherRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockOtherRequestHandler);

        $mockServerRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $this->mockBaseUriProvider->expects(self::once())->method('getConfiguredBaseUriOrFallbackToCurrentRequest')->willThrowException(new HttpException());

        $this->mockServerRequestFactory->expects(self::once())->method('createServerRequest')->with('GET', 'http://localhost')->willReturn($mockServerRequest);
        self::assertSame($mockServerRequest, $this->activeHttpRequestProvider->getActiveHttpRequest());
    }

}
