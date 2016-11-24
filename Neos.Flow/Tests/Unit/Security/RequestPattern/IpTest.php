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
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Security\RequestPattern\Ip;
use Neos\Flow\Tests\UnitTestCase;

/**
 * Testcase for the IP request pattern
 */
class IpTest extends UnitTestCase
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
    public function requestMatchingBasicallyWorks($pattern, $ip, $expected)
    {
        $requestMock = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->setMethods(array('getClientIpAddress'))->getMock();
        $requestMock->expects($this->once())->method('getClientIpAddress')->will($this->returnValue($ip));
        $actionRequestMock = $this->getMockBuilder(ActionRequest::class)->disableOriginalConstructor()->getMock();
        $actionRequestMock->expects($this->any())->method('getHttpRequest')->will($this->returnValue($requestMock));

        $requestPattern = new Ip(['cidrPattern' => $pattern]);

        $this->assertEquals($expected, $requestPattern->matchRequest($actionRequestMock));
    }
}
