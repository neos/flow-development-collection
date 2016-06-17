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

/**
 * Testcase for the IP request pattern
 */
class IpTest extends \TYPO3\Flow\Tests\UnitTestCase
{
    /**
     * Data provider with valid and invalid IP ranges
     */
    public function validAndInvalidIpPatterns()
    {
        return array(
            array('127.0.0.1', '127.0.0.1', true),
            array('127.0.0.0/24', '127.0.0.1', true),
            array('255.255.255.255/0', '127.0.0.1', true),
            array('127.0.255.255/16', '127.0.0.1', true),
            array('127.0.0.1/32', '127.0.0.1', true),
            array('1:2::3:4', '1:2:0:0:0:0:3:4', true),
            array('127.0.0.2/32', '127.0.0.1', false),
            array('127.0.1.0/24', '127.0.0.1', false),
            array('127.0.0.255/31', '127.0.0.1', false),
            array('::1', '127.0.0.1', false),
            array('::127.0.0.1', '127.0.0.1', true),
            array('127.0.0.1', '::127.0.0.1', true),
        );
    }

    /**
     * @dataProvider validAndInvalidIpPatterns
     * @test
     */
    public function requestMatchingBasicallyWorks($pattern, $ip, $expected)
    {
        $requestMock = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->setMethods(array('getClientIpAddress'))->getMock();
        $requestMock->expects($this->once())->method('getClientIpAddress')->will($this->returnValue($ip));
        $actionRequestMock = $this->getMockBuilder(\TYPO3\Flow\Mvc\ActionRequest::class)->disableOriginalConstructor()->getMock();
        $actionRequestMock->expects($this->any())->method('getHttpRequest')->will($this->returnValue($requestMock));

        $requestPattern = new \TYPO3\Flow\Security\RequestPattern\Ip();

        $requestPattern->setPattern($pattern);
        $this->assertEquals($expected, $requestPattern->matchRequest($actionRequestMock));
    }
}
