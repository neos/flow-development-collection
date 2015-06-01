<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class for testing setting injection
 */
class ClassWithInjectedConfiguration {

	/**
	 * @Flow\InjectConfiguration(path="some.nonExisting.setting")
	 * @var string
	 */
	protected $nonExistingSetting = 'defaultValue';

	/**
	 * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting")
	 * @var string
	 */
	protected $injectedSettingA;

	/**
	 * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting", package="TYPO3.Flow")
	 * @var string
	 */
	protected $injectedSettingB;

	/**
	 * @Flow\InjectConfiguration(path="tests.functional.settingInjection.someSetting", package="TYPO3.Flow")
	 * @var string
	 */
	protected $injectedSettingWithSetter;

	/**
	 * @Flow\InjectConfiguration(package="TYPO3.Flow")
	 * @var array
	 */
	protected $injectedSpecifiedPackageSettings;

	/**
	 * @Flow\InjectConfiguration
	 * @var array
	 */
	protected $injectedCurrentPackageSettings;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @Flow\InjectConfiguration(type="Views")
	 * @var array
	 */
	protected $injectedViewsConfiguration;

	/**
	 * @Flow\Inject(setting="tests.functional.settingInjection.someSetting", package="TYPO3.Flow")
	 * @var string
	 */
	protected $legacySetting;

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @return string
	 */
	public function getNonExistingSetting() {
		return $this->nonExistingSetting;
	}

	/**
	 * @return string
	 */
	public function getInjectedSettingA() {
		return $this->injectedSettingA;
	}

	/**
	 * @return string
	 */
	public function getInjectedSettingB() {
		return $this->injectedSettingB;
	}

	/**
	 * @return string
	 */
	public function getInjectedSettingWithSetter() {
		return $this->injectedSettingWithSetter;
	}

	/**
	 * @param string $injectedSettingWithSetter
	 * @return void
	 */
	public function setInjectedSettingWithSetter($injectedSettingWithSetter) {
		$this->injectedSettingWithSetter = strtoupper($injectedSettingWithSetter);
	}

	/**
	 * @return array
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * @return array
	 */
	public function getInjectedSpecifiedPackageSettings() {
		return $this->injectedSpecifiedPackageSettings;
	}

	/**
	 * @return array
	 */
	public function getInjectedCurrentPackageSettings() {
		return $this->injectedCurrentPackageSettings;
	}

	/**
	 * @return array
	 */
	public function getInjectedViewsConfiguration() {
		return $this->injectedViewsConfiguration;
	}

	/**
	 * @return string
	 */
	public function getLegacySetting() {
		return $this->legacySetting;
	}

}
