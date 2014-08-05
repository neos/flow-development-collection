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

use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Mvc\Routing\Router;
use TYPO3\Flow\Mvc\Routing\RouterCachingService;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Object\ObjectManagerInterface;
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
	 * @var ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\Flow\Http\Request
	 */
	protected $mockHttpRequest;

	/**
	 * @var ActionRequest
	 */
	protected $mockActionRequest;

	/**
	 * @return void
	 */
	public function setUp() {
		$this->router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('dummy'));

		$this->mockSystemLogger = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$this->inject($this->router, 'systemLogger', $this->mockSystemLogger);

		$this->mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$this->inject($this->router, 'objectManager', $this->mockObjectManager);

		$this->mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$this->mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->will($this->returnValue(FALSE));
		$this->mockRouterCachingService->expects($this->any())->method('getCachedMatchResults')->will($this->returnValue(FALSE));
		$this->inject($this->router, 'routerCachingService', $this->mockRouterCachingService);

		$this->mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$this->mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$this->mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Mvc\ActionRequest', $this->mockHttpRequest)->will($this->returnValue($this->mockActionRequest));
	}

	/**
	 * @test
	 */
	public function resolveCallsCreateRoutesFromConfiguration() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		// not saying anything, but seems better than to expect the exception we'd get otherwise
		$mockRoute = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(TRUE));
		$mockRoute->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('foobar'));

		$this->inject($router, 'routes', array($mockRoute));

		// this we actually want to know
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->resolve(array());
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
		$this->router->setRoutesConfiguration($routesConfiguration);
		$this->router->_call('createRoutesFromConfiguration');
	}

	/**
	 * @test
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheResolvedUriPathIfAny() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves', 'getResolvedUriPath'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue('route2'));

		$route3 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);

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
	public function resolveThrowsExceptionIfNoMatchingRouteWasFound() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$route1 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$route1->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$route2->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$mockRoutes = array($route1, $route2);

		$router->_set('routes', $mockRoutes);

		$router->resolve(array());
	}

	/**
	 * @test
	 */
	public function getLastResolvedRouteReturnsNullByDefault() {
		$this->assertNull($this->router->getLastResolvedRoute());
	}

	/**
	 * @test
	 */
	public function resolveSetsLastResolvedRoute() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$routeValues = array('some' => 'route values');
		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));

		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$router->resolve($routeValues);

		$this->assertSame($mockRoute2, $router->getLastResolvedRoute());
	}

	/**
	 * @test
	 */
	public function resolveReturnsCachedResolvedUriPathIfFoundInCache() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$routeValues = array('some' => 'route values');
		$cachedResolvedUriPath = 'some/cached/Request/Path';

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->any())->method('getCachedResolvedUriPath')->with($routeValues)->will($this->returnValue($cachedResolvedUriPath));
		$router->_set('routerCachingService', $mockRouterCachingService);

		$router->expects($this->never())->method('createRoutesFromConfiguration');
		$this->assertSame($cachedResolvedUriPath, $router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function resolveStoresResolvedUriPathInCacheIfNotFoundInCache() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$routeValues = array('some' => 'route values');
		$resolvedUriPath = 'some/resolved/Request/Path';

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->once())->method('storeResolvedUriPath')->with($resolvedUriPath, $routeValues);

		$this->assertSame($resolvedUriPath, $router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function resolveDoesNotStoreResolvedUriPathInCacheIfItsNull() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$routeValues = array('some' => 'route values');
		$resolvedUriPath = NULL;

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->atLeastOnce())->method('getResolvedUriPath')->will($this->returnValue($resolvedUriPath));
		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->never())->method('storeResolvedUriPath');

		$this->assertSame($resolvedUriPath, $router->resolve($routeValues));
	}

	/**
	 * @test
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue('TestPackage\Controller\FooController'));

		$this->assertEquals('TestPackage\Controller\FooController', $this->router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * @test
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$this->mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\bar\baz\Controller\fooController'))
			->will($this->returnValue('TestPackage\Bar\Baz\Controller\FooController'));

		$this->assertEquals('TestPackage\Bar\Baz\Controller\FooController', $this->router->getControllerObjectName('testpackage', 'bar\baz', 'foo'));
	}

	/**
	 * @test
	 */
	public function routeSetsDefaultControllerAndActionNameIfNoRouteMatches() {
		$this->mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$this->mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$this->mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$this->mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$this->inject($router, 'objectManager', $this->mockObjectManager);
		$router->expects($this->once())->method('findMatchResults')->with($this->mockHttpRequest)->will($this->returnValue(NULL));

		$router->route($this->mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function routeIgnoresRequestArguments() {
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

		$this->mockActionRequest->expects($this->never())->method('getArguments');
		$this->mockActionRequest->expects($this->once())->method('setArguments')->with($routeValues);

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$this->inject($router, 'objectManager', $this->mockObjectManager);
		$router->expects($this->once())->method('findMatchResults')->with($this->mockHttpRequest)->will($this->returnValue($routeValues));

		$router->route($this->mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function routeReturnsCachedMatchResultsIfFoundInCache() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$cachedMatchResults = array('some' => 'cached results');

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Mvc\ActionRequest', $mockHttpRequest)->will($this->returnValue($mockActionRequest));
		$this->inject($router, 'objectManager', $mockObjectManager);

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue($cachedMatchResults));
		$this->inject($router, 'routerCachingService', $mockRouterCachingService);

		$router->expects($this->never())->method('createRoutesFromConfiguration');

		$this->assertSame($mockActionRequest, $router->route($mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function routeStoresMatchResultsInCacheIfNotFoundInCache() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$matchResults = array('some' => 'match results');

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Mvc\ActionRequest', $mockHttpRequest)->will($this->returnValue($mockActionRequest));
		$this->inject($router, 'objectManager', $mockObjectManager);

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$this->mockRouterCachingService->expects($this->once())->method('storeMatchResults')->with($mockHttpRequest, $matchResults);

		$this->assertSame($mockActionRequest, $router->route($mockHttpRequest));
	}

	/**
	 * @test
	 */
	public function routeDoesNotStoreMatchResultsInCacheIfTheyAreNull() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$matchResults = NULL;

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->any())->method('get')->with('TYPO3\Flow\Mvc\ActionRequest', $mockHttpRequest)->will($this->returnValue($mockActionRequest));
		$this->inject($router, 'objectManager', $mockObjectManager);

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));
		$mockRoute2->expects($this->once())->method('getMatchResults')->will($this->returnValue($matchResults));

		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$mockRouterCachingService = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\RouterCachingService')->getMock();
		$mockRouterCachingService->expects($this->once())->method('getCachedMatchResults')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRouterCachingService->expects($this->never())->method('storeMatchResults');
		$router->_set('routerCachingService', $mockRouterCachingService);

		$router->route($mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function getLastMatchedRouteReturnsNullByDefault() {
		$this->assertNull($this->router->getLastMatchedRoute());
	}

	/**
	 * @test
	 */
	public function findMatchResultsSetsLastMatchedRoute() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$this->inject($router, 'routerCachingService', $this->mockRouterCachingService);
		$this->inject($router, 'systemLogger', $this->mockSystemLogger);

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($mockHttpRequest)->will($this->returnValue(TRUE));

		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$router->_call('findMatchResults', $mockHttpRequest);

		$this->assertSame($mockRoute2, $router->getLastMatchedRoute());
	}

	/**
	 * @test
	 */
	public function getControllerObjectNameReturnsNullIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue(FALSE));

		$this->inject($this->router, 'objectManager', $mockObjectManager);

		$this->assertEquals('', $this->router->getControllerObjectName('testpackage', '', 'foo'));
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

		$this->inject($this->router, 'objectManager', $mockObjectManager);

		$this->assertEquals($expectedObjectName, $this->router->getControllerObjectName($packageKey, $subpackageKey, $controllerName));
	}

}
