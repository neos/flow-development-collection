<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Core;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

	// Those are needed before the autoloader is active
require_once(__DIR__ . '/../Utility/Files.php');
require_once(__DIR__ . '/../Package/PackageInterface.php');
require_once(__DIR__ . '/../Package/Package.php');

/**
 * General purpose central core hyper FLOW3 bootstrap class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Bootstrap {

	/**
	 * Required PHP version
	 */
	const MINIMUM_PHP_VERSION = '5.3.0RC2-dev';
	const MAXIMUM_PHP_VERSION = '5.99.9';

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

	/**
	 * Flag telling if the site / application is currently locked, e.g. due to flushing the code caches
	 * @var boolean
	 */
	protected $siteLocked = FALSE;

	/**
	 * @var \F3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * A reference to the FLOW3 package which can be used before the Package Manager is initialized
	 * @var \F3\FLOW3\Package\Package
	 */
	protected $FLOW3Package;

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \F3\FLOW3\Error\ExceptionHandlerInterface
	 */
	protected $exceptionHandler;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * The settings for the FLOW3 package
	 * @var array
	 */
	protected $settings;

	/**
	 * Defines various path constants used by FLOW3 and if no root path or web root was
	 * specified by an environment variable, exits with a respective error message.
	 *
	 * @return void
	 */
	public static function defineConstants() {
		if (!defined('FLOW3_PATH_FLOW3')) {
			define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../') . '/'))));
		}
		define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

		$rootPath = isset($_SERVER['FLOW3_ROOTPATH']) ? $_SERVER['FLOW3_ROOTPATH'] : FALSE;
		if ($rootPath === FALSE && isset($_SERVER['REDIRECT_FLOW3_ROOTPATH'])) {
			$rootPath = $_SERVER['REDIRECT_FLOW3_ROOTPATH'];
		}
		if ($rootPath !== FALSE) {
			$rootPath = str_replace('//', '/', str_replace('\\', '/', (realpath($rootPath)))) . '/';
			$testPath = str_replace('//', '/', str_replace('\\', '/', (realpath($rootPath . 'Packages/Framework/FLOW3')))) . '/';
			if ($testPath !== FLOW3_PATH_FLOW3) {
				exit('FLOW3: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $rootPath . 'Packages/Framework/FLOW3' .'" does not lead to' . PHP_EOL . '"' . FLOW3_PATH_FLOW3 .'"' . PHP_EOL);
			}
			define('FLOW3_PATH_ROOT', $rootPath);
			unset($rootPath);
			unset($testPath);
		}

		if (FLOW3_SAPITYPE === 'CLI') {
			if (!defined('FLOW3_PATH_ROOT')) {
				exit('FLOW3: No root path defined in environment variable FLOW3_ROOTPATH (Error #1248964376)' . PHP_EOL);
			}
			if (!isset($_SERVER['FLOW3_WEBPATH']) || !is_dir($_SERVER['FLOW3_WEBPATH'])) {
				exit('FLOW3: No web path defined in environment variable FLOW3_WEBPATH or directory does not exist (Error #1249046843)' . PHP_EOL);
			}
			define('FLOW3_PATH_WEB', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($_SERVER['FLOW3_WEBPATH'])) . '/');
		} else {
			if (!defined('FLOW3_PATH_ROOT')) {
				define('FLOW3_PATH_ROOT', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../')) . '/');
			}
			define('FLOW3_PATH_WEB', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']))) . '/');
		}

		define('FLOW3_PATH_CONFIGURATION', FLOW3_PATH_ROOT . 'Configuration/');
		define('FLOW3_PATH_DATA', FLOW3_PATH_ROOT . 'Data/');
		define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');
	}

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($context = 'Production') {
		$this->ensureRequiredEnvironment();
		$this->context = (strlen($context) === 0) ? 'Production' : $context;

		if ($this->context !== 'Production' && $this->context !== 'Development' && $this->context !== 'Testing') {
			exit('FLOW3: Unknown context "' . $this->context . '" provided, currently only "Production", "Development" and "Testing" are supported. (Error #1254216868)');
		}
		$this->FLOW3Package = new \F3\FLOW3\Package\Package('FLOW3', FLOW3_PATH_FLOW3);
	}

	/**
	 * Explicitly initializes all necessary FLOW3 objects by invoking the various initialize* methods.
	 *
	 * Usually this method is only called from unit tests or other applications which need a more fine grained control over
	 * the initialization and request handling process. Most other applications just call the run() method.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see run()
	 * @throws \F3\FLOW3\Exception if the framework has already been initialized.
	 * @api
	 */
	public function initialize() {
		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeErrorHandling();

		$this->initializeDirectories();

		$this->initializeObjectManager();
		$this->initializeSystemLogger();

#		$this->initializeLockManager();
		if ($this->siteLocked === TRUE) return;

		$this->initializePackages();

		$this->initializeSignalsSlots();
		$this->initializeCache();
		$this->initializeFileMonitor();
		$this->initializeReflection();
		$this->initializeObjectContainer();
		$this->initializePersistence();
		$this->initializeSession();
		$this->initializeResources();
		$this->initializeI18n();
	}

	/**
	 * Check and if needed create basic directories needed by FLOW3.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function initializeDirectories() {
		if (!is_dir(FLOW3_PATH_DATA)) mkdir(FLOW3_PATH_DATA);
		if (!is_dir(FLOW3_PATH_DATA . 'Persistent')) mkdir(FLOW3_PATH_DATA . 'Persistent');
	}

	/**
	 * Initializes the class loader
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	public function initializeClassLoader() {
		require_once(FLOW3_PATH_FLOW3 . 'Classes/Resource/ClassLoader.php');
		$this->classLoader = new \F3\FLOW3\Resource\ClassLoader();
		$this->classLoader->setPackages(array('FLOW3' => $this->FLOW3Package));
		spl_autoload_register(array($this->classLoader, 'loadClass'), TRUE, TRUE);
	}

	/**
	 * Initializes the configuration manager and the FLOW3 settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeConfiguration() {
		$this->configurationManager = new \F3\FLOW3\Configuration\ConfigurationManager($this->context);
		$this->configurationManager->injectConfigurationSource(new \F3\FLOW3\Configuration\Source\YamlSource());
		$this->configurationManager->setPackages(array('FLOW3' => $this->FLOW3Package));

		$this->settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');
	}

	/**
	 * Initializes the error handling
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeErrorHandling() {
		$errorHandler = new \F3\FLOW3\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors($this->settings['error']['errorHandler']['exceptionalErrors']);
		$this->exceptionHandler = new $this->settings['error']['exceptionHandler']['className'];
		$this->classLoader->loadClass('F3\FLOW3\Error\Debugger');
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjectManager() {
		$this->objectManager = new \F3\FLOW3\Object\ObjectManager();
		$this->objectManager->injectClassLoader($this->classLoader);
		$this->objectManager->injectConfigurationManager($this->configurationManager);
		$this->objectManager->setContext($this->context);

		$this->objectManager->initialize();

		\F3\FLOW3\Error\Debugger::injectObjectManager($this->objectManager);
	}

	/**
	 * Initializes the system logger
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSystemLogger() {
		$this->systemLogger = $this->objectManager->get('F3\FLOW3\Log\SystemLoggerInterface');
		$this->systemLogger->log(sprintf('--- Launching FLOW3 in %s context. ---', $this->context), LOG_INFO);
		$this->exceptionHandler->injectSystemLogger($this->systemLogger);
	}

	/**
	 * Initializes the Lock Manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeLockManager() {
		$lockManager = $this->objectManager->get('F3\FLOW3\Core\LockManager');
		$this->siteLocked = $lockManager->isSiteLocked();
		$this->exceptionHandler->injectLockManager($lockManager);
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializePackages() {
		$this->packageManager = $this->objectManager->get('F3\FLOW3\Package\PackageManagerInterface');
		$this->packageManager->initialize();
		$activePackages = $this->packageManager->getActivePackages();
		$this->classLoader->setPackages($activePackages);
		$this->configurationManager->setPackages($activePackages);

		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_PACKAGE, $packageKey);
			$this->evaluatePackageConfiguration($package, $packageConfiguration);
		}
	}

	/**
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	public function initializeSignalsSlots() {
		$this->signalSlotDispatcher = $this->objectManager->get('F3\FLOW3\SignalSlot\Dispatcher');

		$signalsSlotsConfiguration = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SIGNALSSLOTS);
		foreach ($signalsSlotsConfiguration as $signalClassName => $signalSubConfiguration) {
			if (is_array($signalSubConfiguration)) {
				foreach ($signalSubConfiguration as $signalMethodName => $slotConfigurations) {
					$signalMethodName = 'emit' . ucfirst($signalMethodName);
					if (is_array($slotConfigurations)) {
						foreach ($slotConfigurations as $slotConfiguration) {
							if (is_array($slotConfiguration)) {
								if (isset($slotConfiguration[0]) && isset($slotConfiguration[1])) {
									$omitSignalInformation = (isset($slotConfiguration[2])) ? TRUE : FALSE;
									$this->signalSlotDispatcher->connect($signalClassName, $signalMethodName, $slotConfiguration[0], $slotConfiguration[1], $omitSignalInformation);
								}
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Initializes the cache framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeCache() {
		$this->cacheManager = $this->objectManager->get('F3\FLOW3\Cache\CacheManager');
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));
		$this->cacheManager->initialize();

		$cacheFactory = $this->objectManager->get('F3\FLOW3\Cache\CacheFactory');
		$cacheFactory->setCacheManager($this->cacheManager);
	}

	/**
	 * Initializes the file monitoring
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeFileMonitor() {
		if ($this->settings['monitor']['detectClassChanges'] === TRUE) {
			$this->monitorClassFiles();
			$this->monitorRoutesConfigurationFiles();
		}
	}

	/**
	 * Checks if classes (ie. php files containing classes) have been altered and if so flushes
	 * the related caches.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function monitorClassFiles() {
		$monitor = $this->objectManager->create('F3\FLOW3\Monitor\FileMonitor', 'FLOW3_ClassFiles');

		foreach ($this->packageManager->getActivePackages() as $package) {
			$classesPath = $package->getClassesPath();
			if (is_dir($classesPath)) {
				$monitor->monitorDirectory($classesPath);
			}
		}

		$classFileCache = $this->cacheManager->getCache('FLOW3_Cache_ClassFiles');
		$cacheManager = $this->cacheManager;
		$cacheFlushingSlot = function() use ($classFileCache, $cacheManager) {
			list($signalName, $monitorIdentifier, $changedFiles) = func_get_args();
			if ($monitorIdentifier === 'FLOW3_ClassFiles') {
				foreach ($changedFiles as $pathAndFilename => $status) {
					$matches = array();
					if (1 === preg_match('/.+\/(.+)\/Classes\/(.+)\.php/', $pathAndFilename, $matches)) {
						$className = 'F3\\' . $matches[1] . '\\' . str_replace('/', '\\', $matches[2]);
						$cacheManager->flushCachesByTag($classFileCache->getClassTag($className));
					}
				}
				if (count($changedFiles) > 0) {
					$cacheManager->flushCachesByTag($classFileCache->getClassTag());
				}
			}
		};

		$this->signalSlotDispatcher->connect('F3\FLOW3\Monitor\FileMonitor', 'emitFilesHaveChanged', $cacheFlushingSlot);
		$monitor->detectChanges();
	}

	/**
	 * Checks if Routes.yaml files have been altered and if so flushes the
	 * related caches.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function monitorRoutesConfigurationFiles() {
		$monitor = $this->objectManager->create('F3\FLOW3\Monitor\FileMonitor', 'FLOW3_RoutesConfigurationFiles');
		$monitor->monitorFile(FLOW3_PATH_CONFIGURATION . 'Routes.yaml');
		$monitor->monitorFile(FLOW3_PATH_CONFIGURATION . $this->context . '/Routes.yaml');

		$cacheManager = $this->cacheManager;
		$cacheFlushingSlot = function() use ($cacheManager) {
			list($signalName, $monitorIdentifier, $changedFiles) = func_get_args();
			if ($monitorIdentifier === 'FLOW3_RoutesConfigurationFiles') {
				$findMatchResultsCache = $cacheManager->getCache('FLOW3_MVC_Web_Routing_FindMatchResults');
				$findMatchResultsCache->flush();
				$resolveCache = $cacheManager->getCache('FLOW3_MVC_Web_Routing_Resolve');
				$resolveCache->flush();
			}
		};

		$this->signalSlotDispatcher->connect('F3\FLOW3\Monitor\FileMonitor', 'emitFilesHaveChanged', $cacheFlushingSlot);

		$monitor->detectChanges();
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeReflection() {
		$this->reflectionService = $this->objectManager->get('F3\FLOW3\Reflection\ReflectionService');
		$this->reflectionService->setStatusCache($this->cacheManager->getCache('FLOW3_ReflectionStatus'));
		$this->reflectionService->setDataCache($this->cacheManager->getCache('FLOW3_ReflectionData'));
		$this->reflectionService->injectSystemLogger($this->systemLogger);
		$this->reflectionService->injectPackageManager($this->packageManager);

		$this->reflectionService->initialize();
	}

	/**
	 * Initializes the Object Container
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjectContainer() {
		$this->objectManager->initializeObjectContainer($this->packageManager->getActivePackages());
	}

	/**
	 * Initializes the Locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	public function initializeI18n() {
		$this->objectManager->get('F3\FLOW3\I18n\Service')->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializePersistence() {
		$persistenceManager = $this->objectManager->get('F3\FLOW3\Persistence\PersistenceManagerInterface');
		$persistenceManager->initialize();
	}

	/**
	 * Initializes the Object Container's session scope
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeSession() {
		$this->objectManager->initializeSession();
	}

	/**
	 * Initialize the resource management component, setting up stream wrappers,
	 * publishing the public resources of all found packages, ...
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	public function initializeResources() {
		$resourceManager = $this->objectManager->get('F3\FLOW3\Resource\ResourceManager');
		$resourceManager->initialize();
		if (FLOW3_SAPITYPE === 'Web') {
			$resourceManager->publishPublicPackageResources($this->packageManager->getActivePackages());
		}
	}

	/**
	 * Returns the object manager instance
	 *
	 * @return \F3\FLOW3\Object\ObjectManagerInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectManager() {
		return $this->objectManager;
	}

	/**
	 * Runs the the FLOW3 Framework by resolving an appropriate Request Handler and passing control to it.
	 * If the Framework is not initialized yet, it will be initialized.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function run() {
		if (!$this->siteLocked) {
			$requestHandlerResolver = $this->objectManager->get('F3\FLOW3\MVC\RequestHandlerResolver');
			$requestHandler = $requestHandlerResolver->resolveRequestHandler();
			$requestHandler->handleRequest();

			$this->objectManager->get('F3\FLOW3\Persistence\PersistenceManagerInterface')->persistAll();

			$this->emitFinishedNormalRun();
			$this->systemLogger->log('Shutting down ...', LOG_INFO);

			$this->configurationManager->shutdown();
			$this->objectManager->shutdown();
		} else {
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			readfile('resource://FLOW3/Private/Core/LockHoldingStackPage.html');
			$this->systemLogger->log('Site is locked, exiting.', LOG_NOTICE);
		}
	}

	/**
	 * Signalizes that FLOW3 completed the shutdown process after a normal run.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 * @see run()
	 * @api
	 */
	protected function emitFinishedNormalRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, array());
	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function ensureRequiredEnvironment() {
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			exit('FLOW3 requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		if (version_compare(PHP_VERSION, self::MAXIMUM_PHP_VERSION, '>')) {
			exit('FLOW3 requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)');
		}
		if (version_compare(PHP_VERSION, '6.0.0', '<') && !extension_loaded('mbstring')) {
			exit('FLOW3 requires the PHP extension "mbstring" for PHP versions below 6.0.0 (Error #1207148809)');
		}

		if (!extension_loaded('Reflection')) throw new \F3\FLOW3\Exception('The PHP extension "Reflection" is required by FLOW3.', 1218016725);
		$method = new \ReflectionMethod(__CLASS__, __FUNCTION__);
		if ($method->getDocComment() === '') throw new \F3\FLOW3\Exception('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.', 1218016727);

		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
		#locale_set_default('en_UK');
		if (ini_get('date.timezone') === '') {
			date_default_timezone_set('Europe/Copenhagen');
		}

		if (ini_get('magic_quotes_gpc') === '1' || ini_get('magic_quotes_gpc') === 'On') {
			exit('FLOW3 requires the PHP setting "magic_quotes_gpc" set to Off. (Error #1224003190)');
		}
	}

	/**
	 * (For now) evaluates the package configuration
	 *
	 * @param \F3\FLOW3\Package\PackageInterface $package The package
	 * @param array $packageConfiguration The configuration to evaluate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initializePackages()
	 * @todo needs refactoring and be moved to elsewhere (package manager)
	 */
	protected function evaluatePackageConfiguration(\F3\FLOW3\Package\PackageInterface $package, array $packageConfiguration) {
		if (isset($packageConfiguration['classLoader'])) {
			if (isset($packageConfiguration['classLoader']['specialClassNameAndPaths'])) {
				$classLoader = $this->objectManager->get('F3\FLOW3\Resource\ClassLoader');
				foreach ($packageConfiguration['classLoader']['specialClassNameAndPaths'] as $className => $classFilePathAndName) {
					$classFilePathAndName = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $classFilePathAndName);
					$classLoader->setSpecialClassNameAndPath($className, $classFilePathAndName);
				}
			}

			if (isset($packageConfiguration['classLoader']['includePaths'])) {
				foreach ($packageConfiguration['classLoader']['includePaths'] as $includePath) {
					$includePath = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $includePath);
					$includePath = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $includePath);
					$includePath = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $includePath);
					$includePath = str_replace('/', DIRECTORY_SEPARATOR, $includePath);
					set_include_path($includePath . PATH_SEPARATOR . get_include_path());
				}
			}

			if (isset($packageConfiguration['classLoader']['autoLoader'])) {
				$autoLoaderPathAndFilename = \F3\FLOW3\Utility\Files::concatenatePaths(array($package->getPackagePath(), $packageConfiguration['classLoader']['autoLoader']));
				if (file_exists($autoLoaderPathAndFilename)) {
					require($autoLoaderPathAndFilename);
				}
			}
		}
	}
}

?>