<?php

declare(strict_types=1);

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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the RouteLifetime DTO
 */
final class RouteLifetimeTest extends UnitTestCase
{
    #[Test]
    public function createFromNegativeIntegerThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);
        RouteLifetime::fromInt(-1);
    }

    #[Test]
    public function createFromIntCreatesANewInstanceWithTheGivenValue()
    {
        $lifetime = RouteLifetime::fromInt(123);
        self::assertSame(123, $lifetime->getValue());
        self::assertFalse($lifetime->isUndefined());
        self::assertFalse($lifetime->isInfinite());
    }

    #[Test]
    public function createUndefinedCreatesANewInstanceWithNullValue()
    {
        $lifetime = RouteLifetime::createUndefined();
        self::assertNull($lifetime->getValue());
        self::assertTrue($lifetime->isUndefined());
        self::assertFalse($lifetime->isInfinite());
    }

    #[Test]
    public function createInfiniteCreatesANewInstanceWithZeroValue()
    {
        $lifetime = RouteLifetime::createInfinite();
        self::assertSame(0, $lifetime->getValue());
        self::assertFalse($lifetime->isUndefined());
        self::assertTrue($lifetime->isInfinite());
    }

    public static function mergeReturnsLowerLifetimeOfNonNullValuesDataProvider(): \Iterator
    {
        yield [100, 200, 100];
        yield [100, 100, 100];
        yield [200, 100, 100];
        yield [null, 200, 200];
        yield [200, null, 200];
        yield [null, null, null];
        yield [100, 0, 100];
        yield [0, 100, 100];
        yield [0, null, 0];
        yield [null, 0, 0];
    }

    #[DataProvider('mergeReturnsLowerLifetimeOfNonNullValuesDataProvider')]
    #[Test]
    public function mergeReturnsLowerLifetimeOfNonNullValues($valueOne, $valueTwo, $expectation)
    {
        $lifetimeOne = is_int($valueOne) ? RouteLifetime::fromInt($valueOne) : RouteLifetime::createUndefined();
        $lifetimeTwo = is_int($valueTwo) ? RouteLifetime::fromInt($valueTwo) : RouteLifetime::createUndefined();

        $mergedLifetime = $lifetimeOne->merge($lifetimeTwo);
        self::assertSame($expectation, $mergedLifetime->getValue());
    }
}
