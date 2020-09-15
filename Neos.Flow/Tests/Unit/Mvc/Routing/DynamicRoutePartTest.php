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

use Neos\Flow\Mvc\Routing\Dto\ResolveResult;
use Neos\Flow\Mvc\Routing\DynamicRoutePart;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the MVC Web Routing DynamicRoutePart Class
 */
class DynamicRoutePartTest extends UnitTestCase
{
    /**
     * @var DynamicRoutePart
     */
    protected $dynamicRoutPart;

    /**
     * @var PersistenceManagerInterface
     */
    protected $mockPersistenceManager;

    protected function setUp(): void
    {
        $this->dynamicRoutPart = $this->getAccessibleMock(DynamicRoutePart::class, ['dummy']);

        $this->mockPersistenceManager = $this->createMock(PersistenceManagerInterface::class);
        $this->dynamicRoutPart->_set('persistenceManager', $this->mockPersistenceManager);
    }

    /*                                                                        *
     * URI matching                                                           *
     *                                                                        */

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty()
    {
        $this->dynamicRoutPart->setName('foo');

        $routePath = null;
        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is NULL.');

        $routePath = '';
        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchEmptyRequestPathEvenIfDefaultValueIsSet()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('bar');

        $routePath = '';
        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchIfNameIsNotSet()
    {
        $routePath = 'foo';

        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if name is not set.');
    }


    /**
     * @test
     */
    public function valueMatchesFirstRequestPathSegmentAfterSuccessfulMatch()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('bar');
        $this->dynamicRoutPart->setSplitString('/');

        $routePath = 'firstSegment/secondSegment';
        $matchResult = $this->dynamicRoutPart->match($routePath);

        self::assertEquals('firstSegment', $matchResult->getMatchedValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
    }

    /**
     * @test
     */
    public function valueIsUrlDecodedAfterSuccessfulMatch()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('bar');
        $this->dynamicRoutPart->setSplitString('/');

        $routePath = 'some%20%5c%20special%20%c3%b6%c3%a4%c3%bc%c3%9f/secondSegment';
        $matchResult = $this->dynamicRoutPart->match($routePath);

        self::assertEquals('some \ special öäüß', $matchResult->getMatchedValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
    }

    /**
     * @test
     */
    public function routePathIsShortenedByOneSegmentAfterSuccessfulMatch()
    {
        $this->dynamicRoutPart->setName('bar');
        $this->dynamicRoutPart->setSplitString('/');

        $routePath = 'bar/foo/test';
        $this->dynamicRoutPart->match($routePath);

        self::assertSame('/foo/test', $routePath, 'Dynamic Route Part should shorten request path by one segment on successful match.');
    }

    /**
     * @test
     */
    public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotSet()
    {
        $this->dynamicRoutPart->setName('foo');

        $routePath = 'foo/bar';

        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and no split string is set.');
    }

    /**
     * @test
     */
    public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotFound()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('not-existing');

        $routePath = 'foo/bar';

        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and does not contain split string.');
    }

    /**
     * @test
     */
    public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotSet()
    {
        $this->dynamicRoutPart->setName('foo');

        $routePath = 'bar';

        $matchResult = $this->dynamicRoutPart->match($routePath);
        self::assertEquals('bar', $matchResult->getMatchedValue(), 'Dynamic Route Part should match if request Path has only one segment and no split string is set.');
    }

    /**
     * @test
     */
    public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotFound()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('not-existing');

        $routePath = 'bar';

        $matchResult = $this->dynamicRoutPart->match($routePath);
        self::assertEquals('bar', $matchResult->getMatchedValue(), 'Dynamic Route Part should match if request Path has only one segment and does not contain split string.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('-');

        $routePath = '-foo/bar';

        self::assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if split string is first character of current request path.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartMatchesIfSplitStringContainsMultipleCharactersThatAreFoundInRequestPath()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('_-_');

        $routePath = 'foo_-_bar';
        self::assertNotFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part with a split string of "_-_" should match request path of "foo_-_bar".');
    }

    /*                                                                        *
     * URI resolving                                                          *
     *                                                                        */

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotResolveIfNameIsNotSet()
    {
        $routeValues = ['foo' => 'bar'];

        self::assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if name is not set.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartResolvesSimpleValueArray()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertEquals('bar', $resolveResult->getResolvedValue(), 'Dynamic Route Part should resolve if an element with the same name exists in $routeValues.');
    }

