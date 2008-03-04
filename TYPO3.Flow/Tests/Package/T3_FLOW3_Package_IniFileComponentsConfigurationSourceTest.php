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
 * Testcase for the .conf file package components configuration source
 *
 * @package		TYPO3
 * @version 	$Id:T3_FLOW3_Package_IniFileComponentsConfigurationSourceTest.php 201 2007-03-30 11:18:30Z robert $
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class T3_FLOW3_Package_IniFileComponentsConfigurationSourceTest extends T3_Testing_BaseTestCase {

	/**
	 * Checks if getComponentConfigurations() returns an object equal to the stored fixture
	 *
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentConfigurationsMatchesFixtureOfTestPackage() {
		$configurationSource = $this->componentManager->getComponent('T3_FLOW3_Package_IniFileComponentsConfigurationSource');
		$package = $this->componentManager->getComponent('T3_FLOW3_Package_Package', 'TestPackage', FLOW3_PATH_PACKAGES . 'TestPackage/', array($configurationSource));

		$componentConfigurations = $configurationSource->getComponentConfigurations($package, array());
		foreach ($componentConfigurations as $componentName => $componentConfiguration) {
			$componentConfigurations[$componentName]->setConfigurationSourceHint('emptied_for_fixture');
		}
			// Uncomment to update the fixture:
#		file_put_contents(dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_Package_IniFileComponentsConfigurationSourceTest_componentConfigurations.dat', serialize($componentConfigurations));
		$componentConfigurationsFixture = unserialize(file_get_contents(dirname(__FILE__) . '/../Fixtures/T3_FLOW3_Fixture_Package_IniFileComponentsConfigurationSourceTest_componentConfigurations.dat'));
		$this->assertEquals($componentConfigurationsFixture, $componentConfigurations, 'Component configuration object of package "TestPackage" is not as expected. (Maybe the fixture needs an update?)');
	}
}
?>