<?php
namespace Neos\Flow\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\PackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Utility\OpcodeCacheHelper;
use Neos\Utility\PositionalArraySorter;

/**
 * A general purpose configuration manager
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(FALSE)
 * @api
 */
class ConfigurationManager
{
    /**
     * The maximum number of recursions when merging subroute configurations.
     *
     * @var integer
     */
    const MAXIMUM_SUBROUTE_RECURSIONS = 99;

    /**
     * Contains a list of caches which are registered automatically. Caches defined in this configuration file are
     * registered in an early stage of the boot process and profit from mechanisms such as automatic flushing by the
     * File Monitor. See the chapter about the Cache Framework for details.
     *
     * @var string
     */
    const CONFIGURATION_TYPE_CACHES = 'Caches';

    /**
     * Contains object configuration, i.e. options which configure objects and the combination of those on a lower
     * level. See the Object Framework chapter for more information.
     *
     * @var string
     */
    const CONFIGURATION_TYPE_OBJECTS = 'Objects';

    /**
     * Contains routes configuration. This routing information is parsed and used by the MVC Web Routing mechanism.
     * Refer to the Routing chapter for more information.
     *
     * @var string
     */
    const CONFIGURATION_TYPE_ROUTES = 'Routes';

    /**
     * Contains the configuration of the security policies of the system. See the Security chapter for details.
     *
     * @var string
     */
    const CONFIGURATION_TYPE_POLICY = 'Policy';

    /**
     * Contains user-level settings, i.e. configuration options the users or administrators are meant to change.
     * Settings are the highest level of system configuration.
     *
     * @var string
     */
    const CONFIGURATION_TYPE_SETTINGS = 'Settings';

    /**
     * This is the default processing, which merges configurations similar to how CONFIGURATION_PROCESSING_TYPE_SETTINGS
     * are merged (except that for settings an empty array is initialized for each package)
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_DEFAULT = 'DefaultProcessing';

    /**
     * Appends all configurations, prefixed by the PackageKey of the configuration source
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_OBJECTS = 'ObjectsProcessing';

    /**
     * Loads and merges configurations from Packages (global Policy-configurations are not allowed)
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_POLICY = 'PolicyProcessing';

    /**
     * Loads and appends global configurations and resolves SubRoutes, creating a combined flat array of all Routes
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_ROUTES = 'RoutesProcessing';

    /**
     * Similar to CONFIGURATION_PROCESSING_TYPE_DEFAULT, but for every active package an empty array is initialized.
     * Besides this sets "Neos.Flow.core.context" to the current context
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_SETTINGS = 'SettingsProcessing';

    /**
     * Appends all configurations to one flat array
     *
     * @var string
     */
    const CONFIGURATION_PROCESSING_TYPE_APPEND = 'AppendProcessing';

