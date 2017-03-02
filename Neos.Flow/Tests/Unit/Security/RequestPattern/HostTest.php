<?php
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

use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Host;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the URI request pattern
 */
class HostTest extends UnitTestCase
{
    /**
     * Data provider with URIs and host patterns
     */
    public function uriAndHostPatterns()
    {
        return [
            ['http://neos.io/index.php', 'neos.*', true, 'Assert that wildcard matches.'],
            ['http://www.neos.io/index.php', 'flow.neos.io', false, 'Assert that subdomains don\'t match.'],
            ['http://www.neos.io/index.php', '*www.neos.io', true, 'Assert that prefix wildcard matches.'],
            ['http://www.neos.io/index.php', '*.www.neos.io', false, 'Assert that subdomain wildcard doesn\'t match.'],
            ['http://flow.neos.io/', '*.neos.io', true, 'Assert that subdomain wildcard matches.'],
            ['http://flow.neos.io/', 'www.neos.io', false, 'Assert that different subdomain doesn\'t match.'],
        ];
    }

    /**
     * @dataProvider uriAndHostPatterns
     * @test
     */
    public function requestMatchingBasicallyWorks($uri, $pattern, $expected, $message)
    {
        $request = new ActionRequest(Request::create(new Uri($uri)));

        $requestPattern = new Host(['hostPattern' => $pattern]);

        $this->assertEquals($expected, $requestPattern->matchRequest($request), $message);
    }
}
