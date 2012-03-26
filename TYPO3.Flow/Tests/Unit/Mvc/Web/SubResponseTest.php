<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc\Web;

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
 * Testcase for the MVC Web SubResponse class
 *
 */
class SubResponseTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function constructorSetsParentResponse() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$subResponse = new \TYPO3\FLOW3\Mvc\Web\SubResponse($mockResponse);
		$this->assertSame($mockResponse, $subResponse->getParentResponse());
	}

	/**
	 * @test
	 */
	public function setStatusSetsStatusOfParentResponse() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$mockResponse->expects($this->once())->method('setStatus')->with('SomeStatusCode', 'SomeStatusMessage');
		$subResponse = new \TYPO3\FLOW3\Mvc\Web\SubResponse($mockResponse);
		$subResponse->setStatus('SomeStatusCode', 'SomeStatusMessage');
	}

	/**
	 * @test
	 */
	public function setHeaderSetsHeaderOfParentResponse() {
		$mockResponse = $this->getMock('TYPO3\FLOW3\Mvc\Web\Response');
		$mockResponse->expects($this->once())->method('setHeader')->with('SomeName', 'SomeValue', FALSE);
		$subResponse = new \TYPO3\FLOW3\Mvc\Web\SubResponse($mockResponse);
		$subResponse->setHeader('SomeName', 'SomeValue', FALSE);
	}
}
?>