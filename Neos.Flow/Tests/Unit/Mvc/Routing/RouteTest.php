<?php

declare(strict_types=1);

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
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\Exception\InvalidRoutePartHandlerException;
use Neos\Flow\Mvc\Exception\InvalidRoutePartValueException;
use Neos\Flow\Mvc\Exception\InvalidRouteSetupException;
use Neos\Flow\Mvc\Exception\InvalidUriPatternException;
use Neos\Flow\Mvc\Routing;
use Neos\Flow\Mvc\Routing\Dto\MatchResult;
use Neos\Flow\Mvc\Routing\Dto\ResolveContext;
use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\Dto\RouteContext;
use Neos\Flow\Mvc\Routing\Dto\RouteParameters;
use Neos\Flow\Mvc\Routing\Dto\UriConstraints;
use Neos\Flow\Mvc\Routing\DynamicRoutePartInterface;
use Neos\Flow\Mvc\Routing\Fixtures\MockRoutePartHandler;
use Neos\Flow\Mvc\Routing\IdentityRoutePart;
use Neos\Flow\Mvc\Routing\Route;
use Neos\Flow\Mvc\Routing\RoutePartInterface;
use Neos\Flow\Mvc\Routing\StaticRoutePart;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

require_once(__DIR__ . '/Fixtures/MockRoutePartHandler.php');

/**
 * Testcase for the MVC Web Routing Route Class
 */
final class RouteTest extends UnitTestCase
{
    /**
     * @var Routing\Route
     */
    protected $route;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    protected $mockObjectManager;

    /**
     * @var Routing\RouteValuesNormalizerInterface|MockObject
     */
    protected $routeValuesNormalizer;

    /**
     * @var array
     */
    protected $routeValues;

    /**
     * Sets up this test case
     *
     */
    protected function setUp(): void
    {
        $this->mockObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->route = $this->getAccessibleMock(Route::class, []);
        $this->route->_set('objectManager', $this->mockObjectManager);

        $this->routeValuesNormalizer = $this->createMock(Routing\RouteValuesNormalizerInterface::class);
        $this->routeValuesNormalizer->method('normalizeObjects')->willReturnCallBack(function ($array) {
            return $array;
        });
        $this->inject($this->route, 'routeValuesNormalizer', $this->routeValuesNormalizer);
    }

    /**
     * @param string $routePath
     * @return boolean
     */
    protected function routeMatchesPath($routePath)
    {
        $mockUri = new Uri('http://localhost/' . $routePath);
        /** @var ServerRequestInterface|MockObject $mockHttpRequest */
        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);
        $mockHttpRequest->method('getUri')->willReturn($mockUri);

