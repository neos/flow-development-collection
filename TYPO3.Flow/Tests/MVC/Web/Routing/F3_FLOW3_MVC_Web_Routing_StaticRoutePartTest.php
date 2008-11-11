<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::MVC::Web::Routing;

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
 * Testcase for the MVC Web Routing StaticRoutePart Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class StaticRoutePartTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartIsPrototype() {
		$routePart1 = $this->objectFactory->create('F3::FLOW3::MVC::Web::Routing::StaticRoutePart');
		$routePart2 = $this->objectFactory->create('F3::FLOW3::MVC::Web::Routing::StaticRoutePart');
		$this->assertNotSame($routePart1, $routePart2, 'Obviously the Static Route Part is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfUriSegmentIsEmptyOrNull() {
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$uriSegments = array('foo', 'bar');
		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should not match if name is not set');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToFirstUriSegment() {
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$routePart->setName('foo');
		$uriSegments = array('bar', 'foo');

		$this->assertFalse($routePart->match($uriSegments), 'Static Route Part should not match if name is not equal to first uriSegment');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesMatchIfNameIsEqualToFirstUriSegment() {
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$routePart->setName('foo');
		$uriSegments = array('foos', 'bar');

		$this->assertFalse($routePart->match($uriSegments));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$routePart->setName('bar');
		$mockUriPatternSegmentCollection = $this->getMock('F3::FLOW3::MVC::Web::Routing::UriPatternSegmentCollection');
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$routePart->setName('bar');
		$mockRoutePart = $this->getMock('F3::FLOW3::MVC::Web::Routing::AbstractRoutePart');
		$mockUriPatternSegmentCollection = $this->getMock('F3::FLOW3::MVC::Web::Routing::UriPatternSegmentCollection');
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
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
		$routePart = new F3::FLOW3::MVC::Web::Routing::StaticRoutePart();
		$routePart->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($routePart->resolve($routeValues));
		$this->assertEquals(array('@controller' => 'foo', '@action' => 'bar'), $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
	}
}
?>