    /**
     * Makes sure that dynamic route parts are encoded via rawurlencode (which encodes spaces to "%20") and not
     * urlencode (which encodes spaces to "+"). According to RFC 3986 that is correct for path segments.
     *
     * @test
     */
    public function dynamicRoutePartRawUrlEncodesValues()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'some \ special öäüß'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertEquals('some%20%5c%20special%20%c3%b6%c3%a4%c3%bc%c3%9f', $resolveResult->getResolvedValue());
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotResolveEmptyArray()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = [];

        self::assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotResolveEmptyArrayEvenIfDefaultValueIsSet()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('defaultValue');
        $routeValues = [];

        self::assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array even if default Value is set.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartLowerCasesValueWhenCallingResolveByDefault()
    {
        $this->dynamicRoutPart->setName('Foo');
        $routeValues = ['Foo' => 'Bar'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertEquals('bar', $resolveResult->getResolvedValue(), 'By default Dynamic Route Part should lowercase route values.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotChangeCaseOfValueIfLowerCaseIsFale()
    {
        $this->dynamicRoutPart->setName('Foo');
        $this->dynamicRoutPart->setLowerCase(false);
        $routeValues = ['Foo' => 'Bar'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertEquals('Bar', $resolveResult->getResolvedValue(), 'Dynamic Route Part should not change the case of the value if lowerCase is false.');
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNoCorrespondingValueIsGiven()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['notFoo' => 'bar'];

        self::assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
    }

    /**
     * @test
     */
    public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar', 'differentString' => 'value2'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertNotFalse($resolveResult);
        self::assertEquals(['differentString' => 'value2'], $routeValues, 'Dynamic Route Part should unset matching element from $routeValues on successful resolve.');
    }

    /**
     * @test
     */
    public function resolveRecursivelyUnsetsCurrentRouteValueOnSuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo.bar.baz');
        $routeValues = ['foo' => ['bar' => ['baz' => 'should be removed', 'otherKey' => 'should stay']], 'differentString' => 'value2'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertNotFalse($resolveResult);
        self::assertEquals(['foo' => ['bar' => ['otherKey' => 'should stay']], 'differentString' => 'value2'], $routeValues);
    }

    /**
     * @test
     */
    public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['differentString' => 'bar'];

        self::assertFalse($this->dynamicRoutPart->resolve($routeValues));
        self::assertEquals(['differentString' => 'bar'], $routeValues, 'Dynamic Route Part should not change $routeValues on unsuccessful resolve.');
    }

    /**
     * @test
     */
    public function resolveValueReturnsMatchResultsAndSetTheValueToTheLowerCasedIdentifierIfTheValueToBeResolvedIsAnObject()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue('TheIdentifier'));
        /** @var ResolveResult $resolveResult */
        $resolveResult = $this->dynamicRoutPart->_call('resolveValue', $object);
        self::assertSame('theidentifier', $resolveResult->getResolvedValue());
    }

    /**
     * @test
     */
    public function resolveValueReturnsMatchResultsAndSetTheValueToTheCorrectlyCasedIdentifierIfTheValueToBeResolvedIsAnObjectAndLowerCaseIsFalse()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue('TheIdentifier'));
        $this->dynamicRoutPart->setLowerCase(false);
        /** @var ResolveResult $resolveResult */
        $resolveResult = $this->dynamicRoutPart->_call('resolveValue', $object);
        self::assertSame('TheIdentifier', $resolveResult->getResolvedValue());
    }

    /**
     * @test
     */
    public function resolveValueReturnsMatchResultsIfTheValueToBeResolvedIsAnObjectWithANumericIdentifier()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue(123));
        self::assertNotFalse($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * @test
     */
    public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectWithAMultiValueIdentifier()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue(['foo' => 'Foo', 'bar' => 'Bar']));
        self::assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * Objects that are unknown to the persistence manager cannot be resolved by the standard DynamicRoutePart handler.
     *
     * @test
     */
    public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectThatIsUnknownToThePersistenceManager()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects(self::once())->method('getIdentifierByObject')->with($object)->will(self::returnValue(null));
        self::assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * @test
     */
    public function routePartValueIsNullAfterUnsuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar'];

        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertNotFalse($resolveResult);

        $routeValues = [];
        $resolveResult = $this->dynamicRoutPart->resolve($routeValues);
        self::assertFalse($resolveResult);
        self::assertNull($this->dynamicRoutPart->getValue(), 'Dynamic Route Part value should be NULL when call to resolve() was not successful.');
    }
}
