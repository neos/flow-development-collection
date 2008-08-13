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

require_once(__DIR__ . '/../../Fixture/Web/Routing/F3_FLOW3_MVC_Fixture_Web_Routing_MockRoutePartHandler.php');

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
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeIsPrototype() {
		$route1 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_Route');
		$route2 = $this->componentFactory->getComponent('F3_FLOW3_MVC_Web_Routing_Route');
		$this->assertNotSame($route1, $route2, 'Obviously route is not prototype!');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('SomePackage');

		$defaults = array(
			'@package' => 'SomePackage',
			'@controller' => 'SomeController',
			'@action' => 'someAction'
		);

		$route->setDefaults($defaults);
		$route->matches('SomePackage');
		$matchResults = $route->getMatchResults();

		$this->assertEquals($defaults['@controller'], $matchResults['@controller']);
		$this->assertEquals($defaults['@action'], $matchResults['@action']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theControllerComponentNamePatternCanBeSetAndRetrieved() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setControllerComponentNamePattern('XY3_@package_@controller');
		$this->assertEquals('XY3_@package_@controller', $route->getControllerComponentNamePattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchUrlPatternIfItIsNullOrEmpty() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$requestPath = 'foo/bar';

		$this->assertFalse($route->matches($requestPath), 'Route should not match if no urlPattern is set.');

		$route->setUrlPattern('');
		$this->assertFalse($route->matches($requestPath), 'Route should not match if urlPattern is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[foo]/[bar]');

		$this->assertFalse($route->matches(NULL), 'Route should not match if requestPath is NULL.');
		$this->assertFalse($route->matches(''), 'Route should not match if requestPath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function aSimpleStaticRouteConsistsOfThePathSegmentsSeparatedByTheForwardSlash() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('foo/bar');

		$this->assertFalse($route->matches('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
		$this->assertTrue($route->matches('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutesAreEnclosedInSquareBrackets() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('foo/[bar]');

		$this->assertFalse($route->matches('bar/someValue'), '"foo/[bar]"-Route should not match "bar/someValue"-request.');
		$this->assertTrue($route->matches('foo/someValue'), '"foo/[bar]"-Route should match "foo/someValue"-request.');
		$this->assertEquals(array('bar' => 'someValue'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticAndDynamicRoutesCanBeMixedInAnyOrder() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($route->matches('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($route->matches('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingUrlPatternResetsRoute() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($route->matches('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($route->matches('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');

		$route->setUrlPattern('foo/[key3]/foo');

		$this->assertFalse($route->matches('foo/value3/bar'), '"foo/[key3]/foo"-Route should not match "foo/value3/bar"-request.');
		$this->assertTrue($route->matches('foo/value3/foo'), '"foo/[key3]/foo"-Route should match "foo/value3/foo"-request.');
		$this->assertEquals(array('key3' => 'value3'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlPatternSegmentCanContainTwoDynamicRouteParts() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('user/[firstName]-[lastName]');

		$this->assertFalse($route->matches('user/johndoe'), '"user/[firstName]-[lastName]"-Route should not match "user/johndoe"-request.');
		$this->assertTrue($route->matches('user/john-doe'), '"user/[firstName]-[lastName]"-Route should match "user/john-doe"-request.');
		$this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function urlPatternSegmentsCanContainMultipleDynamicRouteParts() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]-[key2]/[key3].[key4].[@format]');

		$this->assertFalse($route->matches('value1-value2/value3.value4value5'), '"[key1]-[key2]/[key3].[key4].[@format]"-Route should not match "value1-value2/value3.value4value5"-request.');
		$this->assertTrue($route->matches('value1-value2/value3.value4.value5'), '"[key1]-[key2]/[key3].[key4].[@format]"-Route should match "value1-value2/value3.value4.value5"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function defaultValuesAreSetForUrlPatternSegmentsWithMultipleRouteParts() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]-[key2]/[key3].[key4].[@format]');
		$defaults = array(
			'key1' => 'defaultValue1',
			'key2' => 'defaultValue2',
			'key3' => 'defaultValue3',
			'key4' => 'defaultValue4'
		);
		$route->setDefaults($defaults);
		$route->matches('foo-/.bar.xml');

		$this->assertEquals(array('key1' => 'foo', 'key2' => 'defaultValue2', 'key3' => 'defaultValue3', 'key4' => 'bar', '@format' => 'xml'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function processingUrlPatternWithSuccessiveDynamicRoutepartsThrowsException() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1][key2]');
		try {
			$route->matches('value1value2');
			$this->fail('matches() did not throw an exception although the specified urlPattern contains successive dynamic route parts which is not possible.');
		} catch (F3_FLOW3_MVC_Exception_SuccessiveDynamicRouteParts $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingNonExistingRoutePartHandlerThrowsException() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'Non_Existing_RoutePartHandler',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler class is inexistent.');
		} catch (F3_FLOW3_Component_Exception_UnknownComponent $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingInvalidRoutePartHandlerThrowsException() {
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3_FLOW3_MVC_Web_Routing_StaticRoutePart',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler is invalid.');
		} catch (F3_FLOW3_MVC_Exception_InvalidRoutePartHandler $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function registeredRoutePartHandlerIsInvoked() {
		$this->componentManager->registerComponent('F3_FLOW3_MVC_Fixture_Web_Routing_MockRoutePartHandler');
		$route = new F3_FLOW3_MVC_Web_Routing_Route($this->componentFactory);
		$route->setUrlPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3_FLOW3_MVC_Fixture_Web_Routing_MockRoutePartHandler',
			)
		);
		$route->matches('foo/bar');

		$this->assertEquals(array('key1' => 'invoked', 'key2' => 'bar'), $route->getMatchResults());
	}
}
?>
