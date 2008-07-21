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
 * Testcase for the MVC Web Routing SubRoutePart Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_Routing_SubRoutePartTest extends F3_Testing_BaseTestCase {

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
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->routePart1 = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Routing_SubRoutePart');
		$this->routePart2 = $this->componentManager->getComponent('F3_FLOW3_MVC_Web_Routing_SubRoutePart');
	}
	
	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subRoutePartIsPrototype() {
		$this->assertNotSame($this->routePart1, $this->routePart2, 'Obviously the sub route part is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subRoutePartDoesNotMatchIfUrlSegmentIsEmptyOrNullAndNoDefaultValueIsSet() {
		$this->routePart1->setName('foo');
		
		$urlSegments = array();
		$this->assertFalse($this->routePart1->match($urlSegments), 'sub route part should not match if urlSegments array is empty and no default value is set.');
		
		$urlSegments = array(NULL, 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'sub route part should never match if urlSegment is NULL.');
		
		$urlSegments = array('', 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'sub route part should never match if current urlSegment is empty and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subRouteMatchesIfDefaultValueIsSet() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue('bar');

		$urlSegments = array();
		$this->assertTrue($this->routePart1->match($urlSegments), 'sub route part should match if urlSegments array is empty and a default value is set.');
		
		$urlSegments = array('', 'foo');
		$this->assertTrue($this->routePart1->match($urlSegments), 'sub route part should match if current urlSegment is empty and a default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function subRoutePartDoesNotMatchIfNameIsNotSet() {
		$urlSegments = array('foo', 'bar');
		$this->routePart1->setDefaultValue('foo');
		
		$this->assertFalse($this->routePart1->match($urlSegments), 'sub route part should not match if name is not set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesDefaultValueIfUrlSegmentIsEmpty() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue(array('foo' => 'bar'));

		$urlSegments = array();
		$this->routePart1->match($urlSegments);

		$expectedValue = array('foo' => 'bar');
		$this->assertEquals($expectedValue, $this->routePart1->getValue(), 'value of sub route part should match default value if urlSegments array is empty');
	}
	
	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesRemainingUrlSegmentsAfterSuccessfulMatch() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue(array('foo' => 'bar'));

		$urlSegments = array('firstSegment', 'secondSegment', 'thirdSegment');
		$this->routePart1->match($urlSegments);

		$expectedValue = array('firstSegment' => 'secondSegment', 'thirdSegment' => NULL);
		$this->assertEquals($expectedValue, $this->routePart1->getValue(), 'value of sub route part should be an array in the form segment1 => segment2, segment3 => segment4.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch () {
		$this->routePart1->setName('foo');
		
		$urlSegments = array('foo', 'bar');
		$this->routePart1->match($urlSegments);
		
		$urlSegments = array('', 'foo');
		$this->routePart1->match($urlSegments);
		$this->assertNull($this->routePart1->getValue(), 'sub route part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlSegmentsAreEmptyAfterSuccessfulMatch () {
		$this->routePart1->setName('bar');
		
		$urlSegments = array('bar', 'foo', 'test');
		
		$this->routePart1->match($urlSegments);
		
		$this->assertSame(array(), $urlSegments, 'sub route part should empty urlSegments array on successful match.');
	}
}
?>