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

/**
 * Testcase for the MVC Web Router
 *
 */
class RouterTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function resolveCallsCreateRoutesFromConfiguration() {
		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));

			// not saying anything, but seems better than to expect the exception we'd get otherwise
		$mockRoute = $this->getMock('TYPO3\Flow\Mvc\Routing\Route');
		$mockRoute->expects($this->once())->method('resolves')->will($this->returnValue(TRUE));
		$mockRoute->expects($this->once())->method('getMatchingUri')->will($this->returnValue('foobar'));
		$router->_set('routes', array($mockRoute));

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
		);

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('dummy'));
		$router->setRoutesConfiguration($routesConfiguration);
		$router->_call('createRoutesFromConfiguration');
		$createdRoutes = $router->_get('routes');

		$this->assertEquals('number1', $createdRoutes[0]->getUriPattern());
		$this->assertTrue($createdRoutes[0]->isLowerCase());
		$this->assertFalse($createdRoutes[0]->getAppendExceedingArguments());
		$this->assertEquals('number2', $createdRoutes[1]->getUriPattern());
		$this->assertEquals('route3', $createdRoutes[2]->getName());
		$this->assertEquals(array('foodefault'), $createdRoutes[2]->getDefaults());
		$this->assertEquals(array('fooroutepart'), $createdRoutes[2]->getRoutePartsConfiguration());
		$this->assertEquals('number3', $createdRoutes[2]->getUriPattern());
		$this->assertFalse($createdRoutes[2]->isLowerCase());
		$this->assertTrue($createdRoutes[2]->getAppendExceedingArguments());
	}

	/**
	 * @test
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheMatchingUriIfAny() {
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves', 'getMatchingUri'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->once())->method('getMatchingUri')->will($this->returnValue('route2'));

		$route3 = $this->getMock('TYPO3\Flow\Mvc\Routing\Route', array('resolves'), array(), '', FALSE);

		$mockRoutes = array($route1, $route2, $route3);

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'), array(), '', FALSE);
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->_set('routes', $mockRoutes);

		$matchingUri = $router->resolve($routeValues);
		$this->assertSame('route2', $matchingUri);
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

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$router->_set('routes', $mockRoutes);
		$mockSystemLogger  = $this->getMockBuilder('TYPO3\Flow\Log\SystemLoggerInterface')->getMock();
		$router->_set('systemLogger', $mockSystemLogger);

		$router->resolve(array());
	}

	/**
	 * @test
	 */
	public function getLastResolvedRouteReturnsNullByDefault() {
		$router = new \TYPO3\Flow\Mvc\Routing\Router();
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

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$router->resolve($routeValues);

		$this->assertSame($mockRoute2, $router->getLastResolvedRoute());
	}

	/**
	 * @test
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('TYPO3\Flow\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('testpackage\Controller\fooController'))
			->will($this->returnValue('TestPackage\Controller\FooController'));

		$router = new \TYPO3\Flow\Mvc\Routing\Router();
		$router->injectObjectManager($mockObjectManager);
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

		$router = new \TYPO3\Flow\Mvc\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals('TestPackage\Bar\Baz\Controller\FooController', $router->getControllerObjectName('testpackage', 'bar\baz', 'foo'));
	}

	/**
	 * @test
	 */
	public function routeSetsDefaultControllerAndActionNameIfNoRouteMatches() {
		$mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockUri->expects($this->once())->method('getPath')->will($this->returnValue('http://www.domain.tld/requestPath'));

		$mockBaseUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockBaseUri->expects($this->once())->method('getPath')->will($this->returnValue('http://www.domain.tld/'));

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$mockActionRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$mockActionRequest->expects($this->once())->method('setControllerName')->with('Standard');
		$mockActionRequest->expects($this->once())->method('setControllerActionName')->with('index');

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));
		$mockHttpRequest->expects($this->once())->method('getUri')->will($this->returnValue($mockUri));
		$mockHttpRequest->expects($this->once())->method('getBaseUri')->will($this->returnValue($mockBaseUri));

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$router->expects($this->once())->method('findMatchResults')->with('requestPath')->will($this->returnValue(NULL));

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

		$mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockUri->expects($this->once())->method('getPath')->will($this->returnValue('http://www.domain.tld/requestPath'));

		$mockBaseUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockBaseUri->expects($this->once())->method('getPath')->will($this->returnValue('http://www.domain.tld/'));

		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->once())->method('getArguments')->will($this->returnValue($requestArguments));
		$mockActionRequest->expects($this->once())->method('setArguments')->with($expectedResult);

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->once())->method('createActionRequest')->will($this->returnValue($mockActionRequest));
		$mockHttpRequest->expects($this->once())->method('getUri')->will($this->returnValue($mockUri));
		$mockHttpRequest->expects($this->once())->method('getBaseUri')->will($this->returnValue($mockBaseUri));

		$router = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Router')->setMethods(array('findMatchResults'))->getMock();
		$router->expects($this->once())->method('findMatchResults')->with('requestPath')->will($this->returnValue($routeValues));

		$router->route($mockHttpRequest);
	}

	/**
	 * @test
	 */
	public function getLastMatchedRouteReturnsNullByDefault() {
		$router = new \TYPO3\Flow\Mvc\Routing\Router();
		$this->assertNull($router->getLastMatchedRoute());
	}

	/**
	 * @test
	 */
	public function findMatchResultsSetsLastMatchedRoute() {
		$routePath = 'some/request/path';
		$mockRoute1 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute1->expects($this->once())->method('matches')->with($routePath)->will($this->returnValue(FALSE));
		$mockRoute2 = $this->getMockBuilder('TYPO3\Flow\Mvc\Routing\Route')->getMock();
		$mockRoute2->expects($this->once())->method('matches')->with($routePath)->will($this->returnValue(TRUE));

		$router = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Router', array('createRoutesFromConfiguration'));
		$router->_set('routes', array($mockRoute1, $mockRoute2));

		$router->_call('findMatchResults', $routePath);

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

		$router = new \TYPO3\Flow\Mvc\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

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

		$router = new \TYPO3\Flow\Mvc\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals($expectedObjectName, $router->getControllerObjectName($packageKey, $subpackageKey, $controllerName));
	}

}
?>