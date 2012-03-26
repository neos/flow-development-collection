<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Routing;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(__DIR__ . '/Fixtures/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 *
 */
class RouteTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManager
	 */
	protected $mockObjectManager;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $mockPersistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\Route
	 */
	protected $route;

	/**
	 * @var \TYPO3\FLOW3\Mvc\Routing\RouterInterface
	 */
	protected $mockRouter;

	/**
	 * Sets up this test case
	 *
	 */
	public function setUp() {
		$this->mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->mockObjectManager->expects($this->any())->method('create')->will($this->returnCallback(array($this, 'objectManagerCallBack')));
		$this->route = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Routing\Route', array('dummy'));
		$this->route->_set('objectManager', $this->mockObjectManager);

		$this->mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$this->mockRouter->expects($this->any())->method('getControllerObjectName')->will($this->returnValue('SomeControllerObjectName'));
		$this->route->injectRouter($this->mockRouter);

		$this->mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$this->mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnCallback(function ($array) { return $array; }));
		$this->route->injectPersistenceManager($this->mockPersistenceManager);
	}

	/**
	 * @return object but only mocks
	 */
	public function objectManagerCallBack() {
		$arguments = func_get_args();
		$objectName = array_shift($arguments);
		return $this->getMock($objectName, array('dummy'), $arguments);
	}

	/*                                                                        *
	 * Basic functionality (scope, getters, setters, exceptions)              *
	 *                                                                        */

	/**
	 * @test
	 */
	public function setNameCorrectlySetsRouteName() {
		$this->route->setName('SomeName');

		$this->assertEquals('SomeName', $this->route->getName());
	}

	/**
	 * @test
	 */
	public function settingUriPatternResetsRoute() {
		$this->route->_set('isParsed', TRUE);
		$this->route->setUriPattern('foo/{key3}/foo');

		$this->assertFalse($this->route->_get('isParsed'));
	}

	/**
	 * @test
	 */
	public function routePartHandlerIsInstantiated() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'SomeRoutePartHandler',
				)
			)
		);
		$mockRoutePartHandler = $this->getMock('TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface');
		$this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartHandlerException
	 */
	public function settingInvalidRoutePartHandlerThrowsException() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'TYPO3\FLOW3\Mvc\Routing\StaticRoutePart',
				)
			)
		);
		$mockRoutePartHandler = $this->getMock('TYPO3\FLOW3\Mvc\Routing\StaticRoutePart');
		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\FLOW3\Mvc\Routing\StaticRoutePart')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

	/**
	 * @test
	 */
	public function ifAnObjectTypeIsSpecifiedTheIdentityRoutePartHandlerIsInstantiated() {
		$this->route->setUriPattern('{key1}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'objectType' => 'SomeObjectType',
				)
			)
		);

		$this->route->parse();
		$identityRoutePart = current($this->route->_get('routeParts'));
		$this->assertInstanceOf('TYPO3\FLOW3\Mvc\Routing\IdentityRoutePart', $identityRoutePart);
		$this->assertSame('SomeObjectType', $identityRoutePart->getObjectType());
	}

	/**
	 * @test
	 */
	public function parseSetsUriPatternOfIdentityRoutePartIfSpecified() {
		$this->route->setUriPattern('{key1}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'objectType' => 'SomeObjectType',
					'uriPattern' => 'SomeUriPattern'
				)
			)
		);

		$this->route->parse();
		$identityRoutePart = current($this->route->_get('routeParts'));
		$this->assertSame('SomeUriPattern', $identityRoutePart->getUriPattern());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
	 */
	public function uriPatternWithTrailingSlashThrowsException() {
		$this->route->setUriPattern('some/uri/pattern/');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
	 */
	public function uriPatternWithLeadingSlashThrowsException() {
		$this->route->setUriPattern('/some/uri/pattern');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
	 */
	public function uriPatternWithSuccessiveDynamicRoutepartsThrowsException() {
		$this->route->setUriPattern('{key1}{key2}');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
	 */
	public function uriPatternWithSuccessiveOptionalSectionsThrowsException() {
		$this->route->setUriPattern('(foo/bar)(/bar/foo)');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
	 */
	public function uriPatternWithUnterminatedOptionalSectionsThrowsException() {
		$this->route->setUriPattern('foo/(bar');
		$this->route->parse();
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidUriPatternException
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
	 */
	public function routeDoesNotMatchIfRequestPathIsNull() {
		$this->route->setUriPattern('');

		$this->assertFalse($this->route->matches(NULL), 'Route should not match if routePath is NULL.');
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet() {
		$this->assertFalse($this->route->matches(''), 'Route should not match if no URI Pattern is set.');
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchIfRequestPathIsDifferentFromStaticUriPattern() {
		$this->route->setUriPattern('foo/bar');

		$this->assertFalse($this->route->matches('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchIfOneSegmentOfRequestPathIsDifferentFromItsRespectiveStaticUriPatternSegment() {
		$this->route->setUriPattern('foo/{bar}');

		$this->assertFalse($this->route->matches('bar/someValue'), '"foo/{bar}"-Route should not match "bar/someValue"-request.');
	}

	/**
	 * @test
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternIsEmpty() {
		$this->route->setUriPattern('');

		$this->assertTrue($this->route->matches(''), 'Route should match if URI Pattern and RequestPath are empty.');
	}

	/**
	 * @test
	 */
	public function routeMatchesIfRequestPathIsEqualToStaticUriPattern() {
		$this->route->setUriPattern('foo/bar');

		$this->assertTrue($this->route->matches('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchIfRequestPathIsEqualToStaticUriPatternWithoutSlashes() {
		$this->route->setUriPattern('required1/required2');

		$this->assertFalse($this->route->matches('required1required2'));
	}

	/**
	 * @test
	 */
	public function routeMatchesIfStaticSegmentsMatchAndASegmentExistsForAllDynamicUriPartSegments() {
		$this->route->setUriPattern('foo/{bar}');

		$this->assertTrue($this->route->matches('foo/someValue'), '"foo/{bar}"-Route should match "foo/someValue"-request.');
	}

	/**
	 * @test
	 */
	public function getMatchResultsReturnsCorrectResultsAfterSuccessfulMatch() {
		$this->route->setUriPattern('foo/{bar}');
		$this->route->matches('foo/someValue');

		$this->assertEquals(array('bar' => 'someValue'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 */
	public function staticAndDynamicRoutesCanBeMixedInAnyOrder() {
		$this->route->setUriPattern('{key1}/foo/{key2}/bar');

		$this->assertFalse($this->route->matches('value1/foo/value2/foo'), '"{key1}/foo/{key2}/bar"-Route should not match "value1/foo/value2/foo"-request.');
		$this->assertTrue($this->route->matches('value1/foo/value2/bar'), '"{key1}/foo/{key2}/bar"-Route should match "value1/foo/value2/bar"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 */
	public function uriPatternSegmentCanContainTwoDynamicRouteParts() {
		$this->route->setUriPattern('user/{firstName}-{lastName}');

		$this->assertFalse($this->route->matches('user/johndoe'), '"user/{firstName}-{lastName}"-Route should not match "user/johndoe"-request.');
		$this->assertTrue($this->route->matches('user/john-doe'), '"user/{firstName}-{lastName}"-Route should match "user/john-doe"-request.');
		$this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 */
	public function uriPatternSegmentsCanContainMultipleDynamicRouteParts() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');

		$this->assertFalse($this->route->matches('value1-value2/value3.value4value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should not match "value1-value2/value3.value4value5"-request.');
		$this->assertTrue($this->route->matches('value1-value2/value3.value4.value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should match "value1-value2/value3.value4.value5"-request.');
		$this->assertEquals(array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchIfRoutePartDoesNotMatchAndDefaultValueIsSet() {
		$this->route->setUriPattern('{foo}');
		$this->route->setDefaults(array('foo' => 'bar'));

		$this->assertFalse($this->route->matches(''), 'Route should not match if required Route Part does not match.');
	}

	/**
	 * @test
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
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingMatch() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler',
				)
			)
		);
		$mockRoutePartHandler = new \TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler();
		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
		$this->route->matches('foo/bar');

		$this->assertEquals(array('key1' => '_match_invoked_', 'key2' => 'bar'), $this->route->getMatchResults());
	}

	/**
	 * @test
	 * @dataProvider matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider()
	 * @param boolean $shouldThrowException
	 * @param mixed $routePartValue
	 */
	public function matchesThrowsExceptionIfRoutePartValueContainsObjects($shouldThrowException, $routePartValue) {
		if ($shouldThrowException === TRUE) {
			$this->setExpectedException('TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException');
		}
		$mockRoutePart = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart->expects($this->once())->method('match')->with('foo')->will($this->returnValue(TRUE));
		$mockRoutePart->expects($this->any())->method('getName')->will($this->returnValue('TestRoutePart'));
		$mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue($routePartValue));

		$this->route->setUriPattern('foo');
		$this->route->_set('routeParts', array($mockRoutePart));
		$this->route->_set('isParsed', TRUE);
		$this->route->matches('foo');
	}

	/**
	 * Data provider
	 */
	public function matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider() {
		$object = new \stdClass();
		return array(
			array(TRUE, array('foo' => $object)),
			array(TRUE, array('foo' => 'bar', 'baz' => $object)),
			array(TRUE, array('foo' => array('bar' => array('baz' => 'quux', 'here' => $object)))),
			array(FALSE, array('no object')),
			array(FALSE, array('foo' => 'no object')),
			array(FALSE, array(TRUE))
		);
	}

	/**
	 * @test
	 */
	public function matchesRecursivelyMergesMatchResults() {
		$mockRoutePart1 = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart1->expects($this->once())->method('match')->will($this->returnValue(TRUE));
		$mockRoutePart1->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart1'));
		$mockRoutePart1->expects($this->once())->method('getValue')->will($this->returnValue('foo'));

		$mockRoutePart2 = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart2->expects($this->once())->method('match')->will($this->returnValue(TRUE));
		$mockRoutePart2->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('someOtherRoutePart'));
		$mockRoutePart2->expects($this->once())->method('getValue')->will($this->returnValue('bar'));

		$mockRoutePart3 = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart3->expects($this->once())->method('match')->will($this->returnValue(TRUE));
		$mockRoutePart3->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart2'));
		$mockRoutePart3->expects($this->once())->method('getValue')->will($this->returnValue('baz'));

		$this->route->setUriPattern('');
		$this->route->_set('routeParts', array($mockRoutePart1, $mockRoutePart2, $mockRoutePart3));
		$this->route->_set('isParsed', TRUE);
		$this->route->matches('');

		$expectedResult = array('firstLevel' => array('secondLevel' => array('routePart1' => 'foo', 'routePart2' => 'baz')), 'someOtherRoutePart' => 'bar');
		$actualResult = $this->route->getMatchResults();
		$this->assertEquals($expectedResult, $actualResult);
	}

	/*                                                                        *
	 * URI matching (optional Route Parts)                                    *
	 *                                                                        */

	/**
	 * @test
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalStaticRoutePart() {
		$this->route->setUriPattern('(optional)');

		$this->assertTrue($this->route->matches(''));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('required(optional)');

		$this->assertTrue($this->route->matches('requiredoptional'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneRequiredAndOneOptionalStaticRoutePart() {
		$this->route->setUriPattern('required(optional)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required(optional2)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart() {
		$this->route->setUriPattern('(optional)required(optional2)');

		$this->assertTrue($this->route->matches('optionalrequiredoptional2'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithoutDefaultValue() {
		$this->route->setUriPattern('({optional})');

		$this->assertFalse($this->route->matches(''));
	}

	/**
	 * @test
	 */
	public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithDefaultValue() {
		$this->route->setUriPattern('({optional})');
		$this->route->setDefaults(array('optional' => 'defaultValue'));

		$this->assertTrue($this->route->matches(''));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathContainingNoneOfTheOptionalRoutePartsIfNoDefaultsAreSet() {
		$this->route->setUriPattern('page(.{@format})');

		$this->assertFalse($this->route->matches('page'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathContainingOnlySomeOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertFalse($this->route->matches('page.'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathContainingNoneOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertTrue($this->route->matches('page'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathContainingAllOfTheOptionalRouteParts() {
		$this->route->setUriPattern('page(.{@format})');
		$this->route->setDefaults(array('@format' => 'html'));

		$this->assertTrue($this->route->matches('page.html'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertTrue($this->route->matches('required'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathWithRequiredAndOnlyOneOptionalPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertFalse($this->route->matches('required/optional1'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathWithAllPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required(/optional1/optional2)');

		$this->assertTrue($this->route->matches('required/optional1/optional2'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1(/optional1/optional2)/required2');

		$this->assertTrue($this->route->matches('required1/required2'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1/(optional1/optional2/)required2');

		$this->assertFalse($this->route->matches('required1/optional1/required2'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('required1/(optional1/optional2/)required2');

		$this->assertTrue($this->route->matches('required1/optional1/optional2/required2'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertTrue($this->route->matches('required1/required2'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertFalse($this->route->matches('optional1/required1/required2'));
	}

	/**
	 * @test
	 */
	public function routeMatchesRequestPathWithAllPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts() {
		$this->route->setUriPattern('(optional1/optional2/)required1/required2');

		$this->assertTrue($this->route->matches('optional1/optional2/required1/required2'));
	}

	/**
	 * @test
	 */
	public function routeDoesNotMatchIfRoutePartDoesNotMatchAndIsOptionalButHasNoDefault() {
		$this->route->setUriPattern('({foo})');

		$this->assertFalse($this->route->matches(''), 'Route should not match if optional Route Part does not match and has no default value.');
	}

	/**
	 * @test
	 */
	public function routeMatchesIfRoutePartDoesNotMatchButIsOptionalAndHasDefault() {
		$this->route->setUriPattern('({foo})');
		$this->route->setDefaults(array('foo' => 'bar'));

		$this->assertTrue($this->route->matches(''), 'Route should match if optional Route Part has a default value.');
	}

	/**
	 * @test
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
	 */
	public function byDefaultRouteDoesNotResolveIfUriPatternContainsLessValuesThanAreSpecified() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
		$this->route->setDefaults(array('@format' => 'xml'));
		$this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo');

		$this->assertFalse($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 */
	public function routeAlwaysAppendsExceedingInternalArguments() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
		$this->route->setDefaults(array('@format' => 'xml'));
		$this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('value1-value2/value3.value4.xml?__someInternalArgument=someValue', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function routeAppendsAllAdditionalQueryParametersIfUriPatternContainsLessValuesThanAreSpecifiedIfAppendExceedingArgumentsIsTrue() {
		$this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
		$this->route->setDefaults(array('@format' => 'xml'));
		$this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue', 'nonexistingkey' => 'foo');
		$this->route->setAppendExceedingArguments(TRUE);

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('value1-value2/value3.value4.xml?__someInternalArgument=someValue&nonexistingkey=foo', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function routeCanBeResolvedIfASpecifiedValueIsEqualToItsDefaultValue() {
		$this->route->setUriPattern('');
		$this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$this->routeValues = array('key1' => 'value1');

		$this->assertTrue($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 */
	public function resolvesAppendsDefaultValuesOfOptionalUriPartsToMatchingUri() {
		$this->route->setUriPattern('foo(/{bar}/{baz})');
		$this->route->setDefaults(array('bar' => 'barDefaultValue', 'baz' => 'bazDefaultValue'));
		$this->routeValues = array('baz' => 'bazValue');

		$this->route->resolves($this->routeValues);
		$expectedResult = 'foo/barDefaultValue/bazvalue';
		$actualResult = $this->route->getMatchingUri();
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 */
	public function resolvesLowerCasesMatchingUriByDefault() {
		$this->route->setUriPattern('CamelCase/{someKey}');
		$this->routeValues = array('someKey' => 'CamelCase');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('camelcase/camelcase', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function resolvesKeepsCaseOfResolvedUriIfToLowerCaseIsFalse() {
		$this->route->setUriPattern('CamelCase/{someKey}');
		$this->route->setLowerCase(FALSE);
		$this->routeValues = array('someKey' => 'CamelCase');

		$this->assertTrue($this->route->resolves($this->routeValues));
		$this->assertEquals('CamelCase/CamelCase', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue() {
		$this->route->setUriPattern('');
		$this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
		$this->routeValues = array('key2' => 'differentValue');

		$this->assertFalse($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 * @todo mock object factory
	 */
	public function matchingRequestPathIsNullAfterUnsuccessfulResolve() {
		$mockObjectManager = $this->getMock('TYPO3\FLOW3\Object\ObjectManagerInterface');
		$this->route = new \TYPO3\FLOW3\Mvc\Routing\Route($this->mockObjectManager, $mockObjectManager);
		$this->route->injectRouter($this->mockRouter);
		$this->route->setUriPattern('{key1}');
		$this->routeValues = array('key1' => 'value1');

		$this->assertTrue($this->route->resolves($this->routeValues));

		$this->routeValues = array('differentKey' => 'value1');
		$this->assertFalse($this->route->resolves($this->routeValues));
		$this->assertNull($this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function registeredRoutePartHandlerIsInvokedWhenCallingResolve() {
		$this->route->setUriPattern('{key1}/{key2}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler',
				)
			)
		);
		$this->routeValues = array('key2' => 'value2');
		$mockRoutePartHandler = new \TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler();
		$this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\FLOW3\Mvc\Routing\Fixtures\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
		$this->route->resolves($this->routeValues);

		$this->assertEquals('_resolve_invoked_/value2', $this->route->getMatchingUri());
	}

	/**
	 * @test
	 */
	public function resolvesReturnsFalseIfNotAllRouteValuesCanBeResolved() {
		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$routeValues = array('foo' => 'bar', 'baz' => array('foo2' => 'bar2'));
		$this->assertFalse($this->route->resolves($routeValues));
	}

	/**
	 * @test
	 */
	public function resolvesAppendsRemainingRouteValuesToMatchingUriIfAppendExceedingArgumentsIsTrue() {
		$this->route->setUriPattern('foo');
		$this->route->setAppendExceedingArguments(TRUE);
		$this->route->_set('isParsed', TRUE);
		$routeValues = array('foo' => 'bar', 'baz' => array('foo2' => 'bar2'));
		$this->route->resolves($routeValues);

		$actualResult = $this->route->getMatchingUri();
		$expectedResult = '?foo=bar&baz%5Bfoo2%5D=bar2';

		$this->assertEquals($expectedResult, $actualResult);
	}


	/**
	 * @test
	 */
	public function resolvesConvertsDomainObjectsToIdentityArrays() {
		$object1 = new \stdClass();
		$object2 = new \stdClass();
		$originalArray = array('foo' => 'bar', 'someObject' => $object1, 'baz' => array('someOtherObject' => $object2));

		$convertedArray = array('foo' => 'bar', 'someObject' => array('__identity' => 'x'), 'baz' => array('someOtherObject' => array('__identity' => 'y')));


		$mockPersistenceManager = $this->getMock('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArray)->will($this->returnValue($convertedArray));
		$this->route->injectPersistenceManager($mockPersistenceManager);

		$this->route->setUriPattern('foo');
		$this->route->setAppendExceedingArguments(TRUE);
		$this->route->_set('isParsed', TRUE);
		$this->route->resolves($originalArray);

		$actualResult = $this->route->getMatchingUri();
		$expectedResult = '?foo=bar&someObject%5B__identity%5D=x&baz%5BsomeOtherObject%5D%5B__identity%5D=y';

		$this->assertEquals($expectedResult, $actualResult);
	}

	/**
	 * This bugfix was generously sponsored with 260 beers at T3BOARD11 by snowflake productions gmbh (snowflake.ch)
	 *
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Routing\Exception\InvalidControllerException
	 */
	public function resolvesReturnsAnExceptionIfTargetControllerDoesNotExist() {
		$this->route->setUriPattern('');
		$this->route->setDefaults(array('@package' => 'Snow'));
		$this->routeValues = array('@controller' => 'flake');

		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')->with('Snow', '', 'flake')->will($this->returnValue(NULL));
		$this->route->injectRouter($mockRouter);

		$this->route->resolves($this->routeValues);
	}

	/**
	 * @test
	 */
	public function resolvesReturnsTrueIfTargetControllerExists() {
		$this->route->setUriPattern('{@package}/{@subpackage}/{@controller}');
		$this->route->setDefaults(array('@package' => 'SomePackage', '@controller' => 'SomeExistingController'));
		$this->routeValues = array('@subpackage' => 'Some\Subpackage');

		$mockRouter = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RouterInterface');
		$mockRouter->expects($this->once())->method('getControllerObjectName')->with('SomePackage', 'Some\Subpackage', 'SomeExistingController')->will($this->returnValue('ControllerObjectName'));
		$this->route->injectRouter($mockRouter);

		$this->assertTrue($this->route->resolves($this->routeValues));
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException
	 */
	public function resolvesThrowsExceptionIfRoutePartValueIsNoString() {
		$mockRoutePart = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(TRUE));
		$mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(TRUE));
		$mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue(array('not a' => 'string')));

		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$this->route->_set('routeParts', array($mockRoutePart));
		$this->route->resolves(array());
	}

	/**
	 * @test
	 * @expectedException \TYPO3\FLOW3\Mvc\Exception\InvalidRoutePartValueException
	 */
	public function resolvesThrowsExceptionIfRoutePartDefaultValueIsNoString() {
		$mockRoutePart = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(TRUE));
		$mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(FALSE));
		$mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue(array('not a' => 'string')));

		$this->route->setUriPattern('foo');
		$this->route->_set('isParsed', TRUE);
		$this->route->_set('routeParts', array($mockRoutePart));
		$this->route->resolves(array());
	}

	/**
	 * @test
	 */
	public function resolvesCallsCompareAndRemoveMatchingDefaultValues() {
		$defaultValues = array('foo' => 'bar');
		$routeValues = array('bar' => 'baz');

		$mockRoutePart = $this->getMock('TYPO3\FLOW3\Mvc\Routing\RoutePartInterface');
		$mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(TRUE));
		$mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(FALSE));
		$mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue('defaultValue'));

		$route = $this->getAccessibleMock('TYPO3\FLOW3\Mvc\Routing\Route', array('compareAndRemoveMatchingDefaultValues'));
		$route->setAppendExceedingArguments(TRUE);
		$route->injectRouter($this->mockRouter);
		$route->injectPersistenceManager($this->mockPersistenceManager);
		$route->setUriPattern('foo');
		$route->setDefaults($defaultValues);
		$route->_set('isParsed', TRUE);
		$route->_set('routeParts', array($mockRoutePart));

		$route->expects($this->once())->method('compareAndRemoveMatchingDefaultValues')->with($defaultValues, $routeValues)->will($this->returnValue(TRUE));

		$this->assertTrue($route->resolves($routeValues));
	}

	/**
	 * @test
	 * @dataProvider compareAndRemoveMatchingDefaultValuesDataProvider()
	 * @param array $defaults
	 * @param array $routeValues
	 * @param array $expectedModifiedRouteValues
	 * @param boolean $expectedResult
	 */
	public function compareAndRemoveMatchingDefaultValuesTests(array $defaults, array $routeValues, $expectedModifiedRouteValues, $expectedResult) {
		$actualResult = $this->route->_callRef('compareAndRemoveMatchingDefaultValues', $defaults, $routeValues);
		$this->assertEquals($expectedResult, $actualResult);
		if ($expectedResult === TRUE) {
			$this->assertEquals($expectedModifiedRouteValues, $routeValues);
		}
	}

	/**
	 * Data provider
	 */
	public function compareAndRemoveMatchingDefaultValuesDataProvider() {
		return array(
			array(array(), array(), array(), TRUE),
			array(array(), array('foo' => 'bar'), array('foo' => 'bar'), TRUE),
			array(array('foo' => 'bar'), array(), array(), TRUE),
			array(array('foo' => 'bar'), array('foo' => 'bar'), array(), TRUE),
			array(array('somekey' => 'somevalue'), array('SomeKey' => 'SomeValue'), array('SomeKey' => 'SomeValue'), TRUE),
			array(array('foo' => 'bar'), array('foo' => 'bar', 'bar' => 'baz'), array('bar' => 'baz'), TRUE),
			array(array('foo' => 'bar', 'bar' => 'baz'), array('foo' => 'bar'), array(), TRUE),
			array(array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue'))), array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue', 'someOtherKey' => 'someOtherValue'))), array('firstLevel' => array('secondLevel' => array('someOtherKey' => 'someOtherValue'))), TRUE),
			array(array('foo' => 'bar'), array('foo' => 'baz'), NULL, FALSE),
			array(array('foo' => 'bar'), array('foo' => array('bar' => 'bar')), NULL, FALSE),
			array(array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue'))), array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeOtherValue'))), NULL, FALSE)
		);
	}

	/**
	 * @test
	 */
	public function parseSetsDefaultValueOfRouteParts() {
		$this->route->setUriPattern('{key1}');
		$this->route->setRoutePartsConfiguration(
			array(
				'key1' => array(
					'handler' => 'SomeRoutePartHandler',
				)
			)
		);
		$this->route->setDefaults(
			array(
				'key1' => 'SomeDefaultValue',
			)
		);
		$mockRoutePartHandler = $this->getMock('TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface');
		$mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
		$this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

	/**
	 * @test
	 */
	public function parseSetsDefaultValueOfRoutePartsRecursively() {
		$this->route->setUriPattern('{foo.bar}');
		$this->route->setRoutePartsConfiguration(
			array(
				'foo.bar' => array(
					'handler' => 'SomeRoutePartHandler',
				)
			)
		);
		$this->route->setDefaults(
			array(
				'foo' => array(
					'bar' => 'SomeDefaultValue'
				)
			)
		);
		$mockRoutePartHandler = $this->getMock('TYPO3\FLOW3\Mvc\Routing\DynamicRoutePartInterface');
		$mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
		$this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

		$this->route->parse();
	}

}
?>
