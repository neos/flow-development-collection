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
 * Testcase for the MVC Web Routing StaticRoutePart Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class StaticRoutePartTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfUriSegmentIsEmptyOrNull() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$uriSegments = array();
		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should never match if uriSegments array is empty');

		$uriSegments = array(NULL, 'foo');
		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should never match if uriSegment is NULL');

		$uriSegments = array('', 'foo');
		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should never match if current uriSegment is empty');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotSet() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$uriSegments = array('foo', 'bar');
		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should not match if name is not set');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToFirstUriSegment() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$uriSegments = array('bar', 'foo');

		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should not match if name is not equal to first uriSegment');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesMatchIfNameIsEqualToFirstUriSegment() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$uriSegments = array('foo', 'bar');

		$this->assertTrue($routePart->match($uriSegments), 'Static Route Part should match if name equals first uriSegment');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsEqualToTheBeginningOfTheFirstUriSegmentButTheSegmentIsLonger() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$uriSegments = array('foos', 'bar');

		$this->assertFalse($routePart->match($uriSegments));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');

		$uriSegments = array('foo', 'bar');
		$routePart->match($uriSegments);

		$uriSegments = array('bar', 'foo');
		$routePart->match($uriSegments);
		$this->assertNull($routePart->getValue(), 'Static Route Part value should be NULL after unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreNotChangedAfterUnsuccessfulMatch() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('bar');

		$uriSegments = array('foo', 'bar');
		$uriSegmentsCopy = $uriSegments;

		$routePart->match($uriSegments);

		$this->assertSame($uriSegmentsCopy, $uriSegments, 'Static Route Part should not change uriSegments array on unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsLastInSegment() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('bar');
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue(NULL));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('bar', 'foo', 'test');

		$routePart->match($uriSegments);

		$this->assertSame(array('foo', 'test'), $uriSegments, 'Static Route Part should shorten uriSegments array by one entry on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreNotShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsNotLastInSegment() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('bar');
		$mockRoutePart = $this->getMock('F3\FLOW3\MVC\Web\Routing\AbstractRoutePart');
		$mockUriPatternSegmentCollection = $this->getMock('F3\FLOW3\MVC\Web\Routing\UriPatternSegmentCollection');
		$mockUriPatternSegmentCollection->expects($this->once())->method('getNextRoutePartInCurrentUriPatternSegment')->will($this->returnValue($mockRoutePart));
		$routePart->setUriPatternSegments($mockUriPatternSegmentCollection);
		$uriSegments = array('bar', 'foo', 'test');

		$routePart->match($uriSegments);

		$this->assertSame(array('', 'foo', 'test'), $uriSegments, 'Static Route Part should shorten uriSegments array by one entry on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveEmptyArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array();

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveNonEmptyArray() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve non-empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingResolveDoesNotChangeRouteValues() {
		$routePart = new \F3\FLOW3\MVC\Web\Routing\StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals(array('@controller' => 'foo', '@action' => 'bar'), $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
	}
}
?>