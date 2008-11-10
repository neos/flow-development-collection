<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Configuration;

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
 * Testcase for the configuration manager
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id:F3::FLOW3::Component::ConfigurationTest.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class ManagerTest extends F3::Testing::BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3SettingsLoadsBaseSettingsOfTheFLOW3Package() {
		$someSettings = array('option1' => 'value1');

		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(3))->method('load')->will($this->onConsecutiveCalls($someSettings, array(), array()));

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadFLOW3Settings();

		$actualSettings = $manager->getSettings('FLOW3');
		$this->assertEquals('value1', $actualSettings['option1']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3SettingsMergesTheFLOW3BaseSettingsWithTheGlobalFLOW3SettingsAndTheContextFLOW3Settings() {
		$baseSettings = array();
		$baseSettings['lastLoaded'] = 'baseSettings';
		$baseSettings['baseSettings'] = TRUE;
		$baseSettings['globalSettings'] = FALSE;
		$baseSettings['contextSettings'] = FALSE;

		$globalSettings = array();
		$globalSettings['lastLoaded'] = 'globalSettings';
		$globalSettings['globalSettings'] = TRUE;

		$contextSettings = array();
		$contextSettings['lastLoaded'] = 'contextSettings';
		$contextSettings['contextSettings'] = TRUE;

		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(3))->method('load')->will($this->onConsecutiveCalls($baseSettings, $globalSettings, $contextSettings));

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadFLOW3Settings();

		$actualSettings = $manager->getSettings('FLOW3');
		$this->assertEquals('contextSettings', $actualSettings['lastLoaded']);
		$this->assertTrue($actualSettings['baseSettings']);
		$this->assertTrue($actualSettings['globalSettings']);
		$this->assertTrue($actualSettings['contextSettings']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettingsLoadsSettingsOfAllSpecifiedPackagesByCallingTheConfigurationSource() {
		$someSettings = array();
		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnValue($someSettings));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadGlobalSettings($packageKeys);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettingsMergesAllLoadedSettingsWhichThenCanBeRetrievedWithGetSettings() {
		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnCallback(array($this, 'packageSettingsCallback')));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadGlobalSettings($packageKeys);

		$actualSettings = $manager->getSettings('PackageA');
		$this->assertEquals('A', $actualSettings['foo']);
		$this->assertEquals('C', $actualSettings['bar']);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadRoutesSettingsLoadsRoutesOfAllSpecifiedPackagesByCallingTheConfigurationSource() {
		$someSettings = array();
		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->exactly(5))->method('load')->will($this->returnValue($someSettings));

		$packageKeys = array('PackageA', 'PackageB', 'PackageC');

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadRoutesSettings($packageKeys);
	}

	/**
	 * Callback for the above test.
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function packageSettingsCallback() {
		$filenameAndPath = func_get_arg(0);

		$settingsA = array(
			'PackageA' => array(
				'foo' => 'A',
				'bar' => 'A'
			)
		);

		$settingsB = array(
			'PackageA' => array(
				'bar' => 'B'
			),
			'PackageB' => array(
				'foo' => 'B',
				'bar' => 'B'
			)
		);

		$settingsC = array(
			'PackageA' => array(
				'bar' => 'C'
			),
			'PackageC' => array(
				'baz' => 'C'
			)
		);

		switch ($filenameAndPath) {
			case FLOW3_PATH_PACKAGES . 'PackageA/Configuration/Settings' : return $settingsA;
			case FLOW3_PATH_PACKAGES . 'PackageB/Configuration/Settings' : return $settingsB;
			case FLOW3_PATH_PACKAGES . 'PackageC/Configuration/Settings' : return $settingsC;
			case FLOW3_PATH_CONFIGURATION . 'Settings' : return array();
			case FLOW3_PATH_CONFIGURATION . 'Testing/Settings' : return array();
			default:
				throw new Exception('Unexpected filename: ' . $filenameAndPath);
		}
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsReturnsAnArray() {
		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$settings = $manager->getSettings('SomePackage');
		$this->assertTrue(is_array($settings));
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettingsReturnsTheSettingsOfTheSpecifiedPackage() {
		$expectedSettings = array('TestPackage' => array('has' => array('several' => array('options' => 'and values'))));

		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));
		$mockConfigurationSource->expects($this->any())->method('load')->will($this->returnValue($expectedSettings));

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->loadGlobalSettings(array('TestPackage'));

		$actualSettings = $manager->getSettings('TestPackage');
		$this->assertEquals($expectedSettings['TestPackage'], $actualSettings);
	}

	/**
	 * @test
	 * @expectedException F3::FLOW3::Configuration::Exception::InvalidConfigurationType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfigurationOnlySupportsSpecialConfigurationTypes() {
		$mockConfigurationSource = $this->getMock('F3::FLOW3::Configuration::SourceInterface', array('load'));

		$manager = new F3::FLOW3::Configuration::Manager('Testing', array($mockConfigurationSource));
		$manager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');
	}
}
?>