<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RouterTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRoutesConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt() {
		$routesConfiguration = array();
		$routesConfiguration['route1']['uriPattern'] = 'number1';
		$routesConfiguration['route2']['uriPattern'] = 'number2';
		$routesConfiguration['route3']['uriPattern'] = 'number3';

		$route1 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('setUriPattern', 'setDefaults'), array(), '', FALSE);
		$route1->expects($this->once())->method('setUriPattern')->with($this->equalTo('number1'));

		$route2 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('setUriPattern', 'setDefaults'), array(), '', FALSE);
		$route2->expects($this->once())->method('setUriPattern')->with($this->equalTo('number2'));

		$route3 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('setUriPattern', 'setDefaults'), array(), '', FALSE);
		$route3->expects($this->once())->method('setUriPattern')->with($this->equalTo('number3'));

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array('create'));
		$mockObjectFactory->expects($this->exactly(3))->method('create')->will($this->onConsecutiveCalls($route1, $route2, $route3));

		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('dummy'));
		$router->_set('objectFactory', $mockObjectFactory);
		$router->setRoutesConfiguration($routesConfiguration);
		$router->resolve(array());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveIteratesOverTheRegisteredRoutesAndReturnsTheMatchingURIIfAny() {
		$routeValues = array('foo' => 'bar');

		$route1 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);
		$route1->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(FALSE));

		$route2 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves', 'getMatchingURI'), array(), '', FALSE);
		$route2->expects($this->once())->method('resolves')->with($routeValues)->will($this->returnValue(TRUE));
		$route2->expects($this->once())->method('getMatchingURI')->will($this->returnValue('route2'));

		$route3 = $this->getMock('F3\FLOW3\MVC\Web\Routing\Route', array('resolves'), array(), '', FALSE);

		$mockRoutes = array($route1, $route2, $route3);

		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('createRoutesFromConfiguration'), array(), '', FALSE);
		$router->expects($this->once())->method('createRoutesFromConfiguration');
		$router->_set('routes', $mockRoutes);

		$matchingURI = $router->resolve($routeValues);
		$this->assertSame('route2', $matchingURI);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function packageKeyCanBeSetByRoute() {
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@package' => 'myPackage')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('setControllerPackageKey')->with($this->equalTo('mypackage'));

		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subpackageKeyCanBeSetByRoute() {
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@subpackage' => 'mySubpackage')));
		
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('setControllerSubpackageKey')->with($this->equalTo('mysubpackage'));
		
		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function controllerNameCanBeSetByRoute() {
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@controller' => 'mycontroller')));
		
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('setControllerName')->with($this->equalTo('mycontroller'));
		
		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function actionNameCanBeSetByRoute() {
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@action' => 'myaction')));
		
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('setControllerActionName')->with($this->equalTo('myaction'));
		
		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function formatCanBeSetByRoute() {
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@format' => 'myFormat')));
		
		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->once())->method('getArguments')->will($this->returnValue(array()));
		$mockRequest->expects($this->once())->method('setFormat')->with($this->equalTo('myformat'));
		
		$router->route($mockRequest);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function packageKeyCanBeOverwrittenByRequest() {
		$packageKey = NULL;
		$setControllerPackageKeyCallback = function() use (&$packageKey) {
			$args = func_get_args();
			$packageKey = $args[0];
		};
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@package' => 'myPackage')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('@package' => 'overwrittenPackage')));
		$mockRequest->expects($this->exactly(2))->method('setControllerPackageKey')->will($this->returnCallback($setControllerPackageKeyCallback));

		$router->route($mockRequest);

		$this->assertEquals('overwrittenpackage', $packageKey);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subpackageKeyCanBeOverwrittenByRequest() {
		$subpackageKey = NULL;
		$setControllerSubpackageKeyCallback = function() use (&$subpackageKey) {
			$args = func_get_args();
			$subpackageKey = $args[0];
		};
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@subpackage' => 'mySubpackage')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('@subpackage' => 'overwrittenSubpackage')));
		$mockRequest->expects($this->exactly(2))->method('setControllerSubpackageKey')->will($this->returnCallback($setControllerSubpackageKeyCallback));

		$router->route($mockRequest);

		$this->assertEquals('overwrittensubpackage', $subpackageKey);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function controllerNameCanBeOverwrittenByRequest() {
		$controllerName = NULL;
		$setControllerNameCallback = function() use (&$controllerName) {
			$args = func_get_args();
			$controllerName = $args[0];
		};
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@controller' => 'myController')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('@controller' => 'overwrittenController')));
		$mockRequest->expects($this->exactly(2))->method('setControllerName')->will($this->returnCallback($setControllerNameCallback));

		$router->route($mockRequest);

		$this->assertEquals('overwrittencontroller', $controllerName);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function actionNameCanOverwrittenByRequest() {
		$actionName = NULL;
		$setControllerActionNameCallback = function() use (&$actionName) {
			$args = func_get_args();
			$actionName = $args[0];
		};
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@action' => 'myAction')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('@action' => 'overwrittenAction')));
		$mockRequest->expects($this->exactly(2))->method('setControllerActionName')->will($this->returnCallback($setControllerActionNameCallback));

		$router->route($mockRequest);

		$this->assertEquals('overwrittenaction', $actionName);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function formatCanBeOverwrittenByRequest() {
		$format = NULL;
		$setFormatCallback = function() use (&$format) {
			$args = func_get_args();
			$format = $args[0];
		};
		$router = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Router'), array('findMatchResults', 'setArgumentsFromRawRequestData'), array(), '', FALSE);
		$router->expects($this->once())->method('findMatchResults')->with('foo')->will($this->returnValue(array('@format' => 'myFormat')));

		$mockRequest = $this->getMock('F3\FLOW3\MVC\Web\Request', array(), array(), '', FALSE);
		$mockRequest->expects($this->once())->method('getRequestPath')->will($this->returnValue('foo'));
		$mockRequest->expects($this->any())->method('getArguments')->will($this->returnValue(array('@format' => 'overwrittenFormat')));
		$mockRequest->expects($this->exactly(2))->method('setFormat')->will($this->returnCallback($setFormatCallback));

		$router->route($mockRequest);

		$this->assertEquals('overwrittenformat', $format);
	}
}
?>