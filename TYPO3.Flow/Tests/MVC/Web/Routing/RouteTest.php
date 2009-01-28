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

require_once(__DIR__ . '/../../Fixture/Web/Routing/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RouteTest extends \F3\Testing\BaseTestCase {

	/*                                                                        *
	 * Basic functionality (scope, getters, setters, ...)                     *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeIsPrototype() {
		$route1 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\Route');
		$route2 = $this->objectFactory->create('F3\FLOW3\MVC\Web\Routing\Route');
		$this->assertNotSame($route1, $route2, 'Obviously route is not prototype!');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setNameCorrectlySetsRouteName() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setName('SomeName');

		$this->assertEquals('SomeName', $route->getName());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theControllerObjectNamePatternCanBeSetAndRetrieved() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setControllerObjectNamePattern('XY3_@package_@controller');
		$this->assertEquals('XY3_@package_@controller', $route->getControllerObjectNamePattern());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingUriPatternResetsRoute() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
	public function settingNonExistingRoutePartHandlerThrowsException() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'Non_Existing_RoutePartHandler',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler class is inexistent.');
		} catch (\F3\FLOW3\Object\Exception\UnknownObject $exception) {
		}
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingInvalidRoutePartHandlerThrowsException() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3\FLOW3\MVC\Web\Routing\StaticRoutePart',
			)
		);
		try {
			$route->matches('foo/bar');
			$this->fail('matches() did not throw an exception although the specified routePart handler is invalid.');
		} catch (\F3\FLOW3\MVC\Exception\InvalidRoutePartHandler $exception) {
		}
	}

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsNull() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[foo]/[bar]');

		$this->assertFalse($route->matches(NULL), 'Route should not match if requestPath is NULL.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsEmpty() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[foo]/[bar]');

		$this->assertFalse($route->matches(''), 'Route should not match if requestPath is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);

		$this->assertFalse($route->matches(''), 'Route should not match if no URI Pattern is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsDifferentFromStaticUriPattern() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('foo/bar');

		$this->assertFalse($route->matches('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfOneSegmentOfRequestPathIsDifferentFromItsRespectiveStaticUriPatternSegment() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('foo/[bar]');

		$this->assertFalse($route->matches('bar/someValue'), '"foo/[bar]"-Route should not match "bar/someValue"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternIsEmpty() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('');

		$this->assertTrue($route->matches(''), 'Route should not match if URI Pattern is empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesIfRequestPathIsEqualToStaticUriPattern() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('foo/bar');

		$this->assertTrue($route->matches('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesIfStaticSegmentsMatchAndASegmentExtistsForAllDynamicUriPartSegments() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('foo/[bar]');

		$this->assertTrue($route->matches('foo/someValue'), '"foo/[bar]"-Route should match "foo/someValue"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMatchResultsReturnsCorrectResultsAfterSuccessfulMatch() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('foo/[bar]');
		$route->matches('foo/someValue');

		$this->assertEquals(array('bar' => 'someValue'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticAndDynamicRoutesCanBeMixedInAnyOrder() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1]/foo/[key2]/bar');

		$this->assertFalse($route->matches('value1/foo/value2/foo'), '"[key1]/foo/[key2]/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($route->matches('value1/foo/value2/bar'), '"[key1]/foo/[key2]/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentCanContainTwoDynamicRouteParts() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('user/[firstName]-[lastName]');

		$this->assertFalse($route->matches('user/johndoe'), '"user/[firstName]-[lastName]"-Route should not match "user/johndoe"-request.');
		$this->assertTrue($route->matches('user/john-doe'), '"user/[firstName]-[lastName]"-Route should match "user/john-doe"-request.');
		$this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentsCanContainMultipleDynamicRouteParts() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1][key2]');
		try {
			$route->matches('value1value2');
			$this->fail('matches() did not throw an exception although the specified URI Pattern contains successive Dynamic Route Parts which is not possible.');
		} catch (\F3\FLOW3\MVC\Exception\SuccessiveDynamicRouteParts $exception) {
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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

	/*                                                                        *
	 * URI matching (query strings)                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithQueryStringIfUriPatternContainsDifferentStaticQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=value1');

		$this->assertFalse($route->matches('search', 'param1=value2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithoutQueryStringIfUriPatternContainsQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]');

		$this->assertFalse($route->matches('search'), 'if UriPattern contains a query string, the URI must include a query string too.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithMissingQueryParametersIfUriPatternContainsQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]&param2=[param2]');

		$this->assertFalse($route->matches('search', 'param1=value1'), 'if UriPattern contains a query string, the URI must include all configured query parameters.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithDifferentQueryParametersIfUriPatternContainsQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]');

		$this->assertFalse($route->matches('search', 'differentParamenter=value'), 'if UriPattern contains a query string, the URI\'s query parameter must be the same.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithDifferentQueryParameterOrderIfUriPatternContainsQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]&param2=[param2]');

		$this->assertFalse($route->matches('search', 'param2=value2&param1=value1'), 'if UriPattern contains a query string, the URI\'s query parameter must be in the same order.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchAnUriWithAdditionalQueryParametersIfUriPatternContainsQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]');

		$this->assertFalse($route->matches('search', 'param1=value1&param2=value2'), 'if UriPattern contains a query string, the URI may not include additional query parameters.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesAnUriWithAnyQueryStringIfUriPatternDoesNotContainQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search');

		$this->assertTrue($route->matches('search', 'param1=value1&param2=value2'), 'if UriPattern does not contain a query string, the URI\'s query parameters are ignored.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesAnUriWithQueryStringIfUriPatternContainsMatchingStaticQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=value1');

		$this->assertTrue($route->matches('search', 'param1=value1'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesAnUriWithQueryStringIfUriPatternContainsMatchingDynamicQueryString() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]');

		$this->assertTrue($route->matches('search', 'param1=value1'));
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */
	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */

	public function matchingRouteIsProperlyResolved() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('');
		$route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$routeValues = array('key2' => 'differentValue');

		$this->assertFalse($route->resolves($routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function matchingRequestPathIsNullAfterUnsuccessfulResolve() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
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
		$this->objectManager->registerObject('F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler');
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler',
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
		$this->objectManager->registerObject('F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler');
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('[key1]/[key2]');
		$route->setRoutePartHandlers(
			array(
				'key1' => 'F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler',
			)
		);
		$routeValues = array('key2' => 'value2');
		$route->resolves($routeValues);

		$this->assertEquals('_resolve_invoked_/value2', $route->getMatchingURI());
	}

	/*                                                                        *
	 * URI resolving (query strings)                                          *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithOneStaticQueryParameter() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=value1');
		$routeValues = array();

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?param1=value1', $route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithMultipleStaticQueryParameter() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=value1&param2=value2&param3=value3');
		$routeValues = array();

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?param1=value1&param2=value2&param3=value3', $route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithOneDefaultDynamicQueryParameter() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[value1]');
		$route->setDefaults(array('value1' => 'defaultValue1'));
		$routeValues = array();

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?param1=defaultvalue1', $route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithMultipleDefaultDynamicQueryParameters() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]&param2=[param2]');
		$route->setDefaults(array('param1' => 'defaultValue1', 'param2' => 'defaultValue2'));
		$routeValues = array();

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?param1=defaultvalue1&param2=defaultvalue2', $route->getMatchingURI());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanResolveUrisWithQueryParameters() {
		$route = new \F3\FLOW3\MVC\Web\Routing\Route($this->objectFactory, $this->objectManager);
		$route->setUriPattern('search?param1=[param1]');
		$routeValues = array('param1' => 'foo');

		$this->assertTrue($route->resolves($routeValues));
		$this->assertEquals('search?param1=foo', $route->getMatchingURI());
	}
}
?>
