<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

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
        $mockHttpRequest = $this->getMockBuilder(\TYPO3\Flow\Http\Request::class)->disableOriginalConstructor()->getMock();
        $mockResponse = $this->getMockBuilder(\TYPO3\Flow\Http\Response::class)->getMock();

        $entryPoint = new HttpBasic();
        $entryPoint->setOptions(array('realm' => 'realm string'));

        $mockResponse->expects($this->once())->method('setStatus')->with(401);
        $mockResponse->expects($this->once())->method('setHeader')->with('WWW-Authenticate', 'Basic realm="realm string"');
        $mockResponse->expects($this->once())->method('setContent')->with('Authorization required');

        $entryPoint->startAuthentication($mockHttpRequest, $mockResponse);

        $this->assertEquals(array('realm' => 'realm string'), $entryPoint->getOptions());
    }
}
