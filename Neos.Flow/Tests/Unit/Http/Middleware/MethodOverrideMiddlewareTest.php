<?php
namespace Neos\Flow\Tests\Unit\Http\Component;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http\Middleware\MethodOverrideMiddleware;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MethodOverrideMiddlewareTest extends UnitTestCase
{

    /**
     * @var MethodOverrideMiddleware
     */
    private $middleware;

    /**
     * @var RequestHandlerInterface|MockObject
     */
    private $mockRequestHandler;

    /**
     * @var ResponseInterface|MockObject
     */
    private $mockResponse;

    public function setUp(): void
    {
        $this->middleware = new MethodOverrideMiddleware();

        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->getMock();
        $this->mockResponse = $this->getMockBuilder(ResponseInterface::class)->getMock();
    }

    public function matchingRequests_dataProvider(): \Traversable
    {
        yield 'parsedBody (__method)' => ['method' => 'POST', 'headers' => [], 'parsedBody' => ['__method' => 'PUT'], 'expectedMethod' => 'PUT'];
        yield 'header (X-Http-Method-Override)' => ['method' => 'POST', 'headers' => ['X-Http-Method-Override' => 'PATCH'], 'parsedBody' => [], 'expectedMethod' => 'PATCH'];
        yield 'header (X-Http-Method header)' => ['method' => 'POST', 'headers' => ['X-Http-Method' => 'DELETE'], 'parsedBody' => [], 'expectedMethod' => 'DELETE'];
        yield 'parsedBody and X-Http-Method header' => ['method' => 'POST', 'headers' => ['X-Http-Method' => 'DELETE'], 'parsedBody' => ['__method' => 'PUT'], 'expectedMethod' => 'PUT'];
        yield 'X-Http-Method-Override and X-Http-Method header' => ['method' => 'POST', 'headers' => ['X-Http-Method-Override' => 'PATCH', 'X-Http-Method' => 'DELETE'], 'parsedBody' => [], 'expectedMethod' => 'PATCH'];
        yield 'parsedBody and both headers' => ['method' => 'POST', 'headers' => ['X-Http-Method-Override' => 'PATCH', 'X-Http-Method' => 'DELETE'], 'parsedBody' => ['__method' => 'PUT'], 'expectedMethod' => 'PUT'];
    }

    /**
     * @test
     * @dataProvider matchingRequests_dataProvider
     */
    public function process_matchingRequests(string $method, array $headers, array $parsedBody, string $expectedMethod): void
    {
        $mockRequest = $this->prepareMockRequest($method, $headers, $parsedBody);
        $mockAlteredRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $mockRequest->expects(self::once())->method('withMethod')->with($expectedMethod)->willReturn($mockAlteredRequest);
        $this->mockRequestHandler->expects(self::once())->method('handle')->willReturnCallback(function ($request) use ($mockAlteredRequest) {
            self::assertSame($request, $mockAlteredRequest);
            return $this->mockResponse;
        });

        $this->middleware->process($mockRequest, $this->mockRequestHandler);
    }

    public function nonMatchingRequests_dataProvider(): \Traversable
    {
        yield 'POST request' => ['method' => 'POST', 'headers' => [], 'parsedBody' => ['foo' => 'bar']];
        yield 'GET request with X-Http-Method-Override and X-Http-Method header' => ['method' => 'GET', 'headers' => ['X-Http-Method-Override' => 'PATCH', 'X-Http-Method' => 'DELETE'], 'parsedBody' => []];
        yield 'DELETE request with parsedBody' => ['method' => 'DELETE', 'headers' => [], 'parsedBody' => ['__method' => 'PUT']];
    }

    /**
     * @test
     * @dataProvider nonMatchingRequests_dataProvider
     */
    public function process_nonMatchingRequests(string $method, array $headers, array $parsedBody): void
    {
        $mockRequest = $this->prepareMockRequest($method, $headers, $parsedBody);
        $mockRequest->expects(self::never())->method('withMethod');
        $this->mockRequestHandler->expects(self::once())->method('handle')->willReturnCallback(function ($request) use ($mockRequest) {
            self::assertSame($request, $mockRequest);
            return $this->mockResponse;
        });

        $this->middleware->process($mockRequest, $this->mockRequestHandler);
    }


    /** ------------------ */

    /**
     * @param string $method
     * @param array $headers
     * @param array $parsedBody
     * @return ServerRequestInterface|MockObject
     */
    private function prepareMockRequest(string $method, array $headers, array $parsedBody)
    {
        $mockRequest = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $mockRequest->method('getMethod')->willReturn($method);
        $mockRequest->method('getParsedBody')->willReturn($parsedBody);
        $mockRequest->method('hasHeader')->willReturnCallback(function ($header) use ($headers) {
            return isset($headers[$header]);
        });
        $mockRequest->method('getHeaderLine')->willReturnCallback(function ($header) use ($headers) {
            return $headers[$header];
        });
        return $mockRequest;
    }
}
