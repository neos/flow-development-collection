<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\EntryPoint;

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
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\Authentication\EntryPoint\HttpBasic;
use TYPO3\Flow\Tests\UnitTestCase;

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
        $mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder('TYPO3\Flow\Http\Response')->getMock();

        $entryPoint = new HttpBasic();
        $entryPoint->setOptions(array('realm' => 'realm string'));

        $mockResponse->expects($this->once())->method('setStatus')->with(401);
        $mockResponse->expects($this->once())->method('setHeader')->with('WWW-Authenticate', 'Basic realm="realm string"');
        $mockResponse->expects($this->once())->method('setContent')->with('Authorization required');

        $entryPoint->startAuthentication($mockHttpRequest, $mockResponse);

        $this->assertEquals(array('realm' => 'realm string'), $entryPoint->getOptions());
    }
}
