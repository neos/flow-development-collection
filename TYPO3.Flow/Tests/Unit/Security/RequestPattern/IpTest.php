<?php
namespace TYPO3\Flow\Tests\Unit\Security\RequestPattern;

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

/**
 * Testcase for the IP request pattern
 */
class IpTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * Data provider with valid and invalid IP ranges
	 */
	public function validAndInvalidIpPatterns() {
		return array(
			array('127.0.0.1', '127.0.0.1', TRUE),
			array('127.0.0.0/24', '127.0.0.1', TRUE),
			array('255.255.255.255/0', '127.0.0.1', TRUE),
			array('127.0.255.255/16', '127.0.0.1', TRUE),
			array('127.0.0.1/32', '127.0.0.1', TRUE),
			array('1:2::3:4', '1:2:0:0:0:0:3:4', TRUE),
			array('127.0.0.2/32', '127.0.0.1', FALSE),
			array('127.0.1.0/24', '127.0.0.1', FALSE),
			array('127.0.0.255/31', '127.0.0.1', FALSE),
			array('::1', '127.0.0.1', FALSE),
			array('::127.0.0.1', '127.0.0.1', TRUE),
			array('127.0.0.1', '::127.0.0.1', TRUE),
		);
	}

	/**
	 * @dataProvider validAndInvalidIpPatterns
	 * @test
	 */
	public function requestMatchingBasicallyWorks($pattern, $ip, $expected) {
		$requestMock = $this->getMock('\TYPO3\Flow\Http\Request', array('getClientIpAddress'), array(), '', FALSE);
		$requestMock->expects($this->once())->method('getClientIpAddress')->will($this->returnValue($ip));
		$actionRequestMock = $this->getMock('\TYPO3\Flow\Mvc\ActionRequest', array(), array(), '', FALSE);
		$actionRequestMock->expects($this->any())->method('getHttpRequest')->will($this->returnValue($requestMock));

		$requestPattern = new \TYPO3\Flow\Security\RequestPattern\Ip();

		$requestPattern->setPattern($pattern);
		$this->assertEquals($expected, $requestPattern->matchRequest($actionRequestMock));
	}
}
?>