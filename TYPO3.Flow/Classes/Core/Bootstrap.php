<?php
namespace TYPO3\FLOW3\Core;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

	// Those are needed before the autoloader is active
require_once(__DIR__ . '/../Utility/Files.php');
require_once(__DIR__ . '/../Package/PackageInterface.php');
require_once(__DIR__ . '/../Package/Package.php');
require_once(__DIR__ . '/../Package/PackageManagerInterface.php');
require_once(__DIR__ . '/../Package/PackageManager.php');

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * General purpose central core hyper FLOW3 bootstrap class
 *
 * @api
 * @FLOW3\Proxy(false)
 * @FLOW3\Scope("singleton")
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
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\FLOW3\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * The same instance like $objectManager, but static, for use in the proxy classes.
	 *
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @see initializeObjectManager(), getObjectManager()
	 */
	static public $staticObjectManager;

	/**
	 * @var \TYPO3\FLOW3\Cache\CacheManager
	 */
	protected $cacheManager;

	/**
	 * @var \TYPO3\FLOW3\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\FLOW3\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \TYPO3\FLOW3\Core\LockManager
	 */
	protected $lockManager;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var \TYPO3\FLOW3\Error\ExceptionHandlerInterface
	 */
	protected $exceptionHandler;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
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
	protected $compiletimeCommands = array();

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @api
	 */
	public function __construct($context) {
		$this->defineConstants();
		$this->ensureRequiredEnvironment();

		$this->context = $context;
		if ($this->context !== 'Production' && $this->context !== 'Development' && $this->context !== 'Testing') {
			echo('FLOW3: Unknown context "' . $this->context . '" provided, currently only "Production", "Development" and "Testing" are supported. (Error #1254216868)' . PHP_EOL);
			exit(1);
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
	 */
	public function run() {
		$this->initializeClassLoader();
		$this->initializeSignalsSlots();
		$this->initializePackageManagement();
		$this->initializeConfiguration();
		$this->initializeSystemLogger();

		$this->initializeLockManager();
		$this->exitIfSiteIsLocked();

		$this->initializeErrorHandling();
		$this->initializeCacheManagement();


		if (FLOW3_SAPITYPE === 'Web') {
			$this->handleWebRequest();
		} else {
			$this->handleCommandLineRequest();
		}
	}

	/**
	 * Registers a command specified by the given identifier to be called during
	 * compiletime (versus runtime). The related command controller must be totally
	 * aware of the limited functionality FLOW3 provides at compiletime.
	 *
	 * @param string $commandIdentifier Package key, controller name and command name separated by colon, e.g. "typo3.flow3:core:shell", wildcard for command name possible: "typo3.flow3:core:*"
	 * @return void
	 * @api
	 */
	public function registerCompiletimeCommand($commandIdentifier) {
		$this->compiletimeCommands[$commandIdentifier] = TRUE;
	}

	/**
	 * Tells if the given command controller is registered for compiletime or not.
	 *
	 * @param string $commandIdentifier Package key, controller name and command name separated by colon, e.g. "typo3.flow3:cache:flush"
	 * @return boolean
	 */
	public function isCompiletimeCommand($commandIdentifier) {
		$commandIdentifierParts = explode(':', $commandIdentifier);
		if (count($commandIdentifierParts) !== 3) {
			return FALSE;
		}
		if (isset($this->compiletimeCommands[$commandIdentifier])) {
			return TRUE;
		}

		unset($commandIdentifierParts[2]);
		$shortControllerIdentifier = implode(':', $commandIdentifierParts);

		foreach ($this->compiletimeCommands as $fullControllerIdentifier => $isCompiletimeCommandController) {
			list($packageKey, $controllerName, $commandName) = explode(':', $fullControllerIdentifier);
			$packageKeyParts = explode('.', $packageKey);
			for ($offset = 0; $offset < count($packageKeyParts); $offset++) {
				$possibleCommandControllerIdentifier = implode('.', array_slice($packageKeyParts, $offset)) . ':' . $controllerName;

				if (substr($fullControllerIdentifier, -2, 2) === ':*') {
					if ($possibleCommandControllerIdentifier === $shortControllerIdentifier) {
						return TRUE;
					}
				} else {
					$possibleCommandControllerIdentifier .= ':' . $commandName;
					if ($possibleCommandControllerIdentifier === $commandIdentifier) {
						return TRUE;
					}
				}
			}
		}

		return FALSE;
	}

	/**
	 * Returns the object manager instance
	 *
	 * @return \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	public function getObjectManager() {
		if ($this->objectManager === NULL) {
			throw new \TYPO3\FLOW3\Exception('The Object Manager is not available at this stage of the bootstrap run.', 1301120788);
		}
		return $this->objectManager;
	}

	/**
	 * Returns the signal slot dispatcher instance
	 *
	 * @return \TYPO3\FLOW3\SignalSlot\Dispatcher
	 */
	public function getSignalSlotDispatcher() {
		return $this->signalSlotDispatcher;
	}

	/**
	 * Bootstrap sequence for a command line request
	 *
	 * @return void
	 */
	protected function handleCommandLineRequest() {
		$commandLine = $this->environment->getCommandLineArguments();
		if (isset($commandLine[1]) && $commandLine[1] === '--start-slave') {
			$this->handleCommandLineSlaveRequest();
		} else {
			try {
				if (isset($commandLine[1]) && $this->isCompiletimeCommand($commandLine[1])) {
					$runLevel = 'compiletime';
					$this->initializeForCompileTime();

					if ($this->context === 'Production') {
						$this->lockManager->lockSite();
					}

					$request = $this->objectManager->get('TYPO3\FLOW3\MVC\CLI\RequestBuilder')->build(array_slice($commandLine, 1));
					$response = new \TYPO3\FLOW3\MVC\CLI\Response();
					$this->objectManager->get('TYPO3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);

					$this->emitFinishedCompiletimeRun();
				} else {
					$runLevel = 'runtime';
					$this->initializeForRuntime();

						// Functional tests are executed in "runtime" but don't need the regular request handling mechanism:
					if ($this->context === 'Testing') {
						return;
					}

					$request = $this->objectManager->get('TYPO3\FLOW3\MVC\CLI\RequestBuilder')->build(array_slice($commandLine, 1));
					$command = $request->getCommand();
					if ($this->isCompiletimeCommand($command->getCommandIdentifier())) {
						throw new \TYPO3\FLOW3\MVC\Exception\InvalidCommandIdentifierException(sprintf('The command "%s" must be specified by its full command identifier because it is a compile time command which cannot be resolved from an abbreviated command identifier.', $command->getCommandIdentifier()), 1310992499);
					}
					$response = new \TYPO3\FLOW3\MVC\CLI\Response();
					$this->objectManager->get('TYPO3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);
					$this->emitFinishedRuntimeRun();
				}
				$response->send();
			} catch (\Exception $exception) {
				$response = new \TYPO3\FLOW3\MVC\CLI\Response();

				$exceptionMessage = '';
				$exceptionReference = "\n<b>More Information</b>\n";
				$exceptionReference .= "  Exception code      #" . $exception->getCode() . "\n";
				$exceptionReference .= "  File                " . $exception->getFile() . ($exception->getLine() ? ' line ' . $exception->getLine() : '') . "\n";
				$exceptionReference .= ($exception instanceof \TYPO3\FLOW3\Exception ? "  Exception reference #" . $exception->getReferenceCode() . "\n" : '');
				foreach (explode(chr(10), wordwrap($exception->getMessage(), 73)) as $messageLine) {
					$exceptionMessage .= "  $messageLine\n";
				}

				$response->setContent(sprintf("<b>Uncaught Exception</b>\n%s%s\n", $exceptionMessage, $exceptionReference));
				$response->send();
				exit(1);
			}
		}
		$this->emitBootstrapShuttingDown($runLevel);
		if ($this->context === 'Production' && $this->lockManager->isSiteLocked()) {
			$this->lockManager->unlockSite();
		}
		if (isset($response)) {
			exit($response->getExitCode());
		}
	}

	/**
	 * Implements a slave process which listens to a master (shell) and executes runtime-level commands
	 * on demand.
	 *
	 * @return void
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
			$request = $this->objectManager->get('TYPO3\FLOW3\MVC\CLI\RequestBuilder')->build($commandLine);
			$response = new \TYPO3\FLOW3\MVC\CLI\Response();
			if ($this->isCompiletimeCommand($request->getCommand()->getCommandIdentifier())) {
				echo "This command must be executed during compiletime.\n";
			} else {
				$this->objectManager->get('TYPO3\FLOW3\MVC\Dispatcher')->dispatch($request, $response);
				$response->send();

				$this->emitDispatchedCommandLineSlaveRequest();
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
	 */
	protected function handleWebRequest() {
		$this->initializeForRuntime();

		$requestHandlerResolver = $this->objectManager->get('TYPO3\FLOW3\MVC\RequestHandlerResolver');
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
	 */
	protected function initializeForCompileTime() {
		$this->objectManager = new \TYPO3\FLOW3\Object\CompileTimeObjectManager($this->context);
		$this->objectManager->setInstance('TYPO3\FLOW3\Cache\CacheManager', $this->cacheManager);

		$this->monitorClassFiles();
		$this->initializeReflectionService();

		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
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
	 */
	protected function initializeForRuntime() {
		$objectConfigurationCache = $this->cacheManager->getCache('FLOW3_Object_Configuration');

			// will be FALSE here only if caches are totally empty, class monitoring runs only in compiletime
		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE || $this->context !== 'Production') {
			$this->executeCommand('typo3.flow3:core:compile');
			if (isset($this->settings['persistence']['doctrine']['enable']) && $this->settings['persistence']['doctrine']['enable'] === TRUE) {
				$this->compileDoctrineProxies();
			}
		}

		if ($objectConfigurationCache->has('allCompiledCodeUpToDate') === FALSE) {
			$phpBinaryPathAndFilename = escapeshellcmd(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($this->settings['core']['phpBinaryPathAndFilename']));
			$command = '"' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' -v';
			system($command, $result);
			if ($result !== 0) {
				throw new \TYPO3\FLOW3\Exception('It seems like the PHP binary "' . $this->settings['core']['phpBinaryPathAndFilename'] . '" cannot be executed by FLOW3. Set the correct path to the PHP executable in Configuration/Settings.yaml, setting FLOW3.core.phpBinaryPathAndFilename.', 1315561483);
			}
			throw new \TYPO3\FLOW3\Exception('The compile run failed. Please check the error output or system log for more information.', 1297263663);
		}

		$this->classLoader->injectClassesCache($this->cacheManager->getCache('FLOW3_Object_Classes'));
		$this->initializeReflectionService();

		$this->objectManager = new \TYPO3\FLOW3\Object\ObjectManager($this->context);
		self::$staticObjectManager = $this->objectManager;
		$this->objectManager->injectAllSettings($this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
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
	 * Update Doctrine 2 proxy classes
	 *
	 * This is not simply bound to the finishedCompilationRun signal because it
	 * needs the advised proxy classes to run. When that signal is fired, they
	 * have been written, but not loaded.
	 *
	 * @return void
	 */
	protected function compileDoctrineProxies() {
		$objectConfigurationCache = $this->cacheManager->getCache('FLOW3_Object_Configuration');
		$coreCache = $this->cacheManager->getCache('FLOW3_Core');
		if ($objectConfigurationCache->has('doctrineProxyCodeUpToDate') === FALSE && $coreCache->has('doctrineSetupRunning') === FALSE) {
			$coreCache->set('doctrineSetupRunning', 'White Russian', array(), 60);
			$this->systemLogger->log('Compiling Doctrine proxies', LOG_DEBUG);
			$this->executeCommand('typo3.flow3:doctrine:compileproxies');
			$coreCache->remove('doctrineSetupRunning');
			$objectConfigurationCache->set('doctrineProxyCodeUpToDate', TRUE);
		}
	}

	/**
	 * Executes the given command as a sub-request to the FLOW3 CLI system.
	 *
	 * @param string $commandIdentifier E.g. typo3.flow3:cache:flush
	 * @return boolean TRUE if the command execution was successful (exit code = 0)
	 */
	public function executeCommand($commandIdentifier) {
		$phpBinaryPathAndFilename = escapeshellcmd(\TYPO3\FLOW3\Utility\Files::getUnixStylePath($this->settings['core']['phpBinaryPathAndFilename']));
		if (DIRECTORY_SEPARATOR === '/') {
			$command = 'XDEBUG_CONFIG="idekey=FLOW3_SUBREQUEST" FLOW3_ROOTPATH=' . escapeshellarg(FLOW3_PATH_ROOT) . ' ' . 'FLOW3_CONTEXT=' . escapeshellarg($this->context) . ' "' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' ' . escapeshellarg(FLOW3_PATH_FLOW3 . 'Scripts/flow3.php') . ' ' . escapeshellarg($commandIdentifier);
		} else {
			$command = 'SET FLOW3_ROOTPATH=' . escapeshellarg(FLOW3_PATH_ROOT) . '&' . 'SET FLOW3_CONTEXT=' . escapeshellarg($this->context) . '&"' . $phpBinaryPathAndFilename . '" -c ' . escapeshellarg(php_ini_loaded_file()) . ' ' . escapeshellarg(FLOW3_PATH_FLOW3 . 'Scripts/flow3.php') . ' ' . escapeshellarg($commandIdentifier);
		}
		system($command, $result);
		return $result === 0;
	}

	/**
	 * Sets the instances of services at the Object Manager which have been initialized before the Object Manager even existed.
	 * This applies to foundational classes such as the Package Manager or the Cache Manager.
	 *
	 * Also injects the Object Manager into early services which so far worked without it.
	 *
	 * @return void
	 */
	protected function setInstancesOfEarlyServices() {
		$this->objectManager->setInstance(__CLASS__, $this);
		$this->objectManager->setInstance('TYPO3\FLOW3\Package\PackageManagerInterface', $this->packageManager);
		$this->objectManager->setInstance('TYPO3\FLOW3\Cache\CacheManager', $this->cacheManager);
		$this->objectManager->setInstance('TYPO3\FLOW3\Cache\CacheFactory', $this->cacheFactory);
		$this->objectManager->setInstance('TYPO3\FLOW3\Configuration\ConfigurationManager', $this->configurationManager);
		$this->objectManager->setInstance('TYPO3\FLOW3\Log\SystemLoggerInterface', $this->systemLogger);
		$this->objectManager->setInstance('TYPO3\FLOW3\Utility\Environment', $this->environment);
		$this->objectManager->setInstance('TYPO3\FLOW3\SignalSlot\Dispatcher', $this->signalSlotDispatcher);
		$this->objectManager->setInstance('TYPO3\FLOW3\Reflection\ReflectionService', $this->reflectionService);
		$this->objectManager->setInstance('TYPO3\FLOW3\Core\ClassLoader', $this->classLoader);

		$this->signalSlotDispatcher->injectObjectManager($this->objectManager);
		\TYPO3\FLOW3\Error\Debugger::injectObjectManager($this->objectManager);
	}


	/**
	 * Initializes the class loader
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeClassLoader() {
		require_once(FLOW3_PATH_FLOW3 . 'Classes/Core/ClassLoader.php');
		$this->classLoader = new \TYPO3\FLOW3\Core\ClassLoader();
		spl_autoload_register(array($this->classLoader, 'loadClass'), TRUE, TRUE);
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializePackageManagement() {
		$this->packageManager = new \TYPO3\FLOW3\Package\PackageManager();
		$this->packageManager->injectClassLoader($this->classLoader);
		$this->packageManager->initialize($this);
	}

	/**
	 * Initializes the configuration manager and the FLOW3 settings
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeConfiguration() {
		$this->configurationManager = new \TYPO3\FLOW3\Configuration\ConfigurationManager($this->context);
		$this->configurationManager->injectConfigurationSource(new \TYPO3\FLOW3\Configuration\Source\YamlSource());
		$this->configurationManager->setPackages($this->packageManager->getActivePackages());

		$this->settings = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'TYPO3.FLOW3');

		$this->environment = new \TYPO3\FLOW3\Utility\Environment($this->context);
		$this->environment->setTemporaryDirectoryBase($this->settings['utility']['environment']['temporaryDirectoryBase']);

		$this->configurationManager->injectEnvironment($this->environment);
		$this->packageManager->injectSettings($this->settings);
	}

	/**
	 * Initializes the system logger
	 *
	 * @return void
	 */
	protected function initializeSystemLogger() {
		$this->systemLogger = \TYPO3\FLOW3\Log\LoggerFactory::create('SystemLogger', 'TYPO3\FLOW3\Log\Logger', $this->settings['log']['systemLogger']['backend'], $this->settings['log']['systemLogger']['backendOptions']);
	}

	/**
	 * Initializes the error handling
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeErrorHandling() {
		$errorHandler = new \TYPO3\FLOW3\Error\ErrorHandler();
		$errorHandler->setExceptionalErrors($this->settings['error']['errorHandler']['exceptionalErrors']);
		$this->exceptionHandler = new $this->settings['error']['exceptionHandler']['className'];
		$this->exceptionHandler->injectSystemLogger($this->systemLogger);
	}

	/**
	 * Initializes the Lock Manager
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeLockManager() {
		if($this->context === 'Production') {
			$this->lockManager = new \TYPO3\FLOW3\Core\LockManager();
			$this->lockManager->injectEnvironment($this->environment);
			$this->lockManager->injectSystemLogger($this->systemLogger);
			$this->lockManager->initializeObject();
		}
	}

	/**
	 * Displays a message and exits if the site currently locked.
	 *
	 * @return
	 */
	protected function exitIfSiteIsLocked() {
		if ($this->context !== 'Production' || $this->lockManager->isSiteLocked() === FALSE) {
			return;
		}

		if (FLOW3_SAPITYPE === 'Web') {
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			readfile(FLOW3_PATH_FLOW3 . 'Resources/Private/Core/LockHoldingStackPage.html');
		} else {
			echo "Site is currently locked, exiting.\n";
		}
		$this->systemLogger->log('Site is locked, exiting.', LOG_NOTICE);
		exit(1);
	}

	/**
	 * Initializes the cache framework
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeCacheManagement() {
		$this->cacheManager = new \TYPO3\FLOW3\Cache\CacheManager();
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_CACHES));

		$this->cacheFactory = new \TYPO3\FLOW3\Cache\CacheFactory($this->context, $this->cacheManager, $this->environment);

		$this->signalSlotDispatcher->connect('TYPO3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', $this->cacheManager, 'flushClassFileCachesByChangedFiles');
		if (isset($this->settings['persistence']['doctrine']['enable']) && $this->settings['persistence']['doctrine']['enable'] === TRUE) {
			$this->signalSlotDispatcher->connect('TYPO3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', $this->cacheManager, 'markDoctrineProxyCodeOutdatedByChangedFiles');
		}
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeReflectionService() {
		$this->reflectionService = new \TYPO3\FLOW3\Reflection\ReflectionService();
		$this->reflectionService->injectSystemLogger($this->systemLogger);
		$this->reflectionService->injectClassLoader($this->classLoader);
		$this->reflectionService->setStatusCache($this->cacheManager->getCache('FLOW3_ReflectionStatus'));
		$this->reflectionService->setDataCache($this->cacheManager->getCache('FLOW3_ReflectionData'));
		$this->reflectionService->initializeObject();
	}

	/**
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @see intialize()
	 */
	protected function initializeSignalsSlots() {
		$this->signalSlotDispatcher = new \TYPO3\FLOW3\SignalSlot\Dispatcher();
	}

	/**
	 * Initializes the file monitoring
	 *
	 * @return void
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
	 */
	protected function monitorClassFiles() {
		$changeDetectionStrategy = new \TYPO3\FLOW3\Monitor\ChangeDetectionStrategy\ModificationTimeStrategy();
		$changeDetectionStrategy->injectCache($this->cacheManager->getCache('FLOW3_Monitor'));
		$changeDetectionStrategy->initializeObject();

		$monitor = new \TYPO3\FLOW3\Monitor\FileMonitor('FLOW3_ClassFiles');
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
	 */
	protected function monitorRoutesConfigurationFiles() {
		$monitor = $this->objectManager->create('TYPO3\FLOW3\Monitor\FileMonitor', 'FLOW3_RoutesConfigurationFiles');
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

		$this->signalSlotDispatcher->connect('TYPO3\FLOW3\Monitor\FileMonitor', 'filesHaveChanged', $cacheFlushingSlot);

		$monitor->detectChanges();
	}

	/**
	 * Initializes the Locale service
	 *
	 * @return void
	 * @see intialize()
	 */
	protected function initializeI18n() {
		$this->objectManager->get('TYPO3\FLOW3\I18n\Service')->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializePersistence() {
		$persistenceManager = $this->objectManager->get('TYPO3\FLOW3\Persistence\PersistenceManagerInterface');
		$persistenceManager->initialize();
	}

	/**
	 * Initializes the session framework
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeSession() {
		if (FLOW3_SAPITYPE === 'Web') {
			$this->objectManager->get('TYPO3\FLOW3\Session\SessionInterface')->resume();
		}
	}

	/**
	 * Initialize the resource management component, setting up stream wrappers,
	 * publishing the public resources of all found packages, ...
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeResources() {
		$resourceManager = $this->objectManager->get('TYPO3\FLOW3\Resource\ResourceManager');
		$resourceManager->initialize();
		$resourceManager->publishPublicPackageResources($this->packageManager->getActivePackages());
	}

	/**
	 * Emits a signal that the bootstrap is basically initialized.
	 *
	 * Note that the Object Manager is not yet available at this point.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitBootstrapReady() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'bootstrapReady', array($this));
	}

	/**
	 * Emits a signal that the compile run was finished.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitFinishedCompiletimeRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedCompiletimeRun', array());
	}

	/**
	 * Emits a signal that the runtime run was finished.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitFinishedRuntimeRun() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'finishedRuntimeRun', array());
	}

	/**
	 * Emits a signal that a CLI slave request was dispatched.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitDispatchedCommandLineSlaveRequest() {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'dispatchedCommandLineSlaveRequest', array());
	}

	/**
	 * Emits a signal that the bootstrap finished and is shutting down.
	 *
	 * @param string $runLevel
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitBootstrapShuttingDown($runLevel) {
		$this->signalSlotDispatcher->dispatch(__CLASS__, 'bootstrapShuttingDown', array($runLevel));
	}

	/**
	 * Defines various path constants used by FLOW3 and if no root path or web root was
	 * specified by an environment variable, exits with a respective error message.
	 *
	 * @return void
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
				if (realpath(__DIR__) !== realpath($rootPath . '/Packages/Framework/TYPO3.FLOW3/Classes/Core')) {
					echo('FLOW3: Invalid root path. (Error #1301225173)' . PHP_EOL . 'You must start FLOW3 from the root directory or set the environment variable FLOW3_ROOTPATH correctly.' . PHP_EOL);
					exit(1);
				}
			}
			if ($rootPath !== FALSE) {
				$rootPath = \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath($rootPath)) . '/';
				$testPath = \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath(\TYPO3\FLOW3\Utility\Files::concatenatePaths(array($rootPath, 'Packages/Framework/TYPO3.FLOW3')))) . '/';
				$expectedPath = \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3)) . '/';
				if ($testPath !== $expectedPath) {
					echo('FLOW3: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $testPath . '" does not lead to' . PHP_EOL . '"' . $expectedPath .'"' . PHP_EOL);
					exit(1);
				}
				define('FLOW3_PATH_ROOT', $rootPath);
				unset($rootPath);
				unset($testPath);
			}
		}

		if (FLOW3_SAPITYPE === 'CLI') {
			if (!defined('FLOW3_PATH_ROOT')) {
				echo('FLOW3: No root path defined in environment variable FLOW3_ROOTPATH (Error #1248964376)' . PHP_EOL);
				exit(1);
			}
			if (!defined('FLOW3_PATH_WEB')) {
				if (isset($_SERVER['FLOW3_WEBPATH']) && is_dir($_SERVER['FLOW3_WEBPATH'])) {
					define('FLOW3_PATH_WEB', \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath($_SERVER['FLOW3_WEBPATH'])) . '/');
				} else {
					define('FLOW3_PATH_WEB', FLOW3_PATH_ROOT . 'Web/');
				}
			}
		} else {
			if (!defined('FLOW3_PATH_ROOT')) {
				define('FLOW3_PATH_ROOT', \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../')) . '/');
			}
			define('FLOW3_PATH_WEB', \TYPO3\FLOW3\Utility\Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']))) . '/');
		}

		define('FLOW3_PATH_CONFIGURATION', FLOW3_PATH_ROOT . 'Configuration/');
		define('FLOW3_PATH_DATA', FLOW3_PATH_ROOT . 'Data/');
		define('FLOW3_PATH_PACKAGES', FLOW3_PATH_ROOT . 'Packages/');
	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 */
	protected function ensureRequiredEnvironment() {
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			echo('FLOW3 requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)' . PHP_EOL);
			exit(1);
		}
		if (version_compare(PHP_VERSION, self::MAXIMUM_PHP_VERSION, '>')) {
			echo('FLOW3 requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)' . PHP_EOL);
			exit(1);
		}
		if (version_compare(PHP_VERSION, '6.0.0', '<') && !extension_loaded('mbstring')) {
			echo('FLOW3 requires the PHP extension "mbstring" for PHP versions below 6.0.0 (Error #1207148809)' . PHP_EOL);
			exit(1);
		}
		if (DIRECTORY_SEPARATOR !== '/' && PHP_WINDOWS_VERSION_MAJOR < 6) {
			echo('FLOW3 does not support Windows versions older than Windows Vista or Windows Server 2008 (Error #1312463704)' . PHP_EOL);
			exit(1);
		}

		if (!extension_loaded('Reflection')) throw new \TYPO3\FLOW3\Exception('The PHP extension "Reflection" is required by FLOW3.', 1218016725);
		$method = new \ReflectionMethod(__CLASS__, __FUNCTION__);
		if ($method->getDocComment() === '') throw new \TYPO3\FLOW3\Exception('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.', 1218016727);

		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
		#locale_set_default('en_UK');
		if (ini_get('date.timezone') === '') {
			date_default_timezone_set('Europe/Copenhagen');
		}
		if (ini_get('magic_quotes_gpc') === '1' || ini_get('magic_quotes_gpc') === 'On') {
			echo('FLOW3 requires the PHP setting "magic_quotes_gpc" set to Off. (Error #1224003190)');
			exit(1);
		}

		if (!is_dir(FLOW3_PATH_DATA) && !is_link(FLOW3_PATH_DATA)) {
			mkdir(FLOW3_PATH_DATA);
		}
		if (!is_dir(FLOW3_PATH_DATA . 'Persistent') && !is_link(FLOW3_PATH_DATA . 'Persistent')) {
			mkdir(FLOW3_PATH_DATA . 'Persistent');
		}
	}
}

?>
