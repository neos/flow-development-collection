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

require_once(__DIR__ . '/../../Fixture/Web/Routing/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RouteTest extends \F3\Testing\BaseTestCase {

	/**
	 * @var \F3\FLOW3\Object\ObjectFactory
	 */
	protected $mockObjectFactory;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	/**
	 * @var \F3\FLOW3\MVC\Web\Routing\Route
	 */
	protected $route;

	/**
	 * Sets up this test case
	 *
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setUp() {
		$this->mockObjectFactory = $this->getMock('F3\FLOW3\Object\ObjectFactoryInterface');
		$this->mockObjectFactory->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectFactoryCallBack')));
		$this->mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->route = $this->getMock($this->buildAccessibleProxy('F3\FLOW3\MVC\Web\Routing\Route'), array('dummy'), array($this->mockObjectFactory, $this->mockObjectManager));
	}

	/**
	 * @return object but only mocks
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function objectFactoryCallBack() {
		$arguments = func_get_args();
		$objectName = array_shift($arguments);
		return $this->getMock($objectName, array('dummy'), $arguments);
	}

	/*                                                                        *
	 * Basic functionality (scope, getters, setters, exceptions)              *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setNameCorrectlySetsRouteName() {
		$this->route->setName('SomeName');

		$this->assertEquals('SomeName', $this->route->getName());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingUriPatternResetsRoute() {
		$this->route->_set('isParsed', TRUE);
		$this->route->setUriPattern('foo/{key3}/foo');

		$this->assertFalse($this->route->_get('isParsed'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routePartHandlerIsInstanciated() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'SomeRoutePartHandler',
				)
			)
		);
		$mockRoutePartHandler = $this->getMock('F3\FLOW3\MVC\Web\Routing\DynamicRoutePartInterface');
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidRoutePartHandlerException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function settingInvalidRoutePartHandlerThrowsException() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'F3\FLOW3\MVC\Web\Routing\StaticRoutePart',
				)
			)
		);
		$mockRoutePartHandler = $this->getMock('F3\FLOW3\MVC\Web\Routing\StaticRoutePart');
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\MVC\Web\Routing\StaticRoutePart')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithTrailingSlashThrowsException() {
		$this->route->setUriPattern('some/uri/pattern/');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithLeadingSlashThrowsException() {
		$this->route->setUriPattern('/some/uri/pattern');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithSuccessiveDynamicRoutepartsThrowsException() {
		$this->route->setUriPattern('{key1}{key2}');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithSuccessiveOptionalSectionsThrowsException() {
		$this->route->setUriPattern('(foo/bar)(/bar/foo)');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithUnterminatedOptionalSectionsThrowsException() {
		$this->route->setUriPattern('foo/(bar');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidUriPatternException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternWithUnopenedOptionalSectionsThrowsException() {
		$this->route->setUriPattern('foo)/bar');
		$this->route->parse();
	}

	/*                                                                        *
	 * URI matching                                                           *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsNull() {
		$this->route->setUriPattern('');

		$this->assertFalse($this->route->matches(NULL), 'Route should not match if requestPath is NULL.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet() {
		$this->assertFalse($this->route->matches(''), 'Route should not match if no URI Pattern is set.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsDifferentFromStaticUriPattern() {
		$this->route->setUriPattern('foo/bar');

		$this->assertFalse($this->route->matches('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfOneSegmentOfRequestPathIsDifferentFromItsRespectiveStaticUriPatternSegment() {
		$this->route->setUriPattern('foo/{bar}');

		$this->assertFalse($this->route->matches('bar/someValue'), '"foo/{bar}"-Route should not match "bar/someValue"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternIsEmpty() {
		$this->route->setUriPattern('');

		$this->assertTrue($this->route->matches(''), 'Route should match if URI Pattern and RequestPath are empty.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesIfRequestPathIsEqualToStaticUriPattern() {
		$this->route->setUriPattern('foo/bar');

		$this->assertTrue($this->route->matches('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRequestPathIsEqualToStaticUriPatternWithoutSlashes() {
		$this->route->setUriPattern('required1/required2');

		$this->assertFalse($this->route->matches('required1required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesIfStaticSegmentsMatchAndASegmentExistsForAllDynamicUriPartSegments() {
		$this->route->setUriPattern('foo/{bar}');

		$this->assertTrue($this->route->matches('foo/someValue'), '"foo/{bar}"-Route should match "foo/someValue"-request.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getMatchResultsReturnsCorrectResultsAfterSuccessfulMatch() {
		$this->route->setUriPattern('foo/{bar}');
		$this->route->matches('foo/someValue');

		$this->assertEquals(array('bar' => 'someValue'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function staticAndDynamicRoutesCanBeMixedInAnyOrder() {
		$this->route->setUriPattern('{key1}/foo/{key2}/bar');

		$this->assertFalse($this->route->matches('value1/foo/value2/foo'), '"{key1}/foo/{key2}/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($this->route->matches('value1/foo/value2/bar'), '"{key1}/foo/{key2}/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentCanContainTwoDynamicRouteParts() {
		$this->route->setUriPattern('user/{firstName}-{lastName}');

		$this->assertFalse($this->route->matches('user/johndoe'), '"user/{firstName}-{lastName}"-Route should not match "user/johndoe"-request.');
		$this->assertTrue($this->route->matches('user/john-doe'), '"user/{firstName}-{lastName}"-Route should match "user/john-doe"-request.');
		$this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function uriPatternSegmentsCanContainMultipleDynamicRouteParts() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');

		$this->assertFalse($this->route->matches('value1-value2/value3.value4value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should not match "value1-value2/value3.value4value5"-request.');
		$this->assertTrue($this->route->matches('value1-value2/value3.value4.value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should match "value1-value2/value3.value4.value5"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRoutePartDoesNotMatchAndDefaultValueIsSet() {
		$this->route->setUriPattern('{foo}');
		$this->route->setDefaults(array('foo' => 'bar'));

		$this->assertFalse($this->route->matches(''), 'Route should not match if required Route Part does not match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName() {
		$this->route->setUriPattern('SomePackage');

		$defaults = array(
			'@package' => 'SomePackage',
			'@controller' => 'SomeController',
			'@action' => 'someAction'
		);

		$this->route->setDefaults($defaults);
		$this->route->matches('SomePackage');
		$matchResults = $this->route->getMatchResults();

		$this->assertEquals($defaults['@controller'], $matchResults{'@controller'});
		$this->assertEquals($defaults['@action'], $matchResults['@action']);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingMatch() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler',
				)
			)
		);
		$mockRoutePartHandler = new \F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler();
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
		$this->route->matches('foo/bar');

		$this->assertEquals(array('key1' => '_match_invoked_', 'key2' => 'bar'), $this->route->getMatchResults());
	}

	/*                                                                        *
	 * URI matching (optional Route Parts)                                    *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalStaticRoutePart() {
		$this->route->setUriPattern('(optional)');

		$this->assertTrue($this->route->matches(''));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('required(optional)');

		$this->assertTrue($this->route->matches('requiredoptional'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneRequiredAndOneOptionalStaticRoutePart() {
		$this->route->setUriPattern('required(optional)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required(optional2)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required(optional2)');

		$this->assertTrue($this->route->matches('optionalrequiredoptional2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithoutDefaultValue() {
		$this->route->setUriPattern('({optional})');

		$this->assertFalse($this->route->matches(''));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithDefaultValue() {
		$this->route->setUriPattern('({optional})');
		$this->route->setDefaults(array('optional' => 'defaultValue'));

		$this->assertTrue($this->route->matches(''));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathContainingNoneOfTheOptionalRoutePartsIfNoDefaultsAreSet() {
		$this->route->setUriPattern('page(.{@format})');

		$this->assertFalse($this->route->matches('page'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathContainingOnlySomeOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertFalse($this->route->matches('page.'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathContainingNoneOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertTrue($this->route->matches('page'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathContainingAllOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertTrue($this->route->matches('page.html'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathWithRequiredAndOnlyOneOptionalPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertFalse($this->route->matches('required/optional1'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathWithAllPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertTrue($this->route->matches('required/optional1/optional2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1(/optional1/optional2)/required2');

		$this->assertTrue($this->route->matches('required1/required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1/(optional1/optional2/)required2');

		$this->assertFalse($this->route->matches('required1/optional1/required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1/(optional1/optional2/)required2');

		$this->assertTrue($this->route->matches('required1/optional1/optional2/required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertTrue($this->route->matches('required1/required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertFalse($this->route->matches('optional1/required1/required2'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertTrue($this->route->matches('optional1/optional2/required1/required2'));
	}


	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeDoesNotMatchIfRoutePartDoesNotMatchAndIsOptionalButHasNoDefault() {
		$this->route->setUriPattern('({foo})');

		$this->assertFalse($this->route->matches(''), 'Route should not match if optional Route Part does not match and has no default value.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeMatchesIfRoutePartDoesNotMatchButIsOptionalAndHasDefault() {
		$this->route->setUriPattern('({foo})');
		$this->route->setDefaults(array('foo' => 'bar'));

		$this->assertTrue($this->route->matches(''), 'Route should match if optional Route Part has a default value.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function defaultValuesAreSetForUriPatternSegmentsWithMultipleOptionalRouteParts() {
		$this->route->setUriPattern('{key1}-({key2})/({key3}).({key4}.{@format})');
		$defaults = array(
			'key1' => 'defaultValue1',
			'key2' => 'defaultValue2',
			'key3' => 'defaultValue3',
			'key4' => 'defaultValue4'
		);
		$this->route->setDefaults($defaults);
		$this->route->matches('foo-/.bar.xml');

		$this->assertEquals(array('key1' => 'foo', 'key2' => 'defaultValue2', 'key3' => 'defaultValue3', 'key4' => 'bar', '@format' => 'xml'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/*                                                                        *
	 * URI resolving                                                          *
	 *                                                                        */

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */

	public function matchingRouteIsProperlyResolved() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
		$this->route->setDefaults(array('@format' => 'xml'));
		$this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('value1-value2/value3.value4.xml', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeAppendsAdditionalQueryParametersIfUriPatternContainsLessValuesThanAreSpecified() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
		$this->route->setDefaults(array('@format' => 'xml'));
		$this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('value1-value2/value3.value4.xml?nonexistingkey=foo', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCanBeResolvedIfASpecifiedValueIsEqualToItsDefaultValue() {
		$this->route->setUriPattern('');
		$this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$this->routeValues = array('key1' => 'value1');

		$this->assertTrue($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolvesDoesNotAlterTheCaseOfMatchingUriByDefault() {
		$this->route->setUriPattern('CamelCase/{someKey}');
		$this->routeValues = array('someKey' => 'CamelCase');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('CamelCase/CamelCase', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolvesLowerCasesMatchingUriIfToLowerCaseIsSet() {
		$this->route->setUriPattern('CamelCase/{someKey}');
		$this->route->setLowerCase(TRUE);
		$this->routeValues = array('someKey' => 'CamelCase');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('camelcase/camelcase', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue() {
		$this->route->setUriPattern('');
		$this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$this->routeValues = array('key2' => 'differentValue');

		$this->assertFalse($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo mock object factory
	 */
	public function matchingRequestPathIsNullAfterUnsuccessfulResolve() {
		$mockObjectManager = $this->getMock('F3\FLOW3\Object\ObjectManagerInterface');
		$this->route = new \F3\FLOW3\MVC\Web\Routing\Route($this->mockObjectFactory, $mockObjectManager);
		$this->route->setUriPattern('{key1}');
		$this->routeValues = array('key1' => 'value1');

		$this->assertTrue($this->route->resolves($this->routeValues));

		$this->routeValues = array('differentKey' => 'value1');
		$this->assertFalse($this->route->resolves($this->routeValues));
		$this->assertNull($this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingResolve() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler',
				)
			)
		);
		$this->routeValues = array('key2' => 'value2');
		$mockRoutePartHandler = new \F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler();
		$this->mockObjectManager->expects($this->once())->method('getObject')->with('F3\FLOW3\MVC\Fixture\Web\Routing\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
		$this->route->resolves($this->routeValues);

		$this->assertEquals('_resolve_invoked_/value2', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolvesAppendsRemainingRouteValuesToMatchingUri() {
		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$this->route->resolves(array('foo' => 'bar', 'baz' => array('foo2' => 'bar2')));

		$actualResult = $this->route->getMatchingUri();
		$expectedResult = '?foo=bar&baz%5Bfoo2%5D=bar2';

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException \F3\FLOW3\MVC\Exception\InvalidArgumentValueException
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolvesThrowsExceptionIfRouteValuesContainsObjectsWithoutIdentity() {
		$objectWithoutIdentity = new \stdClass();

		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($objectWithoutIdentity)->will($this->returnValue(FALSE));
		$this->route->injectPersistenceManager($mockPersistenceManager);

		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$this->route->resolves(array('foo' => 'bar', 'someObject' => $objectWithoutIdentity));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function resolvesRecursivelyConvertsDomainObjectsIntoAUuidIdentity() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();


		$mockPersistenceManager = $this->getMock('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->at(0))->method('getIdentifierByObject')->with($object1)->will($this->returnValue('uuid1'));
		$mockPersistenceManager->expects($this->at(1))->method('getIdentifierByObject')->with($object2)->will($this->returnValue('uuid2'));
		$this->route->injectPersistenceManager($mockPersistenceManager);

		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$this->route->resolves(array('foo' => 'bar', 'someObject' => $object1, 'baz' => array('someOtherObject' => $object2)));

		$actualResult = $this->route->getMatchingUri();
		$expectedResult = '?foo=bar&someObject%5B__identity%5D=uuid1&baz%5BsomeOtherObject%5D%5B__identity%5D=uuid2';

		$this->assertEquals($expectedResult, $actualResult);
	}
}
?>
