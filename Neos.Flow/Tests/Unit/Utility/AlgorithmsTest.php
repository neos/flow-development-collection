<?php
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

/**
 * Testcase for the Utility Algorithms class
 *
 */
class AlgorithmsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function generateUUIDGeneratesUuidLikeString()
    {
        self::assertRegExp('/^[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}$/', Algorithms::generateUUID());
    }

    /**
     * @test
     */
    public function generateUUIDGeneratesLowercaseString()
    {
        $uuid = Algorithms::generateUUID();
        self::assertSame(strtolower($uuid), $uuid);
    }

    /**
     * @test
     */
    public function generateUUIDGeneratesAtLeastNotTheSameUuidOnSubsequentCalls()
    {
        self::assertNotEquals(Algorithms::generateUUID(), Algorithms::generateUUID());
    }

    /**
     * @test
     */
    public function generateRandomBytesGeneratesRandomBytes()
    {
        self::assertEquals(20, strlen(Algorithms::generateRandomBytes(20)));
    }

    /**
     * @test
     */
    public function generateRandomTokenGeneratesRandomToken()
    {
        self::assertRegExp('/^[[:xdigit:]]{64}$/', Algorithms::generateRandomToken(32));
    }

    /**
     * @test
     */
    public function generateRandomStringGeneratesAlnumCharactersPerDefault()
    {
        self::assertRegExp('/^[a-z0-9]{64}$/i', Algorithms::generateRandomString(64));
    }

    /**
     * signature: $regularExpression, $charactersClass
     */
    public function randomStringCharactersDataProvider()
    {
        return [
            ['/^[#~+]{64}$/', '#~+'],
            ['/^[a-f2-4%]{64}$/', 'abcdef234%'],
        ];
    }

    /**
     * @test
     * @dataProvider randomStringCharactersDataProvider
     */
    public function generateRandomStringGeneratesOnlyDefinedCharactersRange($regularExpression, $charactersClass)
    {
        self::assertRegExp($regularExpression, Algorithms::generateRandomString(64, $charactersClass));
    }
}
