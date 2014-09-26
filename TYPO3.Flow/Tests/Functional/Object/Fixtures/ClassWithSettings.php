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
class ClassWithSettings {

	/**
	 * @Flow\InjectSettings(path="some.nonExisting.setting")
	 * @var string
	 */
	protected $nonExistingSetting;

	/**
	 * @Flow\InjectSettings(path="tests.functional.settingInjection.someSetting")
	 * @var string
	 */
	protected $injectedSettingA;

	/**
	 * @Flow\InjectSettings(path="tests.functional.settingInjection.someSetting", package="TYPO3.Flow")
	 * @var string
	 */
	protected $injectedSettingB;

	/**
	 * @Flow\InjectSettings(package="TYPO3.Flow")
	 * @var array
	 */
	protected $injectedSpecifiedPackageSettings;

	/**
	 * @Flow\InjectSettings
	 * @var array
	 */
	protected $injectedCurrentPackageSettings;

	/**
	 * @var array
	 */
	protected $settings;

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


}
