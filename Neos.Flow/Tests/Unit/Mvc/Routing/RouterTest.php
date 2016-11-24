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
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\Router;
use Neos\Flow\Mvc\Routing\RouterCachingService;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Tests\UnitTestCase;

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
     * @return void
     */
    public function setUp()
    {
        $this->router = $this->getAccessibleMock(Router::class, ['dummy']);

        $this->mockSystemLogger = $this->createMock(SystemLoggerInterface::class);
        $this->inject($this->router, 'systemLogger', $this->mockSystemLogger);

        $this->mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $this->mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->will($this->returnValue(false));
        $this->mockRouterCachingService->expects($this->any())->method('getCachedMatchResults')->will($this->returnValue(false));
        $this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

        $this->mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

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
        $mockRoute->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('foobar'));

        $this->inject($router, 'routes', [$mockRoute]);

        // this we actually want to know
        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->resolve([]);
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
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriPathIfAny()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);
        $routeValues = ['foo' => 'bar'];

        $route1 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();
        $route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));

        $route2 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves', 'getResolvedUriPath'])->getMock();
        $route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $route2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('route2'));

        $route3 = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->setMethods(['resolves'])->getMock();

        $mockRoutes = [$route1, $route2, $route3];

        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->_set('routes', $mockRoutes);

        $matchingRequestPath = $router->resolve($routeValues);
        $this->assertSame('route2', $matchingRequestPath);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\NoMatchingRouteException
     */
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $route1 = $this->createMock(Route::class);
        $route1->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $route2 = $this->createMock(Route::class);
        $route2->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $mockRoutes = [$route1, $route2];

        $router->_set('routes', $mockRoutes);

        $router->resolve([]);
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
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->resolve($routeValues);

        $this->assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriPathIfFoundInCache()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $cachedResolvedUriPath = 'some/cached/Request/Path';

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->with($routeValues)->will($this->returnValue($cachedResolvedUriPath));
        $router->_set('routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');
        $this->assertSame($cachedResolvedUriPath, $router->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $resolvedUriPath = 'some/resolved/Request/Path';

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriPath')->with($resolvedUriPath, $routeValues);

        $this->assertSame($resolvedUriPath, $router->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveDoesNotStoreResolvedUriPathInCacheIfItsNull()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $routeValues = ['some' => 'route values'];
        $resolvedUriPath = null;

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->never())->method('storeResolvedUriPath');

        $this->assertSame($resolvedUriPath, $router->resolve($routeValues));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $cachedMatchResults = ['some' => 'cached results'];

        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue($cachedMatchResults));
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');

        $this->assertSame($cachedMatchResults, $router->route($mockHttpRequest));
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $matchResults = ['some' => 'match results'];

        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($mockHttpRequest, $matchResults);

        $this->assertSame($matchResults, $router->route($mockHttpRequest));
    }

    /**
     * @test
     */
    public function routeDoesNotStoreMatchResultsInCacheIfTheyAreNull()
    {
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $matchResults = null;

        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $mockRouterCachingService = $this->getMockBuilder(RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRouterCachingService->expects($this->never())->method('storeMatchResults');
        $router->_set('routerCachingService', $mockRouterCachingService);

        $router->route($mockHttpRequest);
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
        $router = $this->getAccessibleMock(Router::class, ['createRoutesFromConfiguration']);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);

        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));

        $router->_set('routes', [$mockRoute1, $mockRoute2]);

        $router->route($mockHttpRequest);

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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $router->route($mockHttpRequest);

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

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $router->setRoutesConfiguration($routesConfiguration);
        $router->route($mockHttpRequest);

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        $this->assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }
}
