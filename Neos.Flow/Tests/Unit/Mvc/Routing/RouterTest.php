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
use Neos\Flow\Mvc\Exception\NoMatchingRouteException;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteLifetime;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\RouteTags;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Mvc\Routing\Routes;
use Neos\Flow\Mvc\Routing\RoutesProviderInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

/**
 * Testcase for the MVC Web Router
 *
 */
final class RouterTest extends UnitTestCase
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RouterCachingService|MockObject
     */
    protected $mockRouterCachingService;

    /**
     * @var ServerRequestInterface|MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var UriInterface|MockObject
     */
    protected $mockBaseUri;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->router = $this->getAccessibleMock(Router::class, []);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->method('getRoutes')->willReturn(Routes::empty());
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->inject($this->router, 'logger', $mockSystemLogger);

        $this->mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $this->mockRouterCachingService->method('getCachedResolvedUriConstraints')->willReturn(false);
        $this->mockRouterCachingService->method('getCachedMatchResults')->willReturn(false);
        $this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

        $this->mockHttpRequest = $this->createMock(ServerRequestInterface::class);

        $this->mockBaseUri = $this->createMock(UriInterface::class);
        $this->mockBaseUri->method('getPath')->willReturn('/');
        $this->mockBaseUri->method('withQuery')->willReturn($this->mockBaseUri);
        $this->mockBaseUri->method('withFragment')->willReturn($this->mockBaseUri);
        $this->mockBaseUri->method('withPath')->willReturn($this->mockBaseUri);

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')->willReturn('/');
        $mockUri->method('withQuery')->willReturn($mockUri);
        $mockUri->method('withFragment')->willReturn($mockUri);
        $mockUri->method('withPath')->willReturn($mockUri);
        $this->mockHttpRequest->method('getUri')->willReturn($mockUri);
    }

    #[Test]
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriConstraintsIfAny()
    {
        $routeValues = ['foo' => 'bar'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $route1 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves'])->getMock();
        $route1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);

        $route2 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves', 'getResolvedUriConstraints'])->getMock();
        $route2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $route2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn(UriConstraints::create()->withPath('route2'));

        $route3 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves'])->getMock();
        $route3->expects($this->never())->method('resolves');

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($route1, $route2, $route3));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $resolvedUri = $this->router->resolve($resolveContext);

        self::assertSame('/route2', $resolvedUri->getPath());
    }

    #[Test]
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $this->expectException(NoMatchingRouteException::class);

        $route1 = $this->createMock(Route::class);
        $route1->expects($this->once())->method('resolves')->willReturn(false);

        $route2 = $this->createMock(Route::class);
        $route2->expects($this->once())->method('resolves')->willReturn(false);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($route1, $route2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
    }

    #[Test]
    public function getLastResolvedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastResolvedRoute());
    }

    #[Test]
    public function resolveSetsLastResolvedRoute()
    {
        $routeValues = ['some' => 'route values'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->resolve($resolveContext);

        self::assertSame($mockRoute2, $this->router->getLastResolvedRoute());
    }

    #[Test]
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

    #[Test]
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn(false);
        $mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints, null, null);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame('/resolved/path', (string)$this->router->resolve($resolveContext));
    }

    #[Test]
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCachWithTagsAndCacheLifetime()
    {
        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $routeTags = RouteTags::createFromArray(['foo', 'bar']);
        $routeLifetime = RouteLifetime::fromInt(12345);

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedTags')->willReturn($routeTags);
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedLifetime')->willReturn($routeLifetime);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects($this->once())->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn(false);
        $mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints)->willReturn(false);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame('/resolved/path', (string)$this->router->resolve($resolveContext));
    }

    #[Test]
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->method('getCachedMatchResults')->with($routeContext)->willReturn($cachedMatchResults);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->never())->method('getRoutes');
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        self::assertSame($cachedMatchResults, $this->router->route($routeContext));
    }

    #[Test]
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects($this->once())->method('getMatchResults')->willReturn($matchResults);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects(self::once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn(false);
        $mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($routeContext, $matchResults, null, null);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($matchResults, $this->router->route($routeContext));
    }

    #[Test]
    public function routeStoresMatchResultsInCacheIfNotFoundInCacheWithTagsAndCacheLifetime()
    {
        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $routeTags = RouteTags::createFromArray(['foo', 'bar']);
        $routeLifetime = RouteLifetime::fromInt(12345);

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects($this->once())->method('getMatchResults')->willReturn($matchResults);
        $mockRoute2->expects($this->once())->method('getMatchedTags')->willReturn($routeTags);
        $mockRoute2->expects($this->once())->method('getMatchedLifetime')->willReturn($routeLifetime);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects(self::once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn(false);
        $mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($routeContext, $matchResults, $routeTags, $routeLifetime);
        $this->inject($this->router, 'routerCachingService', $mockRouterCachingService);

        self::assertSame($matchResults, $this->router->route($routeContext));
    }

    #[Test]
    public function getLastMatchedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastMatchedRoute());
    }

    #[Test]
    public function routeSetsLastMatchedRoute()
    {
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects($this->once())->method('getMatchResults')->willReturn([]);

        $mockRoutesProvider = $this->createMock(RoutesProviderInterface::class);
        $mockRoutesProvider->expects($this->once())->method("getRoutes")->willReturn(Routes::create($mockRoute1, $mockRoute2));
        $this->inject($this->router, 'routesProvider', $mockRoutesProvider);

        $this->router->route($routeContext);

        self::assertSame($mockRoute2, $this->router->getLastMatchedRoute());
    }
}