    /**
     * Defines which Configuration Type is processed by which logic
     *
     * @var array
     */
    protected $configurationTypes = [
        self::CONFIGURATION_TYPE_CACHES => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_DEFAULT, 'allowSplitSource' => false],
        self::CONFIGURATION_TYPE_OBJECTS => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_OBJECTS, 'allowSplitSource' => false],
        self::CONFIGURATION_TYPE_ROUTES => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_ROUTES, 'allowSplitSource' => false],
        self::CONFIGURATION_TYPE_POLICY => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_POLICY, 'allowSplitSource' => false],
        self::CONFIGURATION_TYPE_SETTINGS => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_SETTINGS, 'allowSplitSource' => false]
    ];

    /**
     * The application context of the configuration to manage
     *
     * @var ApplicationContext
     */
    protected $context;

    /**
     * An array of context name strings, from the most generic one to the most special one.
     * Example:
     * Development, Development/Foo, Development/Foo/Bar
     *
     * @var array
     */
    protected $orderedListOfContextNames = [];

    /**
     * @var Source\YamlSource
     */
    protected $configurationSource;

    /**
     * Storage of the raw special configurations
     *
     * @var array
     */
    protected $configurations = [
        self::CONFIGURATION_TYPE_SETTINGS => [],
    ];

    /**
     * Active packages to load the configuration for
     *
     * @var array<Neos\Flow\Package\PackageInterface>
     */
    protected $packages = [];

    /**
     * @var boolean
     */
    protected $cacheNeedsUpdate = false;

    /**
     * Counts how many SubRoutes have been loaded. If this number exceeds MAXIMUM_SUBROUTE_RECURSIONS, an exception is thrown
     *
     * @var integer
     */
    protected $subRoutesRecursionLevel = 0;

    /**
     * An absolute file path to store configuration caches in. If null no cache will be active.
     *
     * @var string
     */
    protected $temporaryDirectoryPath;

    /**
     * @var array
     */
    protected $unprocessedConfiguration = [];

    /**
     * Constructs the configuration manager
     *
     * @param ApplicationContext $context The application context to fetch configuration for
     */
    public function __construct(ApplicationContext $context)
    {
        $this->context = $context;

        $orderedListOfContextNames = [];
        $currentContext = $context;
        do {
            $orderedListOfContextNames[] = (string)$currentContext;
        } while ($currentContext = $currentContext->getParent());
        $this->orderedListOfContextNames = array_reverse($orderedListOfContextNames);
    }

    /**
     * Injects the configuration source
     *
     * @param Source\YamlSource $configurationSource
     * @return void
     */
    public function injectConfigurationSource(Source\YamlSource $configurationSource)
    {
        $this->configurationSource = $configurationSource;
    }

    /**
     * Set an absolute file path to store configuration caches in. If null no cache will be active.
     *
     * @param string $temporaryDirectoryPath
     */
    public function setTemporaryDirectoryPath($temporaryDirectoryPath)
    {
        $this->temporaryDirectoryPath = $temporaryDirectoryPath;
    }

    /**
     * Sets the active packages to load the configuration for
     *
     * @param array<Neos\Flow\Package\PackageInterface> $packages
     * @return void
     */
    public function setPackages(array $packages)
    {
        $this->packages = $packages;
    }

    /**
     * Get the available configuration-types
     *
     * @return array<string> array of configuration-type identifier strings
     */
    public function getAvailableConfigurationTypes()
    {
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
     * @throws Exception\InvalidConfigurationTypeException on non-existing configurationType
     */
    public function resolveConfigurationProcessingType($configurationType)
    {
        if (!isset($this->configurationTypes[$configurationType])) {
            throw new Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" is not registered. You can Register it by calling $configurationManager->registerConfigurationType($configurationType).', 1339166495);
        }

        return $this->configurationTypes[$configurationType]['processingType'];
    }

    /**
     * Check the allowSplitSource setting for the configuration type.
     *
     * @param string $configurationType
     * @return boolean
     * @throws Exception\InvalidConfigurationTypeException on non-existing configurationType
     */
    public function isSplitSourceAllowedForConfigurationType($configurationType)
    {
        if (!isset($this->configurationTypes[$configurationType])) {
            throw new Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" is not registered. You can Register it by calling $configurationManager->registerConfigurationType($configurationType).', 1359998400);
        }

        return $this->configurationTypes[$configurationType]['allowSplitSource'];
    }

    /**
     * Registers a new configuration type with the given configuration processing type.
     *
     * The processing type must be supported by the ConfigurationManager, see
     * CONFIGURATION_PROCESSING_TYPE_* for what is available.
     *
     * @param string $configurationType The type to register, may be anything
     * @param string $configurationProcessingType One of CONFIGURATION_PROCESSING_TYPE_*, defaults to CONFIGURATION_PROCESSING_TYPE_DEFAULT
     * @param boolean $allowSplitSource If TRUE, the type will be used as a "prefix" when looking for split configuration. Only supported for DEFAULT and SETTINGS processing types!
     * @throws \InvalidArgumentException on invalid configuration processing type
     * @return void
     */
    public function registerConfigurationType($configurationType, $configurationProcessingType = self::CONFIGURATION_PROCESSING_TYPE_DEFAULT, $allowSplitSource = false)
    {
        $configurationProcessingTypes = [
            self::CONFIGURATION_PROCESSING_TYPE_DEFAULT,
            self::CONFIGURATION_PROCESSING_TYPE_OBJECTS,
            self::CONFIGURATION_PROCESSING_TYPE_POLICY,
            self::CONFIGURATION_PROCESSING_TYPE_ROUTES,
            self::CONFIGURATION_PROCESSING_TYPE_SETTINGS,
            self::CONFIGURATION_PROCESSING_TYPE_APPEND
        ];
        if (!in_array($configurationProcessingType, $configurationProcessingTypes)) {
            throw new \InvalidArgumentException(sprintf('Specified invalid configuration processing type "%s" while registering custom configuration type "%s"', $configurationProcessingType, $configurationType), 1365496111);
        }
        $this->configurationTypes[$configurationType] = ['processingType' => $configurationProcessingType, 'allowSplitSource' => $allowSplitSource];
    }

    /**
     * Emits a signal after The ConfigurationManager has been loaded
     *
     * @param \Neos\Flow\Configuration\ConfigurationManager $configurationManager
     * @return void
     * @Flow\Signal
     */
    protected function emitConfigurationManagerReady($configurationManager)
    {
    }

    /**
     * Returns the specified raw configuration.
     * The actual configuration will be merged from different sources in a defined order.
     *
     * Note that this is a low level method and mostly makes sense to be used by Flow internally.
     * If possible just use settings and have them injected.
     *
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string $configurationPath The path inside the configuration to fetch
     * @return array The configuration
     * @throws Exception\InvalidConfigurationTypeException on invalid configuration types
     */
    public function getConfiguration($configurationType, $configurationPath = null)
    {
        $configurationProcessingType = $this->resolveConfigurationProcessingType($configurationType);
        $configuration = [];
        switch ($configurationProcessingType) {
            case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
            case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
            case self::CONFIGURATION_PROCESSING_TYPE_POLICY:
            case self::CONFIGURATION_PROCESSING_TYPE_APPEND:
                if (!isset($this->configurations[$configurationType])) {
                    $this->loadConfiguration($configurationType, $this->packages);
                }
                if (isset($this->configurations[$configurationType])) {
                    $configuration = &$this->configurations[$configurationType];
                }
            break;

            case self::CONFIGURATION_PROCESSING_TYPE_SETTINGS:
                if (!isset($this->configurations[$configurationType]) || $this->configurations[$configurationType] === []) {
                    $this->configurations[$configurationType] = [];
                    $this->loadConfiguration($configurationType, $this->packages);
                }
                if (isset($this->configurations[$configurationType])) {
                    $configuration = &$this->configurations[$configurationType];
                }
            break;

            case self::CONFIGURATION_PROCESSING_TYPE_OBJECTS:
                if (!isset($this->configurations[$configurationType]) || $this->configurations[$configurationType] === []) {
                    $this->loadConfiguration($configurationType, $this->packages);
                }
                if (isset($this->configurations[$configurationType])) {
                    $configuration = &$this->configurations[$configurationType];
                }
            break;
        }

        if ($configurationPath !== null && $configuration !== null) {
            return (Arrays::getValueByPath($configuration, $configurationPath));
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
    public function shutdown()
    {
        if ($this->cacheNeedsUpdate === true) {
            $this->saveConfigurationCache();
        }
    }

    /**
     * Warms up the complete configuration cache, i.e. fetching every configured configuration type
     * in order to be able to store it into the cache, if configured to do so.
     *
     * @see \Neos\Flow\Configuration\ConfigurationManager::shutdown
     * @return void
     */
    public function warmup()
    {
        foreach ($this->getAvailableConfigurationTypes() as $configurationType) {
            $this->getConfiguration($configurationType);
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
     * @throws Exception\InvalidConfigurationTypeException
     * @throws Exception\InvalidConfigurationException
     * @return void
     */
    protected function loadConfiguration($configurationType, array $packages)
    {
        $this->cacheNeedsUpdate = true;

        $configurationProcessingType = $this->resolveConfigurationProcessingType($configurationType);
        $allowSplitSource = $this->isSplitSourceAllowedForConfigurationType($configurationType);
        switch ($configurationProcessingType) {
            case self::CONFIGURATION_PROCESSING_TYPE_SETTINGS:

                // Make sure that the Flow package is the first item of the packages array:
                if (isset($packages['Neos.Flow'])) {
                    $flowPackage = $packages['Neos.Flow'];
                    unset($packages['Neos.Flow']);
                    $packages = array_merge(['Neos.Flow' => $flowPackage], $packages);
                    unset($flowPackage);
                }

                $settings = [];
                /** @var $package PackageInterface */
                foreach ($packages as $packageKey => $package) {
                    if (Arrays::getValueByPath($settings, $packageKey) === null) {
                        $settings = Arrays::setValueByPath($settings, $packageKey, []);
                    }
                    $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
                }
                $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    /** @var $package PackageInterface */
                    foreach ($packages as $package) {
                        $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
                    }
                    $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }

                if ($this->configurations[$configurationType] !== []) {
                    $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $settings);
                } else {
                    $this->configurations[$configurationType] = $settings;
                }

                $this->configurations[$configurationType]['Neos']['Flow']['core']['context'] = (string)$this->context;
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_OBJECTS:
                $this->configurations[$configurationType] = [];
                /** @var $package PackageInterface */
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
            case self::CONFIGURATION_PROCESSING_TYPE_POLICY:
                if ($this->context->isTesting()) {
                    $testingPolicyPathAndFilename = $this->temporaryDirectoryPath . 'Policy';
                    if ($this->configurationSource->has($testingPolicyPathAndFilename)) {
                        $this->configurations[$configurationType] = $this->configurationSource->load($testingPolicyPathAndFilename);
                        break;
                    }
                }
                $this->configurations[$configurationType] = [];
                /** @var $package PackageInterface */
                foreach ($packages as $package) {
                    $packagePolicyConfiguration = $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource);
                    $this->validatePolicyConfiguration($packagePolicyConfiguration, $package);
                    $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $packagePolicyConfiguration);
                }
                $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    /** @var $package PackageInterface */
                    foreach ($packages as $package) {
                        $packagePolicyConfiguration = $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource);
                        $this->validatePolicyConfiguration($packagePolicyConfiguration, $package);
                        $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $packagePolicyConfiguration);
                    }
                    $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
                $this->configurations[$configurationType] = [];
                /** @var $package PackageInterface */
                foreach ($packages as $package) {
                    $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
                }
                $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    /** @var $package PackageInterface */
                    foreach ($packages as $package) {
                        $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
                    }
                    $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
                // load main routes
                $this->configurations[$configurationType] = [];
                foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
                    $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
                }
                $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType));

                // load subroutes from Routes.yaml and Settings.yaml and merge them with main routes recursively
                $this->includeSubRoutesFromSettings($this->configurations[$configurationType]);
                $this->mergeRoutesWithSubRoutes($this->configurations[$configurationType]);
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_APPEND:
                $this->configurations[$configurationType] = [];
                /** @var $package PackageInterface */
                foreach ($packages as $package) {
                    $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
                }
                $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    foreach ($packages as $package) {
                        $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
                    }
                    $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }
            break;
            default:
                throw new Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" cannot be loaded with loadConfiguration().', 1251450613);
        }

        $this->unprocessedConfiguration[$configurationType] = $this->configurations[$configurationType];
    }

    /**
     * If a cache file with previously saved configuration exists, it is loaded.
     *
     * @return boolean If cached configuration was loaded or not
     */
    public function loadConfigurationCache()
    {
        $cachePathAndFilename = $this->constructConfigurationCachePath();
        if (is_file($cachePathAndFilename)) {
            $this->configurations = require($cachePathAndFilename);
            return true;
        }

        return false;
    }

    /**
     * If a cache file with previously saved configuration exists, it is removed.
     * Internal: After this the configuration manager is left without any configuration,
     * use refreshConfiguration if you want to reread the configuration.
     *
     * @return void
     * @throws Exception
     * @see refreshConfiguration
     */
    public function flushConfigurationCache()
    {
        $this->configurations = [self::CONFIGURATION_TYPE_SETTINGS => []];
        if ($this->temporaryDirectoryPath === null) {
            return;
        }

        $cachePathAndFilename = $this->constructConfigurationCachePath();
        if (is_file($cachePathAndFilename)) {
            if (unlink($cachePathAndFilename) === false) {
                throw new Exception(sprintf('Could not delete configuration cache file "%s". Check file permissions for the parent directory.', $cachePathAndFilename), 1341999203);
            }
            OpcodeCacheHelper::clearAllActive($cachePathAndFilename);
        }
    }

    /**
     * Saves the current configuration into a cache file and creates a cache inclusion script
     * in the context's Configuration directory.
     *
     * @return void
     * @throws Exception
     */
    protected function saveConfigurationCache()
    {
        // Make sure that all configuration types are loaded before writing configuration caches.
        foreach (array_keys($this->configurationTypes) as $configurationType) {
            if (!isset($this->configurations[$configurationType]) || !is_array($this->configurations[$configurationType])) {
                $this->loadConfiguration($configurationType, $this->packages);
            }
        }

        if ($this->temporaryDirectoryPath === null) {
            return;
        }

        $cachePathAndFilename = $this->constructConfigurationCachePath();
        if (!file_exists(dirname($cachePathAndFilename))) {
            Files::createDirectoryRecursively(dirname($cachePathAndFilename));
        }

        file_put_contents($cachePathAndFilename, '<?php return ' . $this->replaceVariablesInPhpString(var_export($this->unprocessedConfiguration, true)) . ';');
        OpcodeCacheHelper::clearAllActive($cachePathAndFilename);
        $this->cacheNeedsUpdate = false;
    }

    /**
     * @return void
     */
    public function refreshConfiguration()
    {
        $this->flushConfigurationCache();
        $this->saveConfigurationCache();
        $this->loadConfigurationCache();
    }

    /**
     * Replaces variables (in the format %CONSTANT% or %env:ENVIRONMENT_VARIABLE%)
     * in the given php exported configuration string.
     *
     * This is applied before caching to alllow runtime evaluation of constants and environment variables.
     *
     * @param string $phpString
     * @return mixed
     */
    protected function replaceVariablesInPhpString($phpString)
    {
        $phpString = preg_replace_callback('/
            (?<startString>=>\s\'.*)?      # optionally assignment operator and starting a string
            (?P<fullMatch>%                # an expression is indicated by %
            (?P<expression>
            (?:(?:\\\?[\d\w_\\\]+\:\:)     # either a class name followed by ::
            |                              # or
            (?:(?P<prefix>[a-z]+)\:)       # a prefix followed by : (like "env:")
            )?
            (?P<name>[A-Z_0-9]+))          # the actual variable name in all upper
            %)                             # concluded by %
            (?<endString>.*\',\n)?         # optionally concluding a string
        /mx', function ($matchGroup) {
            $replacement = "";
            $constantDoesNotStartAsBeginning = false;
            if ($matchGroup['startString'] !== "=> '") {
                $constantDoesNotStartAsBeginning = true;
            }
            $replacement .= ($constantDoesNotStartAsBeginning ? $matchGroup['startString'] . "' . " : '=> ');

            if (isset($matchGroup['prefix']) && $matchGroup['prefix'] === 'env') {
                $replacement .= "getenv('" . $matchGroup['name'] . "')";
            } elseif (isset($matchGroup['expression'])) {
                $replacement .= "(defined('" . $matchGroup['expression'] . "') ? constant('" . $matchGroup['expression'] . "') : null)";
            }

            $constantUntilEndOfLine = false;
            if (!isset($matchGroup['endString'])) {
                $matchGroup['endString'] = "',\n";
            }
            if ($matchGroup['endString'] === "',\n") {
                $constantUntilEndOfLine = true;
            }
            $replacement .= ($constantUntilEndOfLine ? ",\n" :  " . '" . $matchGroup['endString']);

            return $replacement;
        }, $phpString);

        return $phpString;
    }

    /**
     * Loads specified sub routes and builds composite routes.
     *
     * @param array $routesConfiguration
     * @return void
     * @throws Exception\ParseErrorException
     * @throws Exception\RecursionException
     */
    protected function mergeRoutesWithSubRoutes(array &$routesConfiguration)
    {
        $mergedRoutesConfiguration = [];
        foreach ($routesConfiguration as $routeConfiguration) {
            if (!isset($routeConfiguration['subRoutes'])) {
                $mergedRoutesConfiguration[] = $routeConfiguration;
                continue;
            }
            $mergedSubRoutesConfiguration = [$routeConfiguration];
            foreach ($routeConfiguration['subRoutes'] as $subRouteKey => $subRouteOptions) {
                if (!isset($subRouteOptions['package'])) {
                    throw new Exception\ParseErrorException(sprintf('Missing package configuration for SubRoute in Route "%s".', (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
                }
                if (!isset($this->packages[$subRouteOptions['package']])) {
                    throw new Exception\ParseErrorException(sprintf('The SubRoute Package "%s" referenced in Route "%s" is not available.', $subRouteOptions['package'], (isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'unnamed Route')), 1318414040);
                }
                /** @var $package PackageInterface */
                $package = $this->packages[$subRouteOptions['package']];
                $subRouteFilename = 'Routes';
                if (isset($subRouteOptions['suffix'])) {
                    $subRouteFilename .= '.' . $subRouteOptions['suffix'];
                }
                $subRouteConfiguration = [];
                foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
                    $subRouteFilePathAndName = $package->getConfigurationPath() . $contextName . '/' . $subRouteFilename;
                    $subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
                }
                $subRouteFilePathAndName = $package->getConfigurationPath() . $subRouteFilename;
                $subRouteConfiguration = array_merge($subRouteConfiguration, $this->configurationSource->load($subRouteFilePathAndName));
                if ($this->subRoutesRecursionLevel > self::MAXIMUM_SUBROUTE_RECURSIONS) {
                    throw new Exception\RecursionException(sprintf('Recursion level of SubRoutes exceed ' . self::MAXIMUM_SUBROUTE_RECURSIONS . ', probably because of a circular reference. Last successfully loaded route configuration is "%s".', $subRouteFilePathAndName), 1361535753);
                }

                $this->subRoutesRecursionLevel++;
                $this->mergeRoutesWithSubRoutes($subRouteConfiguration);
                $this->subRoutesRecursionLevel--;
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
     * @throws Exception\ParseErrorException
     */
    protected function buildSubRouteConfigurations(array $routesConfiguration, array $subRoutesConfiguration, $subRouteKey, array $subRouteOptions)
    {
        $variables = isset($subRouteOptions['variables']) ? $subRouteOptions['variables'] : [];
        $mergedSubRoutesConfigurations = [];
        foreach ($subRoutesConfiguration as $subRouteConfiguration) {
            foreach ($routesConfiguration as $routeConfiguration) {
                $mergedSubRouteConfiguration = $subRouteConfiguration;
                $mergedSubRouteConfiguration['name'] = sprintf('%s :: %s', isset($routeConfiguration['name']) ? $routeConfiguration['name'] : 'Unnamed Route', isset($subRouteConfiguration['name']) ? $subRouteConfiguration['name'] : 'Unnamed Subroute');
                $mergedSubRouteConfiguration['name'] = $this->replacePlaceholders($mergedSubRouteConfiguration['name'], $variables);
                if (!isset($mergedSubRouteConfiguration['uriPattern'])) {
                    throw new Exception\ParseErrorException('No uriPattern defined in route configuration "' . $mergedSubRouteConfiguration['name'] . '".', 1274197615);
                }
                if ($mergedSubRouteConfiguration['uriPattern'] !== '') {
                    $mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($mergedSubRouteConfiguration['uriPattern'], $variables);
                    $mergedSubRouteConfiguration['uriPattern'] = $this->replacePlaceholders($routeConfiguration['uriPattern'], [$subRouteKey => $mergedSubRouteConfiguration['uriPattern']]);
                } else {
                    $mergedSubRouteConfiguration['uriPattern'] = rtrim($this->replacePlaceholders($routeConfiguration['uriPattern'], [$subRouteKey => '']), '/');
                }
                if (isset($mergedSubRouteConfiguration['defaults'])) {
                    foreach ($mergedSubRouteConfiguration['defaults'] as $key => $defaultValue) {
                        $mergedSubRouteConfiguration['defaults'][$key] = $this->replacePlaceholders($defaultValue, $variables);
                    }
                }
                $mergedSubRouteConfiguration = Arrays::arrayMergeRecursiveOverrule($routeConfiguration, $mergedSubRouteConfiguration);
                unset($mergedSubRouteConfiguration['subRoutes']);
                $mergedSubRoutesConfigurations[] = $mergedSubRouteConfiguration;
            }
        }
        return $mergedSubRoutesConfigurations;
    }

    /**
     * Merges routes from Neos.Flow.mvc.routes settings into $routeDefinitions
     * NOTE: Routes from settings will always be appended to existing route definitions from the main Routes configuration!
     *
     * @param array $routeDefinitions
     * @return void
     */
    protected function includeSubRoutesFromSettings(&$routeDefinitions)
    {
        $routeSettings = $this->getConfiguration(self::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.mvc.routes');
        if ($routeSettings === null) {
            return;
        }
        $sortedRouteSettings = (new PositionalArraySorter($routeSettings))->toArray();
        foreach ($sortedRouteSettings as $packageKey => $routeFromSettings) {
            if ($routeFromSettings === false) {
                continue;
            }
            $subRoutesName = $packageKey . 'SubRoutes';
            $subRoutesConfiguration = ['package' => $packageKey];
            if (isset($routeFromSettings['variables'])) {
                $subRoutesConfiguration['variables'] = $routeFromSettings['variables'];
            }
            if (isset($routeFromSettings['suffix'])) {
                $subRoutesConfiguration['suffix'] = $routeFromSettings['suffix'];
            }
            $routeDefinitions[] = [
                'name' => $packageKey,
                'uriPattern' => '<' . $subRoutesName . '>',
                'subRoutes' => [
                    $subRoutesName => $subRoutesConfiguration
                ]
            ];
        }
    }

    /**
     * Replaces placeholders in the format <variableName> with the corresponding variable of the specified $variables collection.
     *
     * @param string $string
     * @param array $variables
     * @return string
     */
    protected function replacePlaceholders($string, array $variables)
    {
        foreach ($variables as $variableName => $variableValue) {
            $string = str_replace('<' . $variableName . '>', $variableValue, $string);
        }
        return $string;
    }

    /**
     * Merges two policy configuration arrays.
     *
     * @param array $firstConfigurationArray
     * @param array $secondConfigurationArray
     * @return array
     */
    protected function mergePolicyConfiguration(array $firstConfigurationArray, array $secondConfigurationArray)
    {
        $result = Arrays::arrayMergeRecursiveOverrule($firstConfigurationArray, $secondConfigurationArray);
        if (!isset($result['roles'])) {
            return $result;
        }
        foreach ($result['roles'] as $roleIdentifier => $roleConfiguration) {
            if (!isset($firstConfigurationArray['roles'][$roleIdentifier]['privileges']) || !isset($secondConfigurationArray['roles'][$roleIdentifier]['privileges'])) {
                continue;
            }
            $result['roles'][$roleIdentifier]['privileges'] = array_merge($firstConfigurationArray['roles'][$roleIdentifier]['privileges'], $secondConfigurationArray['roles'][$roleIdentifier]['privileges']);
        }
        return $result;
    }

    /**
     * Validates the given $policyConfiguration and throws an exception if its not valid
     *
     * @param array $policyConfiguration
     * @param PackageInterface $package
     * @return void
     * @throws Exception
     */
    protected function validatePolicyConfiguration(array $policyConfiguration, PackageInterface $package)
    {
        $errors = [];
        if (isset($policyConfiguration['resources'])) {
            $errors[] = 'deprecated "resources" options';
        }
        if (isset($policyConfiguration['acls'])) {
            $errors[] = 'deprecated "acls" options';
        }
        if ($errors !== []) {
            throw new Exception(sprintf('The policy configuration for package "%s" is not valid.%sIt contains following error(s):%s Make sure to run all code migrations.', $package->getPackageKey(), chr(10), chr(10) . '  * ' . implode(chr(10) . '  * ', $errors) . chr(10)), 1415717875);
        }
    }

    /**
     * Constructs a path to the configuration cache PHP file.
     * Derived from the temporary path and application context.
     *
     * @return string
     */
    protected function constructConfigurationCachePath()
    {
        $configurationCachePath = $this->temporaryDirectoryPath . 'Configuration/';
        return $configurationCachePath . str_replace('/', '_', (string)$this->context) . 'Configurations.php';
    }
}
