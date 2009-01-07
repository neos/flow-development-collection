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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the MVC Web Routing RoutePartCollection Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class UriPatternSegmentCollectionTest extends \F3\Testing\BaseTestCase {

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