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
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Host;
use Neos\Flow\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Testcase for the URI request pattern
 */
final class HostTest extends UnitTestCase
{
    /**
     * Data provider with URIs and host patterns
     */
    public static function uriAndHostPatterns(): \Iterator
    {
        yield ['http://neos.io/index.php', 'neos.*', true, 'Assert that wildcard matches.'];
        yield ['http://www.neos.io/index.php', 'flow.neos.io', false, 'Assert that subdomains don\'t match.'];
        yield ['http://www.neos.io/index.php', '*www.neos.io', true, 'Assert that prefix wildcard matches.'];
        yield ['http://www.neos.io/index.php', '*.www.neos.io', false, 'Assert that subdomain wildcard doesn\'t match.'];
        yield ['http://flow.neos.io/', '*.neos.io', true, 'Assert that subdomain wildcard matches.'];
        yield ['http://flow.neos.io/', 'www.neos.io', false, 'Assert that different subdomain doesn\'t match.'];
    }

    #[DataProvider('uriAndHostPatterns')]
    #[Test]
    public function requestMatchingBasicallyWorks($uri, $pattern, $expected, $message)
    {
        $httpRequest = new ServerRequest('GET', new Uri($uri));
        $request = ActionRequest::fromHttpRequest($httpRequest);

        $requestPattern = new Host(['hostPattern' => $pattern]);

        self::assertEquals($expected, $requestPattern->matchRequest($request), $message);
    }
}
