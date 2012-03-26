<?php
namespace TYPO3\FLOW3\Tests\Unit\Mvc;

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
 * Testcase for the MVC Generic Response
 *
 */
class ResponseTest extends \TYPO3\FLOW3\Tests\UnitTestCase {
	/**
	 * @test
	 */
	public function toStringReturnsContentOfResponse() {
		$response = new \TYPO3\FLOW3\Mvc\Response();
		$response->setContent('SomeContent');

		$expected = 'SomeContent';
		$actual = $response->__toString();
		$this->assertEquals($expected, $actual);
	}
}