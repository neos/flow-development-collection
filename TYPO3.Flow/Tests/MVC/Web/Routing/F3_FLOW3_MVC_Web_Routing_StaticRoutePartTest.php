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
	 * @var F3::FLOW3::MVC::Web::Routing::StaticRoutePart
	 */
	protected $routePart1;

	/**
	 * @var F3::FLOW3::MVC::Web::Routing::StaticRoutePart
	 */
	protected $routePart2;

	/**
	 * Sets up this test case
	 *
	 * @author  Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->routePart1 = $this->componentManager->getComponent('F3::FLOW3::MVC::Web::Routing::StaticRoutePart');
		$this->routePart2 = $this->componentManager->getComponent('F3::FLOW3::MVC::Web::Routing::StaticRoutePart');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartIsPrototype() {
		$this->assertNotSame($this->routePart1, $this->routePart2, 'Obviously the static route part is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfUriSegmentIsEmptyOrNull() {
		$this->routePart1->setName('foo');

		$uriSegments = array();
		$this->assertFalse($this->routePart1->match($uriSegments), 'static route part should never match if uriSegments array is empty');

		$uriSegments = array(NULL, 'foo');
		$this->assertFalse($this->routePart1->match($uriSegments), 'static route part should never match if uriSegment is NULL');

		$uriSegments = array('', 'foo');
		$this->assertFalse($this->routePart1->match($uriSegments), 'static route part should never match if current uriSegment is empty');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotSet() {
		$uriSegments = array('foo', 'bar');
		$this->assertFalse($this->routePart1->match($uriSegments), 'static route part should not match if name is not set');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToFirstUriSegment() {
		$this->routePart1->setName('foo');
		$uriSegments = array('bar', 'foo');

		$this->assertFalse($this->routePart1->match($uriSegments), 'static route part should not match if name is not equal to first uriSegment');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesMatchIfNameIsEqualToFirstUriSegment() {
		$this->routePart1->setName('foo');
		$uriSegments = array('foo', 'bar');

		$this->assertTrue($this->routePart1->match($uriSegments), 'static route part should match if name equals first uriSegment');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsEqualToTheBeginningOfTheFirstUriSegmentButTheSegmentIsLonger() {
		$this->routePart1->setName('foo');
		$this->routePart1->setLastRoutePartInSegment(TRUE);
		$uriSegments = array('foos', 'bar');

		$this->assertFalse($this->routePart1->match($uriSegments));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$this->routePart1->setName('foo');

		$uriSegments = array('foo', 'bar');
		$this->routePart1->match($uriSegments);

		$uriSegments = array('bar', 'foo');
		$this->routePart1->match($uriSegments);
		$this->assertNull($this->routePart1->getValue(), 'static route part value should be NULL after unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreNotChangedAfterUnsuccessfulMatch() {
		$this->routePart1->setName('bar');

		$uriSegments = array('foo', 'bar');
		$uriSegmentsCopy = $uriSegments;

		$this->routePart1->match($uriSegments);

		$this->assertSame($uriSegmentsCopy, $uriSegments, 'static route part should not change uriSegments array on unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsLastInSegment() {
		$this->routePart1->setName('bar');
		$this->routePart1->setLastRoutePartInSegment(TRUE);
		$uriSegments = array('bar', 'foo', 'test');

		$this->routePart1->match($uriSegments);

		$this->assertSame(array('foo', 'test'), $uriSegments, 'static route part should shorten uriSegments array by one entry on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriSegmentsAreNotShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsNotLastInSegment() {
		$this->routePart1->setName('bar');
		$this->routePart1->setLastRoutePartInSegment(FALSE);
		$uriSegments = array('bar', 'foo', 'test');

		$this->routePart1->match($uriSegments);

		$this->assertSame(array('', 'foo', 'test'), $uriSegments, 'static route part should shorten uriSegments array by one entry on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveEmptyArray() {
		$this->routePart1->setName('foo');
		$routeValues = array();
		
		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals('foo', $this->routePart1->getValue(), 'static route part should resolve empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartCanResolveNonEmptyArray() {
		$this->routePart1->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals('foo', $this->routePart1->getValue(), 'static route part should resolve non-empty routeValues-array');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function callingResolveDoesNotChangeRouteValues() {
		$this->routePart1->setName('foo');
		$routeValues = array('@controller' => 'foo', '@action' => 'bar');

		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals(array('@controller' => 'foo', '@action' => 'bar'), $routeValues, 'when resolve() is called on static route part, specified routeValues-array should never be changed');
	}
}
?>