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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ConfigurationManager {

	const CONFIGURATION_TYPE_CACHES = 'Caches';
	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_OBJECTS = 'Objects';
	const CONFIGURATION_TYPE_PACKAGE = 'Package';
	const CONFIGURATION_TYPE_PACKAGESTATES = 'PackageStates';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';
	const CONFIGURATION_TYPE_POLICY = 'Policy';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_SIGNALSSLOTS = 'SignalsSlots';

	/**
	 * The application context of the configuration to manage
	 * @var string
	 */
	protected $context;

	/**
	 * @var \F3\FLOW3\Configuration\Source\SourceInterface
	 */
	protected $configurationSource;

	/**
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var string
	 */
	protected $includeCachedConfigurationsPathAndFilename;

	/**
	 * Storage of the raw special configurations
	 * @var array
	 */
	protected $configurations = array(
		self::CONFIGURATION_TYPE_SETTINGS => array(),
	);

	/**
	 * Active packages to load the configuration for
	 * @var array <F3\FLOW3\Package\PackageInterface>
	 */
	protected $packages = array();

	/**
	 * @var boolean
	 */
	protected $cacheNeedsUpdate = FALSE;

	/**
	 * Constructs the configuration manager
	 *
	 * @param string $context The application context to fetch configuration for
	 */
	public function __construct($context) {
		$this->context = $context;
		$this->includeCachedConfigurationsPathAndFilename = FLOW3_PATH_CONFIGURATION . $context . '/IncludeCachedConfigurations.php';
		$this->loadConfigurationCache();
	}

	/**
	 * Injects the configuration source
	 *
	 * @param \F3\FLOW3\Configuration\Source\SourceInterface $configurationSource
	 * @return void
	 */
	public function injectConfigurationSource(\F3\FLOW3\Configuration\Source\SourceInterface $configurationSource) {
		$this->configurationSource = $configurationSource;
	}

	/**
	 * Injects the environment
	 *
	 * @param \F3\FLOW3\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\F3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the active packages to load the configuration for
	 *
	 * @param array<F3\FLOW3\Package\PackageInterface> $packages
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

	/**
	 * Returns the specified raw configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey Key of the package to return the configuration for
	 * @return array The configuration
	 * @throws \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfiguration($configurationType, $packageKey = NULL) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ROUTES :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_PACKAGESTATES :
			case self::CONFIGURATION_TYPE_POLICY :
				if (!isset($this->configurations[$configurationType])) {
					$this->loadConfiguration($configurationType, $this->packages);
				}
				return isset($this->configurations[$configurationType]) ? $this->configurations[$configurationType] : array();;

			case self::CONFIGURATION_TYPE_SETTINGS :
				if ($packageKey === NULL) {
					foreach ($this->packages as $package) {
						if (!isset($this->configurations[self::CONFIGURATION_TYPE_SETTINGS][$package->getPackageKey()])) {
							$this->loadConfiguration($configurationType, $this->packages);
						}
					}
					return $this->configurations[self::CONFIGURATION_TYPE_SETTINGS];
				}
			case self::CONFIGURATION_TYPE_PACKAGE :
			case self::CONFIGURATION_TYPE_OBJECTS :
				if ($packageKey === NULL) throw new \InvalidArgumentException('No package specified.', 1233336279);
				if (!isset($this->configurations[$configurationType][$packageKey])) {
					$this->loadConfiguration($configurationType, $this->packages);
				}
				return isset($this->configurations[$configurationType][$packageKey]) ? $this->configurations[$configurationType][$packageKey] : array();

			default :
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}
	}

	/**
	 * Sets the specified raw configuration.
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $configuration The new configuration
	 * @param string $packageKey The package key the configuration relates to, needed for CONFIGURATION_TYPE_SETTINGS
	 * @return void
	 * @throws \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@dambekalns.de>
	 */
	public function setConfiguration($configurationType, array $configuration, $packageKey = NULL) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGESTATES :
				$this->configurations[$configurationType] = $configuration;
				$this->cacheNeedsUpdate = TRUE;
			break;
			case self::CONFIGURATION_TYPE_SETTINGS :
				$this->configurations[$configurationType][$packageKey] = $configuration;
				$this->cacheNeedsUpdate = TRUE;
			break;
			default :
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1251127738);
		}
	}

	/**
	 * Saves configuration of the given configuration type back to the configuration file
	 * (if supported)
	 *
	 * @param string $configurationType The kind of configuration to save - must be one of the supported CONFIGURATION_TYPE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@dambekalns.de>
	 */
	public function saveConfiguration($configurationType) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGESTATES :
				$this->configurationSource->save(FLOW3_PATH_CONFIGURATION . $configurationType, $this->configurations[$configurationType]);
			break;
			case self::CONFIGURATION_TYPE_SETTINGS :
				$this->configurationSource->save(FLOW3_PATH_CONFIGURATION . $configurationType,
						$this->pickChangedSettings($this->configurations[$configurationType])
					);
			break;
			default :
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" does not support saving.', 1251127425);
		}
	}

	/**
	 *
	 * @param array $settings
	 * @return array
	 * @author Karsten Dambekalns <karsten@dambekalns.de>
	 */
	protected function pickChangedSettings(array $settings) {
		$changedSettings = array();
		$cleanSettings = $this->configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/' . self::CONFIGURATION_TYPE_SETTINGS);
		$cleanSettings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($cleanSettings, $this->configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/' . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS));
		foreach ($this->packages as $package) {
			$cleanSettings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($cleanSettings, $this->configurationSource->load($package->getConfigurationPath() . self::CONFIGURATION_TYPE_SETTINGS));
		}

		return \F3\FLOW3\Utility\Arrays::recursiveDiff($settings, $cleanSettings);
	}

	/**
	 * Shuts down the configuration manager.
	 * This method writes the current configuration into a cache file if FLOW3 was configured to do so.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdown() {
		if ($this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['configuration']['compileConfigurationFiles'] === TRUE && $this->cacheNeedsUpdate === TRUE) {
			$this->saveConfigurationCache();
		}
	}

	/**
	 * Loads special configuration defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders. The result is stored
	 * in the configuration manager's configuration registry and can be retrieved with the
	 * getConfiguration() method.
	 *
	 * @param string $configurationType The kind of configuration to load - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $packages An array of Package objects to consider
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function loadConfiguration($configurationType, array $packages) {
		$this->cacheNeedsUpdate = TRUE;

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_SETTINGS :
				if (count($packages) === 1 && isset($packages['FLOW3'])) {
					$this->configurations[$configurationType] = array();
					$settings = $this->configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/' . self::CONFIGURATION_TYPE_SETTINGS);
					$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/' . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS));
					$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_SETTINGS));
					$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS));

					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = $settings;
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['FLOW3']['core']['context'] = $this->context;
				} elseif (count($packages) > 1) {
					$settings = array();
					if (isset($packages['FLOW3'])) {
						unset ($packages['FLOW3']);
					}
					foreach ($packages as $packageKey => $package) {
						if (!isset($settings[$packageKey])) {
							$settings[$packageKey] = array();
						}
						$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . self::CONFIGURATION_TYPE_SETTINGS));
					}
					$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_SETTINGS, TRUE));
					$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . self::CONFIGURATION_TYPE_SETTINGS, TRUE));

					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = (!isset($this->configurations[self::CONFIGURATION_TYPE_SETTINGS])) ? $settings : \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[self::CONFIGURATION_TYPE_SETTINGS], $settings);
				}
			break;
			case self::CONFIGURATION_TYPE_OBJECTS :
			case self::CONFIGURATION_TYPE_PACKAGE :
				$this->configurations[$configurationType] = array();
				foreach ($packages as $packageKey => $package) {
					$configuration = array();
					$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load($package->getConfigurationPath() . $configurationType));

					$globalConfiguration = $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType);

					$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $globalConfiguration);
					$contextConfiguration = $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType);

					$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $contextConfiguration);
					$this->configurations[$configurationType][$packageKey] = $configuration;
				}
			break;
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
				$this->configurations[$configurationType] = array();
				foreach ($packages as $packageKey => $package) {
					$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
			break;
			case self::CONFIGURATION_TYPE_ROUTES :
				$this->configurations[$configurationType] = array();
				$subRoutesConfiguration = array();
				foreach ($packages as $packageKey => $package) {
					$subRoutesConfiguration[$packageKey] = array();
					$subRoutesConfiguration[$packageKey] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($subRoutesConfiguration[$packageKey], $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
			break;
			case self::CONFIGURATION_TYPE_PACKAGESTATES :
				$this->configurations[$configurationType] = array();
				$configuration = $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_PACKAGESTATES);
				$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . self::CONFIGURATION_TYPE_PACKAGESTATES));
				$this->configurations[$configurationType] = $configuration;
			break;
			default:
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" cannot be loaded with loadConfiguration().', 1251450613);
		}

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
			case self::CONFIGURATION_TYPE_ROUTES :
			case self::CONFIGURATION_TYPE_PACKAGESTATES :
				$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));
				$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}

		if ($configurationType === self::CONFIGURATION_TYPE_ROUTES) {
			$this->mergeRoutesWithSubRoutes($this->configurations[$configurationType], $subRoutesConfiguration);
		}

		$this->postProcessConfiguration($this->configurations[$configurationType]);
	}

	/**
	 * If a cache file with previously saved configuration exists, it is loaded.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function loadConfigurationCache() {
		if (file_exists($this->includeCachedConfigurationsPathAndFilename)) {
			$this->configurations = require($this->includeCachedConfigurationsPathAndFilename);
		}
	}

	/**
	 * Saves the current configuration into a cache file and creates a cache inclusion script
	 * in the context's Configuration directory.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function saveConfigurationCache() {
		$configurationCachePath = $this->environment->getPathToTemporaryDirectory() . 'Configuration/';
		if (!file_exists($configurationCachePath )) {
			\F3\FLOW3\Utility\Files::createDirectoryRecursively($configurationCachePath );
		}
		$cachePathAndFilename = $configurationCachePath  . $this->context . 'Configurations.php';
		$currentRevision = \F3\FLOW3\Core\Bootstrap::REVISION;
		$includeCachedConfigurationsCode = <<< "EOD"
<?php
	if (file_exists('$cachePathAndFilename') && \F3\FLOW3\Core\Bootstrap::REVISION === '$currentRevision') {
		return require '$cachePathAndFilename';
	} else {
		unlink(__FILE__);
		return array();
	}
?>
EOD;
		file_put_contents($cachePathAndFilename, '<?php return ' . var_export($this->configurations, TRUE) . '?>');
		file_put_contents($this->includeCachedConfigurationsPathAndFilename, $includeCachedConfigurationsCode);
	}

	/**
	 * Post processes the given configuration array by replacing constants with their
	 * actual value.
	 *
	 * @param array &$configurations The configuration to post process. The results are stored directly in the given array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function postProcessConfiguration(array &$configurations) {
		foreach ($configurations as $key => $configuration) {
			if (is_array($configuration)) {
				$this->postProcessConfiguration($configurations[$key]);
			} elseif (is_string($configuration)) {
				$matches = array();
				preg_match_all('/(?:%)([A-Z_0-9]+)(?:%)/', $configuration, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $configurations[$key] = str_replace('%' . $match . '%', constant($match), $configurations[$key]);
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
	
				if (!isset($subRouteConfiguration['uriPattern'])) {
					throw new \F3\FLOW3\Configuration\Exception\ParseErrorException('No uriPattern defined in route configuration "' . $name . '".', 1274197615);
		     	}

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
}
?>