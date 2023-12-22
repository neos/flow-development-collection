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

use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\RouteLifetime;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\Mvc\Routing\RoutesProviderInterface;
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
     * @var RoutesProviderInterface
     */
    protected $mockRoutesProvider;


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
        $this->router = $this->getAccessibleMock(Router::class, ['dummy']);

        $this->mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $this->mockRoutesProvider->method('getRoutes')->willReturn(Routes::empty());
        $this->inject($this->router, 'routesProvider', $this->mockRoutesProvider);

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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($route1, $route2, $route3));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $resolvedUri = $this->router->resolve($resolveContext);

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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($route1, $route2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->resolve($resolveContext);

        self::assertSame($mockRoute2, $this->router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriIfFoundInCache()
    {
        $routeValues = ['some' => 'route values'];
        $mockCachedResolvedUriConstraints = UriConstraints::create()->withPath('cached/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects($this->once())->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn($mockCachedResolvedUriConstraints);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->never())->method("getRoutes");
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        self::assertSame('/cached/path', (string)$this->router->resolve($resolveContext));
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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn(false);
        $mockRouterCachingService->expects(self::once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints, null, null);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame('/resolved/path', (string)$this->router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCachWithTagsAndCacheLifetime()
    {
        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $routeTags = RouteTags::createFromArray(['foo', 'bar']);
        $routeLifetime = RouteLifetime::fromInt(12345);

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects(self::atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);
        $mockRoute2->expects(self::atLeastOnce())->method('getResolvedTags')->willReturn($routeTags);
        $mockRoute2->expects(self::atLeastOnce())->method('getResolvedLifetime')->willReturn($routeLifetime);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects($this->once())->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn(false);
        $mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints)->willReturn(false);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame('/resolved/path', (string)$this->router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->method('getCachedMatchResults')->with($routeContext)->willReturn($cachedMatchResults);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($cachedMatchResults, $this->router->route($routeContext));
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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects(self::once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn(false);
        $mockRouterCachingService->expects(self::once())->method('storeMatchResults')->with($routeContext, $matchResults, null, null);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($matchResults, $this->router->route($routeContext));
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCacheWithTagsAndCacheLifetime()
    {
        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $routeTags = RouteTags::createFromArray(['foo', 'bar']);
        $routeLifetime = RouteLifetime::fromInt(12345);

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects(self::once())->method('getMatchResults')->willReturn($matchResults);
        $mockRoute2->expects(self::once())->method('getMatchedTags')->willReturn($routeTags);
        $mockRoute2->expects(self::once())->method('getMatchedLifetime')->willReturn($routeLifetime);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects(self::once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn(false);
        $mockRouterCachingService->expects(self::once())->method('storeMatchResults')->with($routeContext, $matchResults, $routeTags, $routeLifetime);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($matchResults, $this->router->route($routeContext));
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

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(new Routes($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->route($routeContext);

        self::assertSame($mockRoute2, $this->router->getLastMatchedRoute());
    }
}
