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

use TYPO3\Flow\Error\Debugger;

/**
 * Testcase for the Debugger
 *
 */
class DebuggerTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function renderingClosuresWorksWithoutThrowingException() {
		Debugger::renderDump(function() {}, 0);
		// dummy assertion to avoid PHPUnit warning
		$this->assertTrue(TRUE);
	}

	/**
	 * @test
	 */
	public function considersProxyClassWhenIsProxyPropertyIsPresent() {
		$object = new \stdClass();
		$object->__IS_PROXY__ = TRUE;
		$this->assertRegExp('/\sclass=\"debug\-proxy\"/', Debugger::renderDump($object, 0, FALSE));
	}

}
