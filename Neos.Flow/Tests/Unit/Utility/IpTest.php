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
use Neos\Flow\Utility\Ip;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the Utility Ip class
 *
 */
final class IpTest extends UnitTestCase
{
    /**
     * Data provider with valid and invalid IP ranges
     */
    public static function validAndInvalidIpPatterns(): \Iterator
    {
        yield ['127.0.0.1', '127.0.0.1', true];
        yield ['127.0.0.0/24', '127.0.0.1', true];
        yield ['255.255.255.255/0', '127.0.0.1', true];
        yield ['127.0.255.255/16', '127.0.0.1', true];
        yield ['127.0.0.1/32', '127.0.0.1', true];
        yield ['1:2::3:4', '1:2:0:0:0:0:3:4', true];
        yield ['127.0.0.2/32', '127.0.0.1', false];
        yield ['127.0.1.0/24', '127.0.0.1', false];
        yield ['127.0.0.255/31', '127.0.0.1', false];
        yield ['::1', '127.0.0.1', false];
        yield ['::127.0.0.1', '127.0.0.1', true];
        yield ['127.0.0.1', '::127.0.0.1', true];
    }

    #[DataProvider('validAndInvalidIpPatterns')]
    #[Test]
    public function cidrMatchCorrectlyMatchesIpRanges($range, $ip, $expected)
    {
        self::assertEquals($expected, Ip::cidrMatch($ip, $range));
    }
}
