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
use Neos\Flow\Configuration\ConfigurationType\ConfigurationTypeInterface;
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
     * Defines which Configuration Type is processed by which logic
     *
     * @var array
     */
    protected $configurationTypes = [];

    /**
     * The application context of the configuration to manage
     *
     * @var ApplicationContext
     */
    protected $context;

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
     * This returns the ConfigurationTypeInterface to use for the given $configurationType.
     *
     * @param string $configurationType
     * @return ConfigurationTypeInterface
     * @throws Exception\InvalidConfigurationTypeException on non-existing configurationType
     */
    public function resolveConfigurationType(string $configurationType): ConfigurationTypeInterface
    {
        if (!isset($this->configurationTypes[$configurationType])) {
            throw new Exception\InvalidConfigurationTypeException('Configuration type "' . $configurationType . '" is not registered. You can Register it by calling $configurationManager->registerConfigurationType($configurationType).', 1339166495);
        }

        return $this->configurationTypes[$configurationType];
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

        return $this->configurationTypes[$configurationType]->isSplitSourceAllowed($configurationType);
    }

    /**
     * Registers a new configuration type with the given $configurationtype.
     *
     * @param string $configurationType The type to register, may be anything
     * @param ConfigurationType\ConfigurationTypeInterface $configurationTypeProcesor Implementation to load the configuration
     * @return void
     */
    public function registerConfigurationType(string $configurationType, ConfigurationType\ConfigurationTypeInterface $configurationTypeProcesor)
    {
        $configurationTypeProcesor->setApplicationContext($this->context);
        $configurationTypeProcesor->setConfigurationManager($this);

        $this->configurationTypes[$configurationType] = $configurationTypeProcesor;
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
        if (!isset($this->configurations[$configurationType])) {
            $this->configurations[$configurationType] = [];
        }

        $this->cacheNeedsUpdate = true;

        $configurationProcessingType = $this->resolveConfigurationType($configurationType);

        $this->configurations[$configurationType] = $configurationProcessingType->process($this->configurationSource, $configurationType, $packages, $this->configurations[$configurationType]);
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
