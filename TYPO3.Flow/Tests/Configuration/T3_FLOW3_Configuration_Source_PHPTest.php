<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the PHP configuration source
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:T3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Configuration_Source_PHPTest extends T3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function throwsExceptionOnNonExistingFile() {
		try {
			T3_FLOW3_Configuration_Source_PHP::load('/ThisFileDoesNotExist.php');
			$this->fail('No exception was thrown.');
		} catch (T3_FLOW3_Configuration_Exception_NoSuchFile $exception) {

		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionSetInTheConfigurationFileReallyEndsUpInTheContainer() {
		$pathAndFilename = dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_Configuration_PHPConfigurationFile.php';
		$configuration = T3_FLOW3_Configuration_Source_PHP::load($pathAndFilename);
		$this->assertTrue($configuration->configurationFileHasBeenLoaded, 'The option has not been set by the fixture.');
	}
}
?>