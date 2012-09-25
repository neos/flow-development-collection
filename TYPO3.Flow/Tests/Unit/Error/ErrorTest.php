<?php
namespace TYPO3\Flow\Tests\Unit\Error;

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
 * Testcase for the Error object
 *
 */
class ErrorTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function theConstructorSetsTheErrorMessageCorrectly() {
		$errorMessage = 'The message';
		$error = new \TYPO3\Flow\Error\Error($errorMessage, 0);

		$this->assertEquals($errorMessage, $error->getMessage());
	}

	/**
	 * @test
	 */
	public function theConstructorSetsTheErrorCodeCorrectly() {
		$errorCode = 123456789;
		$error = new \TYPO3\Flow\Error\Error('', $errorCode);

		$this->assertEquals($errorCode, $error->getCode());
	}
}

?>