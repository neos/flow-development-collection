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
require_once(__DIR__ . '/../Package/PackageManagerInterface.php');
require_once(__DIR__ . '/../Package/PackageManager.php');

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
	const MINIMUM_PHP_VERSION = '5.3.2';
	const MAXIMUM_PHP_VERSION = '5.99.9';

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

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
	 *
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
	 * @var array
	 */
	protected $compiletimeCommandControllers = array();

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __construct($context) {
		$this->defineConstants();
		$this->ensureRequiredEnvironment();

		$this->context = $context;
		if ($this->context !== 'Production' && $this->context !== 'Development' && $this->context !== 'Testing') {
			exit('FLOW3: Unknown context "' . $this->context . '" provided, currently only "Production", "Development" and "Testing" are supported. (Error #1254216868)' . PHP_EOL);
		}

		if ($this->context === 'Testing') {
			require_once('PHPUnit/Autoload.php');
			require_once(FLOW3_PATH_FLOW3 . 'Tests/BaseTestCase.php');
			require_once(FLOW3_PATH_FLOW3 . 'Tests/FunctionalTestCase.php');
		}
	}

	/**
	 * Returns the context this bootstrap was started in.
	 *
	 * @return string The context, for example "Development"
	 * @api
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Runs the the FLOW3 Framework by resolving an appropriate bootstrap sequence and passing control to it.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializeSignalsSlots();
		$this->initializePackageManagement();
		$this->initializeConfiguration();
		$this->initializeSystemLogger();
		$this->initializeErrorHandling();
		$this->initializeCacheManagement();

		switch (FLOW3_SAPITYPE) {
			case 'Web' :
				$this->handleWebRequest();
			break;
			case 'CLI' :
				$this->handleCommandLineRequest();
			break;
		}
	}

	/**
	 * Registers a command controller specified by the given identifier to be called
	 * during compiletime (versus runtime). The command controller must be totally
	 * aware of the limited functionality FLOW3 provides at compiletime.
	 *
	 * @param string $commandIdentifier Package key and controller name separated by colon, e.g. "flow3:core"
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerCompiletimeCommandController($commandIdentifier) {
		$this->compiletimeCommandControllers[$commandIdentifier] = TRUE;
	}

	/**
	 * Tells if the given command controller is registered for compiletime or not.
	 *
	 * @param string $commandIdentifier Package key, controller name and command name separated by colon, e.g. "flow3:cache:flush"
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isCompiletimeCommandController($commandIdentifier) {
		$commandIdentifierParts = explode(':', $commandIdentifier);
		if (count($commandIdentifierParts) !== 3) {
			return FALSE;
		}
		unset($commandIdentifierParts[2]);
		return isset($this->compiletimeCommandControllers[implode(':', $commandIdentifierParts)]);
	}

	/**
	 * Returns the object manager instance
	 *
	 * @return \F3\FLOW3\Object\ObjectManagerInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectManager() {
		if ($this->objectManager === NULL) {
			throw new \F3\FLOW3\Exception('The Object Manager is not available at this stage of the bootstrap run.', 1301120788);
		}
		return $this->objectManager;
	}

	/**
	 * Returns the signal slot dispatcher instance
	 *
	 * @return \F3\FLOW3\SignalSlot\Dispatcher
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSignalSlotDispatcher() {
		return $this->signalSlotDispatcher;
	}

	/**
	 * Bootstrap sequence for a command line request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function handleCommandLineRequest() {
		$commandLine = $this->environment->getCommandLineArguments();
		if (isset($commandLine[1]) && $commandLine[1] === '--start-slave') {
			$this->handleCommandLineSlaveRequest();
		} else {
			if (isset($commandLine[1]) && $this->isCompiletimeCommandController($commandLine[1])) {
				$runLevel = 'compiletime';
				$this->initializeForCompileTime();
				$request = $this->objectManager->get('F3\FLOW3\MVC\CLI\RequestBuilder')->build(array_slice($commandLine, 1));
				$response = new \F3\FLOW3\MVC\CLI\Response();
				$this->objectManager->get('F3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);
				$this->emitFinishedCompiletimeRun();
			} else {
				$runLevel = 'runtime';
				$this->initializeForRuntime();

				if ($this->context === 'Testing') return;

				$request = $this->objectManager->get('F3\FLOW3\MVC\CLI\RequestBuilder')->build(array_slice($commandLine, 1));
				$response = new \F3\FLOW3\MVC\CLI\Response();
				$this->objectManager->get('F3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);
				$this->emitFinishedRuntimeRun();
			}
			$response->send();
		}
		$this->emitBootstrapShuttingDown($runLevel);
	}

	/**
	 * Implements a slave process which listens to a master (shell) and executes runtime-level commands
	 * on demand.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function handleCommandLineSlaveRequest() {
		$this->initializeForRuntime();

		$this->systemLogger->log('Running sub process loop.', LOG_DEBUG);
		echo "\nREADY\n";

		while (TRUE) {
			$commandLine = trim(fgets(STDIN));
			$this->systemLogger->log(sprintf('Received command "%s".', $commandLine), LOG_INFO);
			if ($commandLine === "QUIT\n") {
				break;
			}
			$request = $this->objectManager->get('F3\FLOW3\MVC\CLI\RequestBuilder')->build($commandLine);
			$response = new \F3\FLOW3\MVC\CLI\Response();
			if ($this->isCompiletimeCommandController($request->getCommandIdentifier())) {
				echo "This command must be executed during compiletime.\n";
			} else {
				$this->objectManager->get('F3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);
				$response->send();

					// FIXME: This will be replaced by a signal / slot:
				$this->objectManager->get('F3\FLOW3\Persistence\PersistenceManagerInterface')->persistAll();
			}
			echo "\nREADY\n";
		}

		$this->systemLogger->log('Exiting sub process loop.', LOG_DEBUG);

		$this->emitFinishedRuntimeRun();
	}

	/**
	 * Bootstrap sequence for a web request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function handleWebRequest() {
		$this->initializeForRuntime();

		$requestHandlerResolver = $this->objectManager->get('F3\FLOW3\MVC\RequestHandlerResolver');
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();

		$this->emitFinishedRuntimeRun();
		$this->emitBootstrapShuttingDown('runtime');
	}

	/**
	 * Initializes the (Compiletime) Object Manager and some other services which need to
	 * be initialized in "compiletime" initialization level.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function initializeForCompileTime() {
		$this->objectManager = new \F3\FLOW3\Object\CompileTimeObjectManager($this->context);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheManager', $this->cacheManager);

		$this->monitorClassFiles();
		$this->initializeReflectionService();

		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectManager->injectReflectionService($this->reflectionService);
		$this->objectManager->injectConfigurationManager($this->configurationManager);
		$this->objectManager->injectConfigurationCache($this->cacheManager->getCache('FLOW3_Object_Configuration'));
		$this->objectManager->injectSystemLogger($this->systemLogger);
		$this->objectManager->initialize($this->packageManager->getActivePackages());

		$this->setInstancesOfEarlyServices();

		$this->emitBootstrapReady();
	}

	/**
	 * Initializes the regular Object Manager and some other services which need to
	 * be initialized in the "runtime" initialization level.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function initializeForRuntime() {
		$objectConfigurationCache = $this->cacheManager->getCache('FLOW3_Object_Configuration');
		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE || $this->context !== 'Production') {
			if (DIRECTORY_SEPARATOR === '/') {
				$command = 'FLOW3_ROOTPATH=' . FLOW3_PATH_ROOT . ' ' . 'FLOW3_CONTEXT=' . $this->context . ' ' . \F3\FLOW3\Utility\Files::getUnixStylePath($this->settings['core']['phpBinaryPathAndFilename']) . ' -c ' . \F3\FLOW3\Utility\Files::getUnixStylePath(php_ini_loaded_file()) . ' ' . FLOW3_PATH_FLOW3 . 'Scripts/flow3' . ' flow3:core:compile';
			} else {
				$command = 'SET FLOW3_ROOTPATH=' . FLOW3_PATH_ROOT . '&' . 'SET FLOW3_CONTEXT=' . $this->context . '&' . $this->settings['core']['phpBinaryPathAndFilename'] . ' -c ' . php_ini_loaded_file() . ' ' . FLOW3_PATH_FLOW3 . 'Scripts/flow3' . ' flow3:core:compile';
			}
			system($command);
		}

		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
			throw new \F3\FLOW3\Exception('Could not load object configuration from cache. This might be due to an unsuccessful compile run. One reason might be, that your PHP binary is not located in "' . $this->settings['core']['phpBinaryPathAndFilename'] . '". In that case, set the correct path to the PHP executable in Configuration/Settings.yaml, setting FLOW3.core.phpBinaryPathAndFilename.', 1297263663);
		}

		$this->classLoader->injectClassesCache($this->cacheManager->getCache('FLOW3_Object_Classes'));
		$this->initializeReflectionService();

		$this->objectManager = new \F3\FLOW3\Object\ObjectManager($this->context);
		self::$staticObjectManager = $this->objectManager;
		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectManager->setObjects($objectConfigurationCache->get('objects'));

		$this->setInstancesOfEarlyServices();

		$this->initializeFileMonitor();
		$this->initializePersistence();
		$this->initializeSession();
		$this->initializeResources();
		$this->initializeI18n();

		$this->emitBootstrapReady();
	}

	/**
	 * Sets the instances of services at the Object Manager which have been initialized before the Object Manager even existed.
	 * This applies to foundational classes such as the Package Manager or the Cache Manager.
	 *
	 * Also injects the Object Manager into early services which so far worked without it.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function setInstancesOfEarlyServices() {
		$this->objectManager->setInstance(__CLASS__, $this);
		$this->objectManager->setInstance('F3\FLOW3\Package\PackageManagerInterface', $this->packageManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheManager', $this->cacheManager);
		$this->objectManager->setInstance('F3\FLOW3\Cache\CacheFactory', $this->cacheFactory);
		$this->objectManager->setInstance('F3\FLOW3\Configuration\ConfigurationManager', $this->configurationManager);
		$this->objectManager->setInstance('F3\FLOW3\Log\SystemLoggerInterface', $this->systemLogger);
		$this->objectManager->setInstance('F3\FLOW3\Utility\Environment', $this->environment);
		$this->objectManager->setInstance('F3\FLOW3\SignalSlot\Dispatcher', $this->signalSlotDispatcher);
		$this->objectManager->setInstance('F3\FLOW3\Reflection\ReflectionService', $this->reflectionService);

		$this->signalSlotDispatcher->injectObjectManager($this->objectManager);
		\F3\FLOW3\Error\Debugger::injectObjectManager($this->objectManager);
	}


	/**
	 * Initializes the class loader
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	protected function initializeClassLoader() {
		require_once(FLOW3_PATH_FLOW3 . 'Classes/Core/ClassLoader.php');
		$this->classLoader = new \F3\FLOW3\Core\ClassLoader();
		spl_autoload_register(array($this->classLoader, 'loadClass'), TRUE, TRUE);
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializePackageManagement() {
		$this->packageManager = new \F3\FLOW3\Package\PackageManager();
		$this->packageManager->initialize($this);

		$activePackages = $this->packageManager->getActivePackages();

		$this->classLoader->setPackages($activePackages);
	}

	/**
	 * Initializes the configuration manager and the FLOW3 settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeConfiguration() {
		$this->configurationManager = new \F3\FLOW3\Configuration\ConfigurationManager($this->context);
		$this->configurationManager->injectConfigurationSource(new \F3\FLOW3\Configuration\Source\YamlSource());
		$this->configurationManager->setPackages($this->packageManager->getActivePackages());

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
	protected function initializeSystemLogger() {
		$this->systemLogger = \F3\FLOW3\Log\LoggerFactory::create('SystemLogger', 'F3\FLOW3\Log\Logger', $this->settings['log']['systemLogger']['backend'], $this->settings['log']['systemLogger']['backendOptions']);
	}

	/**
	 * Initializes the error handling
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeErrorHandling() {
		$errorHandler = new \F3\FLOW3\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors($this->settings['error']['errorHandler']['exceptionalErrors']);
		$this->exceptionHandler = new $this->settings['error']['exceptionHandler']['className'];
		$this->exceptionHandler->injectSystemLogger($this->systemLogger);
	}

	/**
	 * Initializes the cache framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeCacheManagement() {
		$this->cacheManager = new \F3\FLOW3\Cache\CacheManager();
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));

		$this->cacheFactory = new \F3\FLOW3\Cache\CacheFactory($this->context, $this->cacheManager, $this->environment);

		$this->signalSlotDispatcher->connect('F3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', $this->cacheManager, 'flushClassFileCachesByChangedFiles');
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeReflectionService() {
		$this->reflectionService = new \F3\FLOW3\Reflection\ReflectionService();
		$this->reflectionService->injectSystemLogger($this->systemLogger);
		$this->reflectionService->setStatusCache($this->cacheManager->getCache('FLOW3_ReflectionStatus'));
		$this->reflectionService->setDataCache($this->cacheManager->getCache('FLOW3_ReflectionData'));
		$this->reflectionService->initializeObject();
	}

	/**
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	protected function initializeSignalsSlots() {
		$this->signalSlotDispatcher = new \F3\FLOW3\SignalSlot\Dispatcher();
	}

	/**
	 * Initializes the file monitoring
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializeFileMonitor() {
		if ($this->settings['monitor']['detectClassChanges'] === TRUE) {
// FIXME: Doesn't work at the moment
#			$this->monitorRoutesConfigurationFiles();
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
			if ($this->context === 'Testing') {
				$functionalTestsPath = $package->getFunctionalTestsPath();
				if (is_dir($functionalTestsPath)) {
					$monitor->monitorDirectory($functionalTestsPath);
				}
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
			list($monitorIdentifier, $changedFiles) = func_get_args();
			if ($monitorIdentifier === 'FLOW3_RoutesConfigurationFiles') {
				$findMatchResultsCache = $cacheManager->getCache('FLOW3_MVC_Web_Routing_FindMatchResults');
				$findMatchResultsCache->flush();
				$resolveCache = $cacheManager->getCache('FLOW3_MVC_Web_Routing_Resolve');
				$resolveCache->flush();
			}
		};

		$this->signalSlotDispatcher->connect('F3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', $cacheFlushingSlot);

		$monitor->detectChanges();
	}

	/**
	 * Initializes the Locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	protected function initializeI18n() {
		$this->objectManager->get('F3\FLOW3\I18n\Service')->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function initializePersistence() {
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
	protected function initializeSession() {
		if (FLOW3_SAPITYPE === 'Web') {
			$this->objectManager->initializeSession();
		}
	}

	/**
	 * Initialize the resource management component, setting up stream wrappers,
	 * publishing the public resources of all found packages, ...
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	protected function initializeResources() {
		$resourceManager = $this->objectManager->get('F3\FLOW3\Resource\ResourceManager');
		$resourceManager->initialize();
		if (FLOW3_SAPITYPE === 'Web') {
			$resourceManager->publishPublicPackageResources($this->packageManager->getActivePackages());
		}
	}

	/**
	 * Emits a signal that the bootstrap is basically initialized.
	 *
	 * Note that the Object Manager is not yet available at this point.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitBootstrapReady() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'bootstrapReady', array($this));
	}

	/**
	 * Emits a signal that the compile run was finished.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitFinishedCompiletimeRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedCompiletimeRun', array());
	}

	/**
	 * Emits a signal that the runtime run was finished.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitFinishedRuntimeRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedRuntimeRun', array());
	}

	/**
	 * Emits a signal that the bootstrap finished and is shutting down.
	 *
	 * @param string $runLevel
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @signal
	 */
	protected function emitBootstrapShuttingDown($runLevel) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'bootstrapShuttingDown', array($runLevel));
	}

	/**
	 * Defines various path constants used by FLOW3 and if no root path or web root was
	 * specified by an environment variable, exits with a respective error message.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function defineConstants() {
		if (defined('FLOW3_SAPITYPE')) {
			return;
		}

		define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

		if (!defined('FLOW3_PATH_FLOW3')) {
			define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', __DIR__ . '/../../')));
		}

		if (!defined('FLOW3_PATH_ROOT')) {
			$rootPath = isset($_SERVER['FLOW3_ROOTPATH']) ? $_SERVER['FLOW3_ROOTPATH'] : FALSE;
			if ($rootPath === FALSE && isset($_SERVER['REDIRECT_FLOW3_ROOTPATH'])) {
				$rootPath = $_SERVER['REDIRECT_FLOW3_ROOTPATH'];
			}
			if (FLOW3_SAPITYPE === 'CLI' && $rootPath === FALSE) {
				$rootPath = getcwd();
				if (realpath(__DIR__) !== realpath($rootPath . '/Packages/Framework/FLOW3/Classes/Core')) {
					exit('FLOW3: Invalid root path. (Error #1301225173)' . PHP_EOL . 'You must start FLOW3 from the root directory or set the environment variable FLOW3_ROOTPATH correctly.' . PHP_EOL);
				}
			}
			if ($rootPath !== FALSE) {
				$rootPath = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($rootPath)) . '/';
				$testPath = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(\F3\FLOW3\Utility\Files::concatenatePaths(array($rootPath, 'Packages/Framework/FLOW3')))) . '/';
				$expectedPath = \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3)) . '/';
				if ($testPath !== $expectedPath) {
					exit('FLOW3: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $testPath . '" does not lead to' . PHP_EOL . '"' . $expectedPath .'"' . PHP_EOL);
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
				if (isset($_SERVER['FLOW3_WEBPATH']) && is_dir($_SERVER['FLOW3_WEBPATH'])) {
					define('FLOW3_PATH_WEB', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath($_SERVER['FLOW3_WEBPATH'])) . '/');
				} else {
					define('FLOW3_PATH_WEB', FLOW3_PATH_ROOT . 'Web/');
				}
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
}

?>
