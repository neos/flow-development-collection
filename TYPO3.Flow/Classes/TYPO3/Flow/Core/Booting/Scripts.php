<?php
namespace TYPO3\Flow\Core\Booting;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Monitor\FileMonitor;
use TYPO3\Flow\Package\Package;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Package\PackageManagerInterface;
use TYPO3\Flow\Utility\OpcodeCacheHelper;

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
        require_once(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/Core/ClassLoader.php');
        $classLoader = new \TYPO3\Flow\Core\ClassLoader($bootstrap->getContext());
        spl_autoload_register(array($classLoader, 'loadClass'), true, true);
        $bootstrap->setEarlyInstance('TYPO3\Flow\Core\ClassLoader', $classLoader);
        if ($bootstrap->getContext()->isTesting()) {
            self::requireAutoloaderForPhpUnit();
            $classLoader->setConsiderTestsNamespace(true);
            require_once(FLOW_PATH_FLOW . 'Tests/BaseTestCase.php');
            require_once(FLOW_PATH_FLOW . 'Tests/FunctionalTestCase.php');
        }
    }

    /**
     * Include the PHPUnit autoloader if PHPUnit is installed via PEAR.
     *
     * @return void
     */
    protected static function requireAutoloaderForPhpUnit()
    {
        if (class_exists('PHPUnit_Framework_TestCase')) {
            return;
        }
        if (stream_resolve_include_path('PHPUnit/Autoload.php') !== false) {
            require_once('PHPUnit/Autoload.php');
        } else {
            echo PHP_EOL . 'TYPO3 Flow Bootstrap Error: The Testing context requires PHPUnit. Looked for "PHPUnit/Autoload.php" without success.';
            exit(1);
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
        \Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader'), 'loadClass'));
    }

    /**
     * Injects the classes cache to the already initialized class loader
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeClassLoaderClassesCache(Bootstrap $bootstrap)
    {
        $classesCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Object_Classes');
        $bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader')->injectClassesCache($classesCache);
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
            || !in_array($_SERVER['argv'][1], array('typo3.flow:cache:flush', 'flow:cache:flush'))
            || !in_array($_SERVER['argv'][2], array('--force', '-f'))) {
            return;
        }

        $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->flushCaches();
        $environment = $bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment');
        \TYPO3\Flow\Utility\Files::emptyDirectoryRecursively($environment->getPathToTemporaryDirectory());

        echo 'Force-flushed caches for "' . $bootstrap->getContext() . '" context.' . PHP_EOL;

        // In production the site will be locked as this is a compiletime request so we need to take care to remove that lock again.
        if ($bootstrap->getContext()->isProduction()) {
            $bootstrap->getEarlyInstance('TYPO3\Flow\Core\LockManager')->unlockSite();
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
        $bootstrap->setEarlyInstance('TYPO3\Flow\SignalSlot\Dispatcher', new \TYPO3\Flow\SignalSlot\Dispatcher());
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
        $packageManager = new \TYPO3\Flow\Package\PackageManager();
        $bootstrap->setEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface', $packageManager);
        $packageManager->injectClassLoader($bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader'));
        $packageManager->initialize($bootstrap);
    }

    /**
     * Initializes the Configuration Manager, the Flow settings and the Environment service
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws \TYPO3\Flow\Exception
     */
    public static function initializeConfiguration(Bootstrap $bootstrap)
    {
        $context = $bootstrap->getContext();
        $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');

        $configurationManager = new \TYPO3\Flow\Configuration\ConfigurationManager($context);
        $configurationManager->injectConfigurationSource(new \TYPO3\Flow\Configuration\Source\YamlSource());
        $configurationManager->loadConfigurationCache();
        $configurationManager->setPackages($packageManager->getActivePackages());

        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        $environment = new \TYPO3\Flow\Utility\Environment($context);
        if (isset($settings['utility']['environment']['temporaryDirectoryBase'])) {
            $defaultTemporaryDirectoryBase = FLOW_PATH_DATA . '/Temporary';
            if (FLOW_PATH_TEMPORARY_BASE !== $defaultTemporaryDirectoryBase) {
                throw new \TYPO3\Flow\Exception(sprintf('It seems like the PHP default temporary base path has been changed from "%s" to "%s" via the FLOW_PATH_TEMPORARY_BASE environment variable. If that variable is present, the TYPO3.Flow.utility.environment.temporaryDirectoryBase setting must not be specified!', $defaultTemporaryDirectoryBase, FLOW_PATH_TEMPORARY_BASE), 1447707261);
            }
            $environment->setTemporaryDirectoryBase($settings['utility']['environment']['temporaryDirectoryBase']);
        } else {
            $environment->setTemporaryDirectoryBase(FLOW_PATH_TEMPORARY_BASE);
        }

        $configurationManager->injectEnvironment($environment);
        $packageManager->injectSettings($settings);

        $bootstrap->getSignalSlotDispatcher()->dispatch('TYPO3\Flow\Configuration\ConfigurationManager', 'configurationManagerReady', array($configurationManager));

        $bootstrap->setEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager', $configurationManager);
        $bootstrap->setEarlyInstance('TYPO3\Flow\Utility\Environment', $environment);
    }

    /**
     * Initializes the System Logger
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeSystemLogger(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        if (!isset($settings['log']['systemLogger']['logger'])) {
            $settings['log']['systemLogger']['logger'] = 'TYPO3\Flow\Log\Logger';
        }
        $loggerFactory = new \TYPO3\Flow\Log\LoggerFactory();
        $bootstrap->setEarlyInstance('TYPO3\Flow\Log\LoggerFactory', $loggerFactory);
        $systemLogger = $loggerFactory->create('SystemLogger', $settings['log']['systemLogger']['logger'], $settings['log']['systemLogger']['backend'], $settings['log']['systemLogger']['backendOptions']);
        $bootstrap->setEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface', $systemLogger);
        $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
        $packageManager->injectSystemLogger($systemLogger);
    }

    /**
     * Initializes the error handling
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeErrorHandling(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        $errorHandler = new \TYPO3\Flow\Error\ErrorHandler();
        $errorHandler->setExceptionalErrors($settings['error']['errorHandler']['exceptionalErrors']);
        $exceptionHandler = new $settings['error']['exceptionHandler']['className'];
        $exceptionHandler->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));
        $exceptionHandler->setOptions($settings['error']['exceptionHandler']);
    }

    /**
     * Initializes the cache framework
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeCacheManagement(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $environment = $bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment');

        $cacheManager = new \TYPO3\Flow\Cache\CacheManager();
        $cacheManager->setCacheConfigurations($configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));
        $cacheManager->injectConfigurationManager($configurationManager);
        $cacheManager->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));
        $cacheManager->injectEnvironment($environment);

        $cacheFactory = new \TYPO3\Flow\Cache\CacheFactory($bootstrap->getContext(), $cacheManager, $environment);

        $bootstrap->setEarlyInstance('TYPO3\Flow\Cache\CacheManager', $cacheManager);
        $bootstrap->setEarlyInstance('TYPO3\Flow\Cache\CacheFactory', $cacheFactory);
    }

    /**
     * Runs the compile step if necessary
     *
     * @param Bootstrap $bootstrap
     * @return void
     * @throws \TYPO3\Flow\Exception
     */
    public static function initializeProxyClasses(Bootstrap $bootstrap)
    {
        $objectConfigurationCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Object_Configuration');

        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        // The compile sub command will only be run if the code cache is completely empty:
        if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === false) {
            OpcodeCacheHelper::clearAllActive(FLOW_PATH_CONFIGURATION);
            OpcodeCacheHelper::clearAllActive(FLOW_PATH_DATA);
            self::executeCommand('typo3.flow:core:compile', $settings);
            if (isset($settings['persistence']['doctrine']['enable']) && $settings['persistence']['doctrine']['enable'] === true) {
                self::compileDoctrineProxies($bootstrap);
            }

            // As the available proxy classes were already loaded earlier we need to refresh them if the proxies where recompiled.
            $classLoader = $bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader');
            $classLoader->initializeAvailableProxyClasses($bootstrap->getContext());
        }

        // Check if code was updated, if not something went wrong
        if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === false) {
            if (DIRECTORY_SEPARATOR === '/') {
                $phpBinaryPathAndFilename = '"' . escapeshellcmd(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
            } else {
                $phpBinaryPathAndFilename = escapeshellarg(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
            }
            $command = sprintf('%s -c %s -v', $phpBinaryPathAndFilename, escapeshellarg(php_ini_loaded_file()));
            exec($command, $output, $result);
            if ($result !== 0) {
                if (!file_exists($phpBinaryPathAndFilename)) {
                    throw new \TYPO3\Flow\Exception(sprintf('It seems like the PHP binary "%s" cannot be executed by Flow. Set the correct path to the PHP executable in Configuration/Settings.yaml, setting TYPO3.Flow.core.phpBinaryPathAndFilename.', $settings['core']['phpBinaryPathAndFilename']), 1315561483);
                }
                throw new \TYPO3\Flow\Exception(sprintf('It seems like the PHP binary "%s" cannot be executed by Flow. The command executed was "%s" and returned the following: %s', $settings['core']['phpBinaryPathAndFilename'], $command, PHP_EOL . implode(PHP_EOL, $output)), 1354704332);
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
     * @throws \TYPO3\Flow\Exception
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
        $objectManager = new \TYPO3\Flow\Object\CompileTimeObjectManager($bootstrap->getContext());
        $bootstrap->setEarlyInstance('TYPO3\Flow\Object\ObjectManagerInterface', $objectManager);
        Bootstrap::$staticObjectManager = $objectManager;

        $signalSlotDispatcher = $bootstrap->getEarlyInstance('TYPO3\Flow\SignalSlot\Dispatcher');
        $signalSlotDispatcher->injectObjectManager($objectManager);

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }
    }

    /**
     * Initializes the Compiletime Object Manager (phase 2)
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeObjectManagerCompileTimeFinalize(Bootstrap $bootstrap)
    {
        $objectManager = $bootstrap->getObjectManager();
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $reflectionService = $objectManager->get('TYPO3\Flow\Reflection\ReflectionService');
        $cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
        $systemLogger = $bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface');
        $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');

        $objectManager->injectAllSettings($configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
        $objectManager->injectReflectionService($reflectionService);
        $objectManager->injectConfigurationManager($configurationManager);
        $objectManager->injectConfigurationCache($cacheManager->getCache('Flow_Object_Configuration'));
        $objectManager->injectSystemLogger($systemLogger);
        $objectManager->initialize($packageManager->getActivePackages());

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }

        \TYPO3\Flow\Error\Debugger::injectObjectManager($objectManager);
    }

    /**
     * Initializes the runtime Object Manager
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeObjectManager(Bootstrap $bootstrap)
    {
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $objectConfigurationCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Object_Configuration');

        $objectManager = new \TYPO3\Flow\Object\ObjectManager($bootstrap->getContext());
        Bootstrap::$staticObjectManager = $objectManager;

        $objectManager->injectAllSettings($configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
        $objectManager->setObjects($objectConfigurationCache->get('objects'));

        foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
            $objectManager->setInstance($objectName, $instance);
        }

        $objectManager->get('TYPO3\Flow\SignalSlot\Dispatcher')->injectObjectManager($objectManager);
        \TYPO3\Flow\Error\Debugger::injectObjectManager($objectManager);
        $bootstrap->setEarlyInstance('TYPO3\Flow\Object\ObjectManagerInterface', $objectManager);
    }

    /**
     * Initializes the Reflection Service
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeReflectionService(Bootstrap $bootstrap)
    {
        $cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        $reflectionService = new \TYPO3\Flow\Reflection\ReflectionService();

        $reflectionService->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));
        $reflectionService->injectClassLoader($bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader'));
        $reflectionService->injectSettings($settings);
        $reflectionService->injectPackageManager($bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface'));
        $reflectionService->setStatusCache($cacheManager->getCache('Flow_Reflection_Status'));
        $reflectionService->setReflectionDataCompiletimeCache($cacheManager->getCache('Flow_Reflection_CompiletimeData'));
        $reflectionService->setReflectionDataRuntimeCache($cacheManager->getCache('Flow_Reflection_RuntimeData'));
        $reflectionService->setClassSchemataRuntimeCache($cacheManager->getCache('Flow_Reflection_RuntimeClassSchemata'));
        $reflectionService->injectSettings($configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow'));
        $reflectionService->injectEnvironment($bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment'));

        $bootstrap->setEarlyInstance('TYPO3\Flow\Reflection\ReflectionService', $reflectionService);
        $bootstrap->getObjectManager()->setInstance('TYPO3\Flow\Reflection\ReflectionService', $reflectionService);
    }

    /**
     * Checks if classes (i.e. php files containing classes), Policy.yaml, Objects.yaml
     * or localization files have been altered and if so flushes the related caches.
     *
     * This function only triggers the detection of changes in the file monitors.
     * The actual cache flushing is handled by other functions which are triggered
     * by the file monitor through a signal. For Flow, those signal-slot connections
     * are defined in the class \TYPO3\Flow\Package.
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeSystemFileMonitor(Bootstrap $bootstrap)
    {
        /** @var FileMonitor[] $fileMonitors */
        $fileMonitors = array(
            'Flow_ClassFiles' => FileMonitor::createFileMonitorAtBoot('Flow_ClassFiles', $bootstrap),
            'Flow_ConfigurationFiles' => FileMonitor::createFileMonitorAtBoot('Flow_ConfigurationFiles', $bootstrap),
            'Flow_TranslationFiles' => FileMonitor::createFileMonitorAtBoot('Flow_TranslationFiles', $bootstrap)
        );

        $context = $bootstrap->getContext();
        /** @var PackageManagerInterface $packageManager */
        $packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
        /** @var PackageInterface $package */
        foreach ($packageManager->getActivePackages() as $packageKey => $package) {
            if ($packageManager->isPackageFrozen($packageKey)) {
                continue;
            }
            self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $package->getClassesPath(), '\.php$');
            self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], $package->getConfigurationPath(), '\.yaml$');
            self::monitorDirectoryIfItExists($fileMonitors['Flow_TranslationFiles'], $package->getResourcesPath() . 'Private/Translations/', '\.xlf');
            if ($context->isTesting() && $package instanceof Package) {
                /** @var Package $package */
                self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $package->getFunctionalTestsPath(), '\.php$');
            }
        }

        self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], FLOW_PATH_CONFIGURATION, '\.yaml$');

        foreach ($fileMonitors as $fileMonitor) {
            $fileMonitor->detectChanges();
        }
        foreach ($fileMonitors as $fileMonitor) {
            $fileMonitor->shutdownObject();
        }
    }

    /**
     * Let the given file monitor track changes of the specified directory if it exists.
     *
     * @param FileMonitor $fileMonitor
     * @param string $path
     * @param string $filenamePattern Optional pattern for filenames to consider for file monitoring (regular expression). @see FileMonitor::monitorDirectory()
     * @return void
     */
    protected static function monitorDirectoryIfItExists(FileMonitor $fileMonitor, $path, $filenamePattern = null)
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
        $cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
        $objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
        $coreCache = $cacheManager->getCache('Flow_Core');
        $systemLogger = $bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface');
        $configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

        if ($objectConfigurationCache->has('doctrineProxyCodeUpToDate') === false && $coreCache->has('doctrineSetupRunning') === false) {
            $coreCache->set('doctrineSetupRunning', 'White Russian', array(), 60);
            $systemLogger->log('Compiling Doctrine proxies', LOG_DEBUG);
            self::executeCommand('typo3.flow:doctrine:compileproxies', $settings);
            $coreCache->remove('doctrineSetupRunning');
            $objectConfigurationCache->set('doctrineProxyCodeUpToDate', true);
        }
    }

    /**
     * Initializes the persistence framework
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializePersistence(Bootstrap $bootstrap)
    {
        $persistenceManager = $bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
        $persistenceManager->initialize();
    }

    /**
     * Initializes the session framework
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeSession(Bootstrap $bootstrap)
    {
        if (FLOW_SAPITYPE === 'Web') {
            $bootstrap->getObjectManager()->get('TYPO3\Flow\Session\SessionInterface')->resume();
        }
    }

    /**
     * Initialize the resource management component, setting up stream wrappers,
     * publishing the public resources of all found packages, ...
     *
     * @param Bootstrap $bootstrap
     * @return void
     */
    public static function initializeResources(Bootstrap $bootstrap)
    {
        $resourceManager = $bootstrap->getObjectManager()->get('TYPO3\Flow\Resource\ResourceManager');
        $resourceManager->initialize();
    }

    /**
     * Executes the given command as a sub-request to the Flow CLI system.
     *
     * @param string $commandIdentifier E.g. typo3.flow:cache:flush
     * @param array $settings The TYPO3.Flow settings
     * @param boolean $outputResults if FALSE the output of this command is only echoed if the execution was not successful
     * @param array $commandArguments Command arguments
     * @return boolean TRUE if the command execution was successful (exit code = 0)
     * @api
     * @throws \TYPO3\Flow\Core\Booting\Exception\SubProcessException if execution of the sub process failed
     */
    public static function executeCommand($commandIdentifier, array $settings, $outputResults = true, array $commandArguments = array())
    {
        $command = self::buildSubprocessCommand($commandIdentifier, $settings, $commandArguments);
        $output = array();
        // Output errors in response
        $command .= ' 2>&1';
        exec($command, $output, $result);
        if ($result !== 0) {
            if (count($output) > 0) {
                $exceptionMessage = implode(PHP_EOL, $output);
            } else {
                $exceptionMessage = sprintf('Execution of subprocess failed with exit code %d without any further output. (Please check your PHP error log for possible Fatal errors)', $result);
            }
            throw new Exception\SubProcessException($exceptionMessage, 1355480641);
        }
        if ($outputResults) {
            echo implode(PHP_EOL, $output);
        }
        return $result === 0;
    }

    /**
     * @param string $commandIdentifier E.g. typo3.flow:cache:flush
     * @param array $settings The TYPO3.Flow settings
     * @param array $commandArguments Command arguments
     * @return string A command line command ready for being exec()uted
     */
    protected static function buildSubprocessCommand($commandIdentifier, array $settings, array $commandArguments = array())
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
        if ($commandArguments !== array()) {
            foreach ($commandArguments as $argument=>$argumentValue) {
                $escapedArguments .= ' ' . escapeshellarg('--' . trim($argument));
                if (trim($argumentValue) !== '') {
                    $escapedArguments .= ' ' . escapeshellarg(trim($argumentValue));
                }
            }
        }

        $command .= sprintf(' %s %s %s', escapeshellarg(FLOW_PATH_FLOW . 'Scripts/flow.php'), escapeshellarg($commandIdentifier), trim($escapedArguments));

        return trim($command);
    }

    /**
     * @param array $settings The TYPO3.Flow settings
     * @return string A command line command for PHP, which can be extended and then exec()uted
     */
    public static function buildPhpCommand(array $settings)
    {
        $subRequestEnvironmentVariables = array(
            'FLOW_ROOTPATH' => FLOW_PATH_ROOT,
            'FLOW_PATH_TEMPORARY_BASE' => FLOW_PATH_TEMPORARY_BASE,
            'FLOW_CONTEXT' => $settings['core']['context']
        );
        if (isset($settings['core']['subRequestEnvironmentVariables'])) {
            $subRequestEnvironmentVariables = array_merge($subRequestEnvironmentVariables, $settings['core']['subRequestEnvironmentVariables']);
        }

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
            $phpBinaryPathAndFilename = '"' . escapeshellcmd(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
        } else {
            $phpBinaryPathAndFilename = escapeshellarg(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
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

        return $command;
    }
}
