<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use GuzzleHttp\Psr7\Response;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\Mvc\Routing\RoutesProviderInterface;
use Neos\Flow\Mvc\Routing\RoutingMiddleware;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Testcase for the MVC RoutingMiddleware
 */
final class RoutingMiddlewareTest extends UnitTestCase
{
    /**
     * @var RoutingMiddleware
     */
    protected $routingMiddleware;

    /**
     * @var Router|MockObject
     */
    protected $mockRouter;

    /**
     * @var RequestHandlerInterface|MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->routingMiddleware = new RoutingMiddleware();

        $this->mockRouter = $this->createMock(Router::class);
        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->method('getRoutes')->willReturn(Routes::empty());
        $this->inject($this->mockRouter, 'routesProvider', $mockRoutesProvider);

        $this->inject($this->routingMiddleware, 'router', $this->mockRouter);

        $this->mockRequestHandler = $this->createMock(RequestHandlerInterface::class);
        $httpResponse = new Response();
        $this->mockRequestHandler->method('handle')->willReturn($httpResponse);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $this->mockHttpRequest->method('withAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($this->mockHttpRequest);

        $mockRequestUri = $this->createMock(UriInterface::class);
        $this->mockHttpRequest->method('getUri')->willReturn($mockRequestUri);
    }

    #[Test]
    public function handleStoresRouterMatchResultsInTheRequestAttributes()
    {
        $mockMatchResults = ['someRouterMatchResults'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $this->mockRouter->expects($this->atLeastOnce())->method('route')->with($routeContext)->willReturn($mockMatchResults);
        $this->mockHttpRequest->expects($this->atLeastOnce())->method('withAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS, $mockMatchResults);

        $this->routingMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
}
