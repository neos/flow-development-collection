<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A general purpose configuration manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Manager {

	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_PACKAGE = 'Package';
	const CONFIGURATION_TYPE_PACKAGE_STATES = 'PackageStates';
	const CONFIGURATION_TYPE_OBJECTS = 'Objects';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';
	const CONFIGURATION_TYPE_SIGNALSSLOTS = 'SignalsSlots';
	const CONFIGURATION_TYPE_CACHES = 'Caches';

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

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
	 * Storage of the raw special configurations
	 *
	 * @var array
	 */
	protected $configurations = array(
		'Routes' => array(),
		'SignalsSlots' => array(),
		'Caches' => array()
	);

	/**
	 * The configuration sources used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources;

	/**
	 * A single writable configuration source
	 *
	 * @var \F3\FLOW3\Configuration\Source\WritableSourceInterface
	 */
	protected $writableConfigurationSource;

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
	 * Injects the package manager
	 *
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\F3\FLOW3\Package\ManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Returns an array with the settings defined for the specified package.
	 *
	 * @param string $packageKey Key of the package to return the settings for
	 * @return array The settings of the specified package
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3Settings() {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/FLOW3'));
		}

		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'FLOW3', TRUE));
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/FLOW3', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings['FLOW3'] = $settings;
		$this->settings['FLOW3']['core']['context'] = $this->context;
	}

	/**
	 * Loads the settings defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's settings registry
	 * and can be retrieved with the getSettings() method.
	 *
	 * @param array $packages An array of Package object
	 * @return void
	 * @see getSettings()
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettings(array $packages) {
		$settings = array();
		if (isset($packages['FLOW3'])) unset ($packages['FLOW3']);

		foreach ($packages as $package) {
			foreach ($this->configurationSources as $configurationSource) {
				$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load($package->getConfigurationPath() . 'Settings'));
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'Settings', TRUE));
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/Settings', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->settings, $settings);
	}

	/**
	 * Loads special configuration defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's configuration registry
	 * and can be retrieved with the getSpecialConfiguration() method. However note
	 * that this is only the raw information which will be further processed by other
	 * parts of FLOW3
	 *
	 * @param string $configurationType The kind of configuration to load - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $packages An array of Package objects to consider
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function loadSpecialConfiguration($configurationType, array $packages) {
		if ($configurationType === self::CONFIGURATION_TYPE_ROUTES) {
			$subRoutesConfiguration = array();
			foreach ($packages as $packageKey => $package) {
				$subRoutesConfiguration[$packageKey] = array();
				foreach ($this->configurationSources as $configurationSource) {
					$subRoutesConfiguration[$packageKey] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($subRoutesConfiguration[$packageKey], $configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
			}
		} else {
			foreach ($packages as $packageKey => $package) {
				foreach ($this->configurationSources as $configurationSource) {
					$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}
		if ($configurationType === self::CONFIGURATION_TYPE_ROUTES) {
			$this->mergeRoutesWithSubRoutes($this->configurations[$configurationType], $subRoutesConfiguration);
		}
		$this->postProcessSettings($this->configurations[$configurationType]);
	}

	/**
	 * Loads and returns the specified raw configuration. The actual configuration will be
	 * merged from different sources in a defined order.
	 *
	 * Note that this is a very low level method and usually only makes sense to be used
	 * by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param \F3\FLOW3\Package\Package $package The package to return the configuration for
	 * @return array The configuration
	 * @throws \F3\FLOW3\Configuration\Exception\InvalidConfigurationType on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfiguration($configurationType, \F3\FLOW3\Package\Package $package = NULL) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ROUTES :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
			case self::CONFIGURATION_TYPE_CACHES :
				return $this->configurations[$configurationType];
			case self::CONFIGURATION_TYPE_PACKAGE :
			case self::CONFIGURATION_TYPE_OBJECTS :
				if (!is_object($package)) throw new \InvalidArgumentException('No package specified.', 1233336279);
				break;
			default :
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationType('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}
		$configuration = array();
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load($package->getConfigurationPath() . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$globalConfiguration = $configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType);
			if ($configurationType == self::CONFIGURATION_TYPE_PACKAGE) {
				$globalConfiguration = isset($globalConfiguration[$package->getPackageKey()]) ? $globalConfiguration[$package->getPackageKey()] : array();
			}
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $globalConfiguration);
		}
		foreach ($this->configurationSources as $configurationSource) {
			$contextConfiguration = $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType);
			if ($configurationType == self::CONFIGURATION_TYPE_PACKAGE) {
				$contextConfiguration = isset($contextConfiguration[$package->getPackageKey()]) ? $contextConfiguration[$package->getPackageKey()] : array();
			}
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $contextConfiguration);
		}

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGE :
			case self::CONFIGURATION_TYPE_OBJECTS :
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
	protected function postProcessSettings(array &$settings) {
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

	/**
	 * Loads specified sub routes and builds composite routes.
	 *
	 * @param array $routesConfiguration
	 * @param array $subRoutesConfiguration
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function mergeRoutesWithSubRoutes(array &$routesConfiguration, array $subRoutesConfiguration) {
		$mergedRoutesConfiguration = array();
		foreach ($routesConfiguration as $routeConfiguration) {
			if (!isset($routeConfiguration['subRoutes'])) {
				$mergedRoutesConfiguration[] = $routeConfiguration;
				continue;
			}
			$mergedSubRoutesConfiguration = array($routeConfiguration);
			foreach($routeConfiguration['subRoutes'] as $subRouteKey => $subRouteOptions) {
				if (!isset($subRouteOptions['package']) || !isset($subRoutesConfiguration[$subRouteOptions['package']])) {
					continue 2;
				}
				$packageSubRoutesConfiguration = $subRoutesConfiguration[$subRouteOptions['package']];
				$mergedSubRoutesConfiguration = $this->buildSubrouteConfigurations($mergedSubRoutesConfiguration, $packageSubRoutesConfiguration, $subRouteKey);
			}
			$mergedRoutesConfiguration = array_merge($mergedRoutesConfiguration, $mergedSubRoutesConfiguration);
		}
		$routesConfiguration = $mergedRoutesConfiguration;
	}

	/**
	 * Merges all routes in $routesConfiguration with the sub routes in $subRoutesConfiguration
	 *
	 * @param array $routesConfiguration
	 * @param array $subRoutesConfiguration
	 * @param string $subRouteKey the key of the sub route: <subRouteKey>
	 * @return array the merged route configuration
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function buildSubrouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, $subRouteKey) {
		$mergedSubRoutesConfiguration = array();
		foreach($subRoutesConfiguration as $subRouteConfiguration) {
			foreach($routesConfiguration as $routeConfiguration) {
				$name = isset($routeConfiguration['name']) ? $routeConfiguration['name'] : $routeConfiguration;
				$name .= ' :: ';
				$name .= isset($subRouteConfiguration['name']) ? $subRouteConfiguration['name'] : 'Subroute';
				$uriPattern = str_replace('<' . $subRouteKey . '>', $subRouteConfiguration['uriPattern'], $routeConfiguration['uriPattern']);
				$defaults = isset($routeConfiguration['defaults']) ? $routeConfiguration['defaults'] : array();
				if (isset($subRouteConfiguration['defaults'])) {
					$defaults = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($defaults, $subRouteConfiguration['defaults']);
				}
				$routeParts = isset($routeConfiguration['routeParts']) ? $routeConfiguration['routeParts'] : array();
				if (isset($subRouteConfiguration['routeParts'])) {
					$routeParts = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($routeParts, $subRouteConfiguration['routeParts']);
				}
				$mergedSubRoutesConfiguration[] = array(
					'name' => $name,
					'uriPattern' => $uriPattern,
					'defaults' => $defaults,
					'routeParts' => $routeParts
				);
			}
		}
		return $mergedSubRoutesConfiguration;
	}

	/**
	 * Get the package states configuration. This configuration is loaded
	 * from the configuration directory and will be overriden by contexts.
	 *
	 * @return array The package states configuration
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getPackageStatesConfiguration() {
		$configuration = $this->writableConfigurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_PACKAGE_STATES);
		return \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $this->writableConfigurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . self::CONFIGURATION_TYPE_PACKAGE_STATES));
	}

	/**
	 * Update the package states configuration
	 *
	 * @param array $configuration The package states configuration
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function updatePackageStatesConfiguration($configuration) {
		$this->writableConfigurationSource->save(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_PACKAGE_STATES, $configuration);
	}

	/**
	 * Set the writable configuration source. This source will be used
	 * for package states configuration and writing back values for
	 * package activation / deactivation from the package manager.
	 *
	 * @param \F3\FLOW3\Configuration\Source\WritableSourceInterface $writableConfigurationSource The writable source
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setWritableConfigurationSource(\F3\FLOW3\Configuration\Source\WritableSourceInterface $writableConfigurationSource) {
		$this->writableConfigurationSource = $writableConfigurationSource;
	}
}
?>