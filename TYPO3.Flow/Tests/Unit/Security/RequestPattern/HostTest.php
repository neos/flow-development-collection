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

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
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
        return array(
            array('http://typo3.org/index.php', 'typo3.*', true, 'Assert that wildcard matches.'),
            array('http://typo3.org/index.php', 'flow.typo3.org', false, 'Assert that subdomains don\'t match.'),
            array('http://typo3.org/index.php', '*typo3.org', true, 'Assert that prefix wildcard matches.'),
            array('http://typo3.org/index.php', '*.typo3.org', false, 'Assert that subdomain wildcard doesn\'t match.'),
            array('http://flow.typo3.org/', '*.typo3.org', true, 'Assert that subdomain wildcard matches.'),
            array('http://flow.typo3.org/', 'neos.typo3.org', false, 'Assert that different subdomain doesn\'t match.'),
        );
    }

    /**
     * @dataProvider uriAndHostPatterns
     * @test
     */
    public function requestMatchingBasicallyWorks($uri, $pattern, $expected, $message)
    {
        $request = Request::create(new Uri($uri))->createActionRequest();

        $requestPattern = new Host(['hostPattern' => $pattern]);

        $this->assertEquals($expected, $requestPattern->matchRequest($request), $message);
    }
}
