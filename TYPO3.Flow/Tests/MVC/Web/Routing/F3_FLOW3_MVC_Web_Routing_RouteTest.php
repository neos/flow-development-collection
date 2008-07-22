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
 * Testcase for the MVC Web Routing Route Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_MVC_Web_Routing_RouteTest extends F3_Testing_BaseTestCase {

	/**
	 * @var F3_FLOW3_MVC_Web_Routing_Route
	 */
	protected $route1;

	/**
	 * @var F3_FLOW3_MVC_Web_Routing_Route
	 */
	protected $route2;

	/**
	 * Sets up this test case
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function setUp() {
		$this->route1 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_Route');
		$this->route2 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_Route');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeIsPrototype() {
		$this->assertNotSame($this->route1, $this->route2, 'Obviously route is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchUrlPatternIsNullOrEmpty() {
		$requestPath = 'foo/bar';

		$this->assertFalse($this->route1->match($requestPath), 'Route should not match if no urlPattern is set.');

		$this->route1->setUrlPattern('');
		$this->assertFalse($this->route1->match($requestPath), 'Route should not match if urlPattern is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$this->route1->setUrlPattern('[foo]/[bar]');

		$this->assertFalse($this->route1->match(NULL), 'Route should not match if requestPath is NULL.');
		$this->assertFalse($this->route1->match(''), 'Route should not match if requestPath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function simpleStaticRouteMatchesCorrectly() {
		$this->route1->setUrlPattern('foo/bar');

		$this->assertFalse($this->route1->match('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
		$this->assertTrue($this->route1->match('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function simpleDynamicRouteMatchesCorrectly() {
		$this->route1->setUrlPattern('foo/[bar]');

		$this->assertFalse($this->route1->match('bar/someValue'), '"foo/[bar]"-Route should not match "bar/someValue"-request.');
		$this->assertTrue($this->route1->match('foo/someValue'), '"foo/[bar]"-Route should match "foo/someValue"-request.');
		$this->assertSame(array('bar' => 'someValue'), $this->route1->getValues(), 'Route-values should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function complexDynamicRouteMatchesCorrectly() {
		$this->route1->setUrlPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($this->route1->match('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($this->route1->match('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertSame(array('key1' => 'value1', 'key2' => 'value2'), $this->route1->getValues(), 'Route-values should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function simpleSubRouteMatchesCorrectly() {
		$this->route1->setUrlPattern('foo/bar/[[parameters]]');

		$this->assertFalse($this->route1->match('bar/foo/key1/value1/key2/value2'), '"foo/bar/[[parameters]]"-Route should not match "bar/foo/key1/value1/key2/value2"-request.');
		$this->assertTrue($this->route1->match('foo/bar/key1/value1/key2/value2'), '"foo/bar/[[parameters]]"-Route should match "foo/bar/key1/value1/key2/value2"-request.');
		$this->assertSame(array('parameters' => array('key1' => 'value1', 'key2' => 'value2')), $this->route1->getValues(), 'Route-values should be set correctly on successful match');
	}
}
?>