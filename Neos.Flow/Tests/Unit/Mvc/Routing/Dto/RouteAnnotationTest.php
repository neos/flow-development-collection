<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

use Neos\Flow\Annotations as Flow;
use PHPUnit\Framework\TestCase;

/**
 * Tests for #[Flow\Route]
 */
class RouteAnnotationTest extends TestCase
{
    /**
     * @test
     */
    public function simpleRoutes()
    {
        $route = new Flow\Route(uriPattern: 'my/path');
        self::assertSame('my/path', $route->uriPattern);
        self::assertSame('', $route->name);
        self::assertSame([], $route->httpMethods);
        self::assertSame([], $route->defaults);

        $route = new Flow\Route(
            uriPattern: 'my/other/path',
            name: 'specialRoute',
            httpMethods: ['POST'],
            defaults: ['test' => 'foo']
        );
        self::assertSame('my/other/path', $route->uriPattern);
        self::assertSame('specialRoute', $route->name);
        self::assertSame(['POST'], $route->httpMethods);
        self::assertSame(['test' => 'foo'], $route->defaults);
    }

    /**
     * @test
     */
    public function preservedDefaults()
    {
        $this->expectExceptionCode(1711129638);

        new Flow\Route(uriPattern: 'my/path', defaults: ['@action' => 'index']);
    }

    /**
     * @test
     */
    public function preservedInUriPattern()
    {
        $this->expectExceptionCode(1711129634);

        new Flow\Route(uriPattern: 'my/{@package}');
    }

    /**
     * @test
     */
    public function uriPatternMustNotStartWithLeadingSlash()
    {
        $this->expectExceptionCode(1711529592);

        new Flow\Route(uriPattern: '/absolute');
    }

    /**
     * @test
     */
    public function uriPatternMustNotBeEmpty()
    {
        $this->expectExceptionCode(1711529592);

        new Flow\Route(uriPattern: '');
    }

    /**
     * @test
     */
    public function httpMethodMustNotBeEmptyString()
    {
        $this->expectExceptionCode(1711530485);

        new Flow\Route(uriPattern: 'foo', httpMethods: ['']);
    }

    /**
     * @test
     */
    public function httpMethodMustBeUpperCase()
    {
        $this->expectExceptionCode(1711530485);

        /** @see \Neos\Flow\Mvc\Routing\Route::matches() where we do case-sensitive comparison against uppercase */
        new Flow\Route(
            uriPattern: 'my/other/path',
            httpMethods: ['post']
        );
    }
}
