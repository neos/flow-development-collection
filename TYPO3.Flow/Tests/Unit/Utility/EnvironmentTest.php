<?php
namespace TYPO3\Flow\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Core\ApplicationContext;

/**
 * Testcase for the Utility Environment class
 */
class EnvironmentTest extends \TYPO3\Flow\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getMaximumPathLengthReturnsCorrectValue() {
		$environment = new \TYPO3\Flow\Utility\Environment(new ApplicationContext('Testing'));
		$expectedValue = PHP_MAXPATHLEN;
		if ((integer)$expectedValue <= 0) {
			$this->fail('The PHP Constant PHP_MAXPATHLEN is not available on your system! Please file a PHP bug report.');
		}
		$this->assertEquals($expectedValue, $environment->getMaximumPathLength());
	}

}
