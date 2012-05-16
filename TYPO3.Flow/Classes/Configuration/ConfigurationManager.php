<?php
namespace TYPO3\FLOW3\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Utility\Arrays;

/**
 * A general purpose configuration manager
 *
 * @FLOW3\Scope("singleton")
 * @api
 */
class ConfigurationManager {

	const CONFIGURATION_TYPE_CACHES = 'Caches';
	const CONFIGURATION_TYPE_OBJECTS = 'Objects';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';
	const CONFIGURATION_TYPE_POLICY = 'Policy';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';

	/**
	 * The application context of the configuration to manage
	 *
	 * @var \TYPO3\FLOW3\Core\ApplicationContext
	 */
	protected $context;

	/**
	 * An array of context name strings, from the most generic one to the most special one.
	 * Example:
	 * Development, Development/Foo, Development/Foo/Bar
	 *
	 * @var array
	 */
	protected $orderedListOfContextNames = array();

	/**
	 * @var \TYPO3\FLOW3\Configuration\Source\YamlSource
	 */
	protected $configurationSource;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
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
	 * @var array<TYPO3\FLOW3\Package\PackageInterface>
	 */
	protected $packages = array();

	/**
	 * @var boolean
	 */
	protected $cacheNeedsUpdate = FALSE;

	/**
	 * Constructs the configuration manager
	 *
	 * @param \TYPO3\FLOW3\Core\ApplicationContext $context The application context to fetch configuration for
	 */
	public function __construct(\TYPO3\FLOW3\Core\ApplicationContext $context) {
		$this->context = $context;

		$orderedListOfContextNames = array();
		$currentContext = $context;
		do {
			$orderedListOfContextNames[] = (string)$currentContext;
		} while ($currentContext = $currentContext->getParent());
		$this->orderedListOfContextNames = array_reverse($orderedListOfContextNames);

		$this->includeCachedConfigurationsPathAndFilename = FLOW3_PATH_CONFIGURATION . (string)$context . '/IncludeCachedConfigurations.php';
	}

	/**
	 * Injects the configuration source
	 *
	 * @param \TYPO3\FLOW3\Configuration\Source\YamlSource $configurationSource
	 * @return void
	 */
	public function injectConfigurationSource(\TYPO3\FLOW3\Configuration\Source\YamlSource $configurationSource) {
		$this->configurationSource = $configurationSource;
	}

	/**
	 * Injects the environment
	 *
	 * @param \TYPO3\FLOW3\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\FLOW3\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the active packages to load the configuration for
	 *
	 * @param array<TYPO3\FLOW3\Package\PackageInterface> $packages
	 * @return void
	 */
	public function setPackages(array $packages) {
		$this->packages = $packages;
	}

	/**
	 * Get the available configuration-types
	 *
	 * @return array<string> array of configuration-type identifier strings
	 */
	public function getAvailableConfigurationTypes() {
		return array(
			self::CONFIGURATION_TYPE_CACHES,
			self::CONFIGURATION_TYPE_OBJECTS,
			self::CONFIGURATION_TYPE_ROUTES,
			self::CONFIGURATION_TYPE_POLICY,
			self::CONFIGURATION_TYPE_SETTINGS
		);
	}

	/**
	 * Returns the specified raw configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * Note that this is a low level method and only makes sense to be used by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey The package key to fetch configuration for.
	 * @return array The configuration
	 * @throws \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 */
	public function getConfiguration($configurationType, $packageKey = NULL) {
		$configuration = array();
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ROUTES :
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
				if (!isset($this->configurations[$configurationType])) {
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[$configurationType];
				}
			break;

			case self::CONFIGURATION_TYPE_SETTINGS :
				if (!isset($this->configurations[$configurationType]) || $this->configurations[$configurationType] === array()) {
					$this->configurations[$configurationType] = array();
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[self::CONFIGURATION_TYPE_SETTINGS];
				}
			break;

			case self::CONFIGURATION_TYPE_OBJECTS :
				$this->loadConfiguration($configurationType, $this->packages);
				$configuration = &$this->configurations[$configurationType];
			break;

			default :
				throw new \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}

