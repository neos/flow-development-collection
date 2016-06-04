<?php
namespace TYPO3\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler;
use TYPO3\Flow\Mvc\Routing\Route;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Tests\UnitTestCase;

require_once(__DIR__ . '/Fixtures/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 *
 */
class RouteTest extends UnitTestCase
{
    /**
     * @var Route
     */
    protected $route;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockObjectManager;

    /**
     * @var PersistenceManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockPersistenceManager;

    /**
     * @var array
     */
    protected $routeValues;

    /**
     * Sets up this test case
     *
     */
    public function setUp()
    {
        $this->mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->route = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Route', array('dummy'));
        $this->route->_set('objectManager', $this->mockObjectManager);

        $this->mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $this->mockPersistenceManager->expects($this->any())->method('convertObjectsToIdentityArrays')->will($this->returnCallback(function ($array) {
            return $array;
        }));
        $this->inject($this->route, 'persistenceManager', $this->mockPersistenceManager);
    }

    /**
     * @param string $routePath
     * @return boolean
     */
    protected function routeMatchesPath($routePath)
    {
        /** @var Request $mockHttpRequest|\PHPUnit_Framework_MockObject_MockObject */
        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
        $mockHttpRequest->expects($this->any())->method('getRelativePath')->will($this->returnValue($routePath));

        return $this->route->matches($mockHttpRequest);
    }

    /*                                                                        *
     * Basic functionality (getters, setters, exceptions)                     *
     *                                                                        */

    /**
     * @test
     */
    public function setNameCorrectlySetsRouteName()
    {
        $this->route->setName('SomeName');

        $this->assertEquals('SomeName', $this->route->getName());
    }

    /**
     * @test
     */
    public function httpMethodConstraintsCanBeSetAndRetrieved()
    {
        $this->assertFalse($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be FALSE by default');
        $httpMethods = array('POST', 'PUT');
        $this->route->setHttpMethods($httpMethods);
        $this->assertTrue($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be TRUE if httpMethods are set');
        $this->assertEquals($httpMethods, $this->route->getHttpMethods());
        $this->route->setHttpMethods(array());
        $this->assertFalse($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be FALSE if httpMethods is empty');
    }

    /**
     * @test
     */
    public function settingUriPatternResetsRoute()
    {
        $this->route->_set('isParsed', true);
        $this->route->setUriPattern('foo/{key3}/foo');

        $this->assertFalse($this->route->_get('isParsed'));
    }

    /**
     * @test
     */
    public function routePartHandlerIsInstantiated()
    {
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            array(
                'key1' => array(
                    'handler' => 'SomeRoutePartHandler',
                )
            )
        );
        $mockRoutePartHandler = $this->createMock('TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidRoutePartHandlerException
     */
    public function settingInvalidRoutePartHandlerThrowsException()
    {
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            array(
                'key1' => array(
                    'handler' => 'TYPO3\Flow\Mvc\Routing\StaticRoutePart',
                )
            )
        );
        $mockRoutePartHandler = $this->createMock('TYPO3\Flow\Mvc\Routing\StaticRoutePart');
        $this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Flow\Mvc\Routing\StaticRoutePart')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }

    /**
     * @test
     */
    public function ifAnObjectTypeIsSpecifiedTheIdentityRoutePartHandlerIsInstantiated()
    {
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
        $this->assertInstanceOf('TYPO3\Flow\Mvc\Routing\IdentityRoutePart', $identityRoutePart);
        $this->assertSame('SomeObjectType', $identityRoutePart->getObjectType());
    }

    /**
     * @test
     */
    public function parseSetsUriPatternOfIdentityRoutePartIfSpecified()
    {
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
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithTrailingSlashThrowsException()
    {
        $this->route->setUriPattern('some/uri/pattern/');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithLeadingSlashThrowsException()
    {
        $this->route->setUriPattern('/some/uri/pattern');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithSuccessiveDynamicRoutepartsThrowsException()
    {
        $this->route->setUriPattern('{key1}{key2}');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithSuccessiveOptionalSectionsThrowsException()
    {
        $this->route->setUriPattern('(foo/bar)(/bar/foo)');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithUnterminatedOptionalSectionsThrowsException()
    {
        $this->route->setUriPattern('foo/(bar');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithUnopenedOptionalSectionsThrowsException()
    {
        $this->route->setUriPattern('foo)/bar');
        $this->route->parse();
    }

    /*                                                                        *
     * URI matching                                                           *
     *                                                                        */

    /**
     * @test
     */
    public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet()
    {
        $this->assertFalse($this->routeMatchesPath(''), 'Route should not match if no URI Pattern is set.');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRequestPathIsDifferentFromStaticUriPattern()
    {
        $this->route->setUriPattern('foo/bar');

        $this->assertFalse($this->routeMatchesPath('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfOneSegmentOfRequestPathIsDifferentFromItsRespectiveStaticUriPatternSegment()
    {
        $this->route->setUriPattern('foo/{bar}');

        $this->assertFalse($this->routeMatchesPath('bar/someValue'), '"foo/{bar}"-Route should not match "bar/someValue"-request.');
    }

    /**
     * @test
     */
    public function routeMatchesEmptyRequestPathIfUriPatternIsEmpty()
    {
        $this->route->setUriPattern('');

        $this->assertTrue($this->routeMatchesPath(''), 'Route should match if URI Pattern and RequestPath are empty.');
    }

    /**
     * @test
     */
    public function routeMatchesIfRequestPathIsEqualToStaticUriPattern()
    {
        $this->route->setUriPattern('foo/bar');

        $this->assertTrue($this->routeMatchesPath('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRequestPathIsEqualToStaticUriPatternWithoutSlashes()
    {
        $this->route->setUriPattern('required1/required2');

        $this->assertFalse($this->routeMatchesPath('required1required2'));
    }

    /**
     * @test
     */
    public function routeMatchesIfStaticSegmentsMatchAndASegmentExistsForAllDynamicUriPartSegments()
    {
        $this->route->setUriPattern('foo/{bar}');

        $this->assertTrue($this->routeMatchesPath('foo/someValue'), '"foo/{bar}"-Route should match "foo/someValue"-request.');
    }

    /**
     * @test
     */
    public function getMatchResultsReturnsCorrectResultsAfterSuccessfulMatch()
    {
        $this->route->setUriPattern('foo/{bar}');
        $this->routeMatchesPath('foo/someValue');

        $this->assertEquals(array('bar' => 'someValue'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function staticAndDynamicRoutesCanBeMixedInAnyOrder()
    {
        $this->route->setUriPattern('{key1}/foo/{key2}/bar');

        $this->assertFalse($this->routeMatchesPath('value1/foo/value2/foo'), '"{key1}/foo/{key2}/bar"-Route should not match "value1/foo/value2/foo"-request.');
        $this->assertTrue($this->routeMatchesPath('value1/foo/value2/bar'), '"{key1}/foo/{key2}/bar"-Route should match "value1/foo/value2/bar"-request.');
        $this->assertEquals(array('key1' => 'value1', 'key2' => 'value2'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function uriPatternSegmentCanContainTwoDynamicRouteParts()
    {
        $this->route->setUriPattern('user/{firstName}-{lastName}');

        $this->assertFalse($this->routeMatchesPath('user/johndoe'), '"user/{firstName}-{lastName}"-Route should not match "user/johndoe"-request.');
        $this->assertTrue($this->routeMatchesPath('user/john-doe'), '"user/{firstName}-{lastName}"-Route should match "user/john-doe"-request.');
        $this->assertEquals(array('firstName' => 'john', 'lastName' => 'doe'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function uriPatternSegmentsCanContainMultipleDynamicRouteParts()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');

        $this->assertFalse($this->routeMatchesPath('value1-value2/value3.value4value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should not match "value1-value2/value3.value4value5"-request.');
        $this->assertTrue($this->routeMatchesPath('value1-value2/value3.value4.value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should match "value1-value2/value3.value4.value5"-request.');
        $this->assertEquals(array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRoutePartDoesNotMatchAndDefaultValueIsSet()
    {
        $this->route->setUriPattern('{foo}');
        $this->route->setDefaults(array('foo' => 'bar'));

        $this->assertFalse($this->routeMatchesPath(''), 'Route should not match if required Route Part does not match.');
    }

    /**
     * @test
     */
    public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName()
    {
        $this->route->setUriPattern('SomePackage');

        $defaults = array(
            '@package' => 'SomePackage',
            '@controller' => 'SomeController',
            '@action' => 'someAction'
        );

        $this->route->setDefaults($defaults);
        $this->routeMatchesPath('SomePackage');
        $matchResults = $this->route->getMatchResults();

        $this->assertEquals($defaults['@controller'], $matchResults{'@controller'});
        $this->assertEquals($defaults['@action'], $matchResults['@action']);
    }

    /**
     * @test
     */
    public function registeredRoutePartHandlerIsInvokedWhenCallingMatch()
    {
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            array(
                'key1' => array(
                    'handler' => 'TYPO3\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler',
                )
            )
        );
        $mockRoutePartHandler = new MockRoutePartHandler();
        $this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
        $this->routeMatchesPath('foo/bar');

        $this->assertEquals(array('key1' => '_match_invoked_', 'key2' => 'bar'), $this->route->getMatchResults());
    }

    /**
     * @test
     * @dataProvider matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider()
     * @param boolean $shouldThrowException
     * @param mixed $routePartValue
     */
    public function matchesThrowsExceptionIfRoutePartValueContainsObjects($shouldThrowException, $routePartValue)
    {
        if ($shouldThrowException === true) {
            $this->setExpectedException('TYPO3\Flow\Mvc\Exception\InvalidRoutePartValueException');
        }
        $mockRoutePart = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart->expects($this->once())->method('match')->with('foo')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('getName')->will($this->returnValue('TestRoutePart'));
        $mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue($routePartValue));

        $this->route->setUriPattern('foo');
        $this->route->_set('routeParts', array($mockRoutePart));
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('foo');
    }

    /**
     * Data provider
     */
    public function matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider()
    {
        $object = new \stdClass();
        return array(
            array(true, array('foo' => $object)),
            array(true, array('foo' => 'bar', 'baz' => $object)),
            array(true, array('foo' => array('bar' => array('baz' => 'quux', 'here' => $object)))),
            array(false, array('no object')),
            array(false, array('foo' => 'no object')),
            array(false, array(true))
        );
    }

    /**
     * @test
     */
    public function matchesRecursivelyMergesMatchResults()
    {
        $mockRoutePart1 = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart1->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart1->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart1'));
        $mockRoutePart1->expects($this->once())->method('getValue')->will($this->returnValue('foo'));

        $mockRoutePart2 = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart2->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart2->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('someOtherRoutePart'));
        $mockRoutePart2->expects($this->once())->method('getValue')->will($this->returnValue('bar'));

        $mockRoutePart3 = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart3->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart3->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart2'));
        $mockRoutePart3->expects($this->once())->method('getValue')->will($this->returnValue('baz'));

        $this->route->setUriPattern('');
        $this->route->_set('routeParts', array($mockRoutePart1, $mockRoutePart2, $mockRoutePart3));
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('');

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
    public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)');

        $this->assertTrue($this->routeMatchesPath(''));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('required(optional)');

        $this->assertTrue($this->routeMatchesPath('requiredoptional'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneRequiredAndOneOptionalStaticRoutePart()
    {
        $this->route->setUriPattern('required(optional)');

        $this->assertTrue($this->routeMatchesPath('required'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required');

        $this->assertTrue($this->routeMatchesPath('required'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required(optional2)');

        $this->assertTrue($this->routeMatchesPath('required'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required(optional2)');

        $this->assertTrue($this->routeMatchesPath('optionalrequiredoptional2'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithoutDefaultValue()
    {
        $this->route->setUriPattern('({optional})');

        $this->assertFalse($this->routeMatchesPath(''));
    }

    /**
     * @test
     */
    public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithDefaultValue()
    {
        $this->route->setUriPattern('({optional})');
        $this->route->setDefaults(array('optional' => 'defaultValue'));

        $this->assertTrue($this->routeMatchesPath(''));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathContainingNoneOfTheOptionalRoutePartsIfNoDefaultsAreSet()
    {
        $this->route->setUriPattern('page(.{@format})');

        $this->assertFalse($this->routeMatchesPath('page'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathContainingOnlySomeOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(array('@format' => 'html'));

        $this->assertFalse($this->routeMatchesPath('page.'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathContainingNoneOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(array('@format' => 'html'));

        $this->assertTrue($this->routeMatchesPath('page'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathContainingAllOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(array('@format' => 'html'));

        $this->assertTrue($this->routeMatchesPath('page.html'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        $this->assertTrue($this->routeMatchesPath('required'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathWithRequiredAndOnlyOneOptionalPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        $this->assertFalse($this->routeMatchesPath('required/optional1'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathWithAllPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        $this->assertTrue($this->routeMatchesPath('required/optional1/optional2'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1(/optional1/optional2)/required2');

        $this->assertTrue($this->routeMatchesPath('required1/required2'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1/(optional1/optional2/)required2');

        $this->assertFalse($this->routeMatchesPath('required1/optional1/required2'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1/(optional1/optional2/)required2');

        $this->assertTrue($this->routeMatchesPath('required1/optional1/optional2/required2'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        $this->assertTrue($this->routeMatchesPath('required1/required2'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        $this->assertFalse($this->routeMatchesPath('optional1/required1/required2'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathWithAllPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        $this->assertTrue($this->routeMatchesPath('optional1/optional2/required1/required2'));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRoutePartDoesNotMatchAndIsOptionalButHasNoDefault()
    {
        $this->route->setUriPattern('({foo})');

        $this->assertFalse($this->routeMatchesPath(''), 'Route should not match if optional Route Part does not match and has no default value.');
    }

    /**
     * @test
     */
    public function routeMatchesIfRoutePartDoesNotMatchButIsOptionalAndHasDefault()
    {
        $this->route->setUriPattern('({foo})');
        $this->route->setDefaults(array('foo' => 'bar'));

        $this->assertTrue($this->routeMatchesPath(''), 'Route should match if optional Route Part has a default value.');
    }

    /**
     * @test
     */
    public function defaultValuesAreSetForUriPatternSegmentsWithMultipleOptionalRouteParts()
    {
        $this->route->setUriPattern('{key1}-({key2})/({key3}).({key4}.{@format})');
        $defaults = array(
            'key1' => 'defaultValue1',
            'key2' => 'defaultValue2',
            'key3' => 'defaultValue3',
            'key4' => 'defaultValue4'
        );
        $this->route->setDefaults($defaults);
        $this->routeMatchesPath('foo-/.bar.xml');

        $this->assertEquals(array('key1' => 'foo', 'key2' => 'defaultValue2', 'key3' => 'defaultValue3', 'key4' => 'bar', '@format' => 'xml'), $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRequestMethodIsNotAccepted()
    {
        $this->route->setUriPattern('');
        $this->route->setHttpMethods(array('POST', 'PUT'));

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

        $mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
        $mockUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($mockUri));

        $mockBaseUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
        $mockBaseUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue($mockBaseUri));

        $mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('GET'));
        $this->assertFalse($this->route->matches($mockHttpRequest), 'Route must not match GET requests if only POST or PUT requests are accepted.');
    }

    /**
     * @test
     */
    public function routeMatchesIfRequestMethodIsAccepted()
    {
        $this->route->setUriPattern('');
        $this->route->setHttpMethods(array('POST', 'PUT'));

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();

        $mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
        $mockUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($mockUri));

        $mockBaseUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
        $mockBaseUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getBaseUri')->will($this->returnValue($mockBaseUri));

        $mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->will($this->returnValue('PUT'));

        $this->assertTrue($this->route->matches($mockHttpRequest), 'Route should match PUT requests if POST and PUT requests are accepted.');
    }

    /*                                                                        *
     * URI resolving                                                          *
     *                                                                        */

    /**
     * @test
     */

    public function matchingRouteIsProperlyResolved()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4');

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function byDefaultRouteDoesNotResolveIfUriPatternContainsLessValuesThanAreSpecified()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo');

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeAlwaysAppendsExceedingInternalArguments()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue');

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml?__someInternalArgument=someValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeAlwaysAppendsExceedingInternalArgumentsRecursively()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '--subRequest' => array('__someInternalArgument' => 'someValue'));

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml?--subRequest%5B__someInternalArgument%5D=someValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeDoesNotResolveIfRouteValuesContainAnIdentityForAnArgumentThatIsNotPartOfTheRoute()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'someArgument' => array('__identity' => 'someUuid'));

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeAppendsAllAdditionalQueryParametersIfUriPatternContainsLessValuesThanAreSpecifiedIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(array('@format' => 'xml'));
        $this->routeValues = array('key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue', 'nonexistingkey' => 'foo');
        $this->route->setAppendExceedingArguments(true);

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml?__someInternalArgument=someValue&nonexistingkey=foo', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeCanBeResolvedIfASpecifiedValueIsEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key2}');
        $this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
        $this->routeValues = array('key1' => 'value1');

        $this->assertTrue($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeCanBeResolvedIfAComplexValueIsEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key2.key2b}');
        $this->route->setDefaults(array('key1' => array('key1a' => 'key1aValue', 'key1b' => 'key1bValue'), 'key2' => array('key2a' => 'key2aValue', 'key2b' => 'key2bValue')));
        $this->routeValues = array('key1' => array('key1a' => 'key1aValue', 'key1b' => 'key1bValue'), 'key2' => array('key2a' => 'key2aValue'));

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('key2bValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function resolvesAppendsDefaultValuesOfOptionalUriPartsToResolvedUriPath()
    {
        $this->route->setUriPattern('foo(/{bar}/{baz})');
        $this->route->setDefaults(array('bar' => 'barDefaultValue', 'baz' => 'bazDefaultValue'));
        $this->routeValues = array('baz' => 'bazValue');

        $this->route->resolves($this->routeValues);
        $expectedResult = 'foo/barDefaultValue/bazvalue';
        $actualResult = $this->route->getResolvedUriPath();
        $this->assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function resolvesLowerCasesResolvedUriPathByDefault()
    {
        $this->route->setUriPattern('CamelCase/{someKey}');
        $this->routeValues = array('someKey' => 'CamelCase');

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('camelcase/camelcase', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function resolvesKeepsCaseOfResolvedUriIfToLowerCaseIsFalse()
    {
        $this->route->setUriPattern('CamelCase/{someKey}');
        $this->route->setLowerCase(false);
        $this->routeValues = array('someKey' => 'CamelCase');

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('CamelCase/CamelCase', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key1}');
        $this->route->setDefaults(array('key1' => 'value1', 'key2' => 'value2'));
        $this->routeValues = array('key2' => 'differentValue');

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function resolvedUriPathIsNullAfterUnsuccessfulResolve()
    {
        $mockObjectManager = $this->createMock('TYPO3\Flow\Object\ObjectManagerInterface');
        $this->route = new Route($this->mockObjectManager, $mockObjectManager);
        $this->route->setUriPattern('{key1}');
        $this->routeValues = array('key1' => 'value1');

        $this->assertTrue($this->route->resolves($this->routeValues));

        $this->routeValues = array('differentKey' => 'value1');
        $this->assertFalse($this->route->resolves($this->routeValues));
        $this->assertNull($this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function registeredRoutePartHandlerIsInvokedWhenCallingResolve()
    {
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            array(
                'key1' => array(
                    'handler' => 'TYPO3\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler',
                )
            )
        );
        $this->routeValues = array('key2' => 'value2');
        $mockRoutePartHandler = new MockRoutePartHandler();
        $this->mockObjectManager->expects($this->once())->method('get')->with('TYPO3\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));
        $this->route->resolves($this->routeValues);

        $this->assertEquals('_resolve_invoked_/value2', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function resolvesReturnsFalseIfNotAllRouteValuesCanBeResolved()
    {
        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $routeValues = array('foo' => 'bar', 'baz' => array('foo2' => 'bar2'));
        $this->assertFalse($this->route->resolves($routeValues));
    }

    /**
     * @test
     */
    public function resolvesAppendsRemainingRouteValuesToResolvedUriPathIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('foo');
        $this->route->setAppendExceedingArguments(true);
        $this->route->_set('isParsed', true);
        $routeValues = array('foo' => 'bar', 'baz' => array('foo2' => 'bar2'));
        $this->route->resolves($routeValues);

        $actualResult = $this->route->getResolvedUriPath();
        $expectedResult = '?foo=bar&baz%5Bfoo2%5D=bar2';

        $this->assertEquals($expectedResult, $actualResult);
    }


    /**
     * @test
     */
    public function resolvesConvertsDomainObjectsToIdentityArrays()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $originalArray = array('foo' => 'bar', 'someObject' => $object1, 'baz' => array('someOtherObject' => $object2));

        $convertedArray = array('foo' => 'bar', 'someObject' => array('__identity' => 'x'), 'baz' => array('someOtherObject' => array('__identity' => 'y')));


        $mockPersistenceManager = $this->createMock('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $mockPersistenceManager->expects($this->once())->method('convertObjectsToIdentityArrays')->with($originalArray)->will($this->returnValue($convertedArray));
        $this->inject($this->route, 'persistenceManager', $mockPersistenceManager);

        $this->route->setUriPattern('foo');
        $this->route->setAppendExceedingArguments(true);
        $this->route->_set('isParsed', true);
        $this->route->resolves($originalArray);

        $actualResult = $this->route->getResolvedUriPath();
        $expectedResult = '?foo=bar&someObject%5B__identity%5D=x&baz%5BsomeOtherObject%5D%5B__identity%5D=y';

        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function resolvesReturnsTrueIfTargetControllerExists()
    {
        $this->route->setUriPattern('{@package}/{@subpackage}/{@controller}');
        $this->route->setDefaults(array('@package' => 'SomePackage', '@controller' => 'SomeExistingController'));
        $this->routeValues = array('@subpackage' => 'Some\Subpackage');

        $this->assertTrue($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidRoutePartValueException
     */
    public function resolvesThrowsExceptionIfRoutePartValueIsNoString()
    {
        $mockRoutePart = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(true));
        $mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue(array('not a' => 'string')));

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', array($mockRoutePart));
        $this->route->resolves(array());
    }

    /**
     * @test
     * @expectedException \TYPO3\Flow\Mvc\Exception\InvalidRoutePartValueException
     */
    public function resolvesThrowsExceptionIfRoutePartDefaultValueIsNoString()
    {
        $mockRoutePart = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(false));
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue(array('not a' => 'string')));

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', array($mockRoutePart));
        $this->route->resolves(array());
    }

    /**
     * @test
     */
    public function resolvesCallsCompareAndRemoveMatchingDefaultValues()
    {
        $defaultValues = array('foo' => 'bar');
        $routeValues = array('bar' => 'baz');

        $mockRoutePart = $this->createMock('TYPO3\Flow\Mvc\Routing\RoutePartInterface');
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(false));
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue('defaultValue'));

        /** @var Route|\PHPUnit_Framework_MockObject_MockObject $route */
        $route = $this->getAccessibleMock('TYPO3\Flow\Mvc\Routing\Route', array('compareAndRemoveMatchingDefaultValues'));
        $route->setAppendExceedingArguments(true);
        $this->inject($route, 'persistenceManager', $this->mockPersistenceManager);
        $route->setUriPattern('foo');
        $route->setDefaults($defaultValues);
        $route->_set('isParsed', true);
        $route->_set('routeParts', array($mockRoutePart));

        $route->expects($this->once())->method('compareAndRemoveMatchingDefaultValues')->with($defaultValues, $routeValues)->will($this->returnValue(true));

        $this->assertTrue($route->resolves($routeValues));
    }

    /**
     * Data provider
     */
    public function compareAndRemoveMatchingDefaultValuesDataProvider()
    {
        return array(
            array(
                'defaults' => array(),
                'routeValues' => array(),
                'expectedModifiedRouteValues' => array(),
                'expectedResult' => true
            ),
            array(
                'defaults' => array(),
                'routeValues' => array('foo' => 'bar'),
                'expectedModifiedRouteValues' => array('foo' => 'bar'),
                'expectedResult' => true
            ),
            array(
                'defaults' => array('foo' => 'bar'),
                'routeValues' => array(),
                'expectedModifiedRouteValues' => array(),
                'expectedResult' => false
            ),
            array(
                'defaults' => array('foo' => 'bar'),
                'routeValues' => array('foo' => 'bar'),
                'expectedModifiedRouteValues' => array(),
                'expectedResult' => true
            ),
            array(
                'defaults' => array('someKey' => 'somevalue'),
                'routeValues' => array('someKey' => 'SomeValue', 'SomeKey' => 'SomeOtherValue'),
                'expectedModifiedRouteValues' => array('SomeKey' => 'SomeOtherValue'),
                'expectedResult' => true
            ),
            array(
                'defaults' => array('foo' => 'bar'),
                'routeValues' => array('foo' => 'bar', 'bar' => 'baz'),
                'expectedModifiedRouteValues' => array('bar' => 'baz'),
                'expectedResult' => true
            ),
            array(
                'defaults' => array('foo' => 'bar', 'bar' => 'baz'),
                'routeValues' => array('foo' => 'bar'),
                'expectedModifiedRouteValues' => array(),
                'expectedResult' => false
            ),
            array(
                'defaults' => array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue'))),
                'routeValues' => array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue', 'someOtherKey' => 'someOtherValue'))),
                'expectedModifiedRouteValues' => array('firstLevel' => array('secondLevel' => array('someOtherKey' => 'someOtherValue'))),
                'expectedResult' => true),
            array(
                'defaults' => array('foo' => 'bar'),
                'routeValues' => array('foo' => 'baz'),
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false),
            array(
                'defaults' => array('foo' => 'bar'),
                'routeValues' => array('foo' => array('bar' => 'bar')),
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false),
            array(
                'defaults' => array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeValue'))),
                'routeValues' => array('firstLevel' => array('secondLevel' => array('someKey' => 'SomeOtherValue'))),
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false)
        );
    }

    /**
     * @test
     * @dataProvider compareAndRemoveMatchingDefaultValuesDataProvider()
     * @param array $defaults
     * @param array $routeValues
     * @param array $expectedModifiedRouteValues
     * @param boolean $expectedResult
     */
    public function compareAndRemoveMatchingDefaultValuesTests(array $defaults, array $routeValues, $expectedModifiedRouteValues, $expectedResult)
    {
        $actualResult = $this->route->_callRef('compareAndRemoveMatchingDefaultValues', $defaults, $routeValues);
        $this->assertEquals($expectedResult, $actualResult);
        if ($expectedResult === true) {
            $this->assertEquals($expectedModifiedRouteValues, $routeValues);
        }
    }

    /**
     * @test
     */
    public function parseSetsDefaultValueOfRouteParts()
    {
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
        $mockRoutePartHandler = $this->createMock('TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface');
        $mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }

    /**
     * @test
     */
    public function parseSetsDefaultValueOfRoutePartsRecursively()
    {
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
        $mockRoutePartHandler = $this->createMock('TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface');
        $mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }
}
