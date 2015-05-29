<?php
namespace TYPO3\Flow\Tests\Unit\Mvc;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
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
class ResponseTest extends \TYPO3\Flow\Tests\UnitTestCase {
	/**
	 * @test
	 */
	public function toStringReturnsContentOfResponse() {
		$response = new \TYPO3\Flow\Mvc\Response();
		$response->setContent('SomeContent');

		$expected = 'SomeContent';
		$actual = $response->__toString();
		$this->assertEquals($expected, $actual);
	}
}