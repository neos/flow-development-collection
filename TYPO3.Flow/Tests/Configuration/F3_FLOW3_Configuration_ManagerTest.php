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
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Testcase for the configuration manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3_FLOW3_Component_ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Configuration_ManagerTest extends F3_Testing_BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3SettingsLoadsBaseSettingsOfTheFLOW3Package() {
		$someSettings = new F3_FLOW3_Configuration_Container();
		$someSettings->option1 = 'value1';

		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(3))->method('load')->will($this->onConsecutiveCalls($someSettings, new F3_FLOW3_Configuration_Container(), new F3_FLOW3_Configuration_Container()));

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadFLOW3Settings();

		$actualSettings = $manager->getSettings('FLOW3');
		$this->assertEquals('value1', $actualSettings->option1);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3SettingsMergesTheFLOW3BaseSettingsWithTheGlobalFLOW3SettingsAndTheContextFLOW3Settings() {
		$baseSettings = new F3_FLOW3_Configuration_Container();
		$baseSettings->lastLoaded = 'baseSettings';
		$baseSettings->baseSettings = TRUE;

		$globalSettings = new F3_FLOW3_Configuration_Container();
		$globalSettings->lastLoaded = 'globalSettings';
		$globalSettings->globalSettings = TRUE;

		$contextSettings = new F3_FLOW3_Configuration_Container();
		$contextSettings->lastLoaded = 'contextSettings';
		$contextSettings->contextSettings = TRUE;

		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(3))->method('load')->will($this->onConsecutiveCalls($baseSettings, $globalSettings, $contextSettings));

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadFLOW3Settings();

		$actualSettings = $manager->getSettings('FLOW3');
		$this->assertEquals('contextSettings', $actualSettings->lastLoaded);
		$this->assertTrue($actualSettings->baseSettings);
		$this->assertTrue($actualSettings->globalSettings);
		$this->assertTrue($actualSettings->contextSettings);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettingsLoadsSettingsOfAllSpecifiedPackagesByCallingTheConfigurationSource() {
		$someSettings = new F3_FLOW3_Configuration_Container();
		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnValue($someSettings));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadGlobalSettings($packageKeys);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettingsMergesAllLoadedSettingsWhichThenCanBeRetrievedWithGetSettings() {
		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadGlobalSettings($packageKeys);

		$actualSettings = $manager->getSettings('PackageA');
		$this->assertEquals('A', $actualSettings->foo);
		$this->assertEquals('C', $actualSettings->bar);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadRoutesSettingsLoadsRoutesOfAllSpecifiedPackagesByCallingTheConfigurationSource() {
		$someSettings = new F3_FLOW3_Configuration_Container();
		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnValue($someSettings));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadRoutesSettings($packageKeys);
	}

	/**
	 * Callback for the above test.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function packageSettingsCallback() {
		$filenameAndPath = func_get_arg(0);

		$settingsA = new F3_FLOW3_Configuration_Container();
		$settingsA->PackageA->foo = 'A';
		$settingsA->PackageA->bar = 'A';

		$settingsB = new F3_FLOW3_Configuration_Container();
		$settingsB->PackageB->foo = 'B';
		$settingsB->PackageA->bar = 'B';

		$settingsC = new F3_FLOW3_Configuration_Container();
		$settingsC->PackageC->baz = 'C';
		$settingsC->PackageA->bar = 'C';

		switch ($filenameAndPath) {
			case FLOW3_PATH_PACKAGES . 'PackageA/Configuration/Settings.php' : return $settingsA;
			case FLOW3_PATH_PACKAGES . 'PackageB/Configuration/Settings.php' : return $settingsB;
			case FLOW3_PATH_PACKAGES . 'PackageC/Configuration/Settings.php' : return $settingsC;
			case FLOW3_PATH_CONFIGURATION . 'Settings.php' : return new F3_FLOW3_Configuration_Container();
			case FLOW3_PATH_CONFIGURATION . 'Testing/Settings.php' : return new F3_FLOW3_Configuration_Container();
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsReturnsALockedConfigurationContainer() {
		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$settings = $manager->getSettings('SomePackage');
		$this->assertTrue($settings->isLocked());
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsReturnsTheSettingsOfTheSpecifiedPackage() {
		$expectedSettings = new F3_FLOW3_Configuration_Container();
		$expectedSettings->TestPackage->has->several->options = 'and values';

		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnValue($expectedSettings));

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->loadGlobalSettings(array('TestPackage'));

		$actualSettings = $manager->getSettings('TestPackage');
		$this->assertEquals($expectedSettings->TestPackage, $actualSettings);
	}

	/**
	 * @test
	 * @expectedException F3_FLOW3_Configuration_Exception_InvalidConfigurationType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfigurationOnlySupportsSpecialConfigurationTypes() {
		$mockConfigurationSource = $this->getMock('F3_FLOW3_Configuration_SourceInterface', array('load'));

		$manager = new F3_FLOW3_Configuration_Manager('Testing', $mockConfigurationSource);
		$manager->getSpecialConfiguration(F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');
	}
}
?>