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
 * @subpackage Configuration
 * @version $Id$
 */

/**
 * A general purpose configuration manager
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license GNU Public License, version 2
 */
class Manager {

	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_PACKAGES = 'Packages';
	const CONFIGURATION_TYPE_COMPONENTS = 'Objects';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';

	/**
	 * @var string The application context of the configuration to manage
	 */
	protected $context;

	/**
	 * Storage for the settings, loaded by loadGlobalSettings()
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Storage of the raw routing configuration
	 *
	 * @var array
	 */
	protected $routes = array();

	/**
	 * The configuration sources used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources;

	/**
	 * Constructs the configuration manager
	 *
	 * @param string $context The application context to fetch configuration for.
	 * @param array $configurationSources An array of configuration sources
	 */
	public function __construct($context, array $configurationSources) {
		$this->context = $context;
		$this->configurationSources = $configurationSources;
	}

	/**
	 * Returns an array with the settings defined for the specified package.
	 *
	 * @param string $packageKey Key of the package to return the settings for
	 * @return array The settings of the specified package
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettings($packageKey) {
		if (isset($this->settings[$packageKey])) {
			$settings = $this->settings[$packageKey];
		} else {
			$settings = array();
		}
		return $settings;
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
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3Settings() {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_PACKAGES . 'FLOW3/Configuration/FLOW3'));
		}

		foreach ($this->configurationSources as $configurationSource) {
			$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'FLOW3', TRUE));
			$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/FLOW3', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings['FLOW3'] = $settings;
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
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettings(array $packageKeys) {
		$settings = array();
		sort ($packageKeys);
		$index = array_search('FLOW3', $packageKeys);
		if ($index !== FALSE) {
			unset ($packageKeys[$index]);
			array_unshift($packageKeys, 'FLOW3');
		}
		foreach ($packageKeys as $packageKey) {
			foreach ($this->configurationSources as $configurationSource) {
				$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/Settings'));
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'Settings', TRUE));
			$settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/Settings', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($this->settings, $settings);
	}

	/**
	 * Loads the routing settings defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's routes registry
	 * and can be retrieved with the getSpecialConfiguration() method. However note
	 * that this is only the raw information which will be further processed by the
	 * Web Request Builder.
	 *
	 * @param array $packageKeys
	 * @return void
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadRoutesSettings(array $packageKeys) {
		sort ($packageKeys);
		$index = array_search('FLOW3', $packageKeys);
		if ($index !== FALSE) {
			unset ($packageKeys[$index]);
			array_unshift($packageKeys, 'FLOW3');
		}
		foreach ($packageKeys as $packageKey) {
			foreach ($this->configurationSources as $configurationSource) {
				$this->routes = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($this->routes, $configurationSource->load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/Routes'));
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->routes = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($this->routes, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'Routes'));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->routes = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($this->routes, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/Routes'));
		}
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
	 * @return array The configuration
	 * @throws F3::FLOW3::Configuration::Exception::InvalidConfigurationType on invalid configuration types
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfiguration($configurationType, $packageKey = 'FLOW3') {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ROUTES :
				return $this->routes;
			case self::CONFIGURATION_TYPE_PACKAGES :
			case self::CONFIGURATION_TYPE_COMPONENTS :
				break;
			default:
				throw new F3::FLOW3::Configuration::Exception::InvalidConfigurationType('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}
		$configuration = array();
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW3_PATH_PACKAGES . $packageKey . '/Configuration/' . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = F3::FLOW3::Utility::Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGES :
				return (isset($configuration[$packageKey])) ? $configuration[$packageKey] : array();
			case self::CONFIGURATION_TYPE_COMPONENTS :
				return $configuration;
		}
	}

	/**
	 * Post processes the given settings array by replacing constants with their
	 * actual value.
	 *
	 * This is a preliminary solution, we'll surely have some better way to handle
	 * this soon.
	 *
	 * @param array &$settings The settings to post process. The results are stored directly in the given array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function postProcessSettings(&$settings) {
		foreach ($settings as $key => $setting) {
			if (is_array($setting)) {
				$this->postProcessSettings($settings[$key]);
			} elseif (is_string($setting)) {
				$matches = array();
				preg_match_all('/(?:%)([a-zA-Z_0-9]+)(?:%)/', $setting, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $settings[$key] = str_replace('%' . $match . '%', constant($match), $settings[$key]);
					}
				}
			}
		}
	}
}
?>