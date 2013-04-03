<?php
namespace TYPO3\Flow\Configuration;

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
use TYPO3\Flow\Utility\Arrays;

/**
 * A general purpose configuration manager
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(FALSE)
 * @api
 */
class ConfigurationManager {

	const MAXIMUM_RECURSIONS = 99;

	const CONFIGURATION_TYPE_CACHES = 'Caches';
	const CONFIGURATION_TYPE_OBJECTS = 'Objects';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';
	const CONFIGURATION_TYPE_POLICY = 'Policy';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';

	const CONFIGURATION_PROCESSING_TYPE_DEFAULT = 'DefaultProcessing';
	const CONFIGURATION_PROCESSING_TYPE_OBJECTS = 'ObjectsProcessing';
	const CONFIGURATION_PROCESSING_TYPE_ROUTES = 'RoutesProcessing';
	const CONFIGURATION_PROCESSING_TYPE_SETTINGS = 'SettingsProcessing';

	/**
	 * Defines which Configuration Type is processed by which logic
	 * @var array
	 */
	protected $configurationTypes = array(
		self::CONFIGURATION_TYPE_CACHES => self::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
		self::CONFIGURATION_TYPE_OBJECTS => self::CONFIGURATION_PROCESSING_TYPE_OBJECTS,
		self::CONFIGURATION_TYPE_ROUTES => self::CONFIGURATION_PROCESSING_TYPE_ROUTES,
		self::CONFIGURATION_TYPE_POLICY => self::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
		self::CONFIGURATION_TYPE_SETTINGS => self::CONFIGURATION_PROCESSING_TYPE_SETTINGS
	);

	/**
	 * The application context of the configuration to manage
	 *
	 * @var \TYPO3\Flow\Core\ApplicationContext
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
	 * @var \TYPO3\Flow\Configuration\Source\YamlSource
	 */
	protected $configurationSource;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
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
	 * @var array<TYPO3\Flow\Package\PackageInterface>
	 */
	protected $packages = array();

	/**
	 * @var boolean
	 */
	protected $cacheNeedsUpdate = FALSE;

	/**
	 * Counts how many SubRoutes have been loaded. If this number exceeds MAXIMUM_RECURSIONS, an exception is thrown
	 * @var integer
	 */
	protected $subRoutesRecursionLevel = 0;

	/**
	 * Constructs the configuration manager
	 *
	 * @param \TYPO3\Flow\Core\ApplicationContext $context The application context to fetch configuration for
	 */
	public function __construct(\TYPO3\Flow\Core\ApplicationContext $context) {
		$this->context = $context;

		$orderedListOfContextNames = array();
		$currentContext = $context;
		do {
			$orderedListOfContextNames[] = (string)$currentContext;
		} while ($currentContext = $currentContext->getParent());
		$this->orderedListOfContextNames = array_reverse($orderedListOfContextNames);

		$this->includeCachedConfigurationsPathAndFilename = FLOW_PATH_CONFIGURATION . (string)$context . '/IncludeCachedConfigurations.php';
	}

	/**
	 * Injects the configuration source
	 *
	 * @param \TYPO3\Flow\Configuration\Source\YamlSource $configurationSource
	 * @return void
	 */
	public function injectConfigurationSource(\TYPO3\Flow\Configuration\Source\YamlSource $configurationSource) {
		$this->configurationSource = $configurationSource;
	}

	/**
	 * Injects the environment
	 *
	 * @param \TYPO3\Flow\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\Flow\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Sets the active packages to load the configuration for
	 *
	 * @param array<TYPO3\Flow\Package\PackageInterface> $packages
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
		return array_keys($this->configurationTypes);
	}

	/**
	 * Resolve the processing type for the configuration type.
	 *
	 * This returns the CONFIGURATION_PROCESSING_TYPE_* to use for the given
	 * $configurationType.
	 *
	 * @param string $configurationType
	 * @return string
	 * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException on non-existing configurationType
	 */
	public function resolveConfigurationProcessingType($configurationType) {
		if (!isset($this->configurationTypes[$configurationType])) {
			throw new \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" is not registered. You can Register it by calling $configurationManager->registerConfigurationType($configurationType, $configurationProcessingType).', 1339166495);
		}
		return $this->configurationTypes[$configurationType];
	}

