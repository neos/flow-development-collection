<?php
namespace TYPO3\Eel\Helper;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Eel".             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Configuration\ConfigurationManager;

/**
 * Configuration helpers for Eel contexts
 */
class ConfigurationHelper implements ProtectedContextAwareInterface {

	/**
	 * @Flow\Inject
	 * @var ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Return the specified settings
	 *
	 * Examples::
	 *
	 *     Configuration.setting('TYPO3.Flow.core.context') == 'Production'
	 *
	 *     Configuration.setting('Acme.Demo.speedMode') == 'light speed'
	 *
	 * @param string $settingPath
	 * @return mixed
	 */
	public function setting($settingPath) {
		return $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $settingPath);
	}

	/**
	 * All methods are considered safe
	 *
	 * @param string $methodName
	 * @return boolean
	 */
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
