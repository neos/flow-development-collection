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
use Neos\Flow\Configuration\Loader\MergeLoader;
use Neos\Flow\Configuration\Loader\ObjectsLoader;
use Neos\Flow\Configuration\Loader\PolicyLoader;
use Neos\Flow\Configuration\Loader\RoutesLoader;
use Neos\Flow\Configuration\Loader\SettingsLoader;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Configuration\Source\YamlSource;
use Neos\Flow\Core\Booting\Exception\SubProcessException;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Core\ClassLoader;
use Neos\Flow\Core\LockManager as CoreLockManager;
use Neos\Flow\Core\PhpCliCommandHandler;
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
use Neos\Flow\Package\GenericPackage;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Reflection\ReflectionServiceFactory;
use Neos\Flow\ResourceManagement\Streams\StreamWrapperAdapter;
use Neos\Flow\SignalSlot\Dispatcher;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Exception\FilesException;
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
            ],
            [
                'namespace' => 'Neos\\Flow\\Tests\\',
                'classPath' => FLOW_PATH_FLOW . 'Tests/',
                'mappingType' => ClassLoader::MAPPING_TYPE_PSR4
            ]
        ];

        $classLoader = new ClassLoader($initialClassLoaderMappings);
        spl_autoload_register([$classLoader, 'loadClass'], true);
        $bootstrap->setEarlyInstance(ClassLoader::class, $classLoader);
        $classLoader->setConsiderTestsNamespace(true);
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
    public static function initializeConfiguration(Bootstrap $bootstrap, bool $enableCache = true)
    {
        $context = $bootstrap->getContext();
        $environment = new Environment($context);
        $environment->setTemporaryDirectoryBase(FLOW_PATH_TEMPORARY_BASE);
        $bootstrap->setEarlyInstance(Environment::class, $environment);

        /** @var PackageManager $packageManager */
        $packageManager = $bootstrap->getEarlyInstance(PackageManager::class);

        $configurationManager = new ConfigurationManager($context);
        $configurationManager->setPackages($packageManager->getFlowPackages());
        if ($enableCache) {
            $configurationManager->setTemporaryDirectoryPath($environment->getPathToTemporaryDirectory());
        }

        $yamlSource = new YamlSource();
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_CACHES, new MergeLoader($yamlSource, ConfigurationManager::CONFIGURATION_TYPE_CACHES));
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, new ObjectsLoader($yamlSource));
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_ROUTES, new RoutesLoader($yamlSource, $configurationManager));
        $policyLoader = new PolicyLoader($yamlSource);
        $policyLoader->setTemporaryDirectoryPath($environment->getPathToTemporaryDirectory());
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_POLICY, $policyLoader);
        $configurationManager->registerConfigurationType(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, new SettingsLoader($yamlSource));

        // Manually inject settings into the PackageManager as the package manager is excluded from the proxy class building
        $flowSettings = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow');
        if (is_array($flowSettings)) {
            $packageManager->injectSettings($flowSettings);
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

        $throwableStorage = self::initializeExceptionStorage($bootstrap, $settings);
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
     * @param array $settings The Neos.Flow settings
     * @return ThrowableStorageInterface
     * @throws FlowException
     * @throws InvalidConfigurationTypeException
     */
    protected static function initializeExceptionStorage(Bootstrap $bootstrap, array $settings): ThrowableStorageInterface
    {
        $storageClassName = $settings['log']['throwables']['storageClass'] ?? FileStorage::class;
        $storageOptions = $settings['log']['throwables']['optionsByImplementation'][$storageClassName] ?? [];
        $renderRequestInformation = $settings['log']['throwables']['renderRequestInformation'] ?? true;


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

        $throwableStorage->setRequestInformationRenderer(function () use ($renderRequestInformation) {
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
            if ($renderRequestInformation) {
                $output .= PHP_EOL . 'HTTP REQUEST:' . PHP_EOL . ($request instanceof RequestInterface ? RequestInformationHelper::renderRequestInformation($request) : '[request was empty]') . PHP_EOL;
            }
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

        /**
         * Workaround to find the correct CacheFactory implementation at compile time.
         * We can rely on the $objectConfiguration being ordered by the package names after their loading order.
         * The object manager _does_ even know that at a later step in compile time: {@see CompileTimeObjectManager::getClassNameByObjectName()}
         * But at this time it is not available. https://github.com/neos/flow-development-collection/issues/3317
         */
        $cacheFactoryClass = CacheFactory::class;
        $cacheFactoryObjectConfiguration = $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);
        foreach ($cacheFactoryObjectConfiguration as $objectConfiguration) {
            if (isset($objectConfiguration[CacheFactoryInterface::class]['className'])) {
                // use the implementation of the package with the highest loading order
                $cacheFactoryClass = $objectConfiguration[CacheFactoryInterface::class]['className'];
            }
        }

        /** @var CacheFactoryInterface $cacheFactory */
        $cacheFactory = new $cacheFactoryClass($bootstrap->getContext(), $environment, $configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Neos.Flow.cache.applicationIdentifier'));

        $cacheManager = new CacheManager();
        $cacheManager->setCacheConfigurations($configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_CACHES));
        $cacheManager->injectConfigurationManager($configurationManager);
        $cacheManager->injectLogger($bootstrap->getEarlyInstance(PsrLoggerFactoryInterface::class)->get('systemLogger'));
        $cacheManager->injectEnvironment($environment);
        $cacheManager->injectCacheFactory($cacheFactory);

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
        PhpCliCommandHandler::executeCommand('neos.flow:core:compile', $settings);
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
            if ($package instanceof GenericPackage) {
                foreach ($package->getAutoloadPaths() as $autoloadPath) {
                    self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $autoloadPath, '\.php$');
                }
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
        $objectManager = $bootstrap->getObjectManager();
        /** @phpstan-ignore-next-line the object manager interface doesn't specify this method */
        $allObjectConfigurations = $objectManager->getAllObjectConfigurations();
        $packagesWithConfiguredObjects = array_reduce($allObjectConfigurations, function ($foundPackages, $item) {
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
            PhpCliCommandHandler::executeCommand('neos.flow:doctrine:compileproxies', $settings);
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
     * Check if the old fallback classloader should be used.
     *
     * The old class loader is used only in a testing context.
     *
     * @param Bootstrap $bootstrap
     * @return bool
     */
    protected static function useClassLoader(Bootstrap $bootstrap)
    {
        return $bootstrap->getContext()->isTesting();
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system.
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array<string, mixed> $settings The Neos.Flow settings
     * @param boolean $outputResults Echo the commands output on success
     * @param array<string, string> $commandArguments Command arguments
     * @return true Legacy return value. Will always be true. A failure is expressed as a thrown exception
     * @throws SubProcessException The execution of the sub process failed
     * @throws FilesException
     * @deprecated Use {@see PhpCliCommandHandler::executeCommand()}
     */
    public static function executeCommand(string $commandIdentifier, array $settings, bool $outputResults = true, array $commandArguments = []): bool
    {
        PhpCliCommandHandler::executeCommand($commandIdentifier, $settings, $outputResults, $commandArguments);
        return true;
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system without waiting for the output.
     *
     * Note: As the command execution is done in a separate thread potential exceptions or failures will *not* be reported
     *
     * @param string $commandIdentifier E.g. neos.flow:cache:flush
     * @param array $settings<string, mixed> The Neos.Flow settings
     * @param array<string, string> $commandArguments Command arguments
     * @return void
     * @deprecated Use {@see PhpCliCommandHandler::executeCommandAsync()}
     */
    public static function executeCommandAsync(string $commandIdentifier, array $settings, array $commandArguments = []): void
    {
        PhpCliCommandHandler::executeCommandAsync($commandIdentifier, $settings, $commandArguments);
    }
}
