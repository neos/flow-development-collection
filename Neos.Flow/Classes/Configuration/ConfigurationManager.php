<?php
declare(strict_types=1);

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
use Neos\Flow\Configuration\Loader\AppendLoader;
use Neos\Flow\Configuration\Loader\LoaderInterface;
use Neos\Flow\Configuration\Loader\MergeLoader;
use Neos\Flow\Configuration\Loader\ObjectsLoader;
use Neos\Flow\Configuration\Loader\PolicyLoader;
use Neos\Flow\Configuration\Loader\RoutesLoader;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Utility\Arrays;
use Neos\Utility\Exception\FilesException;
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
    public const CONFIGURATION_TYPE_CACHES = 'Caches';

    /**
     * Contains object configuration, i.e. options which configure objects and the combination of those on a lower
     * level. See the Object Framework chapter for more information.
     *
     * @var string
     */
    public const CONFIGURATION_TYPE_OBJECTS = 'Objects';

    /**
     * Contains routes configuration. This routing information is parsed and used by the MVC Web Routing mechanism.
     * Refer to the Routing chapter for more information.
     *
     * @var string
     */
    public const CONFIGURATION_TYPE_ROUTES = 'Routes';

    /**
     * Contains the configuration of the security policies of the system. See the Security chapter for details.
     *
     * @var string
     */
    public const CONFIGURATION_TYPE_POLICY = 'Policy';

    /**
     * Contains user-level settings, i.e. configuration options the users or administrators are meant to change.
     * Settings are the highest level of system configuration.
     *
     * @var string
     */
    public const CONFIGURATION_TYPE_SETTINGS = 'Settings';


    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_DEFAULT = 'DefaultProcessing';

    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_OBJECTS = 'ObjectsProcessing';

    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_POLICY = 'PolicyProcessing';

    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_ROUTES = 'RoutesProcessing';

    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_SETTINGS = 'SettingsProcessing';

    /**
     * @var string
     * @deprecated since 7.1 – Use the existing or custom ConfigurationType implementations instead
     */
    public const CONFIGURATION_PROCESSING_TYPE_APPEND = 'AppendProcessing';


    /**
     * Defines which Configuration Type is processed by which logic
     *
     * @var LoaderInterface[]
     */
    protected $configurationLoaders = [];

    /**
     * The application context of the configuration to manage
     *
     * @var ApplicationContext
     */
    protected $context;

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
     * An absolute file path to store configuration caches in. If not set, no cache will be active.
     *
     * @var string|null
     */
    protected $temporaryDirectoryPath = null;

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
    }

    /**
     * Set an absolute file path to store configuration caches in.
     *
     * If never called no cache will be active.
     *
     * @param string $temporaryDirectoryPath
     */
    public function setTemporaryDirectoryPath(string $temporaryDirectoryPath): void
    {
        if (!is_dir($temporaryDirectoryPath)) {
            throw new \RuntimeException(sprintf('Cannot set temporaryDirectoryPath to "%s" for the ConfigurationManager cache, as it must be a valid directory.', $temporaryDirectoryPath));
        }

        $this->temporaryDirectoryPath = $temporaryDirectoryPath;

        $this->loadConfigurationsFromCache();
    }

    /**
     * Sets the active packages to load the configuration for
     *
     * @param FlowPackageInterface[] $packages
     * @return void
     */
    public function setPackages(array $packages): void
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
        return array_keys($this->configurationLoaders);
    }

    /**
     * Registers a new configuration type with the given source.
     *
     * @param string $configurationType The type to register, may be anything
     * @param LoaderInterface|string|null $configurationLoader This should be an instance of LoaderInterface. For backwards compatibility reasons it might also be a string representing one of the CONFIGURATION_PROCESSING_TYPE_* constants. If null, the default "MergeLoader" is used
     * @return void
     * @throws \InvalidArgumentException on invalid configuration processing type
     */
    public function registerConfigurationType(string $configurationType, $configurationLoader = null): void
    {
        if ($configurationLoader === null) {
            $configurationLoader = new MergeLoader(new YamlSource(), $configurationType);

            // B/C layer
        } elseif (is_string($configurationLoader)) {
            $configurationLoader = $this->convertLegacyProcessingType($configurationType, $configurationLoader);
        }
        if (!$configurationLoader instanceof LoaderInterface) {
            throw new \InvalidArgumentException(sprintf('Specified invalid configuration loader of type "%s" while registering custom configuration type "%s". This should be an instance of %s', is_object($configurationLoader) ? get_class($configurationLoader) : gettype($configurationLoader), $configurationType, LoaderInterface::class), 1617895964);
        }

        // if the configuration was already registered and the there is an unprocessed loaded configuration, the configuration needs to be loaded again
        // on the other hand, if there is a processed configuration loaded, but no unprocessed configuration, the config must be from the cache and is assumed to be valid
        if (isset($this->configurationLoaders[$configurationType]) && isset($this->unprocessedConfiguration[$configurationType])) {
            unset($this->configurations[$configurationType], $this->unprocessedConfiguration[$configurationType]);
        }
        $this->configurationLoaders[$configurationType] = $configurationLoader;
    }

    private function convertLegacyProcessingType(string $configurationType, string $configurationProcessingType): LoaderInterface
    {
        switch ($configurationProcessingType) {
            case self::CONFIGURATION_PROCESSING_TYPE_APPEND:
                return new AppendLoader(new YamlSource(), $configurationType);
            case self::CONFIGURATION_PROCESSING_TYPE_DEFAULT:
                return new MergeLoader(new YamlSource(), $configurationType);
            case self::CONFIGURATION_PROCESSING_TYPE_OBJECTS:
                return new ObjectsLoader(new YamlSource());
            case self::CONFIGURATION_PROCESSING_TYPE_POLICY:
                $policyLoader = new PolicyLoader(new YamlSource());
                if ($this->temporaryDirectoryPath) {
                    $policyLoader->setTemporaryDirectoryPath($this->temporaryDirectoryPath);
                }
                return $policyLoader;
            case self::CONFIGURATION_PROCESSING_TYPE_ROUTES:
                return new RoutesLoader(new YamlSource(), $this);
            case self::CONFIGURATION_PROCESSING_TYPE_SETTINGS:
                return new SettingsLoader(new YamlSource());
        }

        throw new \InvalidArgumentException(sprintf('Specified invalid configuration processing type "%s" while registering custom configuration type "%s".', $configurationProcessingType, $configurationType), 1365496111);
    }

    /**
     * Emits a signal after The ConfigurationManager has been loaded
     *
     * @param ConfigurationManager $configurationManager
     * @return void
     * @Flow\Signal
     */
    protected function emitConfigurationManagerReady(ConfigurationManager $configurationManager): void
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
     * @param string|null $configurationPath The path inside the configuration to fetch
     * @return mixed The configuration or NULL if the configuration doesn't exist
     * @throws Exception\InvalidConfigurationTypeException on invalid configuration types
     */
    public function getConfiguration(string $configurationType, string $configurationPath = null)
    {
        if (empty($this->configurations[$configurationType])) {
            $this->loadConfiguration($configurationType, $this->packages);
            $this->processConfigurationType($configurationType);
        }

        $configuration = $this->configurations[$configurationType] ?? [];
        if ($configurationPath === null || $configuration === []) {
            return $configuration;
        }

        return Arrays::getValueByPath($configuration, $configurationPath);
    }

    /**
     * Shuts down the configuration manager.
     * This method writes the current configuration into a cache file if Flow was configured to do so.
     *
     * @return void
     * @throws Exception\InvalidConfigurationException
     * @throws Exception\InvalidConfigurationTypeException
     * @throws FilesException
     */
    public function shutdown(): void
    {
        if ($this->cacheNeedsUpdate === true) {
            $this->saveConfigurationCache();
        }
    }

    /**
     * Warms up the complete configuration cache, i.e. fetching every configured configuration type
     * in order to be able to store it into the cache, if configured to do so.
     *
     * @return void
     * @throws InvalidConfigurationException | InvalidConfigurationTypeException
     * @see \Neos\Flow\Configuration\ConfigurationManager::shutdown
     */
    public function warmup(): void
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
     * @throws InvalidConfigurationTypeException
     * @return void
     */
    protected function loadConfiguration(string $configurationType, array $packages)
    {
        if (!isset($this->configurationLoaders[$configurationType])) {
            throw new Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" is not registered. You can Register it by calling $configurationManager->registerConfigurationType($configurationType).', 1339166495);
        }

        if (!isset($this->configurations[$configurationType])) {
            $this->configurations[$configurationType] = [];
        }

        $this->cacheNeedsUpdate = true;

        $this->configurations[$configurationType] = $this->configurationLoaders[$configurationType]->load($packages, $this->context);
        $this->unprocessedConfiguration[$configurationType] = $this->configurations[$configurationType];
    }

    /**
     * If a cache file with previously saved configuration exists, it is loaded.
     *
     * @param string $configurationType The kind of configuration to fetch
     * @param string $cachePathAndFilename The cache file to load the configuration from
     * @return void
     */
    protected function replaceConfigurationForConfigurationType(string $configurationType, string $cachePathAndFilename): void
    {
        if (is_file($cachePathAndFilename)) {
            $this->configurations[$configurationType] = include $cachePathAndFilename;
        }
    }

    /**
     * Includes the cached configuration file such that $this->configurations is fully populated
     */
    protected function loadConfigurationsFromCache(): void
    {
        if ($this->temporaryDirectoryPath === null) {
            return;
        }
        $cachePathAndFilename = $this->constructConfigurationCachePath();
        if (is_file($cachePathAndFilename)) {
            $this->configurations = include $cachePathAndFilename;
        }
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
    public function flushConfigurationCache(): void
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
     * Generate configuration with environment variables replaced without modifying or loading the cache
     *
     * @param string $configurationType The kind of configuration to fetch
     */
    protected function processConfigurationType(string $configurationType)
    {
        if ($this->temporaryDirectoryPath !== null) {
            // to avoid issues regarding concurrency and invalid filesystem characters
            // the configurationType is encoded as a uniqueid
            $configurationTypeId = \uniqid(\sprintf('%x', \crc32($configurationType)), true);
            $cachePathAndFilename = $this->constructConfigurationCachePath() . '.' . $configurationTypeId . '.tmp';

            $this->writeConfigurationCacheFile($cachePathAndFilename, $configurationType);
            $this->replaceConfigurationForConfigurationType($configurationType, $cachePathAndFilename);
            @unlink($cachePathAndFilename);
        } else {
            // in case the cache is disabled (implicitly, because temporaryDirectoryPath is null)
            // we need to still handle replacing the environment variables like `%FLOW_PATH_ROOT%` in the configuration
            $configuration = $this->unprocessedConfiguration[$configurationType];
            $this->configurations[$configurationType] = eval('return ' . $this->replaceVariablesInPhpString(var_export($configuration, true)) . ';');
        }
    }

    /**
     * Saves the current configuration into a cache file and creates a cache inclusion script
     * in the context's Configuration directory.
     *
     * @return void
     * @throws InvalidConfigurationTypeException | FilesException
     */
    protected function saveConfigurationCache(): void
    {
        // Make sure that all configuration types are loaded before writing configuration caches.
        foreach (array_keys($this->configurationLoaders) as $configurationType) {
            if (!isset($this->unprocessedConfiguration[$configurationType]) || !is_array($this->unprocessedConfiguration[$configurationType])) {
                $this->loadConfiguration($configurationType, $this->packages);
            }
        }

        if ($this->temporaryDirectoryPath === null) {
            return;
        }

        $cachePathAndFilename = $this->constructConfigurationCachePath();
        $this->writeConfigurationCacheFile($cachePathAndFilename);
        $this->cacheNeedsUpdate = false;
    }

    /**
     * Writes the cache on disk in the given cache file
     *
     * @param string $cachePathAndFilename The file to save the cache
     */
    protected function writeConfigurationCacheFile(string $cachePathAndFilename, string $configurationType = null): void
    {
        if (!file_exists(dirname($cachePathAndFilename))) {
            Files::createDirectoryRecursively(dirname($cachePathAndFilename));
        }

        $configToWrite = $this->unprocessedConfiguration;
        if ($configurationType) {
            $configToWrite = $this->unprocessedConfiguration[$configurationType];
        }
        file_put_contents($cachePathAndFilename, '<?php return ' . $this->replaceVariablesInPhpString(var_export($configToWrite, true)) . ';');
        OpcodeCacheHelper::clearAllActive($cachePathAndFilename);
    }

    /**
     * @return void
     * @throws InvalidConfigurationTypeException | FilesException | Exception
     */
    public function refreshConfiguration(): void
    {
        $this->flushConfigurationCache();
        $this->saveConfigurationCache();
        $this->loadConfigurationsFromCache();
    }

    /**
     * Replaces variables (in the format %CONSTANT% or %env:ENVIRONMENT_VARIABLE%)
     * in the given php exported configuration string.
     *
     * This is applied before caching to allow runtime evaluation of constants and environment variables.
     *
     * @param string $phpString
     * @return string
     */
    protected function replaceVariablesInPhpString(string $phpString): string
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
     * Constructs a path to the configuration cache PHP file.
     * Derived from the temporary path and application context.
     *
     * @return string
     */
    protected function constructConfigurationCachePath(): string
    {
        assert($this->temporaryDirectoryPath !== null, 'ConfigurationManager::$temporaryDirectoryPath must not be null.');
        $configurationCachePath = $this->temporaryDirectoryPath . 'Configuration/';
        return $configurationCachePath . str_replace('/', '_', (string)$this->context) . 'Configurations.php';
    }
}
