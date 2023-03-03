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

use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\Tests\UnitTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the MVC Web Router
 *
 */
class RouterTest extends UnitTestCase
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var RouterCachingService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockRouterCachingService;

    /**
     * @var ServerRequestInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ActionRequest|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockActionRequest;

    /**
     * @var UriInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $mockBaseUri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::empty()])->getMock();

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->router, 'logger', $this->mockSystemLogger);

        $this->mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $this->mockRouterCachingService->method('getCachedResolvedUriConstraints')->willReturn(false);
        $this->mockRouterCachingService->method('getCachedMatchResults')->willReturn(false);
        $this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

        $this->mockHttpRequest = $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock();

        $this->mockBaseUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockBaseUri->method('getPath')->willReturn('/');
        $this->mockBaseUri->method('withQuery')->willReturn($this->mockBaseUri);
        $this->mockBaseUri->method('withFragment')->willReturn($this->mockBaseUri);
        $this->mockBaseUri->method('withPath')->willReturn($this->mockBaseUri);

        $mockUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $mockUri->method('getPath')->willReturn('/');
        $mockUri->method('withQuery')->willReturn($mockUri);
        $mockUri->method('withFragment')->willReturn($mockUri);
        $mockUri->method('withPath')->willReturn($mockUri);
        $this->mockHttpRequest->method('getUri')->willReturn($mockUri);

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriConstraintsIfAny()
    {
        $routeValues = ['foo' => 'bar'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $route1 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();
        $route1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);

        $route2 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves', 'getResolvedUriConstraints'])->getMock();
        $route2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $route2->expects(self::atLeastOnce())->method('getResolvedUriConstraints')->willReturn(UriConstraints::create()->withPath('route2'));

        $route3 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();
        $route3->expects(self::never())->method('resolves');

        $mockRoutes = [$route1, $route2, $route3];
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray($mockRoutes)])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $resolvedUri = $router->resolve($resolveContext);
        self::assertSame('/route2', $resolvedUri->getPath());
    }

    /**
     * @test
     */
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $this->expectException(NoMatchingRouteException::class);


        $route1 = $this->createMock(Route::class);
        $route1->expects(self::once())->method('resolves')->willReturn(false);

        $route2 = $this->createMock(Route::class);
        $route2->expects(self::once())->method('resolves')->willReturn(false);

        $mockRoutes = [$route1, $route2];

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray($mockRoutes)])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
    }

    /**
     * @test
     */
    public function getLastResolvedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveSetsLastResolvedRoute()
    {
        $routeValues = ['some' => 'route values'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $mockRoutes = [$mockRoute1, $mockRoute2];
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray($mockRoutes)])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $router->resolve($resolveContext);

        self::assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriIfFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::empty()])->getMock();

        $routeValues = ['some' => 'route values'];
        $mockCachedResolvedUriConstraints = UriConstraints::create()->withPath('cached/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn($mockCachedResolvedUriConstraints);

        $this->inject($router, 'routerCachingService', $mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        self::assertSame('/cached/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects(self::atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);


        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray([$mockRoute1, $mockRoute2])])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);


        $this->mockRouterCachingService->expects(self::once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints);
        self::assertSame('/resolved/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $this->router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::empty()])->getMock();
        $this->inject($this->router, 'logger', $this->mockSystemLogger);

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn($cachedMatchResults);
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($cachedMatchResults, $router->route($routeContext));
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects(self::once())->method('getMatchResults')->willReturn($matchResults);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray([$mockRoute1, $mockRoute2])])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $this->mockRouterCachingService->expects(self::once())->method('storeMatchResults')->with($routeContext, $matchResults);

        self::assertSame($matchResults, $router->route($routeContext));
    }

    /**
     * @test
     */
    public function getLastMatchedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastMatchedRoute());
    }

    /**
     * @test
     */
    public function routeSetsLastMatchedRoute()
    {
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects(self::once())->method('getMatchResults')->willReturn([]);

        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getMockBuilder(Router::class)->setConstructorArgs([Routes::fromArray([$mockRoute1, $mockRoute2])])->getMock();
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $router->route($routeContext);

        self::assertSame($mockRoute2, $router->getLastMatchedRoute());
    }

}
