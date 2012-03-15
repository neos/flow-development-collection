<?php
namespace TYPO3\FLOW3\Tests\Unit\Core;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Core\Bootstrap;

/**
 * Testcase for the Bootstrap class
 */
class BootstrapTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @return array
	 */
	public function commandIdentifiersAndCompiletimeControllerInfo() {
		return array(
			array(array('typo3.flow3:core:shell', 'typo3.flow3:cache:flush'), 'typo3.flow3:core:shell', TRUE),
			array(array('typo3.flow3:core:shell', 'typo3.flow3:cache:flush'), 'flow3:core:shell', TRUE),
			array(array('typo3.flow3:core:shell', 'typo3.flow3:cache:flush'), 'core:shell', FALSE),
			array(array('typo3.flow3:core:*', 'typo3.flow3:cache:flush'), 'typo3.flow3:core:shell', TRUE),
			array(array('typo3.flow3:core:*', 'typo3.flow3:cache:flush'), 'flow3:core:shell', TRUE),
			array(array('typo3.flow3:core:shell', 'typo3.flow3:cache:flush'), 'typo3.flow3:help:help', FALSE),
			array(array('typo3.flow3:core:*', 'typo3.flow3:cache:*'), 'flow3:cache:flush', TRUE),
			array(array('typo3.flow3:core:*', 'typo3.flow3:cache:*'), 'flow5:core:shell', FALSE),
			array(array('typo3.flow3:core:*', 'typo3.flow3:cache:*'), 'typo3:core:shell', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider commandIdentifiersAndCompiletimeControllerInfo
	 */
	public function isCompileTimeCommandControllerChecksIfTheGivenCommandIdentifierRefersToACompileTimeController($compiletimeCommandControllerIdentifiers, $givenCommandIdentifier, $expectedResult) {
		$bootstrap = new Bootstrap('Testing');
		foreach ($compiletimeCommandControllerIdentifiers as $compiletimeCommandControllerIdentifier) {
			$bootstrap->registerCompiletimeCommand($compiletimeCommandControllerIdentifier);
		}

		$this->assertSame($expectedResult, $bootstrap->isCompiletimeCommand($givenCommandIdentifier));
	}

}
?>