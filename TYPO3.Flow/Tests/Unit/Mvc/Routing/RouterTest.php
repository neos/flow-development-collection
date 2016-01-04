<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Http\Redirection\RedirectionService;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Tests\UnitTestCase;

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
     * @var RedirectionService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRedirectionService;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->router = $this->getAccessibleMock(Router::class, array('dummy'));

        $this->mockSystemLogger = $this->getMockBuilder(\TYPO3\Flow\Log\SystemLoggerInterface::class)->getMock();
        $this->inject($this->router, 'systemLogger', $this->mockSystemLogger);

        $this->mockRouterCachingService = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\RouterCachingService::class)->getMock();
        $this->mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->will($this->returnValue(false));
        $this->mockRouterCachingService->expects($this->any())->method('getCachedMatchResults')->will($this->returnValue(false));
        $this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

        $this->mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $this->mockActionRequest = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();

        $this->mockRedirectionService = $this->getMockBuilder(RedirectionService::class)->getMock();
        $this->inject($this->router, 'redirectionService', $this->mockRedirectionService);
    }

    /**
     * @test
     */
    public function resolveCallsCreateRoutesFromConfiguration()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        // not saying anything, but seems better than to expect the exception we'd get otherwise
        /** @var Route|\PHPUnit_Framework_MockObject_MockObject $mockRoute */
        $mockRoute = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class);
        $mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(true));
        $mockRoute->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('foobar'));

        $this->inject($router, 'routes', array($mockRoute));

        // this we actually want to know
        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->resolve(array());
    }

    /**
     * @test
     */
    public function createRoutesFromConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt()
    {
        $routesConfiguration = array();
        $routesConfiguration['route1']['uriPattern'] = 'number1';
        $routesConfiguration['route2']['uriPattern'] = 'number2';
        $routesConfiguration['route3'] = array(
            'name' => 'route3',
            'defaults' => array('foodefault'),
            'routeParts' => array('fooroutepart'),
            'uriPattern' => 'number3',
            'toLowerCase' => false,
            'appendExceedingArguments' => true,
            'httpMethods' => array('POST', 'PUT')
        );

        $this->router->setRoutesConfiguration($routesConfiguration);
        $this->router->_call('createRoutesFromConfiguration');

        $createdRoutes = $this->router->_get('routes');

        $this->assertEquals('number1', $createdRoutes[0]->getUriPattern());
        $this->assertTrue($createdRoutes[0]->isLowerCase());
        $this->assertFalse($createdRoutes[0]->getAppendExceedingArguments());
        $this->assertEquals('number2', $createdRoutes[1]->getUriPattern());
        $this->assertFalse($createdRoutes[1]->hasHttpMethodConstraints());
        $this->assertEquals(array(), $createdRoutes[1]->getHttpMethods());
        $this->assertEquals('route3', $createdRoutes[2]->getName());
        $this->assertEquals(array('foodefault'), $createdRoutes[2]->getDefaults());
        $this->assertEquals(array('fooroutepart'), $createdRoutes[2]->getRoutePartsConfiguration());
        $this->assertEquals('number3', $createdRoutes[2]->getUriPattern());
        $this->assertFalse($createdRoutes[2]->isLowerCase());
        $this->assertTrue($createdRoutes[2]->getAppendExceedingArguments());
        $this->assertTrue($createdRoutes[2]->hasHttpMethodConstraints());
        $this->assertEquals(array('POST', 'PUT'), $createdRoutes[2]->getHttpMethods());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidRouteSetupException
     */
    public function createRoutesFromConfigurationThrowsExceptionIfOnlySomeRoutesWithTheSameUriPatternHaveHttpMethodConstraints()
    {
        $routesConfiguration = array(
            array(
                'uriPattern' => 'somePattern'
            ),
            array(
                'uriPattern' => 'somePattern',
                'httpMethods' => array('POST', 'PUT')
            ),
        );
        shuffle($routesConfiguration);
        $this->router->setRoutesConfiguration($routesConfiguration);
        $this->router->_call('createRoutesFromConfiguration');
    }

    /**
     * @test
     */
    public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriPathIfAny()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));
        $routeValues = array('foo' => 'bar');

        $route1 = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class, array('resolves'), array(), '', false);
        $route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));

        $route2 = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class, array('resolves', 'getResolvedUriPath'), array(), '', false);
        $route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $route2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('route2'));

        $route3 = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class, array('resolves'), array(), '', false);

        $mockRoutes = array($route1, $route2, $route3);

        $router->expects($this->once())->method('createRoutesFromConfiguration');
        $router->_set('routes', $mockRoutes);

        $matchingRequestPath = $router->resolve($routeValues);
        $this->assertSame('route2', $matchingRequestPath);
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
     */
    public function resolveThrowsExceptionIfNoMatchingRouteWasFound()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $route1 = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class);
        $route1->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $route2 = $this->getMock(\TYPO3\Flow\Mvc\Routing\Route::class);
        $route2->expects($this->once())->method('resolves')->will($this->returnValue(false));

        $mockRoutes = array($route1, $route2);

        $router->_set('routes', $mockRoutes);

        $router->resolve(array());
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
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $routeValues = array('some' => 'route values');
        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));

        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $router->resolve($routeValues);

        $this->assertSame($mockRoute2, $router->getLastResolvedRoute());
    }

    /**
     * @test
     */
    public function resolveReturnsCachedResolvedUriPathIfFoundInCache()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $routeValues = array('some' => 'route values');
        $cachedResolvedUriPath = 'some/cached/Request/Path';

        $mockRouterCachingService = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\RouterCachingService::class)->getMock();
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
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $routeValues = array('some' => 'route values');
        $resolvedUriPath = 'some/resolved/Request/Path';

        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriPath')->with($resolvedUriPath, $routeValues);

        $this->assertSame($resolvedUriPath, $router->resolve($routeValues));
    }

    /**
     * @test
     */
    public function resolveDoesNotStoreResolvedUriPathInCacheIfItsNull()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $routeValues = array('some' => 'route values');
        $resolvedUriPath = null;

        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(true));
        $mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $this->mockRouterCachingService->expects($this->never())->method('storeResolvedUriPath');

        $this->assertSame($resolvedUriPath, $router->resolve($routeValues));
    }

    /**
     * @test
     */
    public function routeReturnsCachedMatchResultsIfFoundInCache()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $cachedMatchResults = array('some' => 'cached results');

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockRouterCachingService = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\RouterCachingService::class)->getMock();
        $mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue($cachedMatchResults));
        $this->inject($router, 'routerCachingService', $mockRouterCachingService);

        $router->expects($this->never())->method('createRoutesFromConfiguration');

        $this->assertSame($cachedMatchResults, $router->route($mockHttpRequest));
    }

    /**
     * @test
     */
    public function routePassesHttpRequestToRedirectionService()
    {
        $mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

        $this->mockRedirectionService->expects($this->once())->method('triggerRedirectIfApplicable')->with($mockHttpRequest);
        $this->router->route($mockHttpRequest);
    }

    /**
     * @test
     */
    public function routeStoresMatchResultsInCacheIfNotFoundInCache()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $matchResults = array('some' => 'match results');

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($mockHttpRequest, $matchResults);

        $this->assertSame($matchResults, $router->route($mockHttpRequest));
    }

    /**
     * @test
     */
    public function routeDoesNotStoreMatchResultsInCacheIfTheyAreNull()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $matchResults = null;

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));
        $mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $mockRouterCachingService = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\RouterCachingService::class)->getMock();
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
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockRoute1 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(false));
        $mockRoute2 = $this->getMockBuilder(\TYPO3\Flow\Mvc\Routing\Route::class)->getMock();
        $mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(true));

        $router->_set('routes', array($mockRoute1, $mockRoute2));

        $router->route($mockHttpRequest);

        $this->assertSame($mockRoute2, $router->getLastMatchedRoute());
    }

    /**
     * @test
     */
    public function routeLoadsRoutesConfigurationFromConfigurationManagerIfNotSetExplicitly()
    {
        $router = $this->getAccessibleRouterMock(array('createRoutesFromConfiguration'));

        $routesConfiguration = array(
            array(
                'uriPattern' => 'some/uri/pattern',
            ),
            array(
                'uriPattern' => 'some/other/uri/pattern',
            ),
        );

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(\TYPO3\Flow\Configuration\ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->once())->method('getConfiguration')->with(ConfigurationManager::CONFIGURATION_TYPE_ROUTES)->will($this->returnValue($routesConfiguration));
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

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
        $router = $this->getAccessibleRouterMock(array('dummy'));

        $routesConfiguration = array(
            array(
                'uriPattern' => 'some/uri/pattern',
            ),
            array(
                'uriPattern' => 'some/other/uri/pattern',
            ),
        );

        /** @var ConfigurationManager|\PHPUnit_Framework_MockObject_MockObject $mockConfigurationManager */
        $mockConfigurationManager = $this->getMockBuilder(\TYPO3\Flow\Configuration\ConfigurationManager::class)->disableOriginalConstructor()->getMock();
        $mockConfigurationManager->expects($this->never())->method('getConfiguration');
        $this->inject($router, 'configurationManager', $mockConfigurationManager);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();

        $router->setRoutesConfiguration($routesConfiguration);
        $router->route($mockHttpRequest);

        $routes = $router->getRoutes();
        $firstRoute = reset($routes);
        $this->assertSame('some/uri/pattern', $firstRoute->getUriPattern());
    }

    /**
     * @param array $methods
     * @return \PHPUnit_Framework_MockObject_MockObject|Router
     */
    protected function getAccessibleRouterMock(array $methods = array())
    {
        $router = $this->getAccessibleMock(Router::class, $methods);
        $this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
        $this->inject($router, 'systemLogger', $this->mockSystemLogger);
        $this->inject($router, 'redirectionService', $this->mockRedirectionService);
        return $router;
    }
}
