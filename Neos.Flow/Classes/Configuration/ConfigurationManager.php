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
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Files;
use Neos\Utility\OpcodeCacheHelper;

/**
 * A general purpose configuration manager
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 * @api
 */
class ConfigurationManager
{
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
        self::CONFIGURATION_TYPE_CACHES => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_DEFAULT, 'allowSplitSource' => true],
        self::CONFIGURATION_TYPE_OBJECTS => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_OBJECTS, 'allowSplitSource' => true],
        self::CONFIGURATION_TYPE_ROUTES => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_ROUTES, 'allowSplitSource' => false],
        self::CONFIGURATION_TYPE_POLICY => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_POLICY, 'allowSplitSource' => true],
        self::CONFIGURATION_TYPE_SETTINGS => ['processingType' => self::CONFIGURATION_PROCESSING_TYPE_SETTINGS, 'allowSplitSource' => true]
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
    protected $configurations = [];

    /**
     * Active packages to load the configuration for
     *
     * @var FlowPackageInterface[]
     */
    protected $packages = [];

    /**
     * @var boolean
     */
    protected $cacheNeedsUpdate = false;

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
    public function setTemporaryDirectoryPath(string $temporaryDirectoryPath)
    {
        $this->temporaryDirectoryPath = $temporaryDirectoryPath;
    }

    /**
     * Sets the active packages to load the configuration for
     *
     * @param FlowPackageInterface[] $packages
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
    public function getAvailableConfigurationTypes(): array
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
    public function resolveConfigurationProcessingType(string $configurationType): string
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
    public function isSplitSourceAllowedForConfigurationType(string $configurationType): bool
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
     * @param boolean $allowSplitSource If true, the type will be used as a "prefix" when looking for split configuration. Only supported for DEFAULT and SETTINGS processing types!
     * @throws \InvalidArgumentException on invalid configuration processing type
     * @return void
     */
    public function registerConfigurationType(string $configurationType, string $configurationProcessingType = self::CONFIGURATION_PROCESSING_TYPE_DEFAULT, bool $allowSplitSource = true)
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
     * @param ConfigurationManager $configurationManager
     * @return void
     * @Flow\Signal
     */
    protected function emitConfigurationManagerReady(ConfigurationManager $configurationManager)
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
     * @return array|null The configuration or NULL if the configuration doesn't exist
     * @throws Exception\InvalidConfigurationTypeException on invalid configuration types
     */
    public function getConfiguration(string $configurationType, string $configurationPath = null)
    {
        if (empty($this->configurations[$configurationType])) {
            $this->loadConfiguration($configurationType, $this->packages);
        }

        $configuration = $this->configurations[$configurationType] ?? [];
        if ($configurationPath === null || $configuration === null) {
            return $configuration;
        }

        return (Arrays::getValueByPath($configuration, $configurationPath));
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
     * @param FlowPackageInterface[] $packages An array of Package objects (indexed by package key) to consider
     * @throws Exception\InvalidConfigurationTypeException
     * @throws Exception\InvalidConfigurationException
     * @return void
     */
    protected function loadConfiguration(string $configurationType, array $packages)
    {
        $this->configurations[$configurationType] = [];
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
                foreach ($packages as $packageKey => $package) {
                    if (Arrays::getValueByPath($settings, $packageKey) === null) {
                        $settings = Arrays::setValueByPath($settings, $packageKey, []);
                    }
                    $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
                }
                $settings = Arrays::arrayMergeRecursiveOverrule($settings, $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
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

                foreach ($packages as $package) {
                    $packagePolicyConfiguration = $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource);
                    $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $packagePolicyConfiguration);
                }
                $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    foreach ($packages as $package) {
                        $packagePolicyConfiguration = $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource);
                        $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $packagePolicyConfiguration);
                    }
                    $this->configurations[$configurationType] = $this->mergePolicyConfiguration($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
                foreach ($packages as $package) {
                    $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $configurationType, $allowSplitSource));
                }
                $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType, $allowSplitSource));

                foreach ($this->orderedListOfContextNames as $contextName) {
                    foreach ($packages as $package) {
                        $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load($package->getConfigurationPath() . $contextName . '/' . $configurationType, $allowSplitSource));
                    }
                    $this->configurations[$configurationType] = Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType, $allowSplitSource));
                }
            break;
            case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
                // load main routes
                foreach (array_reverse($this->orderedListOfContextNames) as $contextName) {
                    $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $contextName . '/' . $configurationType));
                }
                $this->configurations[$configurationType] = array_merge($this->configurations[$configurationType], $this->configurationSource->load(FLOW_PATH_CONFIGURATION . $configurationType));
                $routeProcessor = new RouteConfigurationProcessor(
                    ($this->getConfiguration(self::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.mvc.routes') ?? []),
                    $this->orderedListOfContextNames,
                    $this->packages,
                    $this->configurationSource
                );
                $this->configurations[$configurationType] = $routeProcessor->process($this->configurations[$configurationType]);
                break;
            case self::CONFIGURATION_PROCESSING_TYPE_APPEND:
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
    public function loadConfigurationCache(): bool
    {
        $cachePathAndFilename = $this->constructConfigurationCachePath();
        $configurations = @include $cachePathAndFilename;
        if ($configurations !== false) {
            $this->configurations = $configurations;
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
            if (!isset($this->unprocessedConfiguration[$configurationType]) || !is_array($this->unprocessedConfiguration[$configurationType])) {
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
    protected function replaceVariablesInPhpString(string $phpString)
    {
        $phpString = preg_replace_callback('/
            (?<startString>=>\s\'.*?)?         # optionally assignment operator and starting a string
            (?P<fullMatch>%                    # an expression is indicated by %
            (?P<expression>
            (?:(?:\\\?[\d\w_\\\]+\:\:)         # either a class name followed by ::
            |                                  # or
            (?:(?P<prefix>[a-z]+)\:)           # a prefix followed by : (like "env:")
            )?
            (?P<name>[A-Z_0-9]+))              # the actual variable name in all upper
            %)                                 # concluded by %
            (?<endString>[^%]*?(?:\',\n)?)?    # optionally concluding a string
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
     * Merges two policy configuration arrays.
     *
     * @param array $firstConfigurationArray
     * @param array $secondConfigurationArray
     * @return array
     */
    protected function mergePolicyConfiguration(array $firstConfigurationArray, array $secondConfigurationArray): array
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
     * Constructs a path to the configuration cache PHP file.
     * Derived from the temporary path and application context.
     *
     * @return string
     */
    protected function constructConfigurationCachePath(): string
    {
        $configurationCachePath = $this->temporaryDirectoryPath . 'Configuration/';
        return $configurationCachePath . str_replace('/', '_', (string)$this->context) . 'Configurations.php';
    }
}
