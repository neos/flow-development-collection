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
use TYPO3\Flow\Tests\UnitTestCase;

/**
 * Testcase for the URI request pattern
 */
class UriTest extends UnitTestCase {

	public function matchRequestDataProvider() {
		return array(
			array('uriPath' => '', 'pattern' => '.*', 'shouldMatch' => TRUE),
			array('uriPath' => '', 'pattern' => '/some/nice/.*', 'shouldMatch' => FALSE),
			array('uriPath' => '/some/nice/path/to/index.php', 'pattern' => '/some/nice/.*', 'shouldMatch' => TRUE),
			array('uriPath' => '/some/other/path', 'pattern' => '.*/other/.*', 'shouldMatch' => TRUE),
		);
	}

	/**
	 * @test
	 * @dataProvider matchRequestDataProvider
	 */
	public function matchRequestTests($uriPath, $pattern, $shouldMatch) {
		$mockActionRequest = $this->getMockBuilder('TYPO3\Flow\Mvc\ActionRequest')->disableOriginalConstructor()->getMock();

		$mockHttpRequest = $this->getMockBuilder('TYPO3\Flow\Http\Request')->disableOriginalConstructor()->getMock();
		$mockActionRequest->expects($this->atLeastOnce())->method('getHttpRequest')->will($this->returnValue($mockHttpRequest));

		$mockUri = $this->getMockBuilder('TYPO3\Flow\Http\Uri')->disableOriginalConstructor()->getMock();
		$mockHttpRequest->expects($this->atLeastOnce())->method('getUri')->will($this->returnValue($mockUri));

		$mockUri->expects($this->atLeastOnce())->method('getPath')->will($this->returnValue($uriPath));

		$requestPattern = new \TYPO3\Flow\Security\RequestPattern\Uri();
		$requestPattern->setPattern($pattern);

		if ($shouldMatch) {
			$this->assertTrue($requestPattern->matchRequest($mockActionRequest));
		} else {
			$this->assertFalse($requestPattern->matchRequest($mockActionRequest));
		}
	}
}
