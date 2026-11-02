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
use Neos\Flow\Mvc;
use Neos\Flow\Mvc\Routing\StaticRoutePart;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the MVC Web Routing StaticRoutePart Class
 */
final class StaticRoutePartTest extends UnitTestCase
{
    /*                                                                        *
     * URI matching                                                           *
     *                                                                        */
    #[Test]
    public function staticRoutePartDoesNotMatchIfRequestPathIsNullOrEmpty()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');

        $routePath = null;
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is NULL.');

        $routePath = '';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
    }

    #[Test]
    public function staticRoutePartDoesNotMatchIfRequestPathIsEmptyEvenIfDefaultValueIsSet()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routePart->setDefaultValue('bar');

        $routePath = '';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should never match if $routePath is empty.');
    }

    #[Test]
    public function staticRoutePartDoesNotMatchIfUnnamed()
    {
        $routePart = new StaticRoutePart();
        $routePath = 'foo/bar';
        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not set.');
    }

    #[Test]
    public function staticRoutePartDoesNotMatchIfNameIsNotEqualToBeginningOfRequestPath()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routePath = 'bar/foo';

        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if name is not equal to beginning of request path.');
    }

    #[Test]
    public function staticRoutePartMatchesIfNameIsEqualToBeginningOfRequestPath()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routePath = 'foo/bar';

        self::assertTrue($routePart->match($routePath), 'Static Route Part should match if name equals beginning of request path.');
    }

    #[Test]
    public function staticRoutePartDoesNotMatchIfCaseOfRequestPathIsNotEqualToTheName()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('SomeName');
        $routePath = 'somename';

        self::assertFalse($routePart->match($routePath), 'Static Route Part should not match if case of name is not equal to case of request path.');
    }

    #[Test]
    public function valueIsNullAfterUnsuccessfulMatch()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');

        $routePath = 'foo/bar';
        self::assertTrue($routePart->match($routePath));

        $routePath = 'bar/foo';
        self::assertFalse($routePart->match($routePath));
        self::assertNull($routePart->getValue(), 'Static Route Part value should be NULL after unsuccessful match.');
    }

    #[Test]
    public function routePathIsNotModifiedAfterUnsuccessfulMatch()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('bar');

        $routePath = 'foo/bar';
        self::assertFalse($routePart->match($routePath));
        self::assertSame('foo/bar', $routePath, 'Static Route Part should not change $routePath on unsuccessful match.');
    }

    #[Test]
    public function routePathIsShortenedByMatchingPartOnSuccessfulMatch()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('bar/');
        $routePath = 'bar/foo/test';

        self::assertTrue($routePart->match($routePath));
        self::assertSame('foo/test', $routePath, 'Static Route Part should shorten $routePath by matching substring on successful match.');
    }

    #[Test]
    public function matchResetsValueBeforeProcessingTheRoutePath()
    {
        $routePart = new StaticRoutePart();
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
    #[Test]
    public function staticRoutePartCanResolveEmptyArray()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = [];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve empty routeValues-array');
    }

    #[Test]
    public function staticRoutePartCanResolveNonEmptyArray()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = ['@controller' => 'foo', '@action' => 'bar'];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertEquals('foo', $routePart->getValue(), 'Static Route Part should resolve non-empty routeValues-array');
    }

    #[Test]
    public function staticRoutePartDoesNotResolveIfUnnamed()
    {
        $routePart = new StaticRoutePart();
        $routeValues = [];
        self::assertFalse($routePart->resolve($routeValues), 'Static Route Part should not resolve if name is not set');
    }

    #[Test]
    public function staticRoutePartDoesNotAlterRouteValuesWhenCallingResolve()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('foo');
        $routeValues = ['@controller' => 'foo', '@action' => 'bar'];

        self::assertTrue($routePart->resolve($routeValues));
        self::assertSame(['@controller' => 'foo', '@action' => 'bar'], $routeValues, 'when resolve() is called on Static Route Part, specified routeValues-array should never be changed');
    }

    #[Test]
    public function staticRoutePartLowerCasesValueByDefault()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('SomeName');
        $routeValues = [];

        $routePart->resolve($routeValues);
        self::assertEquals('somename', $routePart->getValue(), 'Static Route Part should lowercase the value if lowerCase is true');
    }

    #[Test]
    public function staticRoutePartDoesNotAlterCaseIfLowerCaseIsFalse()
    {
        $routePart = new StaticRoutePart();
        $routePart->setName('SomeName');
        $routePart->setLowerCase(false);
        $routeValues = [];

        $routePart->resolve($routeValues);
        self::assertEquals('SomeName', $routePart->getValue(), 'By default Static Route Part should not alter the case of name');
    }
}
