<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Http;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler;
use Neos\Flow\Mvc\Routing;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;

require_once(__DIR__ . '/Fixtures/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 */
class RouteTest extends UnitTestCase
{
    /**
     * @var Routing\Route
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
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->route = $this->getAccessibleMock(Routing\Route::class, ['dummy']);
        $this->route->_set('objectManager', $this->mockObjectManager);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
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
        /** @var Http\Request $mockHttpRequest|\PHPUnit_Framework_MockObject_MockObject */
        $mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();
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
        $httpMethods = ['POST', 'PUT'];
        $this->route->setHttpMethods($httpMethods);
        $this->assertTrue($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be TRUE if httpMethods are set');
        $this->assertEquals($httpMethods, $this->route->getHttpMethods());
        $this->route->setHttpMethods([]);
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
            [
                'key1' => [
                    'handler' => 'SomeRoutePartHandler',
                ]
            ]
        );
        $mockRoutePartHandler = $this->createMock(Routing\DynamicRoutePartInterface::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidRoutePartHandlerException
     */
    public function settingInvalidRoutePartHandlerThrowsException()
    {
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            [
                'key1' => [
                    'handler' => Routing\StaticRoutePart::class,
                ]
            ]
        );
        $mockRoutePartHandler = $this->createMock(Routing\StaticRoutePart::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with(Routing\StaticRoutePart::class)->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }

    /**
     * @test
     */
    public function ifAnObjectTypeIsSpecifiedTheIdentityRoutePartHandlerIsInstantiated()
    {
        $this->route->setUriPattern('{key1}');
        $this->route->setRoutePartsConfiguration(
            [
                'key1' => [
                    'objectType' => 'SomeObjectType',
                ]
            ]
        );

        $this->route->parse();
        $identityRoutePart = current($this->route->_get('routeParts'));
        $this->assertInstanceOf(Routing\IdentityRoutePart::class, $identityRoutePart);
        $this->assertSame('SomeObjectType', $identityRoutePart->getObjectType());
    }

    /**
     * @test
     */
    public function parseSetsUriPatternOfIdentityRoutePartIfSpecified()
    {
        $this->route->setUriPattern('{key1}');
        $this->route->setRoutePartsConfiguration(
            [
                'key1' => [
                    'objectType' => 'SomeObjectType',
                    'uriPattern' => 'SomeUriPattern'
                ]
            ]
        );

        $this->route->parse();
        $identityRoutePart = current($this->route->_get('routeParts'));
        $this->assertSame('SomeUriPattern', $identityRoutePart->getUriPattern());
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithTrailingSlashThrowsException()
    {
        $this->route->setUriPattern('some/uri/pattern/');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithLeadingSlashThrowsException()
    {
        $this->route->setUriPattern('/some/uri/pattern');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithSuccessiveDynamicRoutepartsThrowsException()
    {
        $this->route->setUriPattern('{key1}{key2}');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithSuccessiveOptionalSectionsThrowsException()
    {
        $this->route->setUriPattern('(foo/bar)(/bar/foo)');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
     */
    public function uriPatternWithUnterminatedOptionalSectionsThrowsException()
    {
        $this->route->setUriPattern('foo/(bar');
        $this->route->parse();
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidUriPatternException
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

        $this->assertEquals(['bar' => 'someValue'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function staticAndDynamicRoutesCanBeMixedInAnyOrder()
    {
        $this->route->setUriPattern('{key1}/foo/{key2}/bar');

        $this->assertFalse($this->routeMatchesPath('value1/foo/value2/foo'), '"{key1}/foo/{key2}/bar"-Route should not match "value1/foo/value2/foo"-request.');
        $this->assertTrue($this->routeMatchesPath('value1/foo/value2/bar'), '"{key1}/foo/{key2}/bar"-Route should match "value1/foo/value2/bar"-request.');
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function uriPatternSegmentCanContainTwoDynamicRouteParts()
    {
        $this->route->setUriPattern('user/{firstName}-{lastName}');

        $this->assertFalse($this->routeMatchesPath('user/johndoe'), '"user/{firstName}-{lastName}"-Route should not match "user/johndoe"-request.');
        $this->assertTrue($this->routeMatchesPath('user/john-doe'), '"user/{firstName}-{lastName}"-Route should match "user/john-doe"-request.');
        $this->assertEquals(['firstName' => 'john', 'lastName' => 'doe'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function uriPatternSegmentsCanContainMultipleDynamicRouteParts()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');

        $this->assertFalse($this->routeMatchesPath('value1-value2/value3.value4value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should not match "value1-value2/value3.value4value5"-request.');
        $this->assertTrue($this->routeMatchesPath('value1-value2/value3.value4.value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should match "value1-value2/value3.value4.value5"-request.');
        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRoutePartDoesNotMatchAndDefaultValueIsSet()
    {
        $this->route->setUriPattern('{foo}');
        $this->route->setDefaults(['foo' => 'bar']);

        $this->assertFalse($this->routeMatchesPath(''), 'Route should not match if required Route Part does not match.');
    }

    /**
     * @test
     */
    public function setDefaultsAllowsToSetTheDefaultPackageControllerAndActionName()
    {
        $this->route->setUriPattern('SomePackage');

        $defaults = [
            '@package' => 'SomePackage',
            '@controller' => 'SomeController',
            '@action' => 'someAction'
        ];

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
            [
                'key1' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $mockRoutePartHandler = new MockRoutePartHandler();
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->will($this->returnValue($mockRoutePartHandler));
        $this->routeMatchesPath('foo/bar');

        $this->assertEquals(['key1' => '_match_invoked_', 'key2' => 'bar'], $this->route->getMatchResults());
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
            $this->setExpectedException(InvalidRoutePartValueException::class);
        }
        $mockRoutePart = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart->expects($this->once())->method('match')->with('foo')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('getName')->will($this->returnValue('TestRoutePart'));
        $mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue($routePartValue));

        $this->route->setUriPattern('foo');
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('foo');
    }

    /**
     * Data provider
     */
    public function matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider()
    {
        $object = new \stdClass();
        return [
            [true, ['foo' => $object]],
            [true, ['foo' => 'bar', 'baz' => $object]],
            [true, ['foo' => ['bar' => ['baz' => 'quux', 'here' => $object]]]],
            [false, ['no object']],
            [false, ['foo' => 'no object']],
            [false, [true]]
        ];
    }

    /**
     * @test
     */
    public function matchesRecursivelyMergesMatchResults()
    {
        $mockRoutePart1 = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart1->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart1->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart1'));
        $mockRoutePart1->expects($this->once())->method('getValue')->will($this->returnValue('foo'));

        $mockRoutePart2 = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart2->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart2->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('someOtherRoutePart'));
        $mockRoutePart2->expects($this->once())->method('getValue')->will($this->returnValue('bar'));

        $mockRoutePart3 = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart3->expects($this->once())->method('match')->will($this->returnValue(true));
        $mockRoutePart3->expects($this->atLeastOnce())->method('getName')->will($this->returnValue('firstLevel.secondLevel.routePart2'));
        $mockRoutePart3->expects($this->once())->method('getValue')->will($this->returnValue('baz'));

        $this->route->setUriPattern('');
        $this->route->_set('routeParts', [$mockRoutePart1, $mockRoutePart2, $mockRoutePart3]);
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('');

        $expectedResult = ['firstLevel' => ['secondLevel' => ['routePart1' => 'foo', 'routePart2' => 'baz']], 'someOtherRoutePart' => 'bar'];
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
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidRouteSetupException
     */
    public function routeThrowsExceptionIfUriPatternContainsOneOptionalDynamicRoutePartWithoutDefaultValue()
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
        $this->route->setDefaults(['optional' => 'defaultValue']);

        $this->assertTrue($this->routeMatchesPath(''));
    }

    /**
     * @test
     */
    public function routeDoesNotMatchRequestPathContainingOnlySomeOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

        $this->assertFalse($this->routeMatchesPath('page.'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathContainingNoneOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

        $this->assertTrue($this->routeMatchesPath('page'));
    }

    /**
     * @test
     */
    public function routeMatchesRequestPathContainingAllOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

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
    public function routeMatchesIfRoutePartDoesNotMatchButIsOptionalAndHasDefault()
    {
        $this->route->setUriPattern('({foo})');
        $this->route->setDefaults(['foo' => 'bar']);

        $this->assertTrue($this->routeMatchesPath(''), 'Route should match if optional Route Part has a default value.');
    }

    /**
     * @test
     */
    public function defaultValuesAreSetForUriPatternSegmentsWithMultipleOptionalRouteParts()
    {
        $this->route->setUriPattern('{key1}-({key2})/({key3}).({key4}.{@format})');
        $defaults = [
            'key1' => 'defaultValue1',
            'key2' => 'defaultValue2',
            'key3' => 'defaultValue3',
            'key4' => 'defaultValue4',
            '@format' => 'xml'
        ];
        $this->route->setDefaults($defaults);
        $this->routeMatchesPath('foo-/.bar.xml');

        $this->assertEquals(['key1' => 'foo', 'key2' => 'defaultValue2', 'key3' => 'defaultValue3', 'key4' => 'bar', '@format' => 'xml'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    /**
     * @test
     */
    public function routeDoesNotMatchIfRequestMethodIsNotAccepted()
    {
        $this->route->setUriPattern('');
        $this->route->setHttpMethods(['POST', 'PUT']);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockUri = $this->getMockBuilder(Http\Uri::class)->disableOriginalConstructor()->getMock();
        $mockUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($mockUri));

        $mockBaseUri = $this->getMockBuilder(Http\Uri::class)->disableOriginalConstructor()->getMock();
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
        $this->route->setHttpMethods(['POST', 'PUT']);

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $mockHttpRequest */
        $mockHttpRequest = $this->getMockBuilder(Http\Request::class)->disableOriginalConstructor()->getMock();

        $mockUri = $this->getMockBuilder(Http\Uri::class)->disableOriginalConstructor()->getMock();
        $mockUri->expects($this->any())->method('getPath')->will($this->returnValue('/'));
        $mockHttpRequest->expects($this->any())->method('getUri')->will($this->returnValue($mockUri));

        $mockBaseUri = $this->getMockBuilder(Http\Uri::class)->disableOriginalConstructor()->getMock();
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
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'];

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function byDefaultRouteDoesNotResolveIfUriPatternContainsLessValuesThanAreSpecified()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo'];

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeAlwaysAppendsExceedingInternalArguments()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue'];

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml?__someInternalArgument=someValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeAlwaysAppendsExceedingInternalArgumentsRecursively()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '--subRequest' => ['__someInternalArgument' => 'someValue']];

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('value1-value2/value3.value4.xml?--subRequest%5B__someInternalArgument%5D=someValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeDoesNotResolveIfRouteValuesContainAnIdentityForAnArgumentThatIsNotPartOfTheRoute()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'someArgument' => ['__identity' => 'someUuid']];

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeAppendsAllAdditionalQueryParametersIfUriPatternContainsLessValuesThanAreSpecifiedIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue', 'nonexistingkey' => 'foo'];
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
        $this->route->setDefaults(['key1' => 'value1', 'key2' => 'value2']);
        $this->routeValues = ['key1' => 'value1'];

        $this->assertTrue($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function routeCanBeResolvedIfAComplexValueIsEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key2.key2b}');
        $this->route->setDefaults(['key1' => ['key1a' => 'key1aValue', 'key1b' => 'key1bValue'], 'key2' => ['key2a' => 'key2aValue', 'key2b' => 'key2bValue']]);
        $this->routeValues = ['key1' => ['key1a' => 'key1aValue', 'key1b' => 'key1bValue'], 'key2' => ['key2a' => 'key2aValue']];

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('key2bValue', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function resolvesAppendsDefaultValuesOfOptionalUriPartsToResolvedUriPath()
    {
        $this->route->setUriPattern('foo(/{bar}/{baz})');
        $this->route->setDefaults(['bar' => 'barDefaultValue', 'baz' => 'bazDefaultValue']);
        $this->routeValues = ['baz' => 'bazValue'];

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
        $this->routeValues = ['someKey' => 'CamelCase'];

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
        $this->routeValues = ['someKey' => 'CamelCase'];

        $this->assertTrue($this->route->resolves($this->routeValues));
        $this->assertEquals('CamelCase/CamelCase', $this->route->getResolvedUriPath());
    }

    /**
     * @test
     */
    public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key1}');
        $this->route->setDefaults(['key1' => 'value1', 'key2' => 'value2']);
        $this->routeValues = ['key2' => 'differentValue'];

        $this->assertFalse($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     */
    public function resolvedUriPathIsNullAfterUnsuccessfulResolve()
    {
        $mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->route = new Routing\Route($this->mockObjectManager, $mockObjectManager);
        $this->route->setUriPattern('{key1}');
        $this->routeValues = ['key1' => 'value1'];

        $this->assertTrue($this->route->resolves($this->routeValues));

        $this->routeValues = ['differentKey' => 'value1'];
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
            [
                'key1' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $this->routeValues = ['key2' => 'value2'];
        $mockRoutePartHandler = new MockRoutePartHandler();
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->will($this->returnValue($mockRoutePartHandler));
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
        $routeValues = ['foo' => 'bar', 'baz' => ['foo2' => 'bar2']];
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
        $routeValues = ['foo' => 'bar', 'baz' => ['foo2' => 'bar2']];
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
        $originalArray = ['foo' => 'bar', 'someObject' => $object1, 'baz' => ['someOtherObject' => $object2]];

        $convertedArray = ['foo' => 'bar', 'someObject' => ['__identity' => 'x'], 'baz' => ['someOtherObject' => ['__identity' => 'y']]];


        $mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
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
        $this->route->setDefaults(['@package' => 'SomePackage', '@controller' => 'SomeExistingController']);
        $this->routeValues = ['@subpackage' => 'Some\Subpackage'];

        $this->assertTrue($this->route->resolves($this->routeValues));
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidRoutePartValueException
     */
    public function resolvesThrowsExceptionIfRoutePartValueIsNoString()
    {
        $mockRoutePart = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(true));
        $mockRoutePart->expects($this->once())->method('getValue')->will($this->returnValue(['not a' => 'string']));

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->route->resolves([]);
    }

    /**
     * @test
     * @expectedException \Neos\Flow\Mvc\Exception\InvalidRoutePartValueException
     */
    public function resolvesThrowsExceptionIfRoutePartDefaultValueIsNoString()
    {
        $mockRoutePart = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(false));
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue(['not a' => 'string']));

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->route->resolves([]);
    }

    /**
     * @test
     */
    public function resolvesCallsCompareAndRemoveMatchingDefaultValues()
    {
        $defaultValues = ['foo' => 'bar'];
        $routeValues = ['bar' => 'baz'];

        $mockRoutePart = $this->createMock(Routing\RoutePartInterface::class);
        $mockRoutePart->expects($this->any())->method('resolve')->will($this->returnValue(true));
        $mockRoutePart->expects($this->any())->method('hasValue')->will($this->returnValue(false));
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->will($this->returnValue('defaultValue'));

        /** @var Route|\PHPUnit_Framework_MockObject_MockObject $route */
        $route = $this->getAccessibleMock(Routing\Route::class, ['compareAndRemoveMatchingDefaultValues']);
        $route->setAppendExceedingArguments(true);
        $this->inject($route, 'persistenceManager', $this->mockPersistenceManager);
        $route->setUriPattern('foo');
        $route->setDefaults($defaultValues);
        $route->_set('isParsed', true);
        $route->_set('routeParts', [$mockRoutePart]);

        $route->expects($this->once())->method('compareAndRemoveMatchingDefaultValues')->with($defaultValues, $routeValues)->will($this->returnValue(true));

        $this->assertTrue($route->resolves($routeValues));
    }

    /**
     * Data provider
     */
    public function compareAndRemoveMatchingDefaultValuesDataProvider()
    {
        return [
            [
                'defaults' => [],
                'routeValues' => [],
                'expectedModifiedRouteValues' => [],
                'expectedResult' => true
            ],
            [
                'defaults' => [],
                'routeValues' => ['foo' => 'bar'],
                'expectedModifiedRouteValues' => ['foo' => 'bar'],
                'expectedResult' => true
            ],
            [
                'defaults' => ['foo' => 'bar'],
                'routeValues' => [],
                'expectedModifiedRouteValues' => [],
                'expectedResult' => false
            ],
            [
                'defaults' => ['foo' => 'bar'],
                'routeValues' => ['foo' => 'bar'],
                'expectedModifiedRouteValues' => [],
                'expectedResult' => true
            ],
            [
                'defaults' => ['someKey' => 'somevalue'],
                'routeValues' => ['someKey' => 'SomeValue', 'SomeKey' => 'SomeOtherValue'],
                'expectedModifiedRouteValues' => ['SomeKey' => 'SomeOtherValue'],
                'expectedResult' => true
            ],
            [
                'defaults' => ['foo' => 'bar'],
                'routeValues' => ['foo' => 'bar', 'bar' => 'baz'],
                'expectedModifiedRouteValues' => ['bar' => 'baz'],
                'expectedResult' => true
            ],
            [
                'defaults' => ['foo' => 'bar', 'bar' => 'baz'],
                'routeValues' => ['foo' => 'bar'],
                'expectedModifiedRouteValues' => [],
                'expectedResult' => false
            ],
            [
                'defaults' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue']]],
                'routeValues' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue', 'someOtherKey' => 'someOtherValue']]],
                'expectedModifiedRouteValues' => ['firstLevel' => ['secondLevel' => ['someOtherKey' => 'someOtherValue']]],
                'expectedResult' => true],
            [
                'defaults' => ['foo' => 'bar'],
                'routeValues' => ['foo' => 'baz'],
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false],
            [
                'defaults' => ['foo' => 'bar'],
                'routeValues' => ['foo' => ['bar' => 'bar']],
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false],
            [
                'defaults' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue']]],
                'routeValues' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeOtherValue']]],
                'expectedModifiedRouteValues' => null,
                'expectedResult' => false]
        ];
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
            [
                'key1' => [
                    'handler' => 'SomeRoutePartHandler',
                ]
            ]
        );
        $this->route->setDefaults(
            [
                'key1' => 'SomeDefaultValue',
            ]
        );
        $mockRoutePartHandler = $this->createMock(Routing\DynamicRoutePartInterface::class);
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
            [
                'foo.bar' => [
                    'handler' => 'SomeRoutePartHandler',
                ]
            ]
        );
        $this->route->setDefaults(
            [
                'foo' => [
                    'bar' => 'SomeDefaultValue'
                ]
            ]
        );
        $mockRoutePartHandler = $this->createMock(Routing\DynamicRoutePartInterface::class);
        $mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->will($this->returnValue($mockRoutePartHandler));

        $this->route->parse();
    }
}
