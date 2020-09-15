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

use Neos\Flow\Tests\UnitTestCase;
use Neos\Flow\Mvc;

/**
 * Testcase for the MVC Web Routing StaticRoutePart Class
 */
class StaticRoutePartTest extends UnitTestCase
{
    /*                                                                        *
     * URI matching                                                           *
     *                                                                        */

    /**
     * @test
     */
    public function staticRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');

        $routePath = null;
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is NULL.');

        $routePath = '';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotMatchIfRequestPathIsEmptyEvenIfDefaultValueIsSet()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routePart->setDefaultValue('bar');

        $routePath = '';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotMatchIfUnnamed()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePath = 'foo/bar';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not set.');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotMatchIfNameIsNotEqualToBeginningOfRequestPath()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routePath = 'bar/foo';

        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not equal to beginning of request path.');
    }

    /**
     * @test
     */
    public function staticRoutePartMatchesIfNameIsEqualToBeginningOfRequestPath()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routePath = 'foo/bar';

        self::assertTrue($routePart->match($routePath), 'Static Route Part should match if name equals beginning of request path.');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotMatchIfCaseOfRequestPathIsNotEqualToTheName()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('SomeName');
        $routePath = 'somename';

        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if case of name is not equal to case of request path.');
    }

    /**
     * @test
     */
    public function valueIsNullAfterUnsuccessfulMatch()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');

        $routePath = 'foo/bar';
        self::assertTrue($routePart->match($routePath));

        $routePath = 'bar/foo';
        self::assertFalse($routePart->match($routePath));
        self::assertNull($routePart->getValue(), 'Static Route Part value should be NULL after unsuccessful match.');
    }

    /**
     * @test
     */
    public function routePathIsNotModifiedAfterUnsuccessfulMatch()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('bar');

        $routePath = 'foo/bar';
        self::assertFalse($routePart->match($routePath));
        self::assertSame('foo/bar', $routePath, 'Static Route Part should not change $routePath on unsuccessful match.');
    }

    /**
     * @test
     */
    public function routePathIsShortenedByMatchingPartOnSuccessfulMatch()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('bar/');
        $routePath = 'bar/foo/test';

        self::assertTrue($routePart->match($routePath));
        self::assertSame('foo/test', $routePath, 'Static Route Part should shorten $routePath by matching substring on successful match.');
    }

    /**
     * @test
     */
    public function matchResetsValueBeforeProcessingTheRoutePath()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = [];
        $routePart->resolve($routeValues);
        self::assertSame('foo', $routePart->getValue());

        $routePath = 'foo';
        $routePart->match($routePath);
        self::assertNull($routePart->getValue(), 'Static Route Part must reset their value to NULL.');
    }

    /*                                                                        *
     * URI resolving                                                          *
     *                                                                        */

    /**
     * @test
     */
    public function staticRoutePartCanResolveEmptyArray()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = [];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve empty routeValues-array');
    }

    /**
     * @test
     */
    public function staticRoutePartCanResolveNonEmptyArray()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = ['@controller' => 'foo', '@action' => 'bar'];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve non-empty routeValues-array');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotResolveIfUnnamed()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routeValues = [];
        self::assertFalse($routePart->resolve($routeValues), 'Static Route Part should not resolve if name is not set');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotAlterRouteValuesWhenCallingResolve()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = ['@controller' => 'foo', '@action' => 'bar'];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertEquals(['@controller' => 'foo', '@action' => 'bar'], $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
    }

    /**
     * @test
     */
    public function staticRoutePartLowerCasesValueByDefault()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('SomeName');
        $routeValues = [];

        $routePart->resolve($routeValues);
        self::assertEquals('somename', $routePart->getValue(), 'Static Route Part should lowercase the value if lowerCase is true');
    }

    /**
     * @test
     */
    public function staticRoutePartDoesNotAlterCaseIfLowerCaseIsFalse()
    {
        $routePart = new Mvc\Routing\StaticRoutePart();
        $routePart->setName('SomeName');
        $routePart->setLowerCase(false);
        $routeValues = [];

        $routePart->resolve($routeValues);
        self::assertEquals('SomeName', $routePart->getValue(), 'By default Static Route Part should not alter the case of name');
    }
}
