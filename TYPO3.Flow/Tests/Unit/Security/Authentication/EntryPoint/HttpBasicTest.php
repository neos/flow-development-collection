<?php
namespace TYPO3\Flow\Tests\Unit\Security\Authentication\EntryPoint;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Response;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Security\Authentication\EntryPoint\HttpBasic;
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for HTTP Basic Auth authentication entry point
 */
class HttpBasicTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function startAuthenticationSetsTheCorrectValuesInTheResponseObject() {
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
