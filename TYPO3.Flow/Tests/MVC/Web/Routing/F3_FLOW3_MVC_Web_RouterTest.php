<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
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
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_Routing_RouterTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setRoutesConfigurationParsesTheGivenConfigurationAndBuildsRouteObjectsFromIt() {
		$routesConfiguration = new F3_FLOW3_Configuration_Container();
		$routesConfiguration->route1->urlPattern = 'number1';
		$routesConfiguration->route2->urlPattern = 'number2';
		$routesConfiguration->route3->urlPattern = 'number3';

		$route1 = $this->getMock('F3_FLOW3_MVC_Web_Routing_Route', array('setUrlPattern', 'setDefaults'), array(), '', FALSE);
		$route1->expects($this->once())->method('setUrlPattern')->with($this->equalTo('number1'));

		$route2 = $this->getMock('F3_FLOW3_MVC_Web_Routing_Route', array('setUrlPattern', 'setDefaults'), array(), '', FALSE);
		$route2->expects($this->once())->method('setUrlPattern')->with($this->equalTo('number2'));

		$route3 = $this->getMock('F3_FLOW3_MVC_Web_Routing_Route', array('setUrlPattern', 'setDefaults'), array(), '', FALSE);
		$route3->expects($this->once())->method('setUrlPattern')->with($this->equalTo('number3'));

		$mockComponentManager = $this->getMock('F3_FLOW3_Component_ManagerInterface');
		$mockEnvironment = $this->getMock('F3_FLOW3_Utility_Environment', array(), array(), '', FALSE);

		$mockComponentFactory = $this->getMock('F3_FLOW3_Component_FactoryInterface', array('getComponent'));
		$mockComponentFactory->expects($this->exactly(3))->method('getComponent')->will($this->onConsecutiveCalls($route1, $route2, $route3));

		$route = new F3_FLOW3_MVC_Web_Routing_Router($mockComponentManager, $mockComponentFactory, $mockEnvironment);
		$route->setRoutesConfiguration($routesConfiguration);
	}

}
?>