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

    public function setUp()
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
        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is NULL.');

        $routePath = '';
        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchEmptyRequestPathEvenIfDefaultValueIsSet()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('bar');

        $routePath = '';
        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchIfNameIsNotSet()
    {
        $routePath = 'foo';

        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if name is not set.');
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
        $this->dynamicRoutPart->match($routePath);

        $this->assertEquals('firstSegment', $this->dynamicRoutPart->getValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
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
        $this->dynamicRoutPart->match($routePath);

        $this->assertEquals('some \ special öäüß', $this->dynamicRoutPart->getValue(), 'value of Dynamic Route Part should be equal to first request path segment after successful match.');
    }

    /**
     * @test
     */
    public function valueIsNullAfterUnsuccessfulMatch()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('/');

        $routePath = 'foo/bar';
        $this->dynamicRoutPart->match($routePath);

        $routePath = '/bar';
        $this->dynamicRoutPart->match($routePath);
        $this->assertNull($this->dynamicRoutPart->getValue(), 'Dynamic Route Part value should be NULL after unsuccessful match.');
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

        $this->assertSame('/foo/test', $routePath, 'Dynamic Route Part should shorten request path by one segment on successful match.');
    }

    /**
     * @test
     */
    public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotSet()
    {
        $this->dynamicRoutPart->setName('foo');

        $routePath = 'foo/bar';

        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and no split string is set.');
    }

    /**
     * @test
     */
    public function dynamicRouteDoesNotMatchRequestPathWithMoreThanOneSegmentIfSplitStringIsNotFound()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('not-existing');

        $routePath = 'foo/bar';

        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if request Path has more than one segment and does not contain split string.');
    }

    /**
     * @test
     */
    public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotSet()
    {
        $this->dynamicRoutPart->setName('foo');

        $routePath = 'bar';

        $this->assertTrue($this->dynamicRoutPart->match($routePath));
        $this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and no split string is set.');
    }

    /**
     * @test
     */
    public function dynamicRouteMatchesRequestPathWithOnlyOneSegmentIfSplitStringIsNotFound()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('not-existing');

        $routePath = 'bar';

        $this->assertTrue($this->dynamicRoutPart->match($routePath));
        $this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should match if request Path has only one segment and does not contain split string.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotMatchIfSplitStringIsAtFirstPosition()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('-');

        $routePath = '-foo/bar';

        $this->assertFalse($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part should not match if split string is first character of current request path.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartMatchesIfSplitStringContainsMultipleCharactersThatAreFoundInRequestPath()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setSplitString('_-_');

        $routePath = 'foo_-_bar';
        $this->assertTrue($this->dynamicRoutPart->match($routePath), 'Dynamic Route Part with a split string of "_-_" should match request path of "foo_-_bar".');
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

        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if name is not set.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartResolvesSimpleValueArray()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should resolve if an element with the same name exists in $routeValues.');
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

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals('some%20%5c%20special%20%c3%b6%c3%a4%c3%bc%c3%9f', $this->dynamicRoutPart->getValue());
        $this->assertNotEquals('some+%5c+special+%c3%b6%c3%a4%c3%bc%c3%9f', $this->dynamicRoutPart->getValue());
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotResolveEmptyArray()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = [];

        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotResolveEmptyArrayEvenIfDefaultValueIsSet()
    {
        $this->dynamicRoutPart->setName('foo');
        $this->dynamicRoutPart->setDefaultValue('defaultValue');
        $routeValues = [];

        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve an empty $routeValues-array even if default Value is set.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartLowerCasesValueWhenCallingResolveByDefault()
    {
        $this->dynamicRoutPart->setName('Foo');
        $routeValues = ['Foo' => 'Bar'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals('bar', $this->dynamicRoutPart->getValue(), 'By default Dynamic Route Part should lowercase route values.');
    }

    /**
     * @test
     */
    public function dynamicRoutePartDoesNotChangeCaseOfValueIfLowerCaseIsFale()
    {
        $this->dynamicRoutPart->setName('Foo');
        $this->dynamicRoutPart->setLowerCase(false);
        $routeValues = ['Foo' => 'Bar'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals('Bar', $this->dynamicRoutPart->getValue(), 'Dynamic Route Part should not change the case of the value if lowerCase is false.');
    }

    /**
     * @test
     */
    public function resolveReturnsFalseIfNoCorrespondingValueIsGiven()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['notFoo' => 'bar'];

        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues), 'Dynamic Route Part should not resolve if no element with the same name exists in $routeValues and no default value is set.');
    }

    /**
     * @test
     */
    public function resolveUnsetsCurrentRouteValueOnSuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar', 'differentString' => 'value2'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals(['differentString' => 'value2'], $routeValues, 'Dynamic Route Part should unset matching element from $routeValues on successful resolve.');
    }

    /**
     * @test
     */
    public function resolveRecursivelyUnsetsCurrentRouteValueOnSuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo.bar.baz');
        $routeValues = ['foo' => ['bar' => ['baz' => 'should be removed', 'otherKey' => 'should stay']], 'differentString' => 'value2'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals(['foo' => ['bar' => ['otherKey' => 'should stay']], 'differentString' => 'value2'], $routeValues);
    }

    /**
     * @test
     */
    public function resolveDoesNotChangeRouteValuesOnUnsuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['differentString' => 'bar'];

        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues));
        $this->assertEquals(['differentString' => 'bar'], $routeValues, 'Dynamic Route Part should not change $routeValues on unsuccessful resolve.');
    }

    /**
     * @test
     */
    public function resolveValueReturnsTrueAndSetTheValueToTheLowerCasedIdentifierIfTheValueToBeResolvedIsAnObject()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
        $this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
        $this->assertSame('theidentifier', $this->dynamicRoutPart->getValue());
    }

    /**
     * @test
     */
    public function resolveValueReturnsTrueAndSetTheValueToTheCorrectlyCasedIdentifierIfTheValueToBeResolvedIsAnObjectAndLowerCaseIsFalse()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue('TheIdentifier'));
        $this->dynamicRoutPart->setLowerCase(false);
        $this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
        $this->assertSame('TheIdentifier', $this->dynamicRoutPart->getValue());
    }

    /**
     * @test
     */
    public function resolveValueReturnsTrueIfTheValueToBeResolvedIsAnObjectWithANumericIdentifier()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(123));
        $this->assertTrue($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * @test
     */
    public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectWithAMultiValueIdentifier()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(['foo' => 'Foo', 'bar' => 'Bar']));
        $this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * Objects that are unknown to the persistence manager cannot be resolved by the standard DynamicRoutePart handler.
     *
     * @test
     */
    public function resolveValueReturnsFalseIfTheValueToBeResolvedIsAnObjectThatIsUnknownToThePersistenceManager()
    {
        $object = new \stdClass();
        $this->mockPersistenceManager->expects($this->once())->method('getIdentifierByObject')->with($object)->will($this->returnValue(null));
        $this->assertFalse($this->dynamicRoutPart->_call('resolveValue', $object));
    }

    /**
     * @test
     */
    public function routePartValueIsNullAfterUnsuccessfulResolve()
    {
        $this->dynamicRoutPart->setName('foo');
        $routeValues = ['foo' => 'bar'];

        $this->assertTrue($this->dynamicRoutPart->resolve($routeValues));

        $routeValues = [];
        $this->assertFalse($this->dynamicRoutPart->resolve($routeValues));
        $this->assertNull($this->dynamicRoutPart->getValue(), 'Dynamic Route Part value should be NULL when call to resolve() was not successful.');
    }
}
