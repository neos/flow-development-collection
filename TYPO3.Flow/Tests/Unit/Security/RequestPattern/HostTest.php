<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Http;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Security\RequestPattern\Host;
use TYPO3\Flow\Tests\UnitTestCase;

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
        $httpRequest = Http\Request::create(new Http\Uri($uri));
        $request = new ActionRequest($httpRequest);

        $requestPattern = new Host();
        $requestPattern->setPattern($pattern);

        $this->assertEquals($expected, $requestPattern->matchRequest($request), $message);
    }
}