        $routeContext = new RouteContext($mockHttpRequest, RouteParameters::createEmpty());
        return $this->route->matches($routeContext);
    }

    /**
     * @param array $routeValues
     * @return bool
     * @throws InvalidRoutePartValueException
     */
    protected function resolveRouteValues(array $routeValues)
    {
        $baseUri = new Uri('http://localhost/');
        $resolveContext = new ResolveContext($baseUri, $routeValues, false, '', RouteParameters::createEmpty());
        return $this->route->resolves($resolveContext);
    }

    /*                                                                        *
     * Basic functionality (getters, setters, exceptions)                     *
     *                                                                        */
    #[Test]
    public function setNameCorrectlySetsRouteName()
    {
        $this->route->setName('SomeName');

        self::assertSame('SomeName', $this->route->getName());
    }

    #[Test]
    public function httpMethodConstraintsCanBeSetAndRetrieved()
    {
        self::assertFalse($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be false by default');
        $httpMethods = ['POST', 'PUT'];
        $this->route->setHttpMethods($httpMethods);
        self::assertTrue($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be true if httpMethods are set');
        self::assertSame($httpMethods, $this->route->getHttpMethods());
        $this->route->setHttpMethods([]);
        self::assertFalse($this->route->hasHttpMethodConstraints(), 'hasHttpMethodConstraints should be false if httpMethods is empty');
    }

    #[Test]
    public function settingUriPatternResetsRoute()
    {
        $this->route->_set('isParsed', true);
        $this->route->setUriPattern('foo/{key3}/foo');

        self::assertFalse($this->route->_get('isParsed'));
    }

    #[Test]
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
        $mockRoutePartHandler = $this->createStub(DynamicRoutePartInterface::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->willReturn($mockRoutePartHandler);

        $this->route->parse();
    }

    #[Test]
    public function settingInvalidRoutePartHandlerThrowsException()
    {
        $this->expectException(InvalidRoutePartHandlerException::class);
        $this->route->setUriPattern('{key1}/{key2}');
        $this->route->setRoutePartsConfiguration(
            [
                'key1' => [
                    'handler' => StaticRoutePart::class,
                ]
            ]
        );
        $mockRoutePartHandler = $this->createStub(StaticRoutePart::class);
        $this->mockObjectManager->expects($this->once())->method('get')->with(StaticRoutePart::class)->willReturn($mockRoutePartHandler);

        $this->route->parse();
    }

    #[Test]
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
        self::assertInstanceOf(IdentityRoutePart::class, $identityRoutePart);
        self::assertSame('SomeObjectType', $identityRoutePart->getObjectType());
    }

    #[Test]
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
        self::assertSame('SomeUriPattern', $identityRoutePart->getUriPattern());
    }

    #[Test]
    public function uriPatternWithTrailingSlashThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('some/uri/pattern/');
        $this->route->parse();
    }

    #[Test]
    public function uriPatternWithLeadingSlashThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('/some/uri/pattern');
        $this->route->parse();
    }

    #[Test]
    public function uriPatternWithSuccessiveDynamicRoutepartsThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('{key1}{key2}');
        $this->route->parse();
    }

    #[Test]
    public function uriPatternWithSuccessiveOptionalSectionsThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('(foo/bar)(/bar/foo)');
        $this->route->parse();
    }

    #[Test]
    public function uriPatternWithUnterminatedOptionalSectionsThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('foo/(bar');
        $this->route->parse();
    }

    #[Test]
    public function uriPatternWithUnopenedOptionalSectionsThrowsException()
    {
        $this->expectException(InvalidUriPatternException::class);
        $this->route->setUriPattern('foo)/bar');
        $this->route->parse();
    }

    /*                                                                        *
     * URI matching                                                           *
     *                                                                        */
    #[Test]
    public function routeDoesNotMatchEmptyRequestPathIfUriPatternIsNotSet()
    {
        self::assertFalse($this->routeMatchesPath(''), 'Route should not match if no URI Pattern is set.');
    }

    #[Test]
    public function routeDoesNotMatchIfRequestPathIsDifferentFromStaticUriPattern()
    {
        $this->route->setUriPattern('foo/bar');

        self::assertFalse($this->routeMatchesPath('bar/foo'), '"foo/bar"-Route should not match "bar/foo"-request.');
    }

    #[Test]
    public function routeDoesNotMatchIfOneSegmentOfRequestPathIsDifferentFromItsRespectiveStaticUriPatternSegment()
    {
        $this->route->setUriPattern('foo/{bar}');

        self::assertFalse($this->routeMatchesPath('bar/someValue'), '"foo/{bar}"-Route should not match "bar/someValue"-request.');
    }

    #[Test]
    public function routeMatchesEmptyRequestPathIfUriPatternIsEmpty()
    {
        $this->route->setUriPattern('');

        self::assertTrue($this->routeMatchesPath(''), 'Route should match if URI Pattern and RequestPath are empty.');
    }

    #[Test]
    public function routeMatchesIfRequestPathIsEqualToStaticUriPattern()
    {
        $this->route->setUriPattern('foo/bar');

        self::assertTrue($this->routeMatchesPath('foo/bar'), '"foo/bar"-Route should match "foo/bar"-request.');
    }

    #[Test]
    public function routeDoesNotMatchIfRequestPathIsEqualToStaticUriPatternWithoutSlashes()
    {
        $this->route->setUriPattern('required1/required2');

        self::assertFalse($this->routeMatchesPath('required1required2'));
    }

    #[Test]
    public function routeMatchesIfStaticSegmentsMatchAndASegmentExistsForAllDynamicUriPartSegments()
    {
        $this->route->setUriPattern('foo/{bar}');

        self::assertTrue($this->routeMatchesPath('foo/someValue'), '"foo/{bar}"-Route should match "foo/someValue"-request.');
    }

    #[Test]
    public function getMatchResultsReturnsCorrectResultsAfterSuccessfulMatch()
    {
        $this->route->setUriPattern('foo/{bar}');
        $this->routeMatchesPath('foo/someValue');

        self::assertSame(['bar' => 'someValue'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    #[Test]
    public function staticAndDynamicRoutesCanBeMixedInAnyOrder()
    {
        $this->route->setUriPattern('{key1}/foo/{key2}/bar');

        self::assertFalse($this->routeMatchesPath('value1/foo/value2/foo'), '"{key1}/foo/{key2}/bar"-Route should not match "value1/foo/value2/foo"-request.');
        self::assertTrue($this->routeMatchesPath('value1/foo/value2/bar'), '"{key1}/foo/{key2}/bar"-Route should match "value1/foo/value2/bar"-request.');
        self::assertSame(['key1' => 'value1', 'key2' => 'value2'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    #[Test]
    public function uriPatternSegmentCanContainTwoDynamicRouteParts()
    {
        $this->route->setUriPattern('user/{firstName}-{lastName}');

        self::assertFalse($this->routeMatchesPath('user/johndoe'), '"user/{firstName}-{lastName}"-Route should not match "user/johndoe"-request.');
        self::assertTrue($this->routeMatchesPath('user/john-doe'), '"user/{firstName}-{lastName}"-Route should match "user/john-doe"-request.');
        self::assertSame(['firstName' => 'john', 'lastName' => 'doe'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    #[Test]
    public function uriPatternSegmentsCanContainMultipleDynamicRouteParts()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');

        self::assertFalse($this->routeMatchesPath('value1-value2/value3.value4value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should not match "value1-value2/value3.value4value5"-request.');
        self::assertTrue($this->routeMatchesPath('value1-value2/value3.value4.value5'), '"{key1}-{key2}/{key3}.{key4}.{@format}"-Route should match "value1-value2/value3.value4.value5"-request.');
        self::assertSame(['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '@format' => 'value5'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    #[Test]
    public function routeDoesNotMatchIfRoutePartDoesNotMatchAndDefaultValueIsSet()
    {
        $this->route->setUriPattern('{foo}');
        $this->route->setDefaults(['foo' => 'bar']);

        self::assertFalse($this->routeMatchesPath(''), 'Route should not match if required Route Part does not match.');
    }

    #[Test]
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

        self::assertSame($defaults['@controller'], $matchResults['@controller']);
        self::assertSame($defaults['@action'], $matchResults['@action']);
    }

    #[Test]
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
        $mockRoutePartHandler = new MockRoutePartHandler(static function () {
            return new MatchResult('_match_invoked_');
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);
        $this->routeMatchesPath('foo/bar');

        self::assertSame(['key1' => '_match_invoked_', 'key2' => 'bar'], $this->route->getMatchResults());
    }

    /**
     * @param boolean $shouldThrowException
     * @param mixed $routePartValue
     */
    #[DataProvider('matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider')]
    #[Test]
    public function matchesThrowsExceptionIfRoutePartValueContainsObjects($shouldThrowException, $routePartValue)
    {
        if ($shouldThrowException === true) {
            $this->expectException(InvalidRoutePartValueException::class);
        }
        $mockRoutePart = $this->createMock(RoutePartInterface::class);
        $mockRoutePart->expects($this->once())->method('match')->with('foo')->willReturn(true);
        $mockRoutePart->method('getName')->willReturn('TestRoutePart');
        $mockRoutePart->expects($this->once())->method('getValue')->willReturn($routePartValue);

        $this->route->setUriPattern('foo');
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('foo');
    }

    /**
     * Data provider
     */
    public static function matchesThrowsExceptionIfRoutePartValueContainsObjectsDataProvider(): \Iterator
    {
        $object = new \stdClass();
        yield [true, ['foo' => $object]];
        yield [true, ['foo' => 'bar', 'baz' => $object]];
        yield [true, ['foo' => ['bar' => ['baz' => 'quux', 'here' => $object]]]];
        yield [false, ['no object']];
        yield [false, ['foo' => 'no object']];
        yield [false, [true]];
    }

    #[Test]
    public function matchesRecursivelyMergesMatchResults()
    {
        $mockRoutePart1 = $this->createMock(RoutePartInterface::class);
        $mockRoutePart1->expects($this->once())->method('match')->willReturn(true);
        $mockRoutePart1->expects($this->atLeastOnce())->method('getName')->willReturn('firstLevel.secondLevel.routePart1');
        $mockRoutePart1->expects($this->once())->method('getValue')->willReturn('foo');

        $mockRoutePart2 = $this->createMock(RoutePartInterface::class);
        $mockRoutePart2->expects($this->once())->method('match')->willReturn(true);
        $mockRoutePart2->expects($this->atLeastOnce())->method('getName')->willReturn('someOtherRoutePart');
        $mockRoutePart2->expects($this->once())->method('getValue')->willReturn('bar');

        $mockRoutePart3 = $this->createMock(RoutePartInterface::class);
        $mockRoutePart3->expects($this->once())->method('match')->willReturn(true);
        $mockRoutePart3->expects($this->atLeastOnce())->method('getName')->willReturn('firstLevel.secondLevel.routePart2');
        $mockRoutePart3->expects($this->once())->method('getValue')->willReturn('baz');

        $this->route->setUriPattern('');
        $this->route->_set('routeParts', [$mockRoutePart1, $mockRoutePart2, $mockRoutePart3]);
        $this->route->_set('isParsed', true);
        $this->routeMatchesPath('');

        $expectedResult = ['firstLevel' => ['secondLevel' => ['routePart1' => 'foo', 'routePart2' => 'baz']], 'someOtherRoutePart' => 'bar'];
        $actualResult = $this->route->getMatchResults();
        self::assertSame($expectedResult, $actualResult);
    }

    /*                                                                        *
     * URI matching (optional Route Parts)                                    *
     *                                                                        */
    #[Test]
    public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)');

        self::assertTrue($this->routeMatchesPath(''));
    }

    #[Test]
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('required(optional)');

        self::assertTrue($this->routeMatchesPath('requiredoptional'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneRequiredAndOneOptionalStaticRoutePart()
    {
        $this->route->setUriPattern('required(optional)');

        self::assertTrue($this->routeMatchesPath('required'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsOneOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required');

        self::assertTrue($this->routeMatchesPath('required'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required(optional2)');

        self::assertTrue($this->routeMatchesPath('required'));
    }

    #[Test]
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoOptionalAndOneRequiredStaticRoutePart()
    {
        $this->route->setUriPattern('(optional)required(optional2)');

        self::assertTrue($this->routeMatchesPath('optionalrequiredoptional2'));
    }

    #[Test]
    public function routeThrowsExceptionIfUriPatternContainsOneOptionalDynamicRoutePartWithoutDefaultValue()
    {
        $this->expectException(InvalidRouteSetupException::class);
        $this->route->setUriPattern('({optional})');

        self::assertFalse($this->routeMatchesPath(''));
    }

    #[Test]
    public function routeMatchesEmptyRequestPathIfUriPatternContainsOneOptionalDynamicRoutePartWithDefaultValue()
    {
        $this->route->setUriPattern('({optional})');
        $this->route->setDefaults(['optional' => 'defaultValue']);

        self::assertTrue($this->routeMatchesPath(''));
    }

    #[Test]
    public function routeDoesNotMatchRequestPathContainingOnlySomeOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

        self::assertFalse($this->routeMatchesPath('page.'));
    }

    #[Test]
    public function routeMatchesRequestPathContainingNoneOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

        self::assertTrue($this->routeMatchesPath('page'));
    }

    #[Test]
    public function routeMatchesRequestPathContainingAllOfTheOptionalRouteParts()
    {
        $this->route->setUriPattern('page(.{@format})');
        $this->route->setDefaults(['@format' => 'html']);

        self::assertTrue($this->routeMatchesPath('page.html'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        self::assertTrue($this->routeMatchesPath('required'));
    }

    #[Test]
    public function routeDoesNotMatchRequestPathWithRequiredAndOnlyOneOptionalPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        self::assertFalse($this->routeMatchesPath('required/optional1'));
    }

    #[Test]
    public function routeDoesNotMatchRequestPathWithAllPartsIfUriPatternEndsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required(/optional1/optional2)');

        self::assertTrue($this->routeMatchesPath('required/optional1/optional2'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1(/optional1/optional2)/required2');

        self::assertTrue($this->routeMatchesPath('required1/required2'));
    }

    #[Test]
    public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1/(optional1/optional2/)required2');

        self::assertFalse($this->routeMatchesPath('required1/optional1/required2'));
    }

    #[Test]
    public function routeMatchesRequestPathWithAllPartsIfUriPatternContainsTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('required1/(optional1/optional2/)required2');

        self::assertTrue($this->routeMatchesPath('required1/optional1/optional2/required2'));
    }

    #[Test]
    public function routeMatchesRequestPathWithOnlyRequiredPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        self::assertTrue($this->routeMatchesPath('required1/required2'));
    }

    #[Test]
    public function routeDoesNotMatchRequestPathWithOnlyOneOptionalPartIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        self::assertFalse($this->routeMatchesPath('optional1/required1/required2'));
    }

    #[Test]
    public function routeMatchesRequestPathWithAllPartsIfUriPatternStartsWithTwoSuccessiveOptionalRouteParts()
    {
        $this->route->setUriPattern('(optional1/optional2/)required1/required2');

        self::assertTrue($this->routeMatchesPath('optional1/optional2/required1/required2'));
    }

    #[Test]
    public function routeMatchesIfRoutePartDoesNotMatchButIsOptionalAndHasDefault()
    {
        $this->route->setUriPattern('({foo})');
        $this->route->setDefaults(['foo' => 'bar']);

        self::assertTrue($this->routeMatchesPath(''), 'Route should match if optional Route Part has a default value.');
    }

    #[Test]
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

        self::assertSame(['key1' => 'foo', 'key2' => 'defaultValue2', 'key3' => 'defaultValue3', 'key4' => 'bar', '@format' => 'xml'], $this->route->getMatchResults(), 'Route match results should be set correctly on successful match');
    }

    #[Test]
    public function routeDoesNotMatchIfRequestMethodIsNotAccepted()
    {
        $this->route->setUriPattern('');
        $this->route->setHttpMethods(['POST', 'PUT']);

        /** @var ServerRequestInterface|MockObject $mockHttpRequest */
        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getPath')->willReturn('/');
        $mockUri->method('withQuery')->willReturn($mockUri);
        $mockUri->method('withFragment')->willReturn($mockUri);
        $mockUri->method('withPath')->willReturn($mockUri);
        $mockHttpRequest->method('getUri')->willReturn($mockUri);

        $mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn('GET');
        self::assertFalse($this->route->matches(new RouteContext($mockHttpRequest, RouteParameters::createEmpty())), 'Route must not match GET requests if only POST or PUT requests are accepted.');
    }

    #[Test]
    public function routeMatchesIfRequestMethodIsAccepted()
    {
        $this->route->setUriPattern('');
        $this->route->setHttpMethods(['POST', 'PUT']);

        /** @var ServerRequestInterface|MockObject $mockHttpRequest */
        $mockHttpRequest = $this->createMock(ServerRequestInterface::class);

        $mockUri = $this->createMock(Uri::class);
        $mockUri->method('getPath')->willReturn('/');
        $mockUri->method('withQuery')->willReturn($mockUri);
        $mockUri->method('withFragment')->willReturn($mockUri);
        $mockUri->method('withPath')->willReturn($mockUri);
        $mockHttpRequest->method('getUri')->willReturn($mockUri);

        $mockHttpRequest->expects($this->atLeastOnce())->method('getMethod')->willReturn('PUT');

        self::assertTrue($this->route->matches(new RouteContext($mockHttpRequest, RouteParameters::createEmpty())), 'Route should match PUT requests if POST and PUT requests are accepted.');
    }

    /*                                                                        *
     * URI resolving                                                          *
     *                                                                        */
    #[Test]
    public function matchingRouteIsProperlyResolved()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4'];
        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/value1-value2/value3.value4.xml', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function byDefaultRouteDoesNotResolveIfUriPatternContainsLessValuesThanAreSpecified()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'nonexistingkey' => 'foo'];

        self::assertFalse($this->resolveRouteValues($this->routeValues));
    }

    #[Test]
    public function routeAlwaysAppendsExceedingInternalArguments()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/value1-value2/value3.value4.xml?__someInternalArgument=someValue', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function routeAlwaysAppendsExceedingInternalArgumentsRecursively()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '--subRequest' => ['__someInternalArgument' => 'someValue']];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/value1-value2/value3.value4.xml?--subRequest%5B__someInternalArgument%5D=someValue', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function routeDoesNotResolveIfRouteValuesContainAnIdentityForAnArgumentThatIsNotPartOfTheRoute()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', 'someArgument' => ['__identity' => 'someUuid']];

        self::assertFalse($this->resolveRouteValues($this->routeValues));
    }

    #[Test]
    public function routeAppendsAllAdditionalQueryParametersIfUriPatternContainsLessValuesThanAreSpecifiedIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('{key1}-{key2}/{key3}.{key4}.{@format}');
        $this->route->setDefaults(['@format' => 'xml']);
        $this->routeValues = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3', 'key4' => 'value4', '__someInternalArgument' => 'someValue', 'nonexistingkey' => 'foo'];
        $this->route->setAppendExceedingArguments(true);

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/value1-value2/value3.value4.xml?__someInternalArgument=someValue&nonexistingkey=foo', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function routeCanBeResolvedIfASpecifiedValueIsEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key2}');
        $this->route->setDefaults(['key1' => 'value1', 'key2' => 'value2']);
        $this->routeValues = ['key1' => 'value1'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
    }

    #[Test]
    public function routeCanBeResolvedIfAComplexValueIsEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key2.key2b}');
        $this->route->setDefaults(['key1' => ['key1a' => 'key1aValue', 'key1b' => 'key1bValue'], 'key2' => ['key2a' => 'key2aValue', 'key2b' => 'key2bValue']]);
        $this->routeValues = ['key1' => ['key1a' => 'key1aValue', 'key1b' => 'key1bValue'], 'key2' => ['key2a' => 'key2aValue']];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/key2bValue', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesAppendsDefaultValuesOfOptionalUriPartsToResolvedUriPathConstraint()
    {
        $this->route->setUriPattern('foo(/{bar}/{baz})');
        $this->route->setDefaults(['bar' => 'barDefaultValue', 'baz' => 'bazDefaultValue']);
        $this->routeValues = ['baz' => 'bazValue'];

        $this->resolveRouteValues($this->routeValues);
        $expectedResult = 'foo/barDefaultValue/bazvalue';
        $actualResult = $this->route->getResolvedUriConstraints()->getPathConstraint();
        self::assertSame($expectedResult, $actualResult);
    }

    #[Test]
    public function resolvesLowerCasesResolvedUriPathConstraintByDefault()
    {
        $this->route->setUriPattern('CamelCase/{someKey}');
        $this->routeValues = ['someKey' => 'CamelCase'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/camelcase/camelcase', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesKeepsCaseOfResolvedUriIfToLowerCaseIsFalse()
    {
        $this->route->setUriPattern('CamelCase/{someKey}');
        $this->route->setLowerCase(false);
        $this->routeValues = ['someKey' => 'CamelCase'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
        self::assertSame('/CamelCase/CamelCase', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function routeCantBeResolvedIfASpecifiedValueIsNotEqualToItsDefaultValue()
    {
        $this->route->setUriPattern('{key1}');
        $this->route->setDefaults(['key1' => 'value1', 'key2' => 'value2']);
        $this->routeValues = ['key2' => 'differentValue'];

        self::assertFalse($this->resolveRouteValues($this->routeValues));
    }

    #[Test]
    public function resolvedUriConstraintsIsEmptyAfterUnsuccessfulResolve()
    {
        $this->route->setUriPattern('{key1}');
        $this->routeValues = ['key1' => 'value1'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));

        $this->routeValues = ['differentKey' => 'value1'];
        self::assertFalse($this->resolveRouteValues($this->routeValues));
        self::assertNull($this->route->getResolvedUriConstraints()->getPathConstraint());
    }

    #[Test]
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
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function () {
            return new ResolveResult('_resolve_invoked_');
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);
        $this->resolveRouteValues($this->routeValues);

        self::assertSame('/_resolve_invoked_/value2', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesPassesEmptyRouteParametersToRegisteredRoutePartHandlerByDefault()
    {
        $this->route->setUriPattern('{foo}');
        $this->route->setRoutePartsConfiguration(
            [
                'foo' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );

        $routePartHandlerWasCalled = false;
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function ($_, RouteParameters $parameters) use (&$routePartHandlerWasCalled) {
            self::assertTrue($parameters->isEmpty());
            $routePartHandlerWasCalled = true;
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);

        $this->routeValues = ['key2' => 'value2'];
        $this->resolveRouteValues($this->routeValues);
        self::assertTrue($routePartHandlerWasCalled, 'RoutePart handler was never called');
    }

    #[Test]
    public function resolvesPassesRouteParametersFromResolveContextToRegisteredRoutePartHandler()
    {
        $this->route->setUriPattern('{foo}');
        $this->route->setRoutePartsConfiguration(
            [
                'foo' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $this->routeValues = ['key2' => 'value2'];

        $routeParameters = RouteParameters::createEmpty()->withParameter('someParameter', 'someValue');

        $routePartHandlerWasCalled = false;
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function ($_, RouteParameters $parameters) use (&$routePartHandlerWasCalled, $routeParameters) {
            self::assertSame($parameters, $routeParameters);
            $routePartHandlerWasCalled = true;
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);

        $baseUri = new Uri('http://localhost/');
        $resolveContext = new ResolveContext($baseUri, $this->routeValues, false, '', $routeParameters);
        $this->route->resolves($resolveContext);
        self::assertTrue($routePartHandlerWasCalled, 'RoutePart handler was never called');
    }

    #[Test]
    public function resolvesReturnsFalseIfNotAllRouteValuesCanBeResolved()
    {
        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $routeValues = ['foo' => 'bar', 'baz' => ['foo2' => 'bar2']];
        self::assertFalse($this->resolveRouteValues($routeValues));
    }

    #[Test]
    public function resolvesRespectsQueryStringConstraint()
    {
        $this->route->setUriPattern('{part1}');
        $this->route->setRoutePartsConfiguration(
            [
                'part1' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $this->routeValues = ['part1' => 'some-value'];
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function () {
            return new ResolveResult('', UriConstraints::create()->withQueryString('some=query[string]'));
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);
        $this->resolveRouteValues($this->routeValues);

        self::assertSame('/?some=query%5Bstring%5D', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesAppendsRemainingRouteValuesToResolvedUriPathConstraintIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('foo');
        $this->route->setAppendExceedingArguments(true);
        $routeValues = ['foo' => 'bar', 'baz' => ['foo2' => 'bar2']];
        $this->resolveRouteValues($routeValues);

        self::assertSame('/foo?foo=bar&baz%5Bfoo2%5D=bar2', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesMergesRemainingRouteValuesWithQueryStringIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('{part1}');
        $this->route->setAppendExceedingArguments(true);
        $this->route->setRoutePartsConfiguration(
            [
                'part1' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $this->routeValues = ['part1' => 'some-value', 'some' => ['nested' => ['foo' => 'ovérridden']]];
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function () {
            return new ResolveResult('', UriConstraints::create()->withQueryString('some[nested][foo]=bar&some[nested][baz]=fôos'));
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);
        $this->resolveRouteValues($this->routeValues);

        self::assertSame('/?some%5Bnested%5D%5Bfoo%5D=ov%C3%A9rridden&some%5Bnested%5D%5Bbaz%5D=f%C3%B4os', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesMergesRemainingRouteValuesWithQueryStringAndResolvedUriIfAppendExceedingArgumentsIsTrue()
    {
        $this->route->setUriPattern('{part1}');
        $this->route->setAppendExceedingArguments(true);
        $this->route->setRoutePartsConfiguration(
            [
                'part1' => [
                    'handler' => MockRoutePartHandler::class,
                ]
            ]
        );
        $this->routeValues = ['part1' => 'some-value', 'exceeding' => 'argument'];
        $mockRoutePartHandler = new MockRoutePartHandler(null, static function () {
            return new ResolveResult('', UriConstraints::fromUri(new Uri('https://neos.io:8080/some/path?some[query]=string#some-fragment')));
        });
        $this->mockObjectManager->expects($this->once())->method('get')->with(MockRoutePartHandler::class)->willReturn($mockRoutePartHandler);
        $this->resolveRouteValues($this->routeValues);

        self::assertSame('https://neos.io:8080/some/path?some%5Bquery%5D=string&exceeding=argument#some-fragment', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesConvertsDomainObjectsToIdentityArrays()
    {
        $object1 = new \stdClass();
        $object2 = new \stdClass();
        $originalArray = ['foo' => 'bar', 'someObject' => $object1, 'baz' => ['someOtherObject' => $object2]];

        $convertedArray = ['foo' => 'bar', 'someObject' => ['__identity' => 'x'], 'baz' => ['someOtherObject' => ['__identity' => 'y']]];

        $routeValuesNormalizer = $this->createMock(Routing\RouteValuesNormalizerInterface::class);
        $routeValuesNormalizer->expects($this->once())->method('normalizeObjects')->with($originalArray)->willReturn($convertedArray);
        $this->inject($this->route, 'routeValuesNormalizer', $routeValuesNormalizer);

        $this->route->setUriPattern('foo');
        $this->route->setAppendExceedingArguments(true);
        $this->route->_set('isParsed', true);
        $this->resolveRouteValues($originalArray);

        self::assertSame('/?foo=bar&someObject%5B__identity%5D=x&baz%5BsomeOtherObject%5D%5B__identity%5D=y', (string)$this->route->getResolvedUriConstraints()->toUri());
    }

    #[Test]
    public function resolvesReturnsTrueIfTargetControllerExists()
    {
        $this->route->setUriPattern('{@package}/{@subpackage}/{@controller}');
        $this->route->setDefaults(['@package' => 'SomePackage', '@controller' => 'SomeExistingController']);
        $this->routeValues = ['@subpackage' => 'Some\Subpackage'];

        self::assertTrue($this->resolveRouteValues($this->routeValues));
    }

    #[Test]
    public function resolvesThrowsExceptionIfRoutePartValueIsNoString()
    {
        $this->expectException(InvalidRoutePartValueException::class);
        $mockRoutePart = $this->createMock(RoutePartInterface::class);
        $mockRoutePart->method('resolve')->willReturn(true);
        $mockRoutePart->method('hasValue')->willReturn(true);
        $mockRoutePart->expects($this->once())->method('getValue')->willReturn(['not a' => 'string']);

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->resolveRouteValues([]);
    }

    #[Test]
    public function resolvesThrowsExceptionIfRoutePartDefaultValueIsNoString()
    {
        $this->expectException(InvalidRoutePartValueException::class);
        $mockRoutePart = $this->createMock(RoutePartInterface::class);
        $mockRoutePart->method('resolve')->willReturn(true);
        $mockRoutePart->method('hasValue')->willReturn(false);
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->willReturn(['not a' => 'string']);

        $this->route->setUriPattern('foo');
        $this->route->_set('isParsed', true);
        $this->route->_set('routeParts', [$mockRoutePart]);
        $this->resolveRouteValues([]);
    }

    #[Test]
    public function resolvesCallsCompareAndRemoveMatchingDefaultValues()
    {
        $defaultValues = ['foo' => 'bar'];
        $routeValues = ['bar' => 'baz'];

        $mockRoutePart = $this->createMock(RoutePartInterface::class);
        $mockRoutePart->method('resolve')->willReturn(true);
        $mockRoutePart->method('hasValue')->willReturn(false);
        $mockRoutePart->expects($this->once())->method('getDefaultValue')->willReturn('defaultValue');

        /** @var Routing\Route|MockObject $route */
        $route = $this->getAccessibleMock(Route::class, ['compareAndRemoveMatchingDefaultValues']);
        $route->setAppendExceedingArguments(true);
        $this->inject($route, 'routeValuesNormalizer', $this->routeValuesNormalizer);
        $route->setUriPattern('foo');
        $route->setDefaults($defaultValues);
        $route->_set('isParsed', true);
        $route->_set('routeParts', [$mockRoutePart]);

        $route->expects($this->once())->method('compareAndRemoveMatchingDefaultValues')->with($defaultValues, $routeValues)->willReturn(true);

        $resolveContext = new ResolveContext(new Uri('http://localhost'), $routeValues, false, '', RouteParameters::createEmpty());
        self::assertTrue($route->resolves($resolveContext));
    }

    /**
     * Data provider
     */
    public static function compareAndRemoveMatchingDefaultValuesDataProvider(): \Iterator
    {
        yield [
            'defaults' => [],
            'routeValues' => [],
            'expectedModifiedRouteValues' => [],
            'expectedResult' => true
        ];
        yield [
            'defaults' => [],
            'routeValues' => ['foo' => 'bar'],
            'expectedModifiedRouteValues' => ['foo' => 'bar'],
            'expectedResult' => true
        ];
        yield [
            'defaults' => ['foo' => 'bar'],
            'routeValues' => [],
            'expectedModifiedRouteValues' => [],
            'expectedResult' => false
        ];
        yield [
            'defaults' => ['foo' => 'bar'],
            'routeValues' => ['foo' => 'bar'],
            'expectedModifiedRouteValues' => [],
            'expectedResult' => true
        ];
        yield [
            'defaults' => ['someKey' => 'somevalue'],
            'routeValues' => ['someKey' => 'SomeValue', 'SomeKey' => 'SomeOtherValue'],
            'expectedModifiedRouteValues' => ['SomeKey' => 'SomeOtherValue'],
            'expectedResult' => true
        ];
        yield [
            'defaults' => ['foo' => 'bar'],
            'routeValues' => ['foo' => 'bar', 'bar' => 'baz'],
            'expectedModifiedRouteValues' => ['bar' => 'baz'],
            'expectedResult' => true
        ];
        yield [
            'defaults' => ['foo' => 'bar', 'bar' => 'baz'],
            'routeValues' => ['foo' => 'bar'],
            'expectedModifiedRouteValues' => [],
            'expectedResult' => false
        ];
        yield [
            'defaults' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue']]],
            'routeValues' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue', 'someOtherKey' => 'someOtherValue']]],
            'expectedModifiedRouteValues' => ['firstLevel' => ['secondLevel' => ['someOtherKey' => 'someOtherValue']]],
            'expectedResult' => true];
        yield [
            'defaults' => ['foo' => 'bar'],
            'routeValues' => ['foo' => 'baz'],
            'expectedModifiedRouteValues' => null,
            'expectedResult' => false];
        yield [
            'defaults' => ['foo' => 'bar'],
            'routeValues' => ['foo' => ['bar' => 'bar']],
            'expectedModifiedRouteValues' => null,
            'expectedResult' => false];
        yield [
            'defaults' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeValue']]],
            'routeValues' => ['firstLevel' => ['secondLevel' => ['someKey' => 'SomeOtherValue']]],
            'expectedModifiedRouteValues' => null,
            'expectedResult' => false];
    }

    /**
     * @param array $defaults
     * @param array $routeValues
     * @param array $expectedModifiedRouteValues
     * @param boolean $expectedResult
     */
    #[DataProvider('compareAndRemoveMatchingDefaultValuesDataProvider')]
    #[Test]
    public function compareAndRemoveMatchingDefaultValuesTests(array $defaults, array $routeValues, $expectedModifiedRouteValues, $expectedResult)
    {
        $actualResult = $this->route->_callRef('compareAndRemoveMatchingDefaultValues', $defaults, $routeValues);
        self::assertSame($expectedResult, $actualResult);
        if ($expectedResult === true) {
            self::assertSame($expectedModifiedRouteValues, $routeValues);
        }
    }

    #[Test]
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
        $mockRoutePartHandler = $this->createMock(DynamicRoutePartInterface::class);
        $mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->willReturn($mockRoutePartHandler);

        $this->route->parse();
    }

    #[Test]
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
        $mockRoutePartHandler = $this->createMock(DynamicRoutePartInterface::class);
        $mockRoutePartHandler->expects($this->once())->method('setDefaultValue')->with('SomeDefaultValue');
        $this->mockObjectManager->expects($this->once())->method('get')->with('SomeRoutePartHandler')->willReturn($mockRoutePartHandler);

        $this->route->parse();
    }
}
