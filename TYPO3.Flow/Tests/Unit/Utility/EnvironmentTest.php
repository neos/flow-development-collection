<?php
namespace TYPO3\FLOW3\Tests\Unit\Utility;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Core\ApplicationContext;

/**
 * Testcase for the Utility Environment class
 */
class EnvironmentTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getPathToTemporaryDirectoryReturnsPathWithTrailingSlash() {
		$environment = new \TYPO3\FLOW3\Utility\Environment(new ApplicationContext('Testing'));
		$environment->setTemporaryDirectoryBase(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3EnvironmentTest')));
		$path = $environment->getPathToTemporaryDirectory();
		$this->assertEquals('/', substr($path, -1, 1), 'The temporary path did not end with slash.');
	}

	/**
	 * @test
	 */
	public function getPathToTemporaryDirectoryReturnsAnExistingPath() {
		$environment = new \TYPO3\FLOW3\Utility\Environment(new ApplicationContext('Testing'));
		$environment->setTemporaryDirectoryBase(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array(sys_get_temp_dir(), 'FLOW3EnvironmentTest')));

		$path = $environment->getPathToTemporaryDirectory();
		$this->assertTrue(file_exists($path), 'The temporary path does not exist.');
	}

	/**
	 * @test
	 */
	public function getMaximumPathLengthReturnsCorrectValue() {
		$environment = new \TYPO3\FLOW3\Utility\Environment(new ApplicationContext('Testing'));
		$expectedValue = PHP_MAXPATHLEN;
		if ((integer)$expectedValue <= 0) {
			$this->fail('The PHP Constant PHP_MAXPATHLEN is not available on your system! Please file a PHP bug report.');
		}
		$this->assertEquals($expectedValue, $environment->getMaximumPathLength());
	}

}
?>