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
 * Testcase for the MVC Web Routing DynamicRoutePart Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class DynamicRoutePartTest extends F3::Testing::BaseTestCase {

	/**
	 * @var F3::FLOW3::MVC::Web::Routing::DynamicRoutePart
	 */
	protected $routePart1;

	/**
	 * @var F3::FLOW3::MVC::Web::Routing::DynamicRoutePart
	 */
	protected $routePart2;

	/**
	 * Sets up this test case
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->routePart1 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Routing::DynamicRoutePart');
		$this->routePart2 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Routing::DynamicRoutePart');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartIsPrototype() {
		$this->assertNotSame($this->routePart1, $this->routePart2, 'Obviously the dynamic route part is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfUrlSegmentIsEmptyOrNullAndNoDefaultValueIsSet() {
		$this->routePart1->setName('foo');

		$urlSegments = array();
		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should not match if urlSegments array is empty and no default value is set.');

		$urlSegments = array(NULL, 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should never match if urlSegment is NULL.');

		$urlSegments = array('', 'foo');
		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should never match if current urlSegment is empty and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfDefaultValueIsSet() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue('bar');

		$urlSegments = array();
		$this->assertTrue($this->routePart1->match($urlSegments), 'dynamic route part should match if urlSegments array is empty and a default value is set.');

		$urlSegments = array('', 'foo');
		$this->assertTrue($this->routePart1->match($urlSegments), 'dynamic route part should match if current urlSegment is empty and a default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfNameIsNotSet() {
		$urlSegments = array('foo', 'bar');
		$this->routePart1->setDefaultValue('foo');

		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should not match if name is not set.');
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueMatchesFirstUrlSegmentAfterSuccessfulMatch() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue('bar');

		$urlSegments = array('firstSegment', 'secondSegment');
		$this->routePart1->match($urlSegments);

		$this->assertEquals('firstSegment', $this->routePart1->getValue(), 'value of dynamic route part should be equal to first urlSegment after successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function valueIsNullAfterUnsuccessfulMatch() {
		$this->routePart1->setName('foo');

		$urlSegments = array('foo', 'bar');
		$this->routePart1->match($urlSegments);

		$urlSegments = array('', 'foo');
		$this->routePart1->match($urlSegments);
		$this->assertNull($this->routePart1->getValue(), 'dynamic route part value should be NULL after unsuccessful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlSegmentsAreShortenedByOneSegmentAfterSuccessfulMatch() {
		$this->routePart1->setName('bar');
		$urlSegments = array('bar', 'foo', 'test');
		$this->routePart1->match($urlSegments);

		$this->assertSame(array('foo', 'test'), $urlSegments, 'dynamic route part should shorten urlSegments array by one entry on successful match.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesIfSplitStringIsFound() {
		$this->routePart1->setName('foo');
		$this->routePart1->setSplitString('-');
		$urlSegments = array('foo-bar', 'test');

		$this->assertTrue($this->routePart1->match($urlSegments), 'dynamic route part should match if current urlSegment contains splitString');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsNotFound() {
		$this->routePart1->setName('foo');
		$this->routePart1->setSplitString('-');
		$urlSegments = array('foo', 'test');

		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should not match if current urlSegment does not contain splitString');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartShortensCurrentUrlSegmentAfterSuccessfulMatch() {
		$this->routePart1->setName('foo');
		$this->routePart1->setSplitString('-');
		$urlSegments = array('foo-bar', 'test');
		$this->routePart1->match($urlSegments);
		
		$this->assertSame(array('-bar', 'test'), $urlSegments,  'dynamic route part should cut off first part of matching string until splitString');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition() {
		$this->routePart1->setName('foo');
		$this->routePart1->setSplitString('-');
		$urlSegments = array('-foo', 'bar');

		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part should not match if splitString is first character of current urlSegment');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartMatchesCorrectlyIfSplitStringContainsMoreCharacters() {
		$this->routePart1->setName('foo');
		$this->routePart1->setSplitString('_-_');

		$urlSegments = array('foo-bar', 'bar');
		$this->assertFalse($this->routePart1->match($urlSegments), 'dynamic route part with a splitString of "_-_" should not match urlParts separated by "-"');

		$urlSegments = array('foo_-_bar', 'bar');
		$this->assertTrue($this->routePart1->match($urlSegments), 'dynamic route part with a splitString of "_-_" should match urlParts separated by "_-_"');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartDoesNotResolveIfNameIsNotSet() {
		$routeValues = array('foo' => 'bar');

		$this->assertFalse($this->routePart1->resolve($routeValues), 'dynamic route part should not resolve if name is not set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartResolvesSimpleValueArray() {
		$this->routePart1->setName('foo');
		$routeValues = array('foo' => 'bar');
		
		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals('bar', $this->routePart1->getValue(), 'dynamic route part should resolve if an element with the same name exists in $routeValues.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutePartResolvesEmptyArrayIfDefaultValueIsSet() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue('defaultValue');
		$routeValues = array();
		
		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals('defaultValue', $this->routePart1->getValue(), 'dynamic route part should resolve if a default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveReturnsFalseIfNoCorrespondingValueIsGiven() {
		$this->routePart1->setName('foo');
		$routeValues = array('differentString' => 'bar');

		$this->assertFalse($this->routePart1->resolve($routeValues), 'dynamic route part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve() {
		$this->routePart1->setName('foo');
		$routeValues = array('foo' => 'bar', 'differentString' => 'value2');

		$this->assertTrue($this->routePart1->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'value2'), $routeValues, 'dynamic route part should unset matching element from $routeValues on successful resolve.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve() {
		$this->routePart1->setName('foo');
		$routeValues = array('differentString' => 'bar');

		$this->assertFalse($this->routePart1->resolve($routeValues));
		$this->assertEquals(array('differentString' => 'bar'), $routeValues, 'dynamic route part should not change $routeValues on unsuccessful resolve.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartValueIsNullAfterUnsuccessfulResolve() {
		$this->routePart1->setName('foo');
		$this->routePart1->setDefaultValue('defaultValue');
		$routeValues = array();
		
		$this->assertTrue($this->routePart1->resolve($routeValues));
		
		$this->routePart1->setDefaultValue(NULL);
		$this->assertFalse($this->routePart1->resolve($routeValues));
		$this->assertNull($this->routePart1->getValue(), 'dynamic route part value should be NULL when call to resolve() was not successful.');
	}
}
?>