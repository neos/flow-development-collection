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
 * @subpackage Configuration
 * @version $Id$
 */

/**
 * Those are needed before the autoloader is active
 */
require_once(FLOW3_PATH_FLOW3 . 'Configuration/F3_FLOW3_Configuration_Container.php');
require_once(FLOW3_PATH_FLOW3 . 'Configuration/F3_FLOW3_Configuration_SourceInterface.php');
require_once(FLOW3_PATH_FLOW3 . 'Configuration/Source/F3_FLOW3_Configuration_Source_PHP.php');

/**
 * A general purpose configuration manager
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Configuration_Manager {

	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_PACKAGES = 'Packages';
	const CONFIGURATION_TYPE_COMPONENTS = 'Components';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';

	/**
	 * @var string The application context of the configuration to manage
	 */
	protected $context;

	/**
	 * Storage of all settings, loaded by loadSettings()
	 *
	 * @var F3_FLOW3_Configuration_Container
	 */
	protected $settings;

	/**
	 * The configuration source used for loading the raw configuration
	 *
	 * @var F3_FLOW3_Configuration_SourceInterface
	 */
	protected $configurationSource;

	/**
	 * Constructs the configuration manager
	 *
	 * @param string $context The application context to fetch configuration for.
	 * @param F3_FLOW3_Configuration_SourceInterface $configurationSource The configuration source
	 */
	public function __construct($context, F3_FLOW3_Configuration_SourceInterface $configurationSource) {
		$this->context = $context;
		$this->configurationSource = $configurationSource;
		$this->settings = new F3_FLOW3_Configuration_Container;
	}

	/**
	 * Loads the FLOW3 core settings defined in the FLOW3 package and the global
	 * configuration directories.
	 *
	 * The FLOW3 settings can be retrieved like any other setting through the
	 * getSettings() method but need to be loaded separately because they are
	 * needed way earlier in the bootstrap than the package's settings.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3Settings() {
		$this->settings->FLOW3->mergeWith($this->configurationSource->load(FLOW3_PATH_PACKAGES . 'FLOW3/Configuration/FLOW3.php'));
		$this->settings->FLOW3->mergeWith($this->configurationSource->load(FLOW3_PATH_CONFIGURATION . 'FLOW3.php'));
		$this->settings->FLOW3->mergeWith($this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/FLOW3.php'));
	}

	/**
	 * Loads the settings defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's settings registry
	 * and can be retrieved with the getSettings() method.
	 *
	 * @param array $packageKeys
	 * @return void
	 * @see getSettings()
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadSettings(array $packageKeys) {
		foreach ($packageKeys as $packageKey) {
			$this->settings->mergeWith($this->configurationSource->load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/Settings.php'));
		}
		$this->settings->mergeWith($this->configurationSource->load(FLOW3_PATH_CONFIGURATION . 'Settings.php'));
		$this->settings->mergeWith($this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/Settings.php'));
	}

	/**
	 * Returns a configuration container with the settings defined for the specified
	 * package.
	 *
	 * @param string $packageKey Key of the package to return the settings for
	 * @return F3_FLOW3_Configuration_Container The settings of the specified package
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettings($packageKey) {
		if ($this->settings->offsetExists($packageKey)) {
			$settingsContainer = $this->settings->$packageKey;
		} else {
			$settingsContainer = new F3_FLOW3_Configuration_Container();
		}
		$settingsContainer->lock();
		return $settingsContainer;
	}

	/**
	 * Loads and returns the specified raw configuration. The actual configuration will be
	 * merged from different sources in a defined order.
	 *
	 * Note that this is a very low level method and usually only makes sense to be used
	 * by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey Key of the package the configuration is for
	 * @return F3_FLOW3_Configuration_Container The configuration
	 * @throws F3_FLOW3_Configuration_Exception_InvalidConfigurationType on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfiguration($configurationType, $packageKey= 'FLOW3') {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGES :
			case self::CONFIGURATION_TYPE_COMPONENTS :
			case self::CONFIGURATION_TYPE_ROUTES :
				break;
			default:
				throw new F3_FLOW3_Configuration_Exception_InvalidConfigurationType('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}

		$configuration = $this->configurationSource->load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/' . $configurationType . '.php');
		if (file_exists(FLOW3_PATH_CONFIGURATION . $configurationType . '.php')) {
			$additionalConfiguration = $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType . '.php');
			$configuration->mergeWith($additionalConfiguration);
		}
		if (file_exists(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType . '.php')) {
			$additionalConfiguration = $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType . '.php');
			$configuration->mergeWith($additionalConfiguration);
		}

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_COMPONENTS :
			case self::CONFIGURATION_TYPE_ROUTES :
				$configuration->lock();
				return $configuration;
			case self::CONFIGURATION_TYPE_PACKAGES :
			case self::CONFIGURATION_TYPE_SETTINGS :
				$configuration->$packageKey->lock();
				return $configuration->$packageKey;
		}
	}
}
?>