	/**
	 * Registers a new configuration type with the given configuration processing type.
	 *
	 * The processing type must be supported by the ConfigurationManager, see
	 * CONFIGURATION_PROCESSING_TYPE_* for what is available.
	 *
	 * @param string $configurationType The type to register, may be anything
	 * @param string $configurationProcessingType One of CONFIGURATION_PROCESSING_TYPE_*, defaults to CONFIGURATION_PROCESSING_TYPE_DEFAULT
	 * @return void
	 */
	public function registerConfigurationType($configurationType, $configurationProcessingType = self::CONFIGURATION_PROCESSING_TYPE_DEFAULT) {
		$this->configurationTypes[$configurationType] = $configurationProcessingType;
	}

	/**
	 * Emits a signal after The ConfigurationManager has been loaded
	 *
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitConfigurationManagerReady($configurationManager) { }

	/**
	 * Returns the specified raw configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * Note that this is a low level method and only makes sense to be used by Flow internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $packageKey The package key to fetch configuration for.
	 * @return array The configuration
	 * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException on invalid configuration types
	 */
	public function getConfiguration($configurationType, $packageKey = NULL) {
		$configurationProcessingType = $this->resolveConfigurationProcessingType($configurationType);
		$configuration = array();
		switch ($configurationProcessingType) {
			case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
			case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
				if (!isset($this->configurations[$configurationType])) {
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[$configurationType];
				}
			break;

			case self::CONFIGURATION_PROCESSING_TYPE_SETTINGS:
				if (!isset($this->configurations[$configurationType]) || $this->configurations[$configurationType] === array()) {
					$this->configurations[$configurationType] = array();
					$this->loadConfiguration($configurationType, $this->packages);
				}
				if (isset($this->configurations[$configurationType])) {
					$configuration = &$this->configurations[$configurationType];
				}
			break;

			case self::CONFIGURATION_PROCESSING_TYPE_OBJECTS:
				$this->loadConfiguration($configurationType, $this->packages);
				$configuration = &$this->configurations[$configurationType];
			break;

			default :
				throw new \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}

		if ($packageKey !== NULL && $configuration !== NULL) {
			return (Arrays::getValueByPath($configuration, $packageKey));
		} else {
			return $configuration;
		}
	}

	/**
	 * Shuts down the configuration manager.
	 * This method writes the current configuration into a cache file if Flow was configured to do so.
	 *
	 * @return void
	 */
	public function shutdown() {
		if ($this->configurations[self::CONFIGURATION_TYPE_SETTINGS]['TYPO3']['Flow']['configuration']['compileConfigurationFiles'] === TRUE && $this->cacheNeedsUpdate === TRUE) {
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
	 * @throws \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException
	 */
	protected function loadConfiguration($configurationType, array $packages) {
		$this->cacheNeedsUpdate = TRUE;

		$configurationProcessingType = $this->resolveConfigurationProcessingType($configurationType);
		switch ($configurationProcessingType) {
			case self::CONFIGURATION_PROCESSING_TYPE_SETTINGS:

					// Make sure that the Flow package is the first item of the packages array:
				if (isset($packages['TYPO3.Flow'])) {
					$flowPackage = $packages['TYPO3.Flow'];
					unset($packages['TYPO3.Flow']);
					$packages = array_merge(array('TYPO3.Flow' => $flowPackage), $packages);
					unset($flowPackage);
				}

				$settings = array();
				/** @var $package \TYPO3\Flow\Package\PackageInterface */
				foreach ($packages as $packageKey => $package) {
					if (Arrays::getValueByPath($settings, $packageKey) === NULL) {
						$settings = Arrays::setValueByPath($settings, $packageKey, array());
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $configurationType));
				}
				$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType));

				foreach ($this->orderedListOfContextNames as $contextName) {
					foreach ($packages as $package) {
						$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType));
					}
					$settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
				}

				if ($this->configurations[$configurationType] !== array()) {
					$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $settings);
				} else {
					$this->configurations[$configurationType] = $settings;
				}

				$this->configurations[$configurationType]['TYPO3']['Flow']['core']['context'] = (string)$this->context;
			break;
			case self::CONFIGURATION_PROCESSING_TYPE_OBJECTS:
				$this->configurations[$configurationType] = array();
				/** @var $package \TYPO3\Flow\Package\PackageInterface */
				foreach ($packages as $packageKey => $package) {

					$configuration = $this->configurationSource->load($package->getConfigurationPath() . $configurationType);
					$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType));

					foreach ($this->orderedListOfContextNames as $contextName) {
						$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType));
						$configuration = Arrays::arrayMergeRecursiveOverrule($configuration, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
					}

					$this->configurations[$configurationType][$packageKey] = $configuration;
				}
			break;
			case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
				$emptyValuesOverride = ($configurationType !== self::CONFIGURATION_TYPE_POLICY);
				$this->configurations[$configurationType] = array();
				/** @var $package \TYPO3\Flow\Package\PackageInterface */
				foreach ($packages as $package) {
					$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType), FALSE, $emptyValuesOverride);
				}
				$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType), FALSE, $emptyValuesOverride);

				foreach ($this->orderedListOfContextNames as $contextName) {
					foreach ($packages as $package) {
						$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType), FALSE, $emptyValuesOverride);
					}
					$this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType), FALSE, $emptyValuesOverride);
				}
			break;
			case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
					// load main routes
				$this->configurations[$configurationType] = array();
				foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
					$this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
				}
				$this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType));

					// Merge routes with SubRoutes recursively
				$this->mergeRoutesWithSubRoutes($this->configurations[$configurationType]);
			break;
			default:
				throw new \TYPO3\Flow\Configuration\Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" cannot be loaded with loadConfiguration().', 1251450613);
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
	 * If a cache file with previously saved configuration exists, it is removed.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Configuration\Exception
	 */
	public function flushConfigurationCache() {
		$configurationCachePath = $this->environment->getPathToTemporaryDirectory() . 'Configuration/';
		$cachePathAndFilename = $configurationCachePath  . str_replace('/', '_', (string)$this->context) . 'Configurations.php';
		if (file_exists($cachePathAndFilename)) {
			if (unlink($cachePathAndFilename) === FALSE) {
				throw new \TYPO3\Flow\Configuration\Exception(sprintf('Could not delete configuration cache file "%s". Check file permissions for the parent directory.', $cachePathAndFilename), 1341999203);
			}
		}
		$this->configurations = array(self::CONFIGURATION_TYPE_SETTINGS => array());
	}

	/**
	 * Saves the current configuration into a cache file and creates a cache inclusion script
	 * in the context's Configuration directory.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Configuration\Exception
	 */
	protected function saveConfigurationCache() {
		$configurationCachePath = $this->environment->getPathToTemporaryDirectory() . 'Configuration/';
		if (!file_exists($configurationCachePath)) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively($configurationCachePath);
		}
		$cachePathAndFilename = $configurationCachePath  . str_replace('/', '_', (string)$this->context) . 'Configurations.php';

		$flowRootPath = FLOW_PATH_ROOT;
		$includeCachedConfigurationsCode = <<< "EOD"
