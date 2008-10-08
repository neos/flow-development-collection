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

require_once(__DIR__ . '/../../Fixture/Web/Routing/F3_FLOW3_MVC_Fixture_Web_Routing_MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class RouteTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeIsPrototype() {
		$route1 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Routing::Route');
		$route2 = $this->componentFactory->getComponent('F3::FLOW3::MVC::Web::Routing::Route');
		$this->assertNotSame($route1, $route2, 'Obviously route is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setNameCorrectlySetsRouteName() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setName('SomeName');

		$this->assertEquals('SomeName', $route->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('SomePackage');

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
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setControllerComponentNamePattern('XY3_@package_@controller');
		$this->assertEquals('XY3_@package_@controller', $route->getControllerComponentNamePattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);

		$this->assertFalse($route->matches(''), 'Route should not match if no URI Pattern is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesMatchEmptyRequestPathIfUriPatternIsEmpty() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('');

		$this->assertTrue($route->matches(''), 'Route should not match if URI Pattern is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsNullOrEmpty() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[foo]/[bar]');

		$this->assertFalse($route->matches(NULL), 'Route should not match if requestPath is NULL.');
		$this->assertFalse($route->matches(''), 'Route should not match if requestPath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function aSimpleStaticRouteConsistsOfThePathSegmentsSeparatedByTheForwardSlash() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('foo/bar');

		$this->assertFalse($route->matches('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
		$this->assertTrue($route->matches('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function dynamicRoutesAreEnclosedInSquareBrackets() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('foo/[bar]');

		$this->assertFalse($route->matches('bar/someValue'), '"foo/[bar]"-Route should not match "bar/someValue"-request.');
		$this->assertTrue($route->matches('foo/someValue'), '"foo/[bar]"-Route should match "foo/someValue"-request.');
		$this->assertEquals(array('bar' => 'someValue'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticAndDynamicRoutesCanBeMixedInAnyOrder() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($route->matches('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($route->matches('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingUriPatternResetsRoute() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($route->matches('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($route->matches('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');

		$route->setUriPattern('foo/[key3]/foo');

		$this->assertFalse($route->matches('foo/value3/bar'), '"foo/[key3]/foo"-Route should not match "foo/value3/bar"-request.');
		$this->assertTrue($route->matches('foo/value3/foo'), '"foo/[key3]/foo"-Route should match "foo/value3/foo"-request.');
		$this->assertEquals(array('key3' => 'value3'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentCanContainTwoDynamicRouteParts() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('user/[firstName]-[lastName]');

		$this->assertFalse($route->matches('user/johndoe'), '"user/[firstName]-[lastName]"-Route should not match "user/johndoe"-request.');
		$this->assertTrue($route->matches('user/john-doe'), '"user/[firstName]-[lastName]"-Route should match "user/john-doe"-request.');
		$this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesUrisWithRequestParameters() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('search?query=[query]');
		
		$this->assertTrue($route->matches('search?query=foo'));
		$this->assertEquals(array('query' => 'foo'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentsCanContainMultipleDynamicRouteParts() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]-[key2]/[key3].[key4].[@format]');

		$this->assertFalse($route->matches('value1-value2/value3.value4value5'), '"[key1]-[key2]/[key3].[key4].[@format]"-Route should not match "value1-value2/value3.value4value5"-request.');
		$this->assertTrue($route->matches('value1-value2/value3.value4.value5'), '"[key1]-[key2]/[key3].[key4].[@format]"-Route should match "value1-value2/value3.value4.value5"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function defaultValuesAreSetForUriPatternSegmentsWithMultipleRouteParts() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]-[key2]/[key3].[key4].[@format]');
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
	public function processingUriPatternWithSuccessiveDynamicRoutepartsThrowsException() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1][key2]');
		try {
			$route->matches('value1value2');
			$this->fail('matches() did not throw an exception although the specified URI Pattern contains successive dynamic route parts which is not possible.');
		} catch (F3::FLOW3::MVC::Exception::SuccessiveDynamicRouteParts $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingNonExistingRoutePartHandlerThrowsException() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'Non_Existing_RoutePartHandler',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler class is inexistent.');
		} catch (F3::FLOW3::Component::Exception::UnknownComponent $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingInvalidRoutePartHandlerThrowsException() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3::FLOW3::MVC::Web::Routing::StaticRoutePart',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler is invalid.');
		} catch (F3::FLOW3::MVC::Exception::InvalidRoutePartHandler $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchingRouteIsProperlyResolved() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]-[key2]/[key3].[key4].[@format]');
		$route->setDefaults(array('@format' => 'xml'));
		$routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4');

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('value1-value2/value3.value4.xml', $route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCantBeResolvedIfUriPatternContainsLessValuesThanAreSpecified() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]-[key2]/[key3].[key4].[@format]');
		$route->setDefaults(array('@format' => 'xml'));
		$routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo');

		$this->assertFalse($route->resolves($routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanBeResolvedIfASpecifiedValueIsEqualToItsDefaultValue() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('');
		$route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$routeValues = array('key1' => 'value1');

		$this->assertTrue($route->resolves($routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('');
		$route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$routeValues = array('key2' => 'differentValue');

		$this->assertFalse($route->resolves($routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithRequestParameters() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('search?query=[query]');
		$routeValues = array('query' => 'foo');

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?query=foo', $route->getMatchingURI());
	}
	
	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchingRequestPathIsNullAfterUnsuccessfulResolve() {
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]');
		$routeValues = array('key1' => 'value1');

		$this->assertTrue($route->resolves($routeValues));

		$routeValues = array('differentKey' => 'value1');
		$this->assertFalse($route->resolves($routeValues));
		$this->assertNull($route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingMatch() {
		$this->componentManager->registerComponent('F3::FLOW3::MVC::Fixture::Web::Routing::MockRoutePartHandler');
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3::FLOW3::MVC::Fixture::Web::Routing::MockRoutePartHandler',
			)
		);
		$route->matches('foo/bar');

		$this->assertEquals(array('key1' => '_match_invoked_', 'key2' => 'bar'), $route->getMatchResults());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingResolve() {
		$this->componentManager->registerComponent('F3::FLOW3::MVC::Fixture::Web::Routing::MockRoutePartHandler');
		$route = new F3::FLOW3::MVC::Web::Routing::Route($this->componentFactory);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3::FLOW3::MVC::Fixture::Web::Routing::MockRoutePartHandler',
			)
		);
		$routeValues = array('key2' => 'value2');
		$route->resolves($routeValues);

		$this->assertEquals('_resolve_invoked_/value2', $route->getMatchingURI());
	}
}
?>
