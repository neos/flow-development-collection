<?php
namespace F3\FLOW3\Tests\Unit\MVC\Web\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the MVC Web Router
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RouterTest extends \F3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setRoutesConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt() {
		$mockLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$routesConfiguration = array();
		$routesConfiguration['route1']['uriPattern'] = 'number1';
		$routesConfiguration['route2']['uriPattern'] = 'number2';
		$routesConfiguration['route3']['uriPattern'] = 'number3';

		$route1 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route');
		$route1->expects($this->once())->method('setUriPattern')->with($this->equalTo('number1'));

		$route2 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route');
		$route2->expects($this->once())->method('setUriPattern')->with($this->equalTo('number2'));

		$route3 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route');
		$route3->expects($this->once())->method('setUriPattern')->with($this->equalTo('number3'));
		$route3->expects($this->once())->method('resolves')->will($this->returnValue(TRUE));

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->exactly(3))->method('create')->will($this->onConsecutiveCalls($route1, $route2, $route3));

		$router = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Routing\Router', array('dummy'));
		$router->injectSystemLogger($mockLogger);
		$router->_set('objectManager', $mockObjectManager);
		$router->setRoutesConfiguration($routesConfiguration);
		$router->resolve(array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheMatchingUriIfAny() {
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves', 'getMatchingUri'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->once())->method('getMatchingUri')->will($this->returnValue('route2'));

		$route3 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);

		$mockRoutes = array($route1, $route2, $route3);
		$mockLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$router = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Routing\Router', array('createRoutesFromConfiguration'), array(), '', FALSE);
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->_set('routes', $mockRoutes);
		$router->injectSystemLogger($mockLogger);

		$matchingUri = $router->resolve($routeValues);
		$this->assertSame('route2', $matchingUri);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \F3\FLOW3\MVC\Exception\NoMatchingRouteException
	 */
	public function resolveThrowsExceptionIfNoMatchingRouteWasFound() {
		$route1 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route');
		$route1->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$route2 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route');
		$route2->expects($this->once())->method('resolves')->will($this->returnValue(FALSE));

		$mockRoutes = array($route1, $route2);
		$mockLogger = $this->getMock('F3\FLOW3\Log\SystemLoggerInterface');

		$router = $this->getAccessibleMock('F3\FLOW3\MVC\Web\Routing\Router', array('createRoutesFromConfiguration'));
		$router->_set('routes', $mockRoutes);
		$router->injectSystemLogger($mockLogger);

		$router->resolve(array());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function packageKeyCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@package' => 'MyPackage')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerPackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with($this->equalTo('MyPackage'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subpackageKeyCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@subpackage' => 'MySubpackage')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerSubpackageKey', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with($this->equalTo('MySubpackage'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function controllerNameCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@controller' => 'MyController')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'setControllerName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerName')->with($this->equalTo('MyController'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 */
	public function controllerNameAndActionAreSetToDefaultIfNotSpecifiedInArguments() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->will($this->returnValue(array()));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getArguments', 'getControllerName', 'setControllerName', 'getControllerActionName', 'setControllerActionName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getControllerName')->will($this->returnValue(NULL));
		$mockRequest->expects($this->once())->method('setControllerName')->with($this->equalTo('Standard'));

		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue(NULL));
		$mockRequest->expects($this->once())->method('setControllerActionName')->with($this->equalTo('index'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function actionNameCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@action' => 'myAction')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'getControllerActionName', 'setControllerActionName', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setControllerActionName')->with('myAction');
		$mockRequest->expects($this->once())->method('getControllerActionName')->will($this->returnValue('myAction'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function formatCanBeSetByRoute() {
		$router = $this->getRouter();
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@format' => 'myFormat')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array('getRoutePath', 'setFormat', 'getControllerObjectName'), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRoutePath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('setFormat')->with($this->equalTo('myFormat'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function theDefaultPatternForBuildingTheControllerObjectNameIsPackageKeyControllerControllerNameController() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('F3\testpackage\Controller\fooController'))
			->will($this->returnValue('F3\TestPackage\Controller\FooController'));

		$router = new \F3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);
		$this->assertEquals('F3\TestPackage\Controller\FooController', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function lowerCasePackageKeysAndObjectNamesAreConvertedToTheRealObjectName() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('F3\testpackage\bar\baz\Controller\fooController'))
			->will($this->returnValue('F3\TestPackage\Bar\Baz\Controller\FooController'));

		$router = new \F3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals('F3\TestPackage\Bar\Baz\Controller\FooController', $router->getControllerObjectName('testpackage', 'bar\baz', 'foo'));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getControllerObjectNameReturnsNullIfTheResolvedControllerDoesNotExist() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$mockObjectManager->expects($this->once())->method('getCaseSensitiveObjectName')
			->with($this->equalTo('F3\testpackage\Controller\fooController'))
			->will($this->returnValue(FALSE));

		$router = new \F3\FLOW3\MVC\Web\Routing\Router();
		$router->injectObjectManager($mockObjectManager);

		$this->assertEquals('', $router->getControllerObjectName('testpackage', '', 'foo'));
	}

	protected function getRouter() {
		return $this->getAccessibleMock('F3\FLOW3\MVC\Web\Routing\Router', array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
	}
}
?>