		if ($packageKey !== NULL && $configuration !== NULL) {
			return (Arrays::getValueByPath($configuration, $packageKey));
		} else {
			return $configuration;
		}
	}

	/**
	 * Shuts down the configuration manager.
	 * This method writes the current configuration into a cache file if FLOW3 was configured to do so.
	 *
	 * @return void
	 */
	public function shutdown() {
		if ($this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['TYPO3']['FLOW3']['configuration']['compileConfigurationFiles'] === TRUE && $this->cacheNeedsUpdate === TRUE) {
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
	 * @param array $packages An array of Package objects (indexed by package key) to consider
	 * @return void
	 * @throws \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException
	 */
	protected function loadConfiguration($configurationType, array $packages) {
		$this->cacheNeedsUpdate = TRUE;

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_SETTINGS :

					// Make sure that the FLOW3 package is the first item of the packages array:
				if (isset($packages['TYPO3.FLOW3'])) {
					$flow3Package = $packages['TYPO3.FLOW3'];
					unset($packages['TYPO3.FLOW3']);
					$packages = array_merge(array('TYPO3.FLOW3' => $flow3Package), $packages);
					unset($flow3Package);
				}

				$settings = array();
				foreach ($packages as $packageKey => $package) {
					if (Arrays::getValueByPath($settings, $packageKey) === NULL) {
						$settings = Arrays::setValueByPath($settings, $packageKey, array());
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . self::CONFIGURATION_TYPE_SETTINGS));
				}
				$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . self::CONFIGURATION_TYPE_SETTINGS));

				foreach ($this->orderedListOfContextNames as $contextName) {
					foreach ($packages as $package) {
						$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . self::CONFIGURATION_TYPE_SETTINGS));
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $contextName . '/' . self::CONFIGURATION_TYPE_SETTINGS));
				}

				if ($this->configurations[self::CONFIGURATION_TYPE_SETTINGS] !== array()) {
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = Arrays::arrayMergeRecursiveOverrule($this->configurations[self::CONFIGURATION_TYPE_SETTINGS], $settings);
				} else {
					$this->configurations[self::CONFIGURATION_TYPE_SETTINGS] = $settings;
				}

				$this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['TYPO3']['FLOW3']['core']['context'] = (string)$this->context;
			break;
			case self::CONFIGURATION_TYPE_OBJECTS :
				$this->configurations[$configurationType] = array();
				foreach ($packages as $packageKey => $package) {

					$configuration = $this->configurationSource->load($package->getConfigurationPath() . $configurationType);
					$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));

					foreach ($this->orderedListOfContextNames as $contextName) {
						$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType));
						$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
					}

					$this->configurations[$configurationType][$packageKey] = $configuration;
				}
			break;
			case self::CONFIGURATION_TYPE_CACHES :
			case self::CONFIGURATION_TYPE_POLICY :
				$this->configurations[$configurationType] = array();
				foreach ($packages as $package) {
					$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
				$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));

				foreach ($this->orderedListOfContextNames as $contextName) {
					foreach ($packages as $package) {
						$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType));
					}
					$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
				}
			break;
			case self::CONFIGURATION_TYPE_ROUTES :

					// load subroutes
				$subRoutesConfiguration = array();
				foreach ($packages as $packageKey => $package) {
					$subRoutesConfiguration[$packageKey] = array();
					foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
						$subRoutesConfiguration[$packageKey] = array_merge($subRoutesConfiguration[$packageKey], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType));
					}
					$subRoutesConfiguration[$packageKey] = array_merge($subRoutesConfiguration[$packageKey], $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}

					// load main routes
				$this->configurations[self::CONFIGURATION_TYPE_ROUTES] = array();
				foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
					$this->configurations[self::CONFIGURATION_TYPE_ROUTES] = array_merge($this->configurations[self::CONFIGURATION_TYPE_ROUTES], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
				}
				$this->configurations[self::CONFIGURATION_TYPE_ROUTES] = array_merge($this->configurations[self::CONFIGURATION_TYPE_ROUTES], $this->configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));

					// Merge routes with subroutes
				$this->mergeRoutesWithSubRoutes($this->configurations[$configurationType], $subRoutesConfiguration);
			break;
			default:
				throw new \TYPO3\FLOW3\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" cannot be loaded with loadConfiguration().', 1251450613);
		}

		$this->postProcessConfiguration($this->configurations[$configurationType]);
	}

	/**
	 * If a cache file with previously saved configuration exists, it is loaded.
	 *
	 * @return boolean If cached configuration was loaded or not
	 */
	public function loadConfigurationCache() {
		if (file_exists($this->includeCachedConfigurationsPathAndFilename)) {
			$this->configurations = require($this->includeCachedConfigurationsPathAndFilename);
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Saves the current configuration into a cache file and creates a cache inclusion script
	 * in the context's Configuration directory.
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Configuration\Exception
	 */
	protected function saveConfigurationCache() {
		$configurationCachePath = $this->environment->getPathToTemporaryDirectory() . 'Configuration/';
		if (!file_exists($configurationCachePath)) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively($configurationCachePath);
		}
		$cachePathAndFilename = $configurationCachePath  . str_replace('/', '_', (string)$this->context) . 'Configurations.php';

		$flow3RootPath = FLOW3_PATH_ROOT;
		$includeCachedConfigurationsCode = <<< "EOD"
<?php
if (FLOW3_PATH_ROOT !== '$flow3RootPath' || !file_exists('$cachePathAndFilename')) {
	unlink(__FILE__);
	return array();
}
return require '$cachePathAndFilename';
?>
EOD;
		file_put_contents($cachePathAndFilename, '<?php return ' . var_export($this->configurations, TRUE) . '?>');
		if (!is_dir(dirname($this->includeCachedConfigurationsPathAndFilename)) && !is_link(dirname($this->includeCachedConfigurationsPathAndFilename))) {
			\TYPO3\FLOW3\Utility\Files::createDirectoryRecursively(dirname($this->includeCachedConfigurationsPathAndFilename));
		}
		file_put_contents($this->includeCachedConfigurationsPathAndFilename, $includeCachedConfigurationsCode);
		if (!file_exists($this->includeCachedConfigurationsPathAndFilename)) {
			throw new \TYPO3\FLOW3\Configuration\Exception(sprintf('Could not write configuration cache file "%s". Check file permissions for the parent directory.', $this->includeCachedConfigurationsPathAndFilename), 1323339284);
		}
	}

	/**
	 * Post processes the given configuration array by replacing constants with their
	 * actual value.
	 *
	 * @param array &$configurations The configuration to post process. The results are stored directly in the given array
	 * @return void
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
	 * @throws \TYPO3\FLOW3\Configuration\Exception\ParseErrorException
	 */
	protected function mergeRoutesWithSubRoutes(array &$routesConfiguration, array $subRoutesConfiguration) {
		$mergedRoutesConfiguration = array();
		foreach ($routesConfiguration as $routeConfiguration) {
			if (!isset($routeConfiguration['subRoutes'])) {
				$mergedRoutesConfiguration[] = $routeConfiguration;
				continue;
			}
			$mergedSubRoutesConfiguration = array($routeConfiguration);
			foreach ($routeConfiguration['subRoutes'] as $subRouteKey => $subRouteOptions) {
				if (!isset($subRouteOptions['package']) || !isset($subRoutesConfiguration[$subRouteOptions['package']])) {
					throw new \TYPO3\FLOW3\Configuration\Exception\ParseErrorException('Missing package configuration for SubRoute "' . (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route') . '".', 1318414040);
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
	 * @throws \TYPO3\FLOW3\Configuration\Exception\ParseErrorException
	 */
	protected function buildSubrouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, $subRouteKey) {
		$mergedSubRoutesConfigurations = array();
		foreach ($subRoutesConfiguration as $subRouteConfiguration) {
			foreach ($routesConfiguration as $routeConfiguration) {
				$subRouteConfiguration['name'] = sprintf('%s :: %s', isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'Unnamed Route', isset($subRouteConfiguration['name']) ? $subRouteConfiguration['name'] : 'Unnamed Subroute');
				if (!isset($subRouteConfiguration['uriPattern'])) {
					throw new \TYPO3\FLOW3\Configuration\Exception\ParseErrorException('No uriPattern defined in route configuration "' . $subRouteConfiguration['name'] . '".', 1274197615);
				}
				$subRouteConfiguration['uriPattern'] = str_replace('<' . $subRouteKey . '>', $subRouteConfiguration['uriPattern'], $routeConfiguration['uriPattern']);
				$subRouteConfiguration = Arrays::arrayMergeRecursiveOverrule($routeConfiguration, $subRouteConfiguration);
				unset($subRouteConfiguration['subRoutes']);
				$mergedSubRoutesConfigurations[] = $subRouteConfiguration;
			}
		}
		return $mergedSubRoutesConfigurations;
	}
}
?>