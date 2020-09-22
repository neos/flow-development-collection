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
use Neos\Flow\Http\ActiveServerRequestProvider;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test cases for the ActiveServerRequestProvider class
 */
class ActiveServerRequestProviderTest extends UnitTestCase
{

    /**
     * @var ActiveServerRequestProvider
     */
    private $activeServerRequestProvider;

    /**
     * @var Bootstrap|MockObject
     */
    private $mockBootstrap;

    /**
     * @var ServerRequestFactoryInterface|MockObject
     */
    private $mockServerRequestFactory;


    public function setUp(): void
    {
        $this->activeServerRequestProvider = new ActiveServerRequestProvider();

        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->activeServerRequestProvider, 'bootstrap', $this->mockBootstrap);

        $this->mockServerRequestFactory = $this->getMockBuilder(ServerRequestFactoryInterface::class)->getMock();
        $this->inject($this->activeServerRequestProvider, 'serverRequestFactory', $this->mockServerRequestFactory);
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

        $this->mockServerRequestFactory->expects($this->never())->method('createServerRequest');
        self::assertSame($mockServerRequest, $this->activeServerRequestProvider->getActiveServerRequest());
    }

    /**
     * @test
     */
    public function getActiveServerRequestCreatesANewInstanceIfTheCurrentRequestHandlerIsNotAHttpHandler(): void
    {
        $mockOtherRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockOtherRequestHandler);

        $mockServerRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();

        $this->mockServerRequestFactory->expects($this->once())->method('createServerRequest')->with('GET', 'http://localhost')->willReturn($mockServerRequest);
        self::assertSame($mockServerRequest, $this->activeServerRequestProvider->getActiveServerRequest());
    }

}
