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
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
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
use Neos\Flow\Tests\UnitTestCase;
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
        $this->inject($this->router, 'logger', $this->createStub(LoggerInterface::class));

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
    public function resolveCallsCreateRoutesFromConfiguration()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        // not saying anything, but seems better than to expect the exception we'd get otherwise
        /** @var Route|MockObject $mockRoute */
        $mockRoute = $this->createMock(Route::class);
        $mockRoute->expects($this->once())->method('resolves')->willReturn(true);
        $mockRoute->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $this->inject($router, 'routes', [$mockRoute]);

        // this we actually want to know
        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
    }

    #[Test]
    public function createRoutesFromConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt()
    {
        $routesConfiguration = [];
        $routesConfiguration['route1']['uriPattern'] = 'number1';
        $routesConfiguration['route2']['uriPattern'] = 'number2';
        $routesConfiguration['route3'] = [
            'name' => 'route3',
            'defaults' => ['foodefault'],
            'routeParts' => ['fooroutepart'],
            'uriPattern' => 'number3',
            'toLowerCase' => false,
            'appendExceedingArguments' => true,
            'httpMethods' => ['POST', 'PUT']
        ];

        $this->router->setRoutesConfiguration($routesConfiguration);
        $this->router->_call('createRoutesFromConfiguration');

        /** @var Route[] $createdRoutes */
        $createdRoutes = $this->router->_get('routes');

        self::assertEquals('number1', $createdRoutes[0]->getUriPattern());
        self::assertTrue($createdRoutes[0]->isLowerCase());
        self::assertFalse($createdRoutes[0]->getAppendExceedingArguments());
        self::assertEquals('number2', $createdRoutes[1]->getUriPattern());
        self::assertFalse($createdRoutes[1]->hasHttpMethodConstraints());
        self::assertEquals([], $createdRoutes[1]->getHttpMethods());
        self::assertEquals('route3', $createdRoutes[2]->getName());
        self::assertEquals(['foodefault'], $createdRoutes[2]->getDefaults());
        self::assertEquals(['fooroutepart'], $createdRoutes[2]->getRoutePartsConfiguration());
        self::assertEquals('number3', $createdRoutes[2]->getUriPattern());
        self::assertFalse($createdRoutes[2]->isLowerCase());
        self::assertTrue($createdRoutes[2]->getAppendExceedingArguments());
        self::assertTrue($createdRoutes[2]->hasHttpMethodConstraints());
        self::assertEquals(['POST', 'PUT'], $createdRoutes[2]->getHttpMethods());
    }

    #[Test]
    public function createRoutesFromConfigurationThrowsExceptionIfOnlySomeRoutesWithTheSameUriPatternHaveHttpMethodConstraints()
    {
        $this->expectException(InvalidRouteSetupException::class);
        $routesConfiguration = [
            [
                'uriPattern' => 'somePattern'
            ],
            [
                'uriPattern' => 'somePattern',
                'httpMethods' => ['POST', 'PUT']
            ],
        ];
        shuffle($routesConfiguration);
        $this->router->setRoutesConfiguration($routesConfiguration);
        $this->router->_call('createRoutesFromConfiguration');
    }

    #[Test]
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriConstraintsIfAny()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));
        $routeValues = ['foo' => 'bar'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $route1 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves'])->getMock();
        $route1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);

        $route2 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves', 'getResolvedUriConstraints'])->getMock();
        $route2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $route2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn(UriConstraints::create()->withPath('route2'));

        $route3 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->onlyMethods(['resolves'])->getMock();
        $route3->expects($this->never())->method('resolves');

        $mockRoutes = [$route1, $route2, $route3];

        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->_set('routes', $mockRoutes);

        $resolvedUri = $router->resolve($resolveContext);
        self::assertSame('/route2', $resolvedUri->getPath());
    }

    #[Test]
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $this->expectException(NoMatchingRouteException::class);
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $route1 = $this->createMock(Route::class);
        $route1->expects($this->once())->method('resolves')->willReturn(false);

        $route2 = $this->createMock(Route::class);
        $route2->expects($this->once())->method('resolves')->willReturn(false);

        $mockRoutes = [$route1, $route2];

        $router->_set('routes', $mockRoutes);

        $router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
    }

    #[Test]
    public function getLastResolvedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastResolvedRoute());
    }

    #[Test]
    public function resolveSetsLastResolvedRoute()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));


        $routeValues = ['some' => 'route values'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->resolve($resolveContext);

        self::assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    #[Test]
    public function resolveReturnsCachedResolvedUriIfFoundInCache()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $routeValues = ['some' => 'route values'];
        $mockCachedResolvedUriConstraints = UriConstraints::create()->withPath('cached/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn($mockCachedResolvedUriConstraints);
        $router->_set('routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');
        self::assertSame('/cached/path', (string)$router->resolve($resolveContext));
    }

    #[Test]
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints, null, null);
        self::assertSame('/resolved/path', (string)$router->resolve($resolveContext));
    }

    #[Test]
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCachWithTagsAndCacheLifetime()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

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
        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints, $routeTags, $routeLifetime);
        self::assertSame('/resolved/path', (string)$router->resolve($resolveContext));
    }

    #[Test]
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->createMock(RouterCachingService::class);
        $mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($routeContext)->willReturn($cachedMatchResults);
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');

        self::assertSame($cachedMatchResults, $router->route($routeContext));
    }

    #[Test]
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects($this->once())->method('getMatchResults')->willReturn($matchResults);

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($routeContext, $matchResults, null, null);

        self::assertSame($matchResults, $router->route($routeContext));
    }

    #[Test]
    public function routeStoresMatchResultsInCacheIfNotFoundInCacheWithTagsAndCacheLifetime()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

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

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($routeContext, $matchResults, $routeTags, $routeLifetime);

        self::assertSame($matchResults, $router->route($routeContext));
    }

    #[Test]
    public function getLastMatchedRouteReturnsNullByDefault()
    {
        self::assertNull($this->router->getLastMatchedRoute());
    }

    #[Test]
    public function routeSetsLastMatchedRoute()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->createMock(Route::class);
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->createMock(Route::class);
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects($this->once())->method('getMatchResults')->willReturn([]);

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->route($routeContext);

        self::assertSame($mockRoute2, $router->getLastMatchedRoute());
    }

    #[Test]
    public function routeLoadsRoutesConfigurationFromConfigurationManagerIfNotSetExplicitly()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, []);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $uri = new Uri('http://localhost/');
        $this->mockHttpRequest->method('getUri')->willReturn($uri);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->willReturn($routesConfiguration);
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        try {
            $router->route(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
        }

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        self::assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }

    #[Test]
    public function routeDoesNotLoadRoutesConfigurationFromConfigurationManagerIfItsSetExplicitly()
    {
        /** @var Router|MockObject $router */
        $router = $this->getAccessibleMock(Router::class, []);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->createStub(LoggerInterface::class));

        $uri = new Uri('http://localhost/');
        $this->mockHttpRequest->method('getUri')->willReturn($uri);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->createMock(ConfigurationManager::class);
        $mockConfigurationManager->expects($this->never())->method('getConfiguration');
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        $router->setRoutesConfiguration($routesConfiguration);
        try {
            $router->route(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
        }

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        self::assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }
}
