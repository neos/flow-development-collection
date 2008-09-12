<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Configuration::Source;

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
 * @version $Id:F3::FLOW3::Component::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the PHP configuration source
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class PHPTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function returnsEmptyContainerOnNonExistingFile() {
		$configuration = F3::FLOW3::Configuration::Source::PHP::load('/ThisFileDoesNotExist.php');
		$this->assertEquals(new F3::FLOW3::Configuration::Container(), $configuration, 'No empty container was returned.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionSetInTheConfigurationFileReallyEndsUpInTheContainer() {
		$pathAndFilename = __DIR__ . '/../../Fixtures/F3_FLOW3_Fixture_Configuration_PHPConfigurationFile.php';
		$configuration = F3::FLOW3::Configuration::Source::PHP::load($pathAndFilename);
		$this->assertTrue($configuration->configurationFileHasBeenLoaded, 'The option has not been set by the fixture.');
	}
}
?>