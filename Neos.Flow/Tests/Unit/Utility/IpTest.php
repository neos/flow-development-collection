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

use Neos\Flow\Utility\Ip;

/**
 * Testcase for the Utility Ip class
 *
 */
class IpTest extends \Neos\Flow\Tests\UnitTestCase
{
    /**
     * Data provider with valid and invalid IP ranges
     */
    public function validAndInvalidIpPatterns()
    {
        return [
            ['127.0.0.1', '127.0.0.1', true],
            ['127.0.0.0/24', '127.0.0.1', true],
            ['255.255.255.255/0', '127.0.0.1', true],
            ['127.0.255.255/16', '127.0.0.1', true],
            ['127.0.0.1/32', '127.0.0.1', true],
            ['1:2::3:4', '1:2:0:0:0:0:3:4', true],
            ['127.0.0.2/32', '127.0.0.1', false],
            ['127.0.1.0/24', '127.0.0.1', false],
            ['127.0.0.255/31', '127.0.0.1', false],
            ['::1', '127.0.0.1', false],
            ['::127.0.0.1', '127.0.0.1', true],
            ['127.0.0.1', '::127.0.0.1', true],
        ];
    }

    /**
     * @dataProvider validAndInvalidIpPatterns
     * @test
     */
    public function cidrMatchCorrectlyMatchesIpRanges($range, $ip, $expected)
    {
        self::assertEquals($expected, Ip::cidrMatch($ip, $range));
    }
}
