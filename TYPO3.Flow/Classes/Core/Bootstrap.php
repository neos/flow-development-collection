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
 * @proxy disable
 * @scope singleton
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
	 * @var \F3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The same instance like $objectManager, but static, for use in the proxy classes.
	 * @var \F3\FLOW3\Object\ObjectManagerInterface
	 * @see initializeObjectManager(), getObjectManager()
	 */
	static public $staticObjectManager;

	/**
	 * @var \F3\FLOW3\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \F3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * A reference to the FLOW3 package which can be used before the Package Manager is initialized
	 * @var \F3\FLOW3\Package\Package
	 */
	protected $flow3Package;

	/**
	 * @var \F3\FLOW3\Core\ClassLoader
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public static function defineConstants() {
		if (!defined('FLOW3_PATH_FLOW3')) {
			define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../') . '/'))));
		}
		define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

		if (!defined('FLOW3_PATH_ROOT')) {
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
		}

		if (FLOW3_SAPITYPE === 'CLI') {
			if (!defined('FLOW3_PATH_ROOT')) {
				exit('FLOW3: No root path defined in environment variable FLOW3_ROOTPATH (Error #1248964376)' . PHP_EOL);
			}
			if (!defined('FLOW3_PATH_WEB')) {
				if (!isset($_SERVER['FLOW3_WEBPATH']) || !is_dir($_SERVER['FLOW3_WEBPATH'])) {
					exit('FLOW3: No web path defined in environment variable FLOW3_WEBPATH or directory does not exist (Error #1249046843)' . PHP_EOL);
				}
				define('FLOW3_PATH_WEB', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($_SERVER['FLOW3_WEBPATH'])) . '/');
			}
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

		$this->flow3Package = new \F3\FLOW3\Package\Package('FLOW3', FLOW3_PATH_FLOW3);
	}

	/**
	 * Signalizes that FLOW3 completed the shutdown process after a normal run.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 * @see compile()
	 */
	protected function emitFinishedCompilationRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__, array());
	}

	/**
	 * Initializes all necessary FLOW3 components for a regular runtime request.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see run()
	 * @api
	 */
	public function initialize() {
		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeSystemLogger();
		$this->initializeErrorHandling();

		$this->initializePackageManagement();
		$this->initializeCacheManagement();
	}

	/**
	 * Initializes and executes all necessary steps for compiling static code and other cache information.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function compile() {
		$this->objectManager = new \F3\FLOW3\Object\CompileTimeObjectManager($this->context);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheManager', $this->cacheManager);

		$this->initializeSignalsSlots();
		$this->monitorClassFiles();
		$this->initializeReflectionService();

		$objectConfigurationCache = $this->cacheManager->getCache('FLOW3_Object_Configuration');
		if ($objectConfigurationCache->has('allCompiledCodeUpToDate')) {
			echo 'OK';
			return;
		}

		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectManager->injectReflectionService($this->reflectionService);
		$this->objectManager->injectConfigurationManager($this->configurationManager);
		$this->objectManager->injectConfigurationCache($this->cacheManager->getCache('FLOW3_Object_Configuration'));
		$this->objectManager->injectSystemLogger($this->systemLogger);
		$this->objectManager->initialize($this->packageManager->getActivePackages());

		$this->objectManager->setInstance('F3\FLOW3\Package\PackageManagerInterface', $this->packageManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheManager', $this->cacheManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheFactory', $this->cacheFactory);
		$this->objectManager->setInstance('F3\FLOW3\Configuration\ConfigurationManager', $this->configurationManager);
		$this->objectManager->setInstance('F3\FLOW3\Log\SystemLoggerInterface', $this->systemLogger);
		$this->objectManager->setInstance('F3\FLOW3\Utility\Environment', $this->environment);
		$this->objectManager->setInstance('F3\FLOW3\SignalSlot\Dispatcher', $this->signalSlotDispatcher);
		$this->objectManager->setInstance('F3\FLOW3\Reflection\ReflectionService', $this->reflectionService);

		$this->signalSlotDispatcher->injectObjectManager($this->objectManager);

		$compiler = $this->objectManager->get('F3\FLOW3\Object\Proxy\Compiler');
		$compiler->injectClassesCache($this->cacheManager->getCache('FLOW3_Object_Classes'));

		\F3\FLOW3\Error\Debugger::injectObjectManager($this->objectManager);

		$aopProxyClassBuilder = $this->objectManager->get('F3\FLOW3\AOP\Builder\ProxyClassBuilder');
		$aopProxyClassBuilder->build();

		$dependencyInjectionProxyClassBuilder = $this->objectManager->get('F3\FLOW3\Object\DependencyInjection\ProxyClassBuilder');
		$dependencyInjectionProxyClassBuilder->build();

		$compiler->compile();

		$objectConfigurationCache->set('allCompiledCodeUpToDate', TRUE, array($objectConfigurationCache->getClassTag()));

		$this->emitFinishedCompilationRun();
		echo 'OK';
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
		if (isset($_GET['FLOW3_BOOTSTRAP_ACTION']) && $_GET['FLOW3_BOOTSTRAP_ACTION'] === 'compile') {
			$compileKey =  isset($_GET['FLOW3_BOOTSTRAP_COMPILEKEY']) ? $_GET['FLOW3_BOOTSTRAP_COMPILEKEY'] : FALSE;
			$compileKeyPathAndFilename = $this->environment->getPathToTemporaryDirectory() . 'CompileKey.txt';
			if (!file_exists($compileKeyPathAndFilename) || $compileKey !== file_get_contents($compileKeyPathAndFilename)) {
				$this->systemLogger->log(sprintf('Tried to execute compile run in %s context with invalid key (%s) ---', $this->context, $_GET['FLOW3_BOOTSTRAP_COMPILEKEY']), LOG_ALERT);
				exit(1);
			}

			$this->systemLogger->log(sprintf('--- Compile run in %s context (compile key: %s) ---', $this->context, $compileKey), LOG_INFO);
			$this->compile();
			unlink($compileKeyPathAndFilename);
			exit;
		}

		if (PHP_SAPI === 'cli' &&
				$GLOBALS['argc'] === 4 &&
				$GLOBALS['argv'][1] === 'FLOW3' &&
				$GLOBALS['argv'][2] == 'Bootstrap' &&
				$GLOBALS['argv'][3] === 'compile') {
			$this->systemLogger->log(sprintf('--- Compile run in %s context (command line) ---', $this->context), LOG_INFO);
			echo (sprintf('Compiling FLOW3 proxy classes for %s context ... ', $this->context));
			$this->compile();
			echo (PHP_EOL);
			exit;
		}

		if (!$this->siteLocked) {
			$this->systemLogger->log(sprintf('--- Run FLOW3 in %s context. ---', $this->context), LOG_INFO);
			$objectConfigurationCache = $this->cacheManager->getCache('FLOW3_Object_Configuration');
			if (!$objectConfigurationCache->has('allCompiledCodeUpToDate') || $this->context !== 'Production') {

				if (PHP_SAPI === 'cli') {
					$command = 'php -c ' . php_ini_loaded_file() . ' ' . realpath(FLOW3_PATH_FLOW3 . 'Scripts/FLOW3.php') . ' FLOW3 Core compile';
					exec($command, $output, $exitCode);
					if ($exitCode !==0) {
						echo implode((PHP_SAPI === 'cli' ? PHP_EOL : '<br />'), $output);
						throw new \F3\FLOW3\Exception(sprintf('Could not execute the FLOW3 compiler with "%s". ', $command), 1299854519);
					}
				} else {
					$compileKey = \F3\FLOW3\Utility\Algorithms::generateUUID();
					file_put_contents($this->environment->getPathToTemporaryDirectory() . 'CompileKey.txt', $compileKey);
					$compileUri = $this->environment->getBaseUri() . '?FLOW3_BOOTSTRAP_ACTION=compile&FLOW3_BOOTSTRAP_COMPILEKEY=' . $compileKey;
					try {
						$result = file_get_contents($compileUri);
					} catch (\Exception $exception) {
						throw new \F3\FLOW3\Exception('The FLOW3 compile run failed due to an exception while sending the HTTP request.', 1300097425, $exception);
					}
					if ($result !== 'OK') {
						$httpResult = (isset($http_response_header)) ? ('The HTTP request responded with ' . $http_response_header[0] . '.') : '';
						throw new \F3\FLOW3\Exception('The FLOW3 compile run sub request failed. ' . $httpResult . ' Response output: ' . $result, 1300097426);
					}
				}
			}

			$this->initializeReflectionService();
			$this->reflectionService->initialize();

			$this->initializeObjectManager();

			$this->initializeSignalsSlots();
	#		$this->initializeFileMonitor();

			$this->initializePersistence();
			$this->initializeSession();
			$this->initializeResources();
	#		$this->initializeI18n();

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
	 * Initializes the class loader
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	public function initializeClassLoader() {
		require_once(FLOW3_PATH_FLOW3 . 'Classes/Core/ClassLoader.php');
		$this->classLoader = new \F3\FLOW3\Core\ClassLoader();
		$this->classLoader->setPackages(array('FLOW3' => $this->flow3Package));
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
		$this->configurationManager->setPackages(array('FLOW3' => $this->flow3Package));

		$this->settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');

		$this->environment = new \F3\FLOW3\Utility\Environment($this->context);
		$this->environment->setTemporaryDirectoryBase($this->settings['utility']['environment']['temporaryDirectoryBase']);

		$this->configurationManager->injectEnvironment($this->environment);
	}

	/**
	 * Initializes the system logger
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSystemLogger() {
		$this->systemLogger = \F3\FLOW3\Log\LoggerFactory::create('SystemLogger', 'F3\FLOW3\Log\Logger', $this->settings['log']['systemLogger']['backend'], $this->settings['log']['systemLogger']['backendOptions']);
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
		$this->exceptionHandler->injectSystemLogger($this->systemLogger);
		$this->classLoader->loadClass('F3\FLOW3\Error\Debugger');
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializePackageManagement() {
		$this->packageManager = new \F3\FLOW3\Package\PackageManager();
		$this->packageManager->injectConfigurationManager($this->configurationManager);
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
	 * Initializes the cache framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeCacheManagement() {
		$this->cacheManager = new \F3\FLOW3\Cache\CacheManager();
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));

		$this->cacheFactory = new \F3\FLOW3\Cache\CacheFactory($this->context, $this->cacheManager, $this->environment);
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeReflectionService() {
		$this->reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$this->reflectionService->injectSystemLogger($this->systemLogger);
		$this->reflectionService->setStatusCache($this->cacheManager->getCache('FLOW3_ReflectionStatus'));
		$this->reflectionService->setDataCache($this->cacheManager->getCache('FLOW3_ReflectionData'));
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjectManager() {
		$this->objectManager = new \F3\FLOW3\Object\ObjectManager($this->context);
		self::$staticObjectManager = $this->objectManager;

		$objects = $this->cacheManager->getCache('FLOW3_Object_Configuration')->get('objects');
		if ($objects === FALSE) {
			throw new \F3\FLOW3\Exception('Could not load object configuration from cache. This might be due to an unsuccesful compile run.', 1297263663);
		}

		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectManager->setObjects($objects);

		$this->classLoader->injectClassesCache($this->cacheManager->getCache('FLOW3_Object_Classes'));

		$this->objectManager->setInstance('F3\FLOW3\Package\PackageManagerInterface', $this->packageManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheManager', $this->cacheManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheFactory', $this->cacheFactory);
		$this->objectManager->setInstance('F3\FLOW3\Configuration\ConfigurationManager', $this->configurationManager);
		$this->objectManager->setInstance('F3\FLOW3\Package\PackageManagerInterface', $this->packageManager);
		$this->objectManager->setInstance('F3\FLOW3\Log\SystemLoggerInterface', $this->systemLogger);
		$this->objectManager->setInstance('F3\FLOW3\Reflection\ReflectionService', $this->reflectionService);
		$this->objectManager->setInstance('F3\FLOW3\Utility\Environment', $this->environment);

		\F3\FLOW3\Error\Debugger::injectObjectManager($this->objectManager);
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
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	public function initializeSignalsSlots() {

			// Need to instantiate the Dispatcher manually because this must work in compile mode as well as in run mode:
		$this->signalSlotDispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
		$this->signalSlotDispatcher->injectSystemLogger($this->systemLogger);
		$this->signalSlotDispatcher->injectObjectManager($this->objectManager);

		$this->objectManager->setInstance('F3\FLOW3\SignalSlot\Dispatcher', $this->signalSlotDispatcher);

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
	 * Initializes the file monitoring
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeFileMonitor() {
		if ($this->settings['monitor']['detectClassChanges'] === TRUE) {
#			$this->monitorClassFiles(); // FIXME: This is probably not needed / allowed in run context, right?
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
		$changeDetectionStrategy = new \F3\FLOW3\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$changeDetectionStrategy->injectCache($this->cacheManager->getCache('FLOW3_Monitor'));
		$changeDetectionStrategy->initializeObject();

		$monitor = new \F3\FLOW3\Monitor\FileMonitor('FLOW3_ClassFiles');
		$monitor->injectCache($this->cacheManager->getCache('FLOW3_Monitor'));
		$monitor->injectChangeDetectionStrategy($changeDetectionStrategy);
		$monitor->injectSignalDispatcher($this->signalSlotDispatcher);
		$monitor->injectSystemLogger($this->systemLogger);
		$monitor->initializeObject();

		foreach ($this->packageManager->getActivePackages() as $package) {
			$classesPath = $package->getClassesPath();
			if (is_dir($classesPath)) {
				$monitor->monitorDirectory($classesPath);
			}
		}

		$monitor->detectChanges();
		$monitor->shutdownObject();
		$changeDetectionStrategy->shutdownObject();
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

		if (!is_dir(FLOW3_PATH_DATA)) {
			mkdir(FLOW3_PATH_DATA);
		}
		if (!is_dir(FLOW3_PATH_DATA . 'Persistent')) {
			mkdir(FLOW3_PATH_DATA . 'Persistent');
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
				$classLoader = $this->objectManager->get('F3\FLOW3\Core\ClassLoader');
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