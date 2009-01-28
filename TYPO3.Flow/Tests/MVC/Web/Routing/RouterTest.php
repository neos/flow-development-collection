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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Testcase for the MVC Web Router
 *
 * @package FLOW3
 * @subpackage MVC
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

		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ManagerInterface');
		$mockEnvironment = $this->getMock('F3\FLOW3\Utility\Environment', array(), array(), '', FALSE);

		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface', array('create'));
		$mockObjectFactory->expects($this->exactly(3))->method('create')->will($this->onConsecutiveCalls($route1, $route2, $route3));

		$route = new \F3\FLOW3\MVC\Web\Routing\Router($mockObjectManager, $mockObjectFactory, $mockEnvironment);
		$route->setRoutesConfiguration($routesConfiguration);
	}

}
?>
