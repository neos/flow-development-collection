<?php
namespace TYPO3\Flow\Core\Booting;

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
use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Http\Uri;

/**
 * Initialization scripts for modules of the Flow package
 *
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class Scripts {

	/**
	 * Initializes the Class Loader
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeClassLoader(Bootstrap $bootstrap) {
		require_once(FLOW_PATH_FLOW . 'Classes/TYPO3/Flow/Core/ClassLoader.php');
		$classLoader = new \TYPO3\Flow\Core\ClassLoader();
		spl_autoload_register(array($classLoader, 'loadClass'), TRUE, TRUE);
		$bootstrap->setEarlyInstance('TYPO3\Flow\Core\ClassLoader', $classLoader);
	}

	/**
	 * Injects the classes cache to the already initialized class loader
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeClassLoaderClassesCache(Bootstrap $bootstrap) {
		$classesCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Object_Classes');
		$bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader')->injectClassesCache($classesCache);
	}

	/**
	 * Does some emergency, forced, low level flush caches if the user told to do
	 * so through the command line.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function forceFlushCachesIfNeccessary(Bootstrap $bootstrap) {
		if (!isset($_SERVER['argv']) || !isset($_SERVER['argv'][1]) || !isset($_SERVER['argv'][2])
			|| !in_array($_SERVER['argv'][1], array('typo3.flow:cache:flush', 'flow:cache:flush'))
			|| !in_array($_SERVER['argv'][2], array('--force', '-f'))) {
			return;
		}

		$bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->flushCaches();
		$environment = $bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment');
		\TYPO3\Flow\Utility\Files::emptyDirectoryRecursively($environment->getPathToTemporaryDirectory());

		echo 'Force-flushed caches for "' . $bootstrap->getContext() . '" context.' . PHP_EOL;
		exit(0);
	}

	/**
	 * Initializes the Signal Slot module
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSignalSlot(Bootstrap $bootstrap) {
		$bootstrap->setEarlyInstance('TYPO3\Flow\SignalSlot\Dispatcher', new \TYPO3\Flow\SignalSlot\Dispatcher());
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializePackageManagement(Bootstrap $bootstrap) {
		$packageManager = new \TYPO3\Flow\Package\PackageManager();
		$bootstrap->setEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface', $packageManager);
		$packageManager->injectClassLoader($bootstrap->getEarlyInstance('TYPO3\Flow\Core\ClassLoader'));
		$packageManager->initialize($bootstrap);
	}

	/**
	 * Initializes the Configuration Manager, the Flow settings and the Environment service
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeConfiguration(Bootstrap $bootstrap) {
		$context = $bootstrap->getContext();
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');

		$configurationManager = new \TYPO3\Flow\Configuration\ConfigurationManager($context);
		$configurationManager->injectConfigurationSource(new \TYPO3\Flow\Configuration\Source\YamlSource());
		$configurationManager->loadConfigurationCache();
		$configurationManager->setPackages($packageManager->getActivePackages());

		$settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

		$environment = new \TYPO3\Flow\Utility\Environment($context);
		$environment->setTemporaryDirectoryBase($settings['utility']['environment']['temporaryDirectoryBase']);

		$configurationManager->injectEnvironment($environment);
		$packageManager->injectSettings($settings);

		$bootstrap->getSignalSlotDispatcher()->dispatch('TYPO3\Flow\Configuration\ConfigurationManager', 'configurationManagerReady', array($configurationManager));

		$bootstrap->setEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager', $configurationManager);
		$bootstrap->setEarlyInstance('TYPO3\Flow\Utility\Environment', $environment);
	}

	/**
	 * Initializes the System Logger
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSystemLogger(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

		$systemLogger = \TYPO3\Flow\Log\LoggerFactory::create('SystemLogger', 'TYPO3\Flow\Log\Logger', $settings['log']['systemLogger']['backend'], $settings['log']['systemLogger']['backendOptions']);
		$bootstrap->setEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface', $systemLogger);
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
		$packageManager->injectSystemLogger($systemLogger);
	}


	/**
	 * Initializes the Lock Manager
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeLockManager(Bootstrap $bootstrap) {
		$systemLogger = $bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface');

		$lockManager = new \TYPO3\Flow\Core\LockManager();
		$lockManager->injectEnvironment($bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment'));
		$lockManager->injectSystemLogger($systemLogger);
		$lockManager->initializeObject();

		$lockManager->exitIfSiteLocked();

		$bootstrap->setEarlyInstance('TYPO3\Flow\Core\LockManager', $lockManager);
	}

	/**
	 * Initializes the error handling
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeErrorHandling(Bootstrap $bootstrap) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeCacheManagement(Bootstrap $bootstrap) {
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		$environment = $bootstrap->getEarlyInstance('TYPO3\Flow\Utility\Environment');

		$cacheManager = new \TYPO3\Flow\Cache\CacheManager();
		$cacheManager->setCacheConfigurations($configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));
		$cacheManager->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));

		$cacheFactory = new \TYPO3\Flow\Cache\CacheFactory($bootstrap->getContext(), $cacheManager, $environment);

		$bootstrap->setEarlyInstance('TYPO3\Flow\Cache\CacheManager', $cacheManager);
		$bootstrap->setEarlyInstance('TYPO3\Flow\Cache\CacheFactory', $cacheFactory);
	}

	/**
	 * Runs the compile step if neccessary
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	static public function initializeProxyClasses(Bootstrap $bootstrap) {
		$objectConfigurationCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Object_Configuration');

		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

			// The compile sub command will only be run if the code cache is completely empty:
		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
			self::executeCommand('typo3.flow:core:compile', $settings);
			if (isset($settings['persistence']['doctrine']['enable']) && $settings['persistence']['doctrine']['enable'] === TRUE) {
				self::compileDoctrineProxies($bootstrap);
			}
		}

			// Check if code was updated, if not something went wrong
		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 * @throws \TYPO3\Flow\Exception
	 */
	static public function recompileClasses(Bootstrap $bootstrap) {
		self::initializeProxyClasses($bootstrap);
	}

	/**
	 * Initializes the Compiletime Object Manager (phase 1)
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 */
	static public function initializeObjectManagerCompileTimeCreate(Bootstrap $bootstrap) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeObjectManagerCompileTimeFinalize(Bootstrap $bootstrap) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeObjectManager(Bootstrap $bootstrap) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeReflectionService(Bootstrap $bootstrap) {
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

		$reflectionService->initialize($bootstrap);

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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSystemFileMonitor(Bootstrap $bootstrap) {
		$fileMonitors = array(
			'Flow_ClassFiles' => self::createFileMonitor('Flow_ClassFiles', $bootstrap),
			'Flow_ConfigurationFiles' => self::createFileMonitor('Flow_ConfigurationFiles', $bootstrap),
			'Flow_TranslationFiles' => self::createFileMonitor('Flow_TranslationFiles', $bootstrap)
		);

		$context = $bootstrap->getContext();
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
		foreach ($packageManager->getActivePackages() as $packageKey => $package) {
			if ($packageManager->isPackageFrozen($packageKey)) {
				continue;
			}
			self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $package->getClassesPath());
			self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], $package->getConfigurationPath());
			self::monitorDirectoryIfItExists($fileMonitors['Flow_TranslationFiles'], $package->getResourcesPath() . 'Private/Translations/');
			if ($context->isTesting()) {
				self::monitorDirectoryIfItExists($fileMonitors['Flow_ClassFiles'], $package->getFunctionalTestsPath());
			}
		}

		self::monitorDirectoryIfItExists($fileMonitors['Flow_ConfigurationFiles'], FLOW_PATH_CONFIGURATION);

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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap The bootstrap instance
	 * @return \TYPO3\Flow\Monitor\FileMonitor
	 */
	static protected function createFileMonitor($monitorIdentifier, Bootstrap $bootstrap) {
		$fileMonitorCache = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager')->getCache('Flow_Monitor');

			// The change detector needs to be instantiated and registered manually because
			// it has a complex dependency (cache) but still needs to be a singleton.
		$fileChangeDetector = new \TYPO3\Flow\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$fileChangeDetector->injectCache($fileMonitorCache);
		$bootstrap->getObjectManager()->registerShutdownObject($fileChangeDetector, 'shutdownObject');

		$fileMonitor = new \TYPO3\Flow\Monitor\FileMonitor($monitorIdentifier);
		$fileMonitor->injectCache($fileMonitorCache);
		$fileMonitor->injectChangeDetectionStrategy($fileChangeDetector);
		$fileMonitor->injectSignalDispatcher($bootstrap->getEarlyInstance('TYPO3\Flow\SignalSlot\Dispatcher'));
		$fileMonitor->injectSystemLogger($bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface'));
		$fileMonitor->initializeObject();

		return $fileMonitor;
	}

	/**
	 * Let the given file monitor track changes of the specified directory if it exists.
	 *
	 * @param \TYPO3\Flow\Monitor\FileMonitor $fileMonitor
	 * @param string $path
	 * @return void
	 */
	static protected function monitorDirectoryIfItExists(\TYPO3\Flow\Monitor\FileMonitor $fileMonitor, $path) {
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
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static protected function compileDoctrineProxies(Bootstrap $bootstrap) {
		$cacheManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Cache\CacheManager');
		$objectConfigurationCache = $cacheManager->getCache('Flow_Object_Configuration');
		$coreCache = $cacheManager->getCache('Flow_Core');
		$systemLogger = $bootstrap->getEarlyInstance('TYPO3\Flow\Log\SystemLoggerInterface');
		$configurationManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Configuration\ConfigurationManager');
		$settings = $configurationManager->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.Flow');

		if ($objectConfigurationCache->has('doctrineProxyCodeUpToDate') === FALSE && $coreCache->has('doctrineSetupRunning') === FALSE) {
			$coreCache->set('doctrineSetupRunning', 'White Russian', array(), 60);
			$systemLogger->log('Compiling Doctrine proxies', LOG_DEBUG);
			self::executeCommand('typo3.flow:doctrine:compileproxies', $settings);
			$coreCache->remove('doctrineSetupRunning');
			$objectConfigurationCache->set('doctrineProxyCodeUpToDate', TRUE);
		}
	}

	/**
	 * Initializes the I18n service
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeI18n(Bootstrap $bootstrap) {
		$bootstrap->getObjectManager()->get('TYPO3\Flow\I18n\Service')->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializePersistence(Bootstrap $bootstrap) {
		$persistenceManager = $bootstrap->getObjectManager()->get('TYPO3\Flow\Persistence\PersistenceManagerInterface');
		$persistenceManager->initialize();
	}

	/**
	 * Initializes the session framework
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeSession(Bootstrap $bootstrap) {
		if (FLOW_SAPITYPE === 'Web') {
			$bootstrap->getObjectManager()->get('TYPO3\Flow\Session\SessionInterface')->resume();
		}
	}

	/**
	 * Initialize the resource management component, setting up stream wrappers,
	 * publishing the public resources of all found packages, ...
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	static public function initializeResources(Bootstrap $bootstrap) {
		$packageManager = $bootstrap->getEarlyInstance('TYPO3\Flow\Package\PackageManagerInterface');
		$resourceManager = $bootstrap->getObjectManager()->get('TYPO3\Flow\Resource\ResourceManager');
		$resourceManager->initialize();
		$resourceManager->publishPublicPackageResources($packageManager->getActivePackages());
	}

	/**
	 * Executes the given command as a sub-request to the Flow CLI system.
	 *
	 * @param string $commandIdentifier E.g. typo3.flow:cache:flush
	 * @param array $settings The TYPO3.Flow settings
	 * @param boolean $outputResults if FALSE the output of this command is only echoed if the execution was not successful
	 * @return boolean TRUE if the command execution was successful (exit code = 0)
	 * @api
	 * @throws \TYPO3\Flow\Core\Booting\Exception\SubProcessException if execution of the sub process failed
	 */
	static public function executeCommand($commandIdentifier, array $settings, $outputResults = TRUE) {
		$command = self::buildSubprocessCommand($commandIdentifier, $settings);
		$output = array();
		exec($command, $output, $result);
		if ($result !== 0) {
			$exceptionMessage = sprintf('Execution of subprocess failed with exit code %d', $result);
			if (count($output) > 0) {
				$exceptionMessage .= ' and output:' .  PHP_EOL . PHP_EOL . implode(PHP_EOL, $output);
			} else {
				$exceptionMessage .= ' and no output.';
			}
			$exceptionMessage .= PHP_EOL . PHP_EOL . 'The erroneous command was:' . PHP_EOL . $command;
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
	 *
	 * @return string A command line command ready for being exec()uted
	 */
	protected static function buildSubprocessCommand($commandIdentifier, $settings) {
		$subRequestEnvironmentVariables = array(
			'FLOW_ROOTPATH' => FLOW_PATH_ROOT,
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
				$command .= sprintf('SET %s=%s&', $argumentKey, escapeshellarg($argumentValue));
			}
		}
		if (DIRECTORY_SEPARATOR === '/') {
			$phpBinaryPathAndFilename = '"' . escapeshellcmd(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename'])) . '"';
		} else {
			$phpBinaryPathAndFilename = escapeshellarg(\TYPO3\Flow\Utility\Files::getUnixStylePath($settings['core']['phpBinaryPathAndFilename']));
		}
		$command .= $phpBinaryPathAndFilename;
		if (!isset($settings['core']['subRequestPhpIniPathAndFilename']) || $settings['core']['subRequestPhpIniPathAndFilename'] !== FALSE) {
			if (!isset($settings['core']['subRequestPhpIniPathAndFilename'])) {
				$useIniFile = php_ini_loaded_file();
			} else {
				$useIniFile = $settings['core']['subRequestPhpIniPathAndFilename'];
			}
			$command .= ' -c ' . escapeshellarg($useIniFile);
		}
		$command .= sprintf(' %s %s', escapeshellarg(FLOW_PATH_FLOW . 'Scripts/flow.php'), escapeshellarg($commandIdentifier));
		return $command;
	}
}

?>