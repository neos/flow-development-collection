<?php
namespace Neos\Flow\Tests\Unit\Mvc\Routing\Dto;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Mvc\Routing\Dto\RouteLifetime;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the RouteLifetime DTO
 */
class RouteLifetimeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createFromNegativeIntegerThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteLifetime::fromInt(-1);
    }

    /**
     * @test
     */
    public function createFromIntCreatesANewInstanceWithTheGivenValue()
    {
        $lifetime = RouteLifetime::fromInt(123);
        self::assertSame(123, $lifetime->getValue());
        self::assertFalse($lifetime->isUndefined());
        self::assertFalse($lifetime->isInfinite());
    }

    /**
     * @test
     */
    public function createUndefinedCreatesANewInstanceWithNullValue()
    {
        $lifetime = RouteLifetime::createUndefined();
        self::assertSame(null, $lifetime->getValue());
        self::assertTrue($lifetime->isUndefined());
        self::assertFalse($lifetime->isInfinite());
    }

    /**
     * @test
     */
    public function createInfiniteCreatesANewInstanceWithZeroValue()
    {
        $lifetime = RouteLifetime::createInfinite();
        self::assertSame(0, $lifetime->getValue());
        self::assertFalse($lifetime->isUndefined());
        self::assertTrue($lifetime->isInfinite());
    }

    public function mergeReturnsLowerLifetimeOfNonNullValuesDataProvider(): array
    {
        return [
            [100, 200, 100],
            [100, 100, 100],
            [200, 100, 100],
            [null, 200, 200],
            [200, null, 200],
            [null, null, null],
            [100, 0, 100],
            [0, 100, 100],
            [0, null, 0],
            [null, 0, 0]
        ];
    }

    /**
     * @test
     * @dataProvider mergeReturnsLowerLifetimeOfNonNullValuesDataProvider
     */
    public function mergeReturnsLowerLifetimeOfNonNullValues($valueOne, $valueTwo, $expectation)
    {
        $lifetimeOne = is_int($valueOne) ? RouteLifetime::fromInt($valueOne) : RouteLifetime::createUndefined();
        $lifetimeTwo = is_int($valueTwo) ? RouteLifetime::fromInt($valueTwo) : RouteLifetime::createUndefined();

        $mergedLifetime = $lifetimeOne->merge($lifetimeTwo);
        self::assertSame($expectation, $mergedLifetime->getValue());
    }
}
