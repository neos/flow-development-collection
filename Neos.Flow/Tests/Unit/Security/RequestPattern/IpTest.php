<?php

declare(strict_types=1);

namespace Neos\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Http\ServerRequestAttributes;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Ip;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testcase for the IP request pattern
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
    public function requestMatchingBasicallyWorks($pattern, $ip, $expected)
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock->expects($this->once())->method('getAttribute')->with(ServerRequestAttributes::CLIENT_IP)->willReturn($ip);
        $actionRequestMock = $this->createMock(ActionRequest::class);
        $actionRequestMock->method('getHttpRequest')->willReturn(($requestMock));

        $requestPattern = new Ip(['cidrPattern' => $pattern]);

        self::assertEquals($expected, $requestPattern->matchRequest($actionRequestMock));
    }
}
