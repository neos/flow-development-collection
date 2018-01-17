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

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Http\Request;
use Neos\Flow\Log\SystemLoggerInterface;
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
     * @var SystemLoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockSystemLogger;

    /**
     * @var RouterCachingService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRouterCachingService;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockHttpRequest;

    /**
     * @var ActionRequest|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockActionRequest;

    /**
     * @var UriInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequestUri;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->router = $this->getAccessibleMock(Router::class, ['dummy']);

        $this->mockSystemLogger = $this->createMock(LoggerInterface::class);
        $this->router->injectSystemLogger($this->mockSystemLogger);

        $this->mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $this->mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriConstraints')->will($this->returnValue(false));
        $this->mockRouterCachingService->expects($this->any())->method('getCachedMatchResults')->will($this->returnValue(false));
        $this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->mockRequestUri = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($this->mockRequestUri));

        $this->mockActionRequest = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
    }

    /**
     * @test
     */
    public function resolveCallsCreateRoutesFromConfiguration()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        // not saying anything, but seems better than to expect the exception we'd get otherwise
        /** @var Route|\PHPUnit_Framework_MockObject_MockObject $mockRoute */
        $mockRoute = $this->createMock(Route::class);
        $mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(true));
        $mockRoute->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->will($this->returnValue(UriConstraints::create()));

        $this->inject($router, 'routes', [$mockRoute]);

        // this we actually want to know
        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->resolve(new ResolveContext($this->mockRequestUri, [], false));
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

        $this->assertEquals('number1', $createdRoutes[0]->getUriPattern());
        $this->assertTrue($createdRoutes[0]->isLowerCase());
        $this->assertFalse($createdRoutes[0]->getAppendExceedingArguments());
        $this->assertEquals('number2', $createdRoutes[1]->getUriPattern());
        $this->assertFalse($createdRoutes[1]->hasHttpMethodConstraints());
        $this->assertEquals([], $createdRoutes[1]->getHttpMethods());
        $this->assertEquals('route3', $createdRoutes[2]->getName());
        $this->assertEquals(['foodefault'], $createdRoutes[2]->getDefaults());
        $this->assertEquals(['fooroutepart'], $createdRoutes[2]->getRoutePartsConfiguration());
        $this->assertEquals('number3', $createdRoutes[2]->getUriPattern());
        $this->assertFalse($createdRoutes[2]->isLowerCase());
        $this->assertTrue($createdRoutes[2]->getAppendExceedingArguments());
        $this->assertTrue($createdRoutes[2]->hasHttpMethodConstraints());
        $this->assertEquals(['POST', 'PUT'], $createdRoutes[2]->getHttpMethods());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidRouteSetupException
     */
    public function createRoutesFromConfigurationThrowsExceptionIfOnlySomeRoutesWithTheSameUriPatternHaveHttpMethodConstraints()
    {
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
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);
        $routeValues = ['foo' => 'bar'];

        $route1 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();
        $route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));

        $route2 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves', 'getResolvedUriConstraints'])->getMock();
        $route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $route2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->will($this->returnValue(UriConstraints::create()->withPath('route2')));

        $route3 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();
        $route3->expects($this->never())->method('resolves');

        $mockRoutes = [$route1, $route2, $route3];

        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->_set('routes', $mockRoutes);

        $resolvedUri = $router->resolve(new ResolveContext($this->mockRequestUri, $routeValues, false));
        $this->assertSame('route2', $resolvedUri->getPath());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\NoMatchingRouteException
     */
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $route1 = $this->createMock(Route::class);
        $route1->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $route2 = $this->createMock(Route::class);
        $route2->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $mockRoutes = [$route1, $route2];

        $router->_set('routes', $mockRoutes);

        $router->resolve(new ResolveContext($this->mockRequestUri, [], false));
    }

    /**
     * @test
     */
    public function getLastResolvedRouteReturnsNullByDefault()
    {
        $this->assertNull($this->router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveSetsLastResolvedRoute()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);


        $routeValues = ['some' => 'route values'];
        $resolveContext = new ResolveContext($this->mockRequestUri, $routeValues, false);
        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->any())->method('getResolvedUriConstraints')->will($this->returnValue(UriConstraints::create()));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->resolve($resolveContext);

        $this->assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriIfFoundInCache()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $mockCachedResolvedUriConstraints = UriConstraints::create()->withPath('cached/path');

        $resolveContext = new ResolveContext($this->mockRequestUri, $routeValues, false);

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriConstraints')->with($resolveContext)->will($this->returnValue($mockCachedResolvedUriConstraints));
        $router->_set('routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');
        $this->assertSame('cached/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $mockResolvedUriConstraints = UriConstraints::create()->withPath('resolved/path');

        $resolveContext = new ResolveContext($this->mockRequestUri, $routeValues, false);

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriConstraints')->will($this->returnValue($mockResolvedUriConstraints));
        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriConstraints')->with($resolveContext, $mockResolvedUriConstraints);
        $this->assertSame('resolved/path', (string)$router->resolve($resolveContext));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());
        $cachedMatchResults = ['some' => 'cached results'];

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($routeContext)->will($this->returnValue($cachedMatchResults));
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');

        $this->assertSame($cachedMatchResults, $router->route($routeContext));
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $matchResults = ['some' => 'match results'];
        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($routeContext, $matchResults);

        $this->assertSame($matchResults, $router->route($routeContext));
    }

    /**
     * @test
     */
    public function getLastMatchedRouteReturnsNullByDefault()
    {
        $this->assertNull($this->router->getLastMatchedRoute());
    }

    /**
     * @test
     */
    public function routeSetsLastMatchedRoute()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeContext = new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty());

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($routeContext)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($routeContext)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue([]));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->route($routeContext);

        $this->assertSame($mockRoute2, $router->getLastMatchedRoute());
    }

    /**
     * @test
     */
    public function routeLoadsRoutesConfigurationFromConfigurationManagerIfNotSetExplicitly()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['dummy']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->will($this->returnValue($routesConfiguration));
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        try {
            $router->route(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
        }

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        $this->assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }

    /**
     * @test
     */
    public function routeDoesNotLoadRoutesConfigurationFromConfigurationManagerIfItsSetExplicitly()
    {
        /** @var Router|\PHPUnit_Framework_MockObject_MockObject $router */
        $router = $this->getAccessibleMock(Router::class, ['dummy']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routesConfiguration = [
            [
                'uriPattern' => 'some/uri/pattern',
            ],
            [
                'uriPattern' => 'some/other/uri/pattern',
            ],
        ];

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->never())->method('getConfiguration');
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        $router->setRoutesConfiguration($routesConfiguration);
        try {
            $router->route(new RouteContext($this->mockHttpRequest, RouteParameters::createEmpty()));
        } catch (NoMatchingRouteException $exception) {
        }

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        $this->assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }
}
