<?php
namespace TYPO3\FLOW3\Tests\Unit\Security\RequestPattern;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for the URI request pattern
 *
 */
class UriTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 * @category unit
	 * @expectedException TYPO3\FLOW3\Security\Exception\RequestTypeNotSupportedException
	 */
	public function anExceptionIsThrownIfTheGivenRequestObjectIsNotSupported() {
		$cliRequest = $this->getMock('TYPO3\FLOW3\Cli\Request');

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\Uri();
		$requestPattern->matchRequest($cliRequest);
	}

	/**
	 * @test
	 * @category unit
	 */
	public function canMatchReturnsTrueForASupportedRequestType() {
		$webRequest = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\Uri();
		$this->assertTrue($requestPattern->canMatch($webRequest));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function canMatchReturnsFalseForAnUnsupportedRequestType() {
		$cliRequest = $this->getMock('TYPO3\FLOW3\Cli\Request');

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\Uri();
		$this->assertFalse($requestPattern->canMatch($cliRequest));
	}

	/**
	 * @test
	 * @category unit
	 */
	public function requestMatchingBasicallyWorks() {
		$request = $this->getMock('TYPO3\FLOW3\Mvc\ActionRequest');
		$uri = $this->getMock('TYPO3\FLOW3\Http\Uri', array(), array(), '', FALSE);

		$request->expects($this->once())->method('getRequestUri')->will($this->returnValue($uri));
		$uri->expects($this->once())->method('getPath')->will($this->returnValue('/some/nice/path/to/index.php'));

		$requestPattern = new \TYPO3\FLOW3\Security\RequestPattern\Uri();
		$requestPattern->setPattern('/some/nice/.*');

		$this->assertTrue($requestPattern->matchRequest($request));
	}
}
?>