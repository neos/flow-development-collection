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
class UriPatternSegmentCollectionTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentCollectionIsPrototype() {
		$uriPatternSegmentCollection1 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$uriPatternSegmentCollection2 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$this->assertNotSame($uriPatternSegmentCollection1, $uriPatternSegmentCollection2, 'Obviously UriPatternSegmentCollection is not prototype!');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addingStringToUriSegmentCollectionThrowsException() {
		$uriPatternSegmentCollection = new \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection();
		$uriPatternSegmentCollection->append('some string');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartCollectionsCanBeAddedToUriSegmentCollection() {
		$uriPatternSegmentCollection = new \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection();
		$mockRoutePartCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
		$uriPatternSegmentCollection->append($mockRoutePartCollection);
		$this->assertSame($uriPatternSegmentCollection->current(), $mockRoutePartCollection);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function nextRoutePartIsNullWhenCollectionIsEmpty() {
		$uriPatternSegmentCollection = new \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection();
		$this->assertNull($uriPatternSegmentCollection->getNextRoutePartInCurrentUriPatternSegment());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function nextRoutePartReturnsRoutePart() {
		$uriPatternSegmentCollection = new \F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection();
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockRoutePartCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\RoutePartCollection');
		$mockRoutePartCollection->expects($this->once())->method('offsetExists')->with(1)->will($this->returnValue(TRUE));
		$mockRoutePartCollection->expects($this->once())->method('offsetGet')->will($this->returnValue($mockRoutePart));
		$uriPatternSegmentCollection->append($mockRoutePartCollection);
		$this->assertSame($mockRoutePart, $uriPatternSegmentCollection->getNextRoutePartInCurrentUriPatternSegment());
	}
}
?>