<?php
if (FLOW_PATH_ROOT !== '$flowRootPath' || !file_exists('$cachePathAndFilename')) {
	unlink(__FILE__);
	return array();
}
return require '$cachePathAndFilename';
?>
EOD;
		file_put_contents($cachePathAndFilename, '<?php return ' . var_export($this->configurations, TRUE) . '?>');
		if (!is_dir(dirname($this->includeCachedConfigurationsPathAndFilename)) && !is_link(dirname($this->includeCachedConfigurationsPathAndFilename))) {
			\TYPO3\Flow\Utility\Files::createDirectoryRecursively(dirname($this->includeCachedConfigurationsPathAndFilename));
		}
		file_put_contents($this->includeCachedConfigurationsPathAndFilename, $includeCachedConfigurationsCode);
		if (!file_exists($this->includeCachedConfigurationsPathAndFilename)) {
			throw new \TYPO3\Flow\Configuration\Exception(sprintf('Could not write configuration cache file "%s". Check file permissions for the parent directory.', $this->includeCachedConfigurationsPathAndFilename), 1323339284);
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
				preg_match_all('/(?:%)((?:\\\?[\d\w_\\\]+\:\:)?[A-Z_0-9]+)(?:%)/', $configuration, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) {
							if ($configurations[$key] === '%' . $match . '%') {
									// the constant expression spans the complete directive, assign directly to keep type
								$configurations[$key] = constant($match);
							} else {
									// the constant is only a substring of the directive, replace that part accordingly
								$configurations[$key] = str_replace('%' . $match . '%', constant($match), $configurations[$key]);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Loads specified sub routes and builds composite routes.
	 *
	 * @param array $routesConfiguration
	 * @return void
	 * @throws \TYPO3\Flow\Configuration\Exception\ParseErrorException
	 * @throws \TYPO3\Flow\Configuration\Exception\RecursionException
	 */
	protected function mergeRoutesWithSubRoutes(array &$routesConfiguration) {
		$mergedRoutesConfiguration = array();
		foreach ($routesConfiguration as $routeConfiguration) {
			if (!isset($routeConfiguration['subRoutes'])) {
				$mergedRoutesConfiguration[] = $routeConfiguration;
				continue;
			}
			$mergedSubRoutesConfiguration = array($routeConfiguration);
			foreach ($routeConfiguration['subRoutes'] as $subRouteKey => $subRouteOptions) {
				if (!isset($subRouteOptions['package'])) {
					throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException(sprintf('Missing package configuration for SubRoute in Route "%s".', (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
				}
				if (!isset($this->packages[$subRouteOptions['package']])) {
					throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException(sprintf('The SubRoute Package "%s" referenced in Route "%s" is not available.', $subRouteOptions['package'], (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
				}
				/** @var $package \TYPO3\Flow\Package\PackageInterface */
				$package = $this->packages[$subRouteOptions['package']];
				$subRouteFilename = 'Routes';
				if (isset($subRouteOptions['suffix'])) {
					$subRouteFilename .= '.' . $subRouteOptions['suffix'];
				}
				$subRouteConfiguration = array();
				foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
					$subRouteFilePathAndName = $package->getConfigurationPath() . $contextName . '/' . $subRouteFilename;
					$subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
				}
				$subRouteFilePathAndName = $package->getConfigurationPath() . $subRouteFilename;
				$subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
				if ($this->subRoutesRecursionLevel > self::MAXIMUM_RECURSIONS) {
					throw new \TYPO3\Flow\Configuration\Exception\RecursionException(sprintf('Recursion level of SubRoutes exceed ' . self::MAXIMUM_RECURSIONS . ', probably because of a circular reference. Last successfully loaded route configuration is "%s".', $subRouteFilePathAndName), 1361535753);
				}

				$this->subRoutesRecursionLevel ++;
				$this->mergeRoutesWithSubRoutes($subRouteConfiguration);
				$this->subRoutesRecursionLevel --;
				$mergedSubRoutesConfiguration = $this->buildSubRouteConfigurations($mergedSubRoutesConfiguration, $subRouteConfiguration, $subRouteKey, $subRouteOptions);
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
	 * @param array $subRouteOptions
	 * @return array the merged route configuration
	 * @throws \TYPO3\Flow\Configuration\Exception\ParseErrorException
	 */
	protected function buildSubRouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, $subRouteKey, array $subRouteOptions) {
		$variables = isset($subRouteOptions['variables']) ? $subRouteOptions['variables'] : array();
		$mergedSubRoutesConfigurations = array();
		foreach ($subRoutesConfiguration as $subRouteConfiguration) {
			foreach ($routesConfiguration as $routeConfiguration) {
				$mergedSubRouteConfiguration = $subRouteConfiguration;
				$mergedSubRouteConfiguration['name'] = sprintf('%s :: %s', isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'Unnamed Route', isset($subRouteConfiguration['name']) ? $subRouteConfiguration['name'] : 'Unnamed Subroute');
				$mergedSubRouteConfiguration['name'] = $this->replacePlaceholders($mergedSubRouteConfiguration['name'], $variables);
				if (!isset($mergedSubRouteConfiguration['uriPattern'])) {
					throw new \TYPO3\Flow\Configuration\Exception\ParseErrorException('No uriPattern defined in route configuration "' . $mergedSubRouteConfiguration['name'] . '".', 1274197615);
				}
				if ($mergedSubRouteConfiguration['uriPattern'] !== '') {
					$mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($mergedSubRouteConfiguration['uriPattern'], $variables);
					$mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($routeConfiguration['uriPattern'], array($subRouteKey => $mergedSubRouteConfiguration['uriPattern']));
				} else {
					$mergedSubRouteConfiguration['uriPattern'] = rtrim($this->replacePlaceholders($routeConfiguration['uriPattern'], array($subRouteKey => '')), '/');
				}
				$mergedSubRouteConfiguration = Arrays::arrayMergeRecursiveOverrule($routeConfiguration, $mergedSubRouteConfiguration);
				unset($mergedSubRouteConfiguration['subRoutes']);
				$mergedSubRoutesConfigurations[] = $mergedSubRouteConfiguration;
			}
		}
		return $mergedSubRoutesConfigurations;
	}

	/**
	 * Replaces placeholders in the format <variableName> with the corresponding variable of the specified $variables collection.
	 *
	 * @param string $string
	 * @param array $variables
	 * @return string
	 */
	protected function replacePlaceholders($string, array $variables) {
		foreach ($variables as $variableName => $variableValue) {
			$string = str_replace('<' . $variableName . '>', $variableValue, $string);
		}
		return $string;
	}
}
?>
