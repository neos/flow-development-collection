<?php
namespace Neos\Flow\Core\Booting;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Neos\Cache\CacheFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cache\CacheFactory;
use Neos\Flow\Cache\CacheManager;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\ClassLoader;
use Neos\Flow\Core\LockManager as CoreLockManager;
use Neos\Flow\Core\ProxyClassLoader;
use Neos\Flow\Error\Debugger;
use Neos\Flow\Error\ErrorHandler;
use Neos\Flow\Error\ProductionExceptionHandler;
use Neos\Flow\Http\Helper\RequestInformationHelper;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Log\PsrLoggerFactoryInterface;
use Neos\Flow\Log\ThrowableStorage\FileStorage;
use Neos\Flow\Log\ThrowableStorageInterface;
use Neos\Flow\Monitor\FileMonitor;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Package\FlowPackageInterface;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Reflection\ReflectionServiceFactory;
use Neos\Flow\ResourceManagement\Streams\StreamWrapperAdapter;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Files;
use Neos\Utility\OpcodeCacheHelper;
use Neos\Flow\Exception as FlowException;
use Psr\Http\Message\RequestInterface;

/**
 * Initialization scripts for modules of the Flow package
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class Scripts
{
    /**
     * Initializes the Class Loader
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeClassLoader(Bootstrap $bootstrap)
    {
        $proxyClassLoader = new ProxyClassLoader($bootstrap->getContext());
        spl_autoload_register([$proxyClassLoader, 'loadClass'], true, true);
        $bootstrap->setEarlyInstance(ProxyClassLoader::class, $proxyClassLoader);

        if (!self::useClassLoader($bootstrap)) {
            return;
        }

        $initialClassLoaderMappings = [
            [
                'namespace' => 'Neos\\Flow\\',
                'classPath' => FLOW_PATH_FLOW . 'Classes/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ];

        if ($bootstrap->getContext()->isTesting()) {
            $initialClassLoaderMappings[] = [
                'namespace' => 'Neos\\Flow\\Tests\\',
                'classPath' => FLOW_PATH_FLOW . 'Tests/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ];
        }

        $classLoader = new ClassLoader($initialClassLoaderMappings);
        spl_autoload_register([$classLoader, 'loadClass'], true);
        $bootstrap->setEarlyInstance(ClassLoader::class, $classLoader);
        if ($bootstrap->getContext()->isTesting()) {
            $classLoader->setConsiderTestsNamespace(true);
        }
    }

    /**
     * Register the class loader into the Doctrine AnnotationRegistry so
     * the DocParser is able to load annation classes from packages.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function registerClassLoaderInAnnotationRegistry(Bootstrap $bootstrap)
    {
        AnnotationRegistry::registerLoader([$bootstrap->getEarlyInstance(\Composer\Autoload\ClassLoader::class), 'loadClass']);
        if (self::useClassLoader($bootstrap)) {
            AnnotationRegistry::registerLoader([$bootstrap->getEarlyInstance(ClassLoader::class), 'loadClass']);
        }
    }

    /**
     * Injects the classes cache to the already initialized class loader
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeClassLoaderClassesCache(Bootstrap $bootstrap)
    {
        $classesCache = $bootstrap->getEarlyInstance(CacheManager::class)->getCache('Flow_Object_Classes');
        $bootstrap->getEarlyInstance(ProxyClassLoader::class)->injectClassesCache($classesCache);
    }

    /**
     * Does some emergency, forced, low level flush caches if the user told to do
     * so through the command line.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function forceFlushCachesIfNecessary(Bootstrap $bootstrap)
    {
        if (!isset($_SERVER['argv']) || !isset($_SERVER['argv'][1]) || !isset($_SERVER['argv'][2])
            || !in_array($_SERVER['argv'][1], ['neos.flow:cache:flush', 'flow:cache:flush'])
            || !in_array($_SERVER['argv'][2], ['--force', '-f'])) {
            return;
        }

        $bootstrap->getEarlyInstance(CacheManager::class)->flushCaches();
        $environment = $bootstrap->getEarlyInstance(Environment::class);
        Files::emptyDirectoryRecursively($environment->getPathToTemporaryDirectory());

        echo 'Force-flushed caches for "' . $bootstrap->getContext() . '" context.' . PHP_EOL;

        // In production the site will be locked as this is a compiletime request so we need to take care to remove that lock again.
        if ($bootstrap->getContext()->isProduction()) {
            $bootstrap->getEarlyInstance(CoreLockManager::class)->unlockSite();
        }

        exit(0);
    }

    /**
     * Initializes the Signal Slot module
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeSignalSlot(Bootstrap $bootstrap)
    {
        $bootstrap->setEarlyInstance(Dispatcher::class, new Dispatcher());
    }

    /**
     * Initializes the package system and loads the package configuration and settings
     * provided by the packages.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializePackageManagement(Bootstrap $bootstrap)
    {
        $packageManager = new PackageManager(PackageManager::DEFAULT_PACKAGE_INFORMATION_CACHE_FILEPATH, FLOW_PATH_PACKAGES);
        $bootstrap->setEarlyInstance(PackageManager::class, $packageManager);

        // The package:rescan must happen as early as possible, compiletime alone is not enough.
        if (isset($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], ['neos.flow:package:rescan', 'flow:package:rescan'])) {
            $packageManager->rescanPackages();
        }

        $packageManager->initialize($bootstrap);
        if (self::useClassLoader($bootstrap)) {
            $bootstrap->getEarlyInstance(ClassLoader::class)->setPackages($packageManager->getAvailablePackages());
        }
    }

    /**
     * Initializes the Configuration Manager, the Flow settings and the Environment service
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     */
    public static function initializeConfiguration(Bootstrap $bootstrap)
    {
        $context = $bootstrap->getContext();
        $environment = new Environment($context);
        $environment->setTemporaryDirectoryBase(FLOW_PATH_TEMPORARY_BASE);
        $bootstrap->setEarlyInstance(Environment::class, $environment);

        /** @var PackageManager $packageManager */
        $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);

        $configurationManager = new ConfigurationManager($context);
        $configurationManager->setTemporaryDirectoryPath($environment->getPathToTemporaryDirectory());
        $configurationManager->injectConfigurationSource(new YamlSource());
        $configurationManager->setPackages($packageManager->getFlowPackages());
        if ($configurationManager->loadConfigurationCache() === false) {
            $configurationManager->refreshConfiguration();
        }

        $bootstrap->getSignalSlotDispatcher()->dispatch(ConfigurationManager::class, 'configurationManagerReady', [$configurationManager]);
        $bootstrap->setEarlyInstance(ConfigurationManager::class, $configurationManager);
    }

    /**
     * Initializes the System Logger
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     * @throws InvalidConfigurationTypeException
     */
    public static function initializeSystemLogger(Bootstrap $bootstrap): void
    {
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        $throwableStorage = self::initializeExceptionStorage($bootstrap);
        $bootstrap->setEarlyInstance(ThrowableStorageInterface::class, $throwableStorage);

        /** @var PsrLoggerFactoryInterface $psrLoggerFactoryName */
        $psrLoggerFactoryName = $settings['log']['psr3']['loggerFactory'];
        $psrLogConfigurations = $settings['log']['psr3'][$psrLoggerFactoryName] ?? [];
        $psrLogFactory = $psrLoggerFactoryName::create($psrLogConfigurations);

        $bootstrap->setEarlyInstance($psrLoggerFactoryName, $psrLogFactory);
        $bootstrap->setEarlyInstance(PsrLoggerFactoryInterface::class, $psrLogFactory);
    }

    /**
     * Initialize the exception storage
     *
     * @param Bootstrap $bootstrap
     * @return ThrowableStorageInterface
     * @throws FlowException
     * @throws InvalidConfigurationTypeException
     */
    protected static function initializeExceptionStorage(Bootstrap $bootstrap): ThrowableStorageInterface
    {
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        $storageClassName = $settings['log']['throwables']['storageClass'] ?? FileStorage::class;
        $storageOptions = $settings['log']['throwables']['optionsByImplementation'][$storageClassName] ?? [];


        if (!in_array(ThrowableStorageInterface::class, class_implements($storageClassName, true))) {
            throw new \Exception(
                sprintf('The class "%s" configured as throwable storage does not implement the ThrowableStorageInterface', $storageClassName),
                1540583485
            );
        }

        /** @var ThrowableStorageInterface $throwableStorage */
        $throwableStorage = $storageClassName::createWithOptions($storageOptions);

        $throwableStorage->setBacktraceRenderer(static function ($backtrace) {
            return Debugger::getBacktraceCode($backtrace, false, true);
        });

        $throwableStorage->setRequestInformationRenderer(static function () {
            // The following lines duplicate FileStorage::__construct(), which is intended to provide a renderer
            // to alternative implementations of ThrowableStorageInterface

            $output = '';
            if (!(Bootstrap::$staticObjectManager instanceof ObjectManagerInterface)) {
                return $output;
            }

            $bootstrap = Bootstrap::$staticObjectManager->get(Bootstrap::class);
            /* @var Bootstrap $bootstrap */
            $requestHandler = $bootstrap->getActiveRequestHandler();
            if (!$requestHandler instanceof HttpRequestHandlerInterface) {
                return $output;
            }

            $request = $requestHandler->getHttpRequest();
            // TODO: Sensible error output
            $output .= PHP_EOL . 'HTTP REQUEST:' . PHP_EOL . ($request instanceof RequestInterface ? RequestInformationHelper::renderRequestHeaders($request) : '[request was empty]') . PHP_EOL;
            $output .= PHP_EOL . 'PHP PROCESS:' . PHP_EOL . 'Inode: ' . getmyinode() . PHP_EOL . 'PID: ' . getmypid() . PHP_EOL . 'UID: ' . getmyuid() . PHP_EOL . 'GID: ' . getmygid() . PHP_EOL . 'User: ' . get_current_user() . PHP_EOL;

            return $output;
        });

        return $throwableStorage;
    }

    /**
     * Initializes the error handling
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     * @throws InvalidConfigurationTypeException
     */
    public static function initializeErrorHandling(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        $errorHandler = new ErrorHandler();
        $errorHandler->setExceptionalErrors($settings['error']['errorHandler']['exceptionalErrors']);
        $exceptionHandler = class_exists($settings['error']['exceptionHandler']['className']) ? new $settings['error']['exceptionHandler']['className'] : new ProductionExceptionHandler();

        if (is_callable([$exceptionHandler, 'injectLogger'])) {
            $exceptionHandler->injectLogger($bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger'));
        }

        if (is_callable([$exceptionHandler, 'injectThrowableStorage'])) {
            $exceptionHandler->injectThrowableStorage($bootstrap->getEarlyInstance(ThrowableStorageInterface::class));
        }

        $exceptionHandler->setOptions($settings['error']['exceptionHandler']);
    }

    /**
     * Initializes the cache framework
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     * @throws InvalidConfigurationTypeException
     */
    public static function initializeCacheManagement(Bootstrap $bootstrap)
    {
        /** @var ConfigurationManager $configurationManager */
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $environment = $bootstrap->getEarlyInstance(Environment::class);

        $cacheFactoryObjectConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, CacheFactoryInterface::class);
        $cacheFactoryClass = isset($cacheFactoryObjectConfiguration['className']) ? $cacheFactoryObjectConfiguration['className'] : CacheFactory::class;

        /** @var CacheFactory $cacheFactory */
        $cacheFactory = new $cacheFactoryClass($bootstrap->getContext(), $environment, $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.cache.applicationIdentifier'));

        $cacheManager = new CacheManager();
        $cacheManager->setCacheConfigurations($configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_CACHES));
        $cacheManager->injectConfigurationManager($configurationManager);
        $cacheManager->injectLogger($bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger'));
        $cacheManager->injectEnvironment($environment);
        $cacheManager->injectCacheFactory($cacheFactory);

        $cacheFactory->injectCacheManager($cacheManager);

        $bootstrap->setEarlyInstance(CacheManager::class, $cacheManager);
        $bootstrap->setEarlyInstance(CacheFactory::class, $cacheFactory);
    }

    /**
     * Runs the compile step if necessary
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     */
    public static function initializeProxyClasses(Bootstrap $bootstrap)
    {
        $objectConfigurationCache = $bootstrap->getEarlyInstance(CacheManager::class)->getCache('Flow_Object_Configuration');
        if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === true) {
            return;
        }

        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        // The compile sub command will only be run if the code cache is completely empty:
        OpcodeCacheHelper::clearAllActive(FLOW_PATH_CONFIGURATION);
        OpcodeCacheHelper::clearAllActive(FLOW_PATH_DATA);
        self::executeCommand('neos.flow:core:compile', $settings);
        if (isset($settings['persistence']['doctrine']['enable']) && $settings['persistence']['doctrine']['enable'] === true) {
            self::compileDoctrineProxies($bootstrap);
        }

        // As the available proxy classes were already loaded earlier we need to refresh them if the proxies where recompiled.
        $proxyClassLoader = $bootstrap->getEarlyInstance(ProxyClassLoader::class);
        $proxyClassLoader->initializeAvailableProxyClasses($bootstrap->getContext());

        // Check if code was updated, if not something went wrong
        if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === false) {
            if (DIRECTORY_SEPARATOR === '/') {
                $phpBinaryPathAndFilename = '"' . escapeshellcmd(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
            } else {
                $phpBinaryPathAndFilename = escapeshellarg(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
            }
            $command = sprintf('%s -c %s -v', $phpBinaryPathAndFilename, escapeshellarg(php_ini_loaded_file()));
            exec($command, $output, $result);
            if ($result !== 0) {
                if (!file_exists($phpBinaryPathAndFilename)) {
                    throw new FlowException(sprintf('It seems like the PHP binary "%s" cannot be executed by Flow. Set the correct path to the PHP executable in Configuration/Settings.yaml, setting Neos.Flow.core.phpBinaryPathAndFilename.', $settings['core']['phpBinaryPathAndFilename']), 1315561483);
                }
                throw new FlowException(sprintf('It seems like the PHP binary "%s" cannot be executed by Flow. The command executed was "%s" and returned the following: %s', $settings['core']['phpBinaryPathAndFilename'], $command, PHP_EOL . implode(PHP_EOL, $output)), 1354704332);
            }
            echo PHP_EOL . 'Flow: The compile run failed. Please check the error output or system log for more information.' . PHP_EOL;
            exit(1);
        }
    }

    /**
     * Recompile classes after file monitoring was executed and class files
     * have been changed.
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws FlowException
     */
    public static function recompileClasses(Bootstrap $bootstrap)
    {
        self::initializeProxyClasses($bootstrap);
    }

    /**
     * Initializes the Compiletime Object Manager (phase 1)
     *
     * @param Bootstrap $bootstrap
     */
    public static function initializeObjectManagerCompileTimeCreate(Bootstrap $bootstrap)
    {
        $objectManager = new CompileTimeObjectManager($bootstrap->getContext());
        $bootstrap->setEarlyInstance(ObjectManagerInterface::class, $objectManager);

        $signalSlotDispatcher = $bootstrap->getEarlyInstance(Dispatcher::class);
        $signalSlotDispatcher->injectObjectManager($objectManager);

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }

        Bootstrap::$staticObjectManager = $objectManager;
    }

    /**
     * Initializes the Compiletime Object Manager (phase 2)
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeObjectManagerCompileTimeFinalize(Bootstrap $bootstrap)
    {
        /** @var CompileTimeObjectManager $objectManager */
        $objectManager = $bootstrap->getObjectManager();
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $reflectionService = $objectManager->get(ReflectionService::class);
        $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
        $logger = $bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger');
        $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);

        $objectManager->injectAllSettings($configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
        $objectManager->injectReflectionService($reflectionService);
        $objectManager->injectConfigurationManager($configurationManager);
        $objectManager->injectConfigurationCache($cacheManager->getCache('Flow_Object_Configuration'));
        $objectManager->injectLogger($logger);
        $objectManager->initialize($packageManager->getAvailablePackages());

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }

        Debugger::injectObjectManager($objectManager);
    }

    /**
     * Initializes the runtime Object Manager
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeObjectManager(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $objectConfigurationCache = $bootstrap->getEarlyInstance(CacheManager::class)->getCache('Flow_Object_Configuration');

        $objectManager = new ObjectManager($bootstrap->getContext());

        $objectManager->injectAllSettings($configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
        $objectManager->setObjects($objectConfigurationCache->get('objects'));

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }

        $objectManager->get(Dispatcher::class)->injectObjectManager($objectManager);
        Debugger::injectObjectManager($objectManager);
        $bootstrap->setEarlyInstance(ObjectManagerInterface::class, $objectManager);

        Bootstrap::$staticObjectManager = $objectManager;
    }

    /**
     * Initializes the Reflection Service Factory
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeReflectionServiceFactory(Bootstrap $bootstrap)
    {
        $reflectionServiceFactory = new ReflectionServiceFactory($bootstrap);
        $bootstrap->setEarlyInstance(ReflectionServiceFactory::class, $reflectionServiceFactory);
        $bootstrap->getObjectManager()->setInstance(ReflectionServiceFactory::class, $reflectionServiceFactory);
    }

    /**
     * Initializes the Reflection Service
     *
     * @param Bootstrap $bootstrap
     * @throws FlowException
     */
    public static function initializeReflectionService(Bootstrap $bootstrap)
    {
        $reflectionService = $bootstrap->getEarlyInstance(ReflectionServiceFactory::class)->create();
        $bootstrap->setEarlyInstance(ReflectionService::class, $reflectionService);
        $bootstrap->getObjectManager()->setInstance(ReflectionService::class, $reflectionService);
    }

    /**
     * Checks if classes (i.e. php files containing classes), Policy.yaml, Objects.yaml
     * or localization files have been altered and if so flushes the related caches.
     *
     * This function only triggers the detection of changes in the file monitors.
     * The actual cache flushing is handled by other functions which are triggered
     * by the file monitor through a signal. For Flow, those signal-slot connections
     * are defined in the class \Neos\Flow\Package.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeSystemFileMonitor(Bootstrap $bootstrap)
    {
        /** @var FileMonitor[] $fileMonitors */
        $fileMonitors = [
            'Flow_ClassFiles' => FileMonitor::createFileMonitorAtBoot('Flow_ClassFiles', $bootstrap),
            'Flow_ConfigurationFiles' => FileMonitor::createFileMonitorAtBoot('Flow_ConfigurationFiles', $bootstrap),
            'Flow_TranslationFiles' => FileMonitor::createFileMonitorAtBoot('Flow_TranslationFiles', $bootstrap)
        ];

        $context = $bootstrap->getContext();
        /** @var PackageManager $packageManager */
        $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);
        $packagesWithConfiguredObjects = static::getListOfPackagesWithConfiguredObjects($bootstrap);

        /** @var FlowPackageInterface $package */
        foreach ($packageManager->getFlowPackages() as $packageKey => $package) {
            if ($packageManager->isPackageFrozen($packageKey)) {
                continue;
            }

            self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], $package->getConfigurationPath(), '\.y(a)?ml$');
            self::monitorDirectoryIfItExists($fileMonitors['Flow_TranslationFiles'], $package->getResourcesPath() . 'Private/Translations/', '\.xlf');

            if (!in_array($packageKey, $packagesWithConfiguredObjects)) {
                continue;
            }
            foreach ($package->getAutoloadPaths() as $autoloadPath) {
                self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $autoloadPath, '\.php$');
            }

            // Note that getFunctionalTestsPath is currently not part of any interface... We might want to add it or find a better way.
            if ($context->isTesting()) {
                self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $package->getFunctionalTestsPath(), '\.php$');
            }
        }
        self::monitorDirectoryIfItExists($fileMonitors['Flow_TranslationFiles'], FLOW_PATH_DATA . 'Translations/', '\.xlf');
        self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], FLOW_PATH_CONFIGURATION, '\.y(a)?ml$');
        foreach ($fileMonitors as $fileMonitor) {
            $fileMonitor->detectChanges();
        }
        foreach ($fileMonitors as $fileMonitor) {
            $fileMonitor->shutdownObject();
        }
    }

    /**
     * @param Bootstrap $bootstrap
     * @return array
     */
    protected static function getListOfPackagesWithConfiguredObjects(Bootstrap $bootstrap): array
    {
        $objectManager = $bootstrap->getEarlyInstance(ObjectManagerInterface::class);
        $packagesWithConfiguredObjects = array_reduce($objectManager->getAllObjectConfigurations(), function ($foundPackages, $item) {
            if (isset($item['p']) && !in_array($item['p'], $foundPackages)) {
                $foundPackages[] = $item['p'];
            }

            return $foundPackages;
        }, []);

        return $packagesWithConfiguredObjects;
    }

    /**
     * Let the given file monitor track changes of the specified directory if it exists.
     *
     * @param FileMonitor $fileMonitor
     * @param string $path
     * @param string $filenamePattern Optional pattern for filenames to consider for file monitoring (regular expression). @see FileMonitor::monitorDirectory()
     * @return void
     */
    protected static function monitorDirectoryIfItExists(FileMonitor $fileMonitor, string $path, string $filenamePattern = null)
    {
        if (is_dir($path)) {
            $fileMonitor->monitorDirectory($path, $filenamePattern);
        }
    }

    /**
     * Update Doctrine 2 proxy classes
     *
     * This is not simply bound to the finishedCompilationRun signal because it
     * needs the advised proxy classes to run. When that signal is fired, they
     * have been written, but not loaded.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    protected static function compileDoctrineProxies(Bootstrap $bootstrap)
    {
        $cacheManager = $bootstrap->getEarlyInstance(CacheManager::class);
        $objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
        $coreCache = $cacheManager->getCache('Flow_Core');
        $configurationManager = $bootstrap->getEarlyInstance(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');

        if ($objectConfigurationCache->has('doctrineProxyCodeUpToDate') === false && $coreCache->has('doctrineSetupRunning') === false) {
            $logger = $bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger');
            $coreCache->set('doctrineSetupRunning', 'White Russian', [], 60);
            $logger->debug('Compiling Doctrine proxies');
            self::executeCommand('neos.flow:doctrine:compileproxies', $settings);
            $coreCache->remove('doctrineSetupRunning');
            $objectConfigurationCache->set('doctrineProxyCodeUpToDate', true);
        }
    }

    /**
     * Initialize the stream wrappers.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeResources(Bootstrap $bootstrap)
    {
        StreamWrapperAdapter::initializeStreamWrapper($bootstrap->getObjectManager());
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system.
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array $settings The Neos.Flow settings
     * @param boolean $outputResults if false the output of this command is only echoed if the execution was not successful
     * @param array $commandArguments Command arguments
     * @return boolean true if the command execution was successful (exit code = 0)
     * @api
     * @throws Exception\SubProcessException if execution of the sub process failed
     */
    public static function executeCommand(string $commandIdentifier, array $settings, bool $outputResults = true, array $commandArguments = []): bool
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        $output = [];
        // Output errors in response
        $command .= ' 2>&1';
        exec($command, $output, $result);
        if ($result !== 0) {
            if (count($output) > 0) {
                $exceptionMessage = implode(PHP_EOL, $output);
            } else {
                $exceptionMessage = sprintf('Execution of subprocess failed with exit code %d without any further output. (Please check your PHP error log for possible Fatal errors)', $result);

                // If the command is too long, it'll just produce /usr/bin/php: Argument list too long but this will be invisible
                // If anything else goes wrong, it may as well not produce any $output, but might do so when run on an interactive
                // shell. Thus we dump the command next to the exception dumps.
                $exceptionMessage .= ' Try to run the command manually, to hopefully get some hint on the actual error.';

                if (!file_exists(FLOW_PATH_DATA . 'Logs/Exceptions')) {
                    Files::createDirectoryRecursively(FLOW_PATH_DATA . 'Logs/Exceptions');
                }
                if (file_exists(FLOW_PATH_DATA . 'Logs/Exceptions') && is_dir(FLOW_PATH_DATA . 'Logs/Exceptions') && is_writable(FLOW_PATH_DATA . 'Logs/Exceptions')) {
                    $referenceCode = date('YmdHis', $_SERVER['REQUEST_TIME']) . substr(md5(rand()), 0, 6);
                    $errorDumpPathAndFilename = FLOW_PATH_DATA . 'Logs/Exceptions/' . $referenceCode . '-command.txt';
                    file_put_contents($errorDumpPathAndFilename, $command);
                    $exceptionMessage .= sprintf(' It has been stored in: %s', basename($errorDumpPathAndFilename));
                } else {
                    $exceptionMessage .= sprintf(' (could not write command into %s because the directory could not be created or is not writable.)', FLOW_PATH_DATA . 'Logs/Exceptions/');
                }
            }
            throw new Exception\SubProcessException($exceptionMessage, 1355480641);
        }
        if ($outputResults) {
            echo implode(PHP_EOL, $output);
        }
        return $result === 0;
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system without waiting for the output.
     *
     * Note: As the command execution is done in a separate thread potential exceptions or failures will *not* be reported
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array $settings The Neos.Flow settings
     * @param array $commandArguments Command arguments
     * @return void
     * @api
     */
    public static function executeCommandAsync(string $commandIdentifier, array $settings, array $commandArguments = [])
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        if (DIRECTORY_SEPARATOR === '/') {
            exec($command . ' > /dev/null 2>/dev/null &');
        } else {
            pclose(popen('START /B CMD /S /C "' . $command . '" > NUL 2> NUL &', 'r'));
        }
    }

    /**
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array $settings The Neos.Flow settings
     * @param array $commandArguments Command arguments
     * @return string A command line command ready for being exec()uted
     */
    protected static function buildSubprocessCommand(string $commandIdentifier, array $settings, array $commandArguments = []): string
    {
        $command = self::buildPhpCommand($settings);

        if (isset($settings['core']['subRequestIniEntries']) && is_array($settings['core']['subRequestIniEntries'])) {
            foreach ($settings['core']['subRequestIniEntries'] as $entry => $value) {
                $command .= ' -d ' . escapeshellarg($entry);
                if (trim($value) !== '') {
                    $command .= '=' . escapeshellarg(trim($value));
                }
            }
        }

        $escapedArguments = '';
        foreach ($commandArguments as $argument => $argumentValue) {
            $argumentValue = trim($argumentValue);
            $escapedArguments .= ' ' . escapeshellarg('--' . trim($argument)) . ($argumentValue !== '' ? '=' . escapeshellarg($argumentValue) : '');
        }

        $command .= sprintf(' %s %s %s', escapeshellarg(FLOW_PATH_FLOW . 'Scripts/flow.php'), escapeshellarg($commandIdentifier), trim($escapedArguments));

        return trim($command);
    }

    /**
     * @param array $settings The Neos.Flow settings
     * @return string A command line command for PHP, which can be extended and then exec()uted
     * @throws FlowException
     */
    public static function buildPhpCommand(array $settings): string
    {
        $subRequestEnvironmentVariables = [
            'FLOW_ROOTPATH' => FLOW_PATH_ROOT,
            'FLOW_PATH_TEMPORARY_BASE' => FLOW_PATH_TEMPORARY_BASE,
            'FLOW_CONTEXT' => $settings['core']['context']
        ];
        if (isset($settings['core']['subRequestEnvironmentVariables'])) {
            $subRequestEnvironmentVariables = array_merge($subRequestEnvironmentVariables, $settings['core']['subRequestEnvironmentVariables']);
        }

        static::ensureCLISubrequestsUseCurrentlyRunningPhpBinary($settings['core']['phpBinaryPathAndFilename']);

        $command = '';
        foreach ($subRequestEnvironmentVariables as $argumentKey => $argumentValue) {
            if (DIRECTORY_SEPARATOR === '/') {
                $command .= sprintf('%s=%s ', $argumentKey, escapeshellarg($argumentValue));
            } else {
                // SET does not parse out quotes, hence we need escapeshellcmd here instead
                $command .= sprintf('SET %s=%s&', $argumentKey, escapeshellcmd($argumentValue));
            }
        }
        if (DIRECTORY_SEPARATOR === '/') {
            $phpBinaryPathAndFilename = '"' . escapeshellcmd(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
        } else {
            $phpBinaryPathAndFilename = escapeshellarg(Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
        }
        $command .= $phpBinaryPathAndFilename;
        if (!isset($settings['core']['subRequestPhpIniPathAndFilename']) || $settings['core']['subRequestPhpIniPathAndFilename'] !== false) {
            if (!isset($settings['core']['subRequestPhpIniPathAndFilename'])) {
                $useIniFile = php_ini_loaded_file();
            } else {
                $useIniFile = $settings['core']['subRequestPhpIniPathAndFilename'];
            }
            $command .= ' -c ' . escapeshellarg($useIniFile);
        }

        static::ensureWebSubrequestsUseCurrentlyRunningPhpVersion($command);

        return $command;
    }

    /**
     * Compares the realpath of the configured PHP binary (if any) with the one flow was called with in a CLI request.
     * This avoids config errors where users forget to set Neos.Flow.core.phpBinaryPathAndFilename in CLI.
     *
     * @param string phpBinaryPathAndFilename
     * @throws FlowException
     */
    protected static function ensureCLISubrequestsUseCurrentlyRunningPhpBinary($phpBinaryPathAndFilename)
    {
        // Do nothing for non-CLI requests
        if (PHP_SAPI !== 'cli') {
            return;
        }

        // Ensure the actual PHP binary is known before checking if it is correct. If empty, we ignore it because it is checked later in the script.
        if (strlen($phpBinaryPathAndFilename) === 0) {
            return;
        }

        // Try to resolve which binary file PHP is pointing to
        exec($phpBinaryPathAndFilename . ' -r "echo realpath(PHP_BINARY);"', $output, $result);
        if ($result === 0 && sizeof($output) === 1) {
            // Resolve any wrapper
            $configuredPhpBinaryPathAndFilename = $output[0];
        } else {
            // Resolve any symlinks that the configured php might be pointing to
            $configuredPhpBinaryPathAndFilename = realpath($phpBinaryPathAndFilename);
        }

        // if the configured PHP binary is empty here, the file does not exist. We ignore that here because it is checked later in the script.
        if ($configuredPhpBinaryPathAndFilename === false || strlen($configuredPhpBinaryPathAndFilename) === 0) {
            return;
        }

        $realPhpBinary = realpath(PHP_BINARY);
        if (strcmp($realPhpBinary, $configuredPhpBinaryPathAndFilename) !== 0) {
            throw new FlowException(sprintf(
                'You are running the Flow CLI with a PHP binary different from the one Flow is configured to use internally. ' .
                'Flow has been run with "%s", while the PHP version Flow is configured to use for subrequests is "%s". Make sure to configure Flow to ' .
                'use the same PHP binary by setting the "Neos.Flow.core.phpBinaryPathAndFilename" configuration option to "%s". Flush the ' .
                'caches by removing the folder Data/Temporary before running ./flow again.',
                $realPhpBinary,
                $configuredPhpBinaryPathAndFilename,
                $realPhpBinary
            ), 1536303119);
        }
    }

    /**
     * Compares the actual version of the configured PHP binary (if any) with the one flow was called with in a non-CLI request.
     * This avoids config errors where users forget to set Neos.Flow.core.phpBinaryPathAndFilename in connection with a web
     * server.
     *
     * @param string $phpCommand the completely build php string that is used to execute subrequests
     * @throws FlowException
     */
    protected static function ensureWebSubrequestsUseCurrentlyRunningPhpVersion($phpCommand)
    {
        // Do nothing for CLI requests
        if (PHP_SAPI === 'cli') {
            return;
        }

        exec($phpCommand . ' -r "echo PHP_VERSION;"', $output, $result);

        if ($result !== 0) {
            return;
        }

        $configuredPHPVersion = $output[0];
        if (array_slice(explode('.', $configuredPHPVersion), 0, 2) !== array_slice(explode('.', PHP_VERSION), 0, 2)) {
            throw new FlowException(sprintf(
                'You are executing Neos/Flow with a PHP version different from the one Flow is configured to use internally. ' .
                'Flow is running with with PHP "%s", while the PHP version Flow is configured to use for subrequests is "%s". Make sure to configure Flow to ' .
                'use the same PHP version by setting the "Neos.Flow.core.phpBinaryPathAndFilename" configuration option to a PHP-CLI binary of the version ' .
                '%s. Flush the caches by removing the folder Data/Temporary before executing Flow/Neos again.',
                PHP_VERSION,
                $configuredPHPVersion,
                PHP_VERSION
            ), 1536563428);
        }
    }

    /**
     * Check if the old fallback classloader should be used.
     *
     * The old class loader is used only in the cases:
     * * the environment variable "FLOW_ONLY_COMPOSER_LOADER" is not set or false
     * * in a testing context
     *
     * @param Bootstrap $bootstrap
     * @return bool
     */
    protected static function useClassLoader(Bootstrap $bootstrap)
    {
        return (!FLOW_ONLY_COMPOSER_LOADER || $bootstrap->getContext()->isTesting());
    }
}
