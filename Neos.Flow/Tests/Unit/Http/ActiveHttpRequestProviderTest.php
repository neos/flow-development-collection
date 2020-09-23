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
use Neos\Flow\Http\Exception as HttpException;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

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


    public function setUp(): void
    {
        $this->activeHttpRequestProvider = new ActiveHttpRequestProvider();

        $this->mockBootstrap = $this->getMockBuilder(Bootstrap::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->activeHttpRequestProvider, 'bootstrap', $this->mockBootstrap);
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

        self::assertSame($mockServerRequest, $this->activeHttpRequestProvider->getActiveHttpRequest());
    }

    /**
     * @test
     */
    public function getActiveServerRequestThrowsAnExceptionIfTheCurrentRequestHandlerIsNotAHttpHandler(): void
    {
        $mockOtherRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockBootstrap->method('getActiveRequestHandler')->willReturn($mockOtherRequestHandler);

        $this->expectException(HttpException::class);
        $this->activeHttpRequestProvider->getActiveHttpRequest();
    }

}
