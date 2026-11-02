<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Utility;

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
use Neos\Flow\Utility\Algorithms;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Utility Algorithms class
 *
 */
final class AlgorithmsTest extends UnitTestCase
{
    #[Test]
    public function generateUUIDGeneratesUuidLikeString()
    {
        self::assertMatchesRegularExpression('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/', Algorithms::generateUUID());
    }

    #[Test]
    public function generateUUIDGeneratesLowercaseString()
    {
        $uuid = Algorithms::generateUUID();
        self::assertSame(strtolower($uuid), $uuid);
    }

    #[Test]
    public function generateUUIDGeneratesAtLeastNotTheSameUuidOnSubsequentCalls()
    {
        self::assertNotSame(Algorithms::generateUUID(), Algorithms::generateUUID());
    }

    #[Test]
    public function generateRandomBytesGeneratesRandomBytes()
    {
        self::assertSame(20, strlen(Algorithms::generateRandomBytes(20)));
    }

    #[Test]
    public function generateRandomTokenGeneratesRandomToken()
    {
        self::assertMatchesRegularExpression('/^[[:xdigit:]]{64}$/', Algorithms::generateRandomToken(32));
    }

    #[Test]
    public function generateRandomStringGeneratesAlnumCharactersPerDefault()
    {
        self::assertMatchesRegularExpression('/^[a-z0-9]{64}$/i', Algorithms::generateRandomString(64));
    }

    /**
     * signature: $regularExpression, $charactersClass
     */
    public static function randomStringCharactersDataProvider(): \Iterator
    {
        yield ['/^[#~+]{64}$/', '#~+'];
        yield ['/^[a-f2-4%]{64}$/', 'abcdef234%'];
    }

    #[DataProvider('randomStringCharactersDataProvider')]
    #[Test]
    public function generateRandomStringGeneratesOnlyDefinedCharactersRange($regularExpression, $charactersClass)
    {
        self::assertMatchesRegularExpression($regularExpression, Algorithms::generateRandomString(64, $charactersClass));
    }
}
