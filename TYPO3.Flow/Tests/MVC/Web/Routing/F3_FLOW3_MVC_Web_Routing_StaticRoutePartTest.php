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
class F3_FLOW3_MVC_Web_Routing_StaticRoutePartTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_MVC_Web_Routing_StaticRoutePart
	 */
	protected $routePart1;

	/**
	 * @var F3_FLOW3_MVC_Web_Routing_StaticRoutePart
	 */
	protected $routePart2;

	/**
	 * Sets up this test case
	 *
	 * @author  Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->routePart1 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_StaticRoutePart');
		$this->routePart2 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_StaticRoutePart');
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
	public function staticRoutePartDoesNotMatchIfUrlSegmentIsEmptyOrNull() {
		$this->routePart1->setName('foo');

		$urlSegments = array();
		$this->assertFalse($this->routePart1->match($urlSegments), 'static route part should never match if urlSegments array is empty');

		$urlSegments = array(NULL, 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'static route part should never match if urlSegment is NULL');

		$urlSegments = array('', 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'static route part should never match if current urlSegment is empty');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotSet() {
		$urlSegments = array('foo', 'bar');
		$this->assertFalse($this->routePart1->match($urlSegments), 'static route part should not match if name is not set');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsNotEqualToFirstUrlSegment() {
		$this->routePart1->setName('foo');
		$urlSegments = array('bar', 'foo');

		$this->assertFalse($this->routePart1->match($urlSegments), 'static route part should not match if name is not equal to first urlSegment');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticRoutePartDoesMatchIfNameIsEqualToFirstUrlSegment() {
		$this->routePart1->setName('foo');
		$urlSegments = array('foo', 'bar');

		$this->assertTrue($this->routePart1->match($urlSegments), 'static route part should match if name equals first urlSegment');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function staticRoutePartDoesNotMatchIfNameIsEqualToTheBeginningOfTheFirstUrlSegmentButTheSegmentIsLonger() {
		$this->routePart1->setName('foo');
		$this->routePart1->setLastRoutePartInSegment(TRUE);
		$urlSegments = array('foos', 'bar');

		$this->assertFalse($this->routePart1->match($urlSegments));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$this->routePart1->setName('foo');

		$urlSegments = array('foo', 'bar');
		$this->routePart1->match($urlSegments);

		$urlSegments = array('bar', 'foo');
		$this->routePart1->match($urlSegments);
		$this->assertNull($this->routePart1->getValue(), 'static route part value should be NULL after unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlSegmentsAreNotChangedAfterUnsuccessfulMatch() {
		$this->routePart1->setName('bar');

		$urlSegments = array('foo', 'bar');
		$urlSegmentsCopy = $urlSegments;

		$this->routePart1->match($urlSegments);

		$this->assertSame($urlSegmentsCopy, $urlSegments, 'static route part should not change urlSegments array on unsuccessful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlSegmentsAreShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsLastInSegment() {
		$this->routePart1->setName('bar');
		$this->routePart1->setLastRoutePartInSegment(TRUE);
		$urlSegments = array('bar', 'foo', 'test');

		$this->routePart1->match($urlSegments);

		$this->assertSame(array('foo', 'test'), $urlSegments, 'static route part should shorten urlSegments array by one entry on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlSegmentsAreNotShortenedByOneSegmentAfterSuccessfulMatchIfRoutePartIsNotLastInSegment() {
		$this->routePart1->setName('bar');
		$this->routePart1->setLastRoutePartInSegment(FALSE);
		$urlSegments = array('bar', 'foo', 'test');

		$this->routePart1->match($urlSegments);

		$this->assertSame(array('', 'foo', 'test'), $urlSegments, 'static route part should shorten urlSegments array by one entry on successful match');
	}
}
?>
