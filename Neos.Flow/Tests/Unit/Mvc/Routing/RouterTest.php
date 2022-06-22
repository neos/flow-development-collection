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
        $this->router = $this->getAccessibleMock(Router::class, ['dummy']);

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
    public function resolveCallsCreateRoutesFromConfiguration()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        // not saying anything, but seems better than to expect the exception we'd get otherwise
        /** @var Route|\PHPUnit\Framework\MockObject\MockObject $mockRoute */
        $mockRoute = $this->createMock(Route::class);
        $mockRoute->expects(self::once())->method('resolves')->willReturn(true);
        $mockRoute->expects(self::atLeastOnce())->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $this->inject($router, 'routes', [$mockRoute]);

        // this we actually want to know
        $router->expects(self::once())->method('createRoutesFromConfiguration');
        $router->resolve(new ResolveContext($this->mockBaseUri, [], false, '', RouteParameters::createEmpty()));
    }

    /**
     * @test
     */
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

    /**
     * @test
     */
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

    /**
     * @test
     */
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriConstraintsIfAny()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);
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

        $router->expects(self::once())->method('createRoutesFromConfiguration');
        $router->_set('routes', $mockRoutes);

        $resolvedUri = $router->resolve($resolveContext);
        self::assertSame('/route2', $resolvedUri->getPath());
    }

    /**
     * @test
     */
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $this->expectException(NoMatchingRouteException::class);
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $route1 = $this->createMock(Route::class);
        $route1->expects(self::once())->method('resolves')->willReturn(false);

        $route2 = $this->createMock(Route::class);
        $route2->expects(self::once())->method('resolves')->willReturn(false);

        $mockRoutes = [$route1, $route2];

        $router->_set('routes', $mockRoutes);

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
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);


        $routeValues = ['some' => 'route values'];
        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());
        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->method('getResolvedUriConstraints')->willReturn(UriConstraints::create());

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->resolve($resolveContext);

        self::assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriIfFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $mockCachedResolvedUriConstraints = UriConstraints::create()->withPath('cached/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->method('getCachedResolvedUriConstraints')->with($resolveContext)->willReturn($mockCachedResolvedUriConstraints);
        $router->_set('routerCachingService', $mockRouterCachingService);

        $router->expects(self::never())->method('createRoutesFromConfiguration');
        self::assertSame('/cached/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockBaseUri, $routeValues, false, '', RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('resolves')->with($resolveContext)->willReturn(true);
        $mockRoute2->expects(self::atLeastOnce())->method('getResolvedUriConstraints')->willReturn($mockResolvedUriConstraints);
        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects(self::once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints);
        self::assertSame('/resolved/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects(self::once())->method('getCachedMatchResults')->with($routeContext)->willReturn($cachedMatchResults);
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        $router->expects(self::never())->method('createRoutesFromConfiguration');

        self::assertSame($cachedMatchResults, $router->route($routeContext));
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects(self::once())->method('getMatchResults')->willReturn($matchResults);

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

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
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects(self::once())->method('matches')->with($routeContext)->willReturn(false);
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects(self::once())->method('matches')->with($routeContext)->willReturn(true);
        $mockRoute2->expects(self::once())->method('getMatchResults')->willReturn([]);

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->route($routeContext);

        self::assertSame($mockRoute2, $router->getLastMatchedRoute());
    }

    /**
     * @test
     */
    public function routeLoadsRoutesConfigurationFromConfigurationManagerIfNotSetExplicitly()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['dummy']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $uri = new Uri('http://localhost/');
        $this->mockHttpRequest->expects(self::any())->method('getUri')->willReturn($uri);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects(self::once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->willReturn($routesConfiguration);
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        try {
            $router->route(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
        }

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        self::assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }

    /**
     * @test
     */
    public function routeDoesNotLoadRoutesConfigurationFromConfigurationManagerIfItsSetExplicitly()
    {
        /** @var Router|\PHPUnit\Framework\MockObject\MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['dummy']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'logger', $this->mockSystemLogger);

        $uri = new Uri('http://localhost/');
        $this->mockHttpRequest->expects(self::any())->method('getUri')->willReturn($uri);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|\PHPUnit\Framework\MockObject\MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects(self::never())->method('getConfiguration');
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
