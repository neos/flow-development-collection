<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC\Web\Routing;

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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the MVC Web Routing RoutePartCollection Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RoutePartCollectionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartCollectionIsPrototype() {
		$routePartCollection1 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
		$routePartCollection2 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
		$this->assertNotSame($routePartCollection1, $routePartCollection2, 'Obviously RoutePartCollection is not prototype!');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addingStringToRoutePartCollectionThrowsException() {
		$routePartCollection = new \F3\FLOW3\MVC\Web\Routing\RoutePartCollection();
		$routePartCollection->append('some string');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartCanBeAddedToRoutePartCollection() {
		$routePartCollection = new \F3\FLOW3\MVC\Web\Routing\RoutePartCollection();
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$routePartCollection->append($mockRoutePart);
		$this->assertSame($routePartCollection->current(), $mockRoutePart);
	}
}
?>