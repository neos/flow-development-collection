<?php
namespace TYPO3\FLOW3\Core\Booting;

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
use TYPO3\FLOW3\Core\Bootstrap;
use TYPO3\FLOW3\Http\Uri;

/**
 * Initialization scripts for modules of the FLOW3 package
 *
 * @FLOW3\Proxy(false)
 * @FLOW3\Scope("singleton")
 */
class Scripts {

	/**
	 * Initializes the Class Loader
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeClassLoader(Bootstrap $bootstrap) {
		require_once(FLOW3_PATH_FLOW3 . 'Classes/Core/ClassLoader.php');
		$classLoader = new \TYPO3\FLOW3\Core\ClassLoader();
		spl_autoload_register(array($classLoader, 'loadClass'), TRUE, TRUE);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Core\ClassLoader', $classLoader);
	}

	/**
	 * Injects the classes cache to the already initialized class loader
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeClassLoaderClassesCache(Bootstrap $bootstrap) {
		$classesCache = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager')->getCache('FLOW3_Object_Classes');
		$bootstrap->getEarlyInstance('TYPO3\FLOW3\Core\ClassLoader')->injectClassesCache($classesCache);
	}

	/**
	 * Does some emergency, forced, low level flush caches if the user told to do
	 * so through the command line.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function forceFlushCachesIfNeccessary(Bootstrap $bootstrap) {
		if (!isset($_SERVER['argv']) || !isset($_SERVER['argv'][1]) || !isset($_SERVER['argv'][2])
			|| !in_array($_SERVER['argv'][1], array('typo3.flow3:cache:flush', 'flow3:cache:flush'))
			|| !in_array($_SERVER['argv'][2], array('--force', '-f'))) {
			return;
		}

		$bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager')->flushCaches();
		$environment = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Utility\Environment');
		\TYPO3\FLOW3\Utility\Files::emptyDirectoryRecursively($environment->getPathToTemporaryDirectory());

		echo 'Force-flushed caches for "' . $bootstrap->getContext() . '" context.' . PHP_EOL;
		exit(0);
	}

	/**
	 * Initializes the Signal Slot module
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSignalSlot(Bootstrap $bootstrap) {
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\SignalSlot\Dispatcher', new \TYPO3\FLOW3\SignalSlot\Dispatcher());
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializePackageManagement(Bootstrap $bootstrap) {
		$packageManager = new \TYPO3\FLOW3\Package\PackageManager();
		$packageManager->injectClassLoader($bootstrap->getEarlyInstance('TYPO3\FLOW3\Core\ClassLoader'));
		$packageManager->initialize($bootstrap);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface', $packageManager);
	}

	/**
	 * Initializes the Configuration Manager, the FLOW3 settings and the Environment service
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeConfiguration(Bootstrap $bootstrap) {
		$context = $bootstrap->getContext();
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface');

		$configurationManager = new \TYPO3\FLOW3\Configuration\ConfigurationManager($context);
		$configurationManager->injectConfigurationSource(new \TYPO3\FLOW3\Configuration\Source\YamlSource());
		$configurationManager->loadConfigurationCache();
		$configurationManager->setPackages($packageManager->getActivePackages());

		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$environment = new \TYPO3\FLOW3\Utility\Environment($context);
		$environment->setTemporaryDirectoryBase($settings['utility']['environment']['temporaryDirectoryBase']);

		$configurationManager->injectEnvironment($environment);
		$packageManager->injectSettings($settings);

		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager', $configurationManager);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Utility\Environment', $environment);
	}

	/**
	 * Initializes the System Logger
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSystemLogger(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$systemLogger = \TYPO3\FLOW3\Log\LoggerFactory::create('SystemLogger', 'TYPO3\FLOW3\Log\Logger', $settings['log']['systemLogger']['backend'], $settings['log']['systemLogger']['backendOptions']);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface', $systemLogger);
	}


	/**
	 * Initializes the Lock Manager
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeLockManager(Bootstrap $bootstrap) {
		$systemLogger = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface');

		$lockManager = new \TYPO3\FLOW3\Core\LockManager();
		$lockManager->injectEnvironment($bootstrap->getEarlyInstance('TYPO3\FLOW3\Utility\Environment'));
		$lockManager->injectSystemLogger($systemLogger);
		$lockManager->initializeObject();

		$lockManager->exitIfSiteLocked();

		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Core\LockManager', $lockManager);
	}

	/**
	 * Initializes the error handling
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeErrorHandling(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$errorHandler = new \TYPO3\FLOW3\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors($settings['error']['errorHandler']['exceptionalErrors']);
		$exceptionHandler = new $settings['error']['exceptionHandler']['className'];
		$exceptionHandler->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface'));
	}

	/**
	 * Initializes the cache framework
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeCacheManagement(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$environment = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Utility\Environment');

		$cacheManager = new \TYPO3\FLOW3\Cache\CacheManager();
		$cacheManager->setCacheConfigurations($configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));
		$cacheManager->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface'));

		$cacheFactory = new \TYPO3\FLOW3\Cache\CacheFactory($bootstrap->getContext(), $cacheManager, $environment);

		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Cache\CacheManager', $cacheManager);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Cache\CacheFactory', $cacheFactory);
	}

	/**
	 * Runs the compile step if neccessary
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 * @throws \TYPO3\FLOW3\Exception
	 */
	static public function initializeProxyClasses(Bootstrap $bootstrap) {
		$objectConfigurationCache = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager')->getCache('FLOW3_Object_Configuration');

		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

			// In Production context, the compile sub command will only be run if the
			// code cache is completely empty:
		if (!$bootstrap->getContext()->isProduction() || $objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
			self::executeCommand('typo3.flow3:core:compile', $settings);
			if (isset($settings['persistence']['doctrine']['enable']) && $settings['persistence']['doctrine']['enable'] === TRUE) {
				self::compileDoctrineProxies($bootstrap);
			}
		}

		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
			$phpBinaryPathAndFilename = escapeshellcmd(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
			$command = '"' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' -v';
			exec($command, $output, $result);
			if ($result !== 0) {
				throw new \TYPO3\FLOW3\Exception('It seems like the PHP binary "' . $settings['core']['phpBinaryPathAndFilename'] . '" cannot be executed by FLOW3. Set the correct path to the PHP executable in Configuration/Settings.yaml, setting TYPO3.FLOW3.core.phpBinaryPathAndFilename.', 1315561483);
			}
			echo PHP_EOL . 'FLOW3: The compile run failed. Please check the error output or system log for more information.' . PHP_EOL;
			exit(1);
		}

	}

	/**
	 * Initializes the Compiletime Object Manager (phase 1)
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 */
	static public function initializeObjectManagerCompileTimeCreate(Bootstrap $bootstrap) {
		$objectManager = new \TYPO3\FLOW3\Object\CompileTimeObjectManager($bootstrap->getContext());
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Object\ObjectManagerInterface', $objectManager);
		Bootstrap::$staticObjectManager = $objectManager;

		$signalSlotDispatcher = $bootstrap->getEarlyInstance('TYPO3\FLOW3\SignalSlot\Dispatcher');
		$signalSlotDispatcher->injectObjectManager($objectManager);

		foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
			$objectManager->setInstance($objectName, $instance);
		}
	}

	/**
	 * Initializes the Compiletime Object Manager (phase 2)
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeObjectManagerCompileTimeFinalize(Bootstrap $bootstrap) {
		$objectManager = $bootstrap->getObjectManager();
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$reflectionService = $objectManager->get('TYPO3\FLOW3\Reflection\ReflectionService');
		$cacheManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager');
		$systemLogger = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface');
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface');

		$objectManager->injectAllSettings($configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$objectManager->injectReflectionService($reflectionService);
		$objectManager->injectConfigurationManager($configurationManager);
		$objectManager->injectConfigurationCache($cacheManager->getCache('FLOW3_Object_Configuration'));
		$objectManager->injectSystemLogger($systemLogger);
		$objectManager->initialize($packageManager->getActivePackages());

		foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
			$objectManager->setInstance($objectName, $instance);
		}

		\TYPO3\FLOW3\Error\Debugger::injectObjectManager($objectManager);
	}

	/**
	 * Initializes the runtime Object Manager
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeObjectManager(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$objectConfigurationCache = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager')->getCache('FLOW3_Object_Configuration');

		$objectManager = new \TYPO3\FLOW3\Object\ObjectManager($bootstrap->getContext());
		Bootstrap::$staticObjectManager = $objectManager;

		$objectManager->injectAllSettings($configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$objectManager->setObjects($objectConfigurationCache->get('objects'));

		foreach ($bootstrap->getEarlyInstances() as $objectName => $instance) {
			$objectManager->setInstance($objectName, $instance);
		}

		$objectManager->get('TYPO3\FLOW3\SignalSlot\Dispatcher')->injectObjectManager($objectManager);
		\TYPO3\FLOW3\Error\Debugger::injectObjectManager($objectManager);
		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Object\ObjectManagerInterface', $objectManager);
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeReflectionService(Bootstrap $bootstrap) {
		$cacheManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager');
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$reflectionService = new \TYPO3\FLOW3\Reflection\ReflectionService();

		$reflectionService->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$reflectionService->injectClassLoader($bootstrap->getEarlyInstance('TYPO3\FLOW3\Core\ClassLoader'));
		$reflectionService->injectSettings($settings);
		$reflectionService->injectPackageManager($bootstrap->getEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface'));
		$reflectionService->setStatusCache($cacheManager->getCache('FLOW3_Reflection_Status'));
		$reflectionService->setReflectionDataCompiletimeCache($cacheManager->getCache('FLOW3_Reflection_CompiletimeData'));
		$reflectionService->setReflectionDataRuntimeCache($cacheManager->getCache('FLOW3_Reflection_RuntimeData'));
		$reflectionService->setClassSchemataRuntimeCache($cacheManager->getCache('FLOW3_Reflection_RuntimeClassSchemata'));
		$reflectionService->injectSettings($configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3'));
		$reflectionService->injectEnvironment($bootstrap->getEarlyInstance('TYPO3\FLOW3\Utility\Environment'));

		$reflectionService->initialize($bootstrap);

		$bootstrap->setEarlyInstance('TYPO3\FLOW3\Reflection\ReflectionService', $reflectionService);
		$bootstrap->getObjectManager()->setInstance('TYPO3\FLOW3\Reflection\ReflectionService', $reflectionService);
	}

	/**
	 * Checks if classes (i.e. php files containing classes), Policy.yaml, Objects.yaml
	 * or localization files have been altered and if so flushes the related caches.
	 *
	 * This function only triggers the detection of changes in the file monitors.
	 * The actual cache flushing is handled by other functions which are triggered
	 * by the file monitor through a signal. For FLOW3, those signal-slot connections
	 * are defined in the class \TYPO3\FLOW3\Package.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSystemFileMonitor(Bootstrap $bootstrap) {
		$fileMonitors = array(
			'FLOW3_ClassFiles' => self::createFileMonitor('FLOW3_ClassFiles', $bootstrap),
			'FLOW3_ConfigurationFiles' => self::createFileMonitor('FLOW3_ConfigurationFiles', $bootstrap),
			'FLOW3_TranslationFiles' => self::createFileMonitor('FLOW3_TranslationFiles', $bootstrap)
		);

		$context = $bootstrap->getContext();
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface');
		foreach ($packageManager->getActivePackages() as $packageKey => $package) {
			if ($packageManager->isPackageFrozen($packageKey)) {
				continue;
			}
			self::monitorDirectoryIfItExists($fileMonitors['FLOW3_ClassFiles'], $package->getClassesPath());
			self::monitorDirectoryIfItExists($fileMonitors['FLOW3_ConfigurationFiles'], $package->getConfigurationPath());
			self::monitorDirectoryIfItExists($fileMonitors['FLOW3_TranslationFiles'], $package->getResourcesPath() . 'Private/Translations/');
			if ($context->isTesting()) {
				self::monitorDirectoryIfItExists($fileMonitors['FLOW3_ClassFiles'], $package->getFunctionalTestsPath());
			}
		}

		self::monitorDirectoryIfItExists($fileMonitors['FLOW3_ConfigurationFiles'], FLOW3_PATH_CONFIGURATION);

		foreach ($fileMonitors as $fileMonitor) {
			$fileMonitor->detectChanges();
		}
		foreach ($fileMonitors as $fileMonitor) {
			$fileMonitor->shutdownObject();
		}
	}

	/**
	 * Factory method for conveniently building a file monitor using a
	 * ModificationTimeStrategy.
	 *
	 * @param string $monitorIdentifier Identifier for the new file monitor
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The bootstrap instance
	 * @return \TYPO3\FLOW3\Monitor\FileMonitor
	 */
	static protected function createFileMonitor($monitorIdentifier, Bootstrap $bootstrap) {
		$fileMonitorCache = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager')->getCache('FLOW3_Monitor');

			// The change detector needs to be instantiated and registered manually because
			// it has a complex dependency (cache) but still needs to be a singleton.
		$fileChangeDetector = new \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$fileChangeDetector->injectCache($fileMonitorCache);
		$bootstrap->getObjectManager()->registerShutdownObject($fileChangeDetector, 'shutdownObject');

		$fileMonitor = new \TYPO3\FLOW3\Monitor\FileMonitor($monitorIdentifier);
		$fileMonitor->injectCache($fileMonitorCache);
		$fileMonitor->injectChangeDetectionStrategy($fileChangeDetector);
		$fileMonitor->injectSignalDispatcher($bootstrap->getEarlyInstance('TYPO3\FLOW3\SignalSlot\Dispatcher'));
		$fileMonitor->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface'));
		$fileMonitor->initializeObject();

		return $fileMonitor;
	}

	/**
	 * Let the given file monitor track changes of the specified directory if it exists.
	 *
	 * @param \TYPO3\FLOW3\Monitor\FileMonitor $fileMonitor
	 * @param string $path
	 * @return void
	 */
	static protected function monitorDirectoryIfItExists(\TYPO3\FLOW3\Monitor\FileMonitor $fileMonitor, $path) {
		if (is_dir($path)) {
			$fileMonitor->monitorDirectory($path);
		}
	}

	/**
	 * Update Doctrine 2 proxy classes
	 *
	 * This is not simply bound to the finishedCompilationRun signal because it
	 * needs the advised proxy classes to run. When that signal is fired, they
	 * have been written, but not loaded.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static protected function compileDoctrineProxies(Bootstrap $bootstrap) {
		$cacheManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Cache\CacheManager');
		$objectConfigurationCache = $cacheManager->getCache('FLOW3_Object_Configuration');
		$coreCache = $cacheManager->getCache('FLOW3_Core');
		$systemLogger = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Log\SystemLoggerInterface');
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		if ($objectConfigurationCache->has('doctrineProxyCodeUpToDate') === FALSE && $coreCache->has('doctrineSetupRunning') === FALSE) {
			$coreCache->set('doctrineSetupRunning', 'White Russian', array(), 60);
			$systemLogger->log('Compiling Doctrine proxies', LOG_DEBUG);
			self::executeCommand('typo3.flow3:doctrine:compileproxies', $settings);
			$coreCache->remove('doctrineSetupRunning');
			$objectConfigurationCache->set('doctrineProxyCodeUpToDate', TRUE);
		}
	}

	/**
	 * Initializes the I18n service
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeI18n(Bootstrap $bootstrap) {
		$bootstrap->getObjectManager()->get('TYPO3\FLOW3\I18n\Service')->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializePersistence(Bootstrap $bootstrap) {
		$persistenceManager = $bootstrap->getObjectManager()->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$persistenceManager->initialize();
	}

	/**
	 * Initializes the session framework
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSession(Bootstrap $bootstrap) {
		if (FLOW3_SAPITYPE === 'Web') {
			$bootstrap->getObjectManager()->get('TYPO3\FLOW3\Session\SessionInterface')->resume();
		}
	}

	/**
	 * Initialize the resource management component, setting up stream wrappers,
	 * publishing the public resources of all found packages, ...
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeResources(Bootstrap $bootstrap) {
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\FLOW3\Package\PackageManagerInterface');
		$resourceManager = $bootstrap->getObjectManager()->get('TYPO3\FLOW3\Resource\ResourceManager');
		$resourceManager->initialize();
		$resourceManager->publishPublicPackageResources($packageManager->getActivePackages());
	}

	/**
	 * Executes the given command as a sub-request to the FLOW3 CLI system.
	 *
	 * @param string $commandIdentifier E.g. typo3.flow3:cache:flush
	 * @param array $settings The FLOW3 settings
	 * @param boolean $outputResults if FALSE the output of this command is only echoed if the execution was not successful
	 * @return boolean TRUE if the command execution was successful (exit code = 0)
	 * @api
	 */
	static public function executeCommand($commandIdentifier, array $settings, $outputResults = TRUE) {
		$phpBinaryPathAndFilename = escapeshellcmd(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
		if (DIRECTORY_SEPARATOR === '/') {
			$command = 'XDEBUG_CONFIG="idekey=FLOW3_SUBREQUEST" FLOW3_ROOTPATH=' . escapeshellarg(FLOW3_PATH_ROOT) . ' ' . 'FLOW3_CONTEXT=' . escapeshellarg($settings['core']['context']) . ' "' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' ' . escapeshellarg(FLOW3_PATH_FLOW3 . 'Scripts/flow3.php') . ' ' . escapeshellarg($commandIdentifier);
		} else {
			$command = 'SET FLOW3_ROOTPATH=' . escapeshellarg(FLOW3_PATH_ROOT) . '&' . 'SET FLOW3_CONTEXT=' . escapeshellarg($settings['core']['context']) . '&"' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' ' . escapeshellarg(FLOW3_PATH_FLOW3 . 'Scripts/flow3.php') . ' ' . escapeshellarg($commandIdentifier);
		}
		$output = array();
		exec($command, $output, $result);
		if ($outputResults || $result !== 0) {
			echo implode(PHP_EOL, $output);
		}
		return $result === 0;
	}

}

?>