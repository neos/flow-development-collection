<?php
namespace Neos\Flow\Tests\Unit\Security\Authentication\EntryPoint;

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
use Neos\Flow\Http\Response;
use Neos\Flow\Http\Uri;
use Neos\Flow\Security\Authentication\EntryPoint\HttpBasic;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for HTTP Basic Auth authentication entry point
 */
class HttpBasicTest extends UnitTestCase
{
    /**
     * @test
     */
    public function startAuthenticationSetsTheCorrectValuesInTheResponseObject()
    {
        $mockHttpRequest = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder(Response::class)->getMock();

        $entryPoint = new HttpBasic();
        $entryPoint->setOptions(['realm' => 'realm string']);

        $mockResponse->expects($this->once())->method('setStatus')->with(401);
        $mockResponse->expects($this->once())->method('setHeader')->with('WWW-Authenticate', 'Basic realm="realm string"');
        $mockResponse->expects($this->once())->method('setContent')->with('Authorization required');

        $entryPoint->startAuthentication($mockHttpRequest, $mockResponse);

        $this->assertEquals(['realm' => 'realm string'], $entryPoint->getOptions());
    }
}
