<?php
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
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RoutingMiddleware;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Testcase for the MVC RoutingMiddleware
 */
class RoutingMiddlewareTest extends UnitTestCase
{
    /**
     * @var RoutingMiddleware
     */
    protected $routingMiddleware;

    /**
     * @var Router|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRouter;

    /**
     * @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockConfigurationManager;

    /**
     * @var RequestHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestHandler;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRequestUri;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->routingMiddleware = new RoutingMiddleware();

        $this->mockRouter = $this->getMockBuilder(Router::class)->getMock();
        $this->mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $this->inject($this->mockRouter, 'configurationManager', $this->mockConfigurationManager);

        $this->inject($this->routingMiddleware, 'router', $this->mockRouter);

        $this->mockRequestHandler = $this->getMockBuilder(RequestHandlerInterface::class)->disableOriginalConstructor()->getMock();
        $httpResponse = new Response();
        $this->mockRequestHandler->method('handle')->willReturn($httpResponse);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();
        $this->mockHttpRequest->method('withAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS)->willReturn($this->mockHttpRequest);

        $this->mockRequestUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockHttpRequest->method('getUri')->willReturn($this->mockRequestUri);
    }

    /**
     * @test
     */
    public function handleStoresRouterMatchResultsInTheRequestAttributes()
    {
        $mockMatchResults = ['someRouterMatchResults'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $this->mockRouter->expects(self::atLeastOnce())->method('route')->with($routeContext)->willReturn($mockMatchResults);
        $this->mockHttpRequest->expects(self::atLeastOnce())->method('withAttribute')->with(ServerRequestAttributes::ROUTING_RESULTS, $mockMatchResults);

        $this->routingMiddleware->process($this->mockHttpRequest, $this->mockRequestHandler);
    }
}
