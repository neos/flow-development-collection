<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Web Router
 *
 */
class RouterTest extends UnitTestCase {

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var SystemLoggerInterface
	 */
	protected $mockSystemLogger;

	/**
	 * @var RouterCachingService
	 */
	protected $mockRouterCachingService;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));

		$this->mockSystemLogger = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$this->router->_set('systemLogger', $this->mockSystemLogger);

		$this->mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$this->mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->will($this->returnValue(FALSE));
		$this->mockRouterCachingService->expects($this->any())->method('getCachedMatchResults')->will($this->returnValue(FALSE));
		$this->router->_set('routerCachingService', $this->mockRouterCachingService);
	}

	/**
	 * @test
	 */
	public function resolveCallsCreateRoutesFromConfiguration() {
		// not saying anything, but seems better than to expect the exception we'd get otherwise
		$mockRoute = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(TRUE));
		$mockRoute->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('foobar'));

		$this->router->_set('routes', array($mockRoute));

		// this we actually want to know
		$this->router->expects($this->once())->method('createRoutesFromConfiguration');
		$this->router->resolve(array());
	}

	/**
	 * @test
	 */
	public function createRoutesFromConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt() {
		$routesConfiguration = array();
		$routesConfiguration['route1']['uriPattern'] = 'number1';
		$routesConfiguration['route2']['uriPattern'] = 'number2';
		$routesConfiguration['route3'] = array(
			'name' => 'route3',
			'defaults' => array('foodefault'),
			'routeParts' => array('fooroutepart'),
			'uriPattern' => 'number3',
			'toLowerCase' => FALSE,
			'appendExceedingArguments' => TRUE,
			'httpMethods' => array('POST', 'PUT')
		);

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('dummy'));
		$router->setRoutesConfiguration($routesConfiguration);
		$router->_call('createRoutesFromConfiguration');
		$createdRoutes = $router->_get('routes');

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
	public function createRoutesFromConfigurationThrowsExceptionIfOnlySomeRoutesWithTheSameUriPatternHaveHttpMethodConstraints() {
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
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('dummy'));
		$router->setRoutesConfiguration($routesConfiguration);
		$router->_call('createRoutesFromConfiguration');
	}

	/**
	 * @test
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriPathIfAny() {
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves', 'getResolvedUriPath'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('route2'));

		$route3 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);

		$mockRoutes = array($route1, $route2, $route3);

		$this->router->expects($this->once())->method('createRoutesFromConfiguration');
		$this->router->_set('routes', $mockRoutes);

		$matchingRequestPath = $this->router->resolve($routeValues);
		$this->assertSame('route2', $matchingRequestPath);
	}

	/**
	 * @test
	 * @expectedException \TYPO3\Flow\Mvc\Exception\NoMatchingRouteException
	 */
	public function resolveThrowsExceptionIfNoMatchingRouteWasFound() {
		$route1 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$route1->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$route2->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$mockRoutes = array($route1, $route2);

		$this->router->_set('routes', $mockRoutes);

		$this->router->resolve(array());
	}

	/**
	 * @test
	 */
	public function getLastResolvedRouteReturnsNullByDefault() {
		$router = new Router();
		$this->assertNull($router->getLastResolvedRoute());
	}

	/**
	 * @test
	 */
	public function resolveSetsLastResolvedRoute() {
		$routeValues = array('some' => 'route values');
		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));

		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->router->resolve($routeValues);

		$this->assertSame($mockRoute2, $this->router->getLastResolvedRoute());
	}

	/**
	 * @test
	 */
	public function resolveReturnsCachedResolvedUriPathIfFoundInCache() {
		$routeValues = array('some' => 'route values');
		$cachedResolvedUriPath = 'some/cached/Request/Path';

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->with($routeValues)->will($this->returnValue($cachedResolvedUriPath));
		$this->router->_set('routerCachingService', $mockRouterCachingService);

		$this->router->expects($this->never())->method('createRoutesFromConfiguration');
		$this->assertSame($cachedResolvedUriPath, $this->router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache() {
		$routeValues = array('some' => 'route values');
		$resolvedUriPath = 'some/resolved/Request/Path';

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriPath')->with($resolvedUriPath, $routeValues);

		$this->assertSame($resolvedUriPath, $this->router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function resolveDoesNotStoreResolvedUriPathInCacheIfItsNull() {
		$routeValues = array('some' => 'route values');
		$resolvedUriPath = NULL;

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->never())->method('storeResolvedUriPath');

		$this->assertSame($resolvedUriPath, $this->router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue('TestPackage\Controller\FooController'));

		$router = new Router();
		$this->inject($router, 'objectManager', $mockObjectManager);
		$this->assertEquals('TestPackage\Controller\FooController', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * @test
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\bar\baz\Controller\fooController'))
			->will($this->returnValue('TestPackage\Bar\Baz\Controller\FooController'));

		$router = new Router();
		$this->inject($router, 'objectManager', $mockObjectManager);

		$this->assertEquals('TestPackage\Bar\Baz\Controller\FooController', $router->getControllerObjectName('testpackage', 'bar\baz', 'foo'));
	}

	/**
	 * @test
	 */
	public function routeSetsDefaultControllerAndActionNameIfNoRouteMatches() {
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$router->expects($this->once())->method('findMatchResults')->with($mockHttpRequest)->will($this->returnValue(NULL));

		$router->route($mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function routeMergesRouteValuesOfMatchedRouteWithRequestArguments() {
		$requestArguments = array(
			'product' => array('__identity' => 'SomeUUID', 'name' => 'name from request'),
			'toBeOverridden' => 'from request',
			'toBeKept' => 'keep me'
		);
		$routeValues = array(
			'product' => array('name' => 'Some product', 'price' => 123.45),
			'toBeOverridden' => 'from route',
			'newValue' => 'new value from route'
		);
		$expectedResult = array(
			'product' => array('__identity' => 'SomeUUID', 'name' => 'Some product', 'price' => 123.45),
			'toBeOverridden' => 'from route',
			'toBeKept' => 'keep me',
			'newValue' => 'new value from route'
		);

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getArguments')->will($this->returnValue($requestArguments));
		$mockActionRequest->expects($this->once())->method('setArguments')->with($expectedResult);

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$router->expects($this->once())->method('findMatchResults')->with($mockHttpRequest)->will($this->returnValue($routeValues));

		$router->route($mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function routeReturnsCachedMatchResultsIfFoundInCache() {
		$cachedMatchResults = array('some' => 'cached results');

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue($cachedMatchResults));
		$this->router->_set('routerCachingService', $mockRouterCachingService);

		$this->router->expects($this->never())->method('createRoutesFromConfiguration');

		$this->assertSame($mockActionRequest, $this->router->route($mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function routeStoresMatchResultsInCacheIfNotFoundInCache() {
		$matchResults = array('some' => 'match results');

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($mockHttpRequest, $matchResults);

		$this->assertSame($mockActionRequest, $this->router->route($mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function routeDoesNotStoresMatchResultsInCacheIfTheyAreNull() {
		$matchResults = NULL;

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRouterCachingService->expects($this->never())->method('storeMatchResults');
		$this->router->_set('routerCachingService', $mockRouterCachingService);

		$this->router->route($mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function getLastMatchedRouteReturnsNullByDefault() {
		$router = new Router();
		$this->assertNull($router->getLastMatchedRoute());
	}

	/**
	 * @test
	 */
	public function findMatchResultsSetsLastMatchedRoute() {
		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));

		$this->router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->router->_call('findMatchResults', $mockHttpRequest);

		$this->assertSame($mockRoute2, $this->router->getLastMatchedRoute());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsNullIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue(FALSE));

		$router = new Router();
		$this->inject($router, 'objectManager', $mockObjectManager);

		$this->assertEquals('', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * Data Provider
	 *
	 * @return array
	 */
	public function getControllerObjectNameDataProvider() {
		return array(
			array('MyPackage', NULL, 'MyController', 'MyPackage\Controller\MyControllerController'),
			array('MyCompany.MyPackage', NULL, 'MyController', 'MyCompany\MyPackage\Controller\MyControllerController'),
			array('Com.FineDudeArt.Gallery', 'Media', 'Image', 'Com\FineDudeArt\Gallery\Media\Controller\ImageController')
		);
	}

	/**
	 * @test
	 * @dataProvider getControllerObjectNameDataProvider
	 */
	public function getControllerObjectNameReturnsCorrectObjectNamesBasedOnTheGivenArguments($packageKey, $subpackageKey, $controllerName, $expectedObjectName) {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')->will($this->returnArgument(0));

		$router = new Router();
		$this->inject($router, 'objectManager', $mockObjectManager);

		$this->assertEquals($expectedObjectName, $router->getControllerObjectName($packageKey, $subpackageKey, $controllerName));
	}

}
