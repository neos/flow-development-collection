<?php
namespace TYPO3\Flow\Core;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

// This is needed for the initial environment check and before the autoloader is active
require_once(__DIR__ . '/../Utility/Files.php');

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Core\Booting\Step;
use TYPO3\Flow\Core\Booting\Sequence;
use TYPO3\Flow\Core\Booting\Scripts;
use TYPO3\Flow\Exception as FlowException;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Files;

/**
 * General purpose central core hyper Flow bootstrap class
 *
 * @api
 * @Flow\Proxy(false)
 * @Flow\Scope("singleton")
 */
class Bootstrap {

	/**
	 * Required PHP version
	 */
	const MINIMUM_PHP_VERSION = '5.5.0';

	const RUNLEVEL_COMPILETIME = 'Compiletime';
	const RUNLEVEL_RUNTIME = 'Runtime';

	/**
	 * @var ApplicationContext
	 */
	protected $context;

	/**
	 * @var array
	 */
	protected $requestHandlers;

	/**
	 * @var string
	 */
	protected $preselectedRequestHandlerClassName;

	/**
	 * @var RequestHandlerInterface
	 */
	protected $activeRequestHandler;

	/**
	 * The same instance like $objectManager, but static, for use in the proxy classes.
	 *
	 * @var ObjectManagerInterface
	 */
	static public $staticObjectManager;

	/**
	 * @var array
	 */
	protected $compiletimeCommands = array();

	/**
	 * @var array
	 */
	protected $earlyInstances = array();

	/**
	 * @param string $context The application context, for example "Production" or "Development"
	 */
	public function __construct($context) {
		$this->defineConstants();
		$this->ensureRequiredEnvironment();

		// Those are needed before the autoloader is active
		require_once(__DIR__ . '/ApplicationContext.php');
		require_once(__DIR__ . '/../Exception.php');
		require_once(__DIR__ . '/../Package/PackageInterface.php');
		require_once(__DIR__ . '/../Package/Package.php');
		require_once(__DIR__ . '/../Package/PackageManagerInterface.php');
		require_once(__DIR__ . '/../Package/PackageManager.php');
		require_once(__DIR__ . '/Booting/Scripts.php');

		$this->context = new ApplicationContext($context);
		$this->earlyInstances[__CLASS__] = $this;
	}

	/**
	 * Bootstraps the minimal infrastructure, resolves a fitting request handler and
	 * then passes control over to that request handler.
	 *
	 * @return void
	 * @api
	 */
	public function run() {
		Scripts::initializeClassLoader($this);
		Scripts::initializeSignalSlot($this);
		Scripts::initializePackageManagement($this);

		$this->activeRequestHandler = $this->resolveRequestHandler();
		$this->activeRequestHandler->handleRequest();
	}

	/**
	 * Initiates the shutdown procedure to safely close all connections, save
	 * modified data and commit other tasks necessary to cleanly exit Flow.
	 *
	 * This method should be called by a request handler after a successful run.
	 * Control is returned to the request handler which can exit the application
	 * as it sees fit.
	 *
	 * @param string $runlevel one of the RUNLEVEL_* constants
	 * @return void
	 * @api
	 */
	public function shutdown($runlevel) {
		switch($runlevel) {
			case self::RUNLEVEL_COMPILETIME:
				$this->emitFinishedCompiletimeRun();
			break;
			case self::RUNLEVEL_RUNTIME:
				$this->emitFinishedRuntimeRun();
			break;
		}
		$this->emitBootstrapShuttingDown($runlevel);
	}

	/**
	 * Returns the context this bootstrap was started in.
	 *
	 * @return ApplicationContext The context encapsulated in an object, for example "Development" or "Development/MyDeployment"
	 * @api
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Registers a request handler which can possibly handle a request.
	 * All registered request handlers will be queried if they can handle a request
	 * when the bootstrap's run() method is called.
	 *
	 * @param RequestHandlerInterface $requestHandler
	 * @return void
	 * @api
	 */
	public function registerRequestHandler(RequestHandlerInterface $requestHandler) {
		$this->requestHandlers[get_class($requestHandler)] = $requestHandler;
	}

	/**
	 * Preselects a specific request handler. If such a request handler exists,
	 * it will be used if it can handle the request – regardless of the priority
	 * of this or other request handlers.
	 *
	 * @param string $className
	 */
	public function setPreselectedRequestHandlerClassName($className) {
		$this->preselectedRequestHandlerClassName = $className;
	}

	/**
	 * Returns the request handler (if any) which is currently handling the request.
	 *
	 * @return RequestHandlerInterface
	 */
	public function getActiveRequestHandler() {
		return $this->activeRequestHandler;
	}

	/**
	 * Explicitly sets the active request handler.
	 *
	 * This method makes only sense to use during functional tests. During a functional
	 * test run the active request handler chosen by the bootstrap will be a command
	 * line request handler specialized on running functional tests. A functional
	 * test case can then set the active request handler to one which simulates, for
	 * example, an HTTP request.
	 *
	 * @param RequestHandlerInterface $requestHandler
	 * @return void
	 */
	public function setActiveRequestHandler(RequestHandlerInterface $requestHandler) {
		$this->activeRequestHandler = $requestHandler;
	}

	/**
	 * Registers a command specified by the given identifier to be called during
	 * compiletime (versus runtime). The related command controller must be totally
	 * aware of the limited functionality Flow provides at compiletime.
	 *
	 * @param string $commandIdentifier Package key, controller name and command name separated by colon, e.g. "typo3.flow:core:shell", wildcard for command name possible: "typo3.flow:core:*"
	 * @return void
	 * @api
	 */
	public function registerCompiletimeCommand($commandIdentifier) {
		$this->compiletimeCommands[$commandIdentifier] = TRUE;
	}

	/**
	 * Tells if the given command controller is registered for compiletime or not.
	 *
	 * @param string $commandIdentifier Package key, controller name and command name separated by colon, e.g. "typo3.flow:cache:flush"
	 * @return boolean
	 * @api
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
			$packageKeyPartsCount = count($packageKeyParts);
			for ($offset = 0; $offset < $packageKeyPartsCount; $offset++) {
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
	 * Builds a boot sequence with the minimum modules necessary for both, compiletime
	 * and runtime.
	 *
	 * @param string $identifier
	 * @return \TYPO3\Flow\Core\Booting\Sequence
	 * @api
	 */
	public function buildEssentialsSequence($identifier) {
		$sequence = new Sequence($identifier);

		if ($this->context->isProduction()) {
			$lockManager = new \TYPO3\Flow\Core\LockManager();
			$lockManager->exitIfSiteLocked();
			if ($identifier === 'compiletime') {
				$lockManager->lockSiteOrExit();
				// make sure the site is unlocked even if the script ends unexpectedly due to an error/exception
				register_shutdown_function(array($lockManager, 'unlockSite'));
			}
			$this->setEarlyInstance('TYPO3\Flow\Core\LockManager', $lockManager);
		}

		$sequence->addStep(new Step('typo3.flow:annotationregistry', array('TYPO3\Flow\Core\Booting\Scripts', 'registerClassLoaderInAnnotationRegistry')));
		$sequence->addStep(new Step('typo3.flow:configuration', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeConfiguration')), 'typo3.flow:annotationregistry');
		$sequence->addStep(new Step('typo3.flow:systemlogger', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeSystemLogger')), 'typo3.flow:configuration');

		$sequence->addStep(new Step('typo3.flow:errorhandling', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeErrorHandling')), 'typo3.flow:systemlogger');
		$sequence->addStep(new Step('typo3.flow:cachemanagement', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeCacheManagement')), 'typo3.flow:systemlogger');
		return $sequence;
	}

	/**
	 * Builds a boot sequence starting all modules necessary for the compiletime state.
	 * This includes all of the "essentials" sequence.
	 *
	 * @return \TYPO3\Flow\Core\Booting\Sequence
	 * @api
	 */
	public function buildCompiletimeSequence() {
		$sequence = $this->buildEssentialsSequence('compiletime');

		$sequence->addStep(new Step('typo3.flow:cachemanagement:forceflush', array('TYPO3\Flow\Core\Booting\Scripts', 'forceFlushCachesIfNecessary')), 'typo3.flow:systemlogger');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:compiletime:create', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeObjectManagerCompileTimeCreate')), 'typo3.flow:systemlogger');
		$sequence->addStep(new Step('typo3.flow:systemfilemonitor', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeSystemFileMonitor')), 'typo3.flow:objectmanagement:compiletime:create');
		$sequence->addStep(new Step('typo3.flow:reflectionservice', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow:systemfilemonitor');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:compiletime:finalize', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeObjectManagerCompileTimeFinalize')), 'typo3.flow:reflectionservice');
		return $sequence;
	}

	/**
	 * Builds a boot sequence starting all modules necessary for the runtime state.
	 * This includes all of the "essentials" sequence.
	 *
	 * @return \TYPO3\Flow\Core\Booting\Sequence
	 * @api
	 */
	public function buildRuntimeSequence() {
		$sequence = $this->buildEssentialsSequence('runtime');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:proxyclasses', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeProxyClasses')), 'typo3.flow:systemlogger');
		$sequence->addStep(new Step('typo3.flow:classloader:cache', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeClassLoaderClassesCache')), 'typo3.flow:objectmanagement:proxyclasses');
		$sequence->addStep(new Step('typo3.flow:objectmanagement:runtime', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeObjectManager')), 'typo3.flow:classloader:cache');

		if (!$this->context->isProduction()) {
			$sequence->addStep(new Step('typo3.flow:systemfilemonitor', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeSystemFileMonitor')), 'typo3.flow:objectmanagement:runtime');
			$sequence->addStep(new Step('typo3.flow:objectmanagement:recompile', array('TYPO3\Flow\Core\Booting\Scripts', 'recompileClasses')), 'typo3.flow:systemfilemonitor');
		}

		$sequence->addStep(new Step('typo3.flow:reflectionservice', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow:objectmanagement:runtime');
		$sequence->addStep(new Step('typo3.flow:persistence', array('TYPO3\Flow\Core\Booting\Scripts', 'initializePersistence')), 'typo3.flow:reflectionservice');
		$sequence->addStep(new Step('typo3.flow:resources', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeResources')), 'typo3.flow:persistence');
		$sequence->addStep(new Step('typo3.flow:session', array('TYPO3\Flow\Core\Booting\Scripts', 'initializeSession')), 'typo3.flow:resources');
		return $sequence;
	}

	/**
	 * Registers the instance of the specified object for an early boot stage.
	 * On finalizing the Object Manager initialization, all those instances will
	 * be transferred to the Object Manager's registry.
	 *
	 * @param string $objectName Object name, as later used by the Object Manager
	 * @param object $instance The instance to register
	 * @return void
	 * @api
	 */
	public function setEarlyInstance($objectName, $instance) {
		$this->earlyInstances[$objectName] = $instance;
	}

	/**
	 * Returns the signal slot dispatcher instance
	 *
	 * @return \TYPO3\Flow\SignalSlot\Dispatcher
	 * @api
	 */
	public function getSignalSlotDispatcher() {
		return $this->earlyInstances['TYPO3\Flow\SignalSlot\Dispatcher'];
	}

	/**
	 * Returns an instance which was registered earlier through setEarlyInstance()
	 *
	 * @param string $objectName Object name of the registered instance
	 * @return object
	 * @throws FlowException
	 * @api
	 */
	public function getEarlyInstance($objectName) {
		if (!isset($this->earlyInstances[$objectName])) {
			throw new FlowException('Unknown early instance "' . $objectName . '"', 1322581449);
		}
		return $this->earlyInstances[$objectName];
	}

	/**
	 * Returns all registered early instances indexed by object name
	 *
	 * @return array
	 */
	public function getEarlyInstances() {
		return $this->earlyInstances;
	}

	/**
	 * Returns the object manager instance
	 *
	 * @return ObjectManagerInterface
	 * @throws FlowException
	 */
	public function getObjectManager() {
		if (!isset($this->earlyInstances['TYPO3\Flow\Object\ObjectManagerInterface'])) {
			debug_print_backtrace();
			throw new FlowException('The Object Manager is not available at this stage of the bootstrap run.', 1301120788);
		}
		return $this->earlyInstances['TYPO3\Flow\Object\ObjectManagerInterface'];
	}

	/**
	 * Iterates over the registered request handlers and determines which one fits best.
	 *
	 * @return RequestHandlerInterface A request handler
	 * @throws FlowException
	 */
	protected function resolveRequestHandler() {
		if ($this->preselectedRequestHandlerClassName !== NULL && isset($this->requestHandlers[$this->preselectedRequestHandlerClassName])) {
			/** @var RequestHandlerInterface $requestHandler */
			$requestHandler = $this->requestHandlers[$this->preselectedRequestHandlerClassName];
			if ($requestHandler->canHandleRequest()) {
				return $requestHandler;
			}
		}

		/** @var RequestHandlerInterface $requestHandler */
		foreach ($this->requestHandlers as $requestHandler) {
			if ($requestHandler->canHandleRequest() > 0) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new FlowException('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
				}
				$suitableRequestHandlers[$priority] = $requestHandler;
			}
		}
		ksort($suitableRequestHandlers);
		return array_pop($suitableRequestHandlers);
	}

	/**
	 * Emits a signal that the compile run was finished.
	 *
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitFinishedCompiletimeRun() {
		$this->earlyInstances['TYPO3\Flow\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'finishedCompiletimeRun', array());
	}

	/**
	 * Emits a signal that the runtime run was finished.
	 *
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitFinishedRuntimeRun() {
		$this->earlyInstances['TYPO3\Flow\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'finishedRuntimeRun', array());
	}

	/**
	 * Emits a signal that the bootstrap finished and is shutting down.
	 *
	 * @param string $runLevel
	 * @return void
	 * @Flow\Signal
	 */
	protected function emitBootstrapShuttingDown($runLevel) {
		$this->earlyInstances['TYPO3\Flow\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'bootstrapShuttingDown', array($runLevel));
	}

	/**
	 * Defines various path constants used by Flow and if no root path or web root was
	 * specified by an environment variable, exits with a respective error message.
	 *
	 * @return void
	 */
	protected function defineConstants() {
		if (defined('FLOW_SAPITYPE')) {
			return;
		}

		define('FLOW_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));

		if (!defined('FLOW_PATH_FLOW')) {
			define('FLOW_PATH_FLOW', str_replace('//', '/', str_replace('\\', '/', __DIR__ . '/../../../../')));
		}

		if (!defined('FLOW_PATH_ROOT')) {
			$rootPath = isset($_SERVER['FLOW_ROOTPATH']) ? $_SERVER['FLOW_ROOTPATH'] : FALSE;
			if ($rootPath === FALSE && isset($_SERVER['REDIRECT_FLOW_ROOTPATH'])) {
				$rootPath = $_SERVER['REDIRECT_FLOW_ROOTPATH'];
			}
			if (FLOW_SAPITYPE === 'CLI' && $rootPath === FALSE) {
				$rootPath = getcwd();
				if (realpath(__DIR__) !== realpath($rootPath . '/Packages/Framework/TYPO3.Flow/Classes/Core')) {
					echo('TYPO3 Flow: Invalid root path. (Error #1301225173)' . PHP_EOL . 'You must start TYPO3 Flow from the root directory or set the environment variable FLOW_ROOTPATH correctly.' . PHP_EOL);
					exit(1);
				}
			}
			if ($rootPath !== FALSE) {
				$rootPath = Files::getUnixStylePath(realpath($rootPath)) . '/';
				$testPath = Files::getUnixStylePath(realpath(Files::concatenatePaths(array($rootPath, 'Packages/Framework/TYPO3.Flow')))) . '/';
				$expectedPath = Files::getUnixStylePath(realpath(FLOW_PATH_FLOW)) . '/';
				if ($testPath !== $expectedPath) {
					echo('Flow: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $testPath . '" does not lead to' . PHP_EOL . '"' . $expectedPath . '"' . PHP_EOL);
					exit(1);
				}
				define('FLOW_PATH_ROOT', $rootPath);
				unset($rootPath);
				unset($testPath);
			}
		}

		if (FLOW_SAPITYPE === 'CLI') {
			if (!defined('FLOW_PATH_ROOT')) {
				echo('Flow: No root path defined in environment variable FLOW_ROOTPATH (Error #1248964376)' . PHP_EOL);
				exit(1);
			}
			if (!defined('FLOW_PATH_WEB')) {
				if (isset($_SERVER['FLOW_WEBPATH']) && is_dir($_SERVER['FLOW_WEBPATH'])) {
					define('FLOW_PATH_WEB', Files::getUnixStylePath(realpath($_SERVER['FLOW_WEBPATH'])) . '/');
				} else {
					define('FLOW_PATH_WEB', FLOW_PATH_ROOT . 'Web/');
				}
			}
		} else {
			if (!defined('FLOW_PATH_ROOT')) {
				define('FLOW_PATH_ROOT', Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']) . '/../')) . '/');
			}
			define('FLOW_PATH_WEB', Files::getUnixStylePath(realpath(dirname($_SERVER['SCRIPT_FILENAME']))) . '/');
		}

		define('FLOW_PATH_CONFIGURATION', FLOW_PATH_ROOT . 'Configuration/');
		define('FLOW_PATH_DATA', FLOW_PATH_ROOT . 'Data/');
		define('FLOW_PATH_PACKAGES', FLOW_PATH_ROOT . 'Packages/');

		define('FLOW_VERSION_BRANCH', 'dev-master');
	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 */
	protected function ensureRequiredEnvironment() {
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			echo('Flow requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)' . PHP_EOL);
			exit(1);
		}
		if (!extension_loaded('mbstring')) {
			echo('Flow requires the PHP extension "mbstring" (Error #1207148809)' . PHP_EOL);
			exit(1);
		}
		if (DIRECTORY_SEPARATOR !== '/' && PHP_WINDOWS_VERSION_MAJOR < 6) {
			echo('Flow does not support Windows versions older than Windows Vista or Windows Server 2008 (Error #1312463704)' . PHP_EOL);
			exit(1);
		}

		if (!extension_loaded('Reflection')) {
			echo('The PHP extension "Reflection" is required by Flow.' . PHP_EOL);
			exit(1);
		}
		$method = new \ReflectionMethod(__CLASS__, __FUNCTION__);
		if ($method->getDocComment() === FALSE || $method->getDocComment() === '') {
			echo('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.' . PHP_EOL);
			exit(1);
		}

		set_time_limit(0);

		if (ini_get('date.timezone') === '') {
			ini_set('date.timezone', 'UTC');
		}

		if (!is_dir(FLOW_PATH_DATA) && !is_link(FLOW_PATH_DATA)) {
			if (!@mkdir(FLOW_PATH_DATA)) {
				echo('Flow could not create the directory "' . FLOW_PATH_DATA . '". Please check the file permissions manually or run "sudo ./flow flow:core:setfilepermissions" to fix the problem. (Error #1347526552)');
				exit(1);
			}
		}
		if (!is_dir(FLOW_PATH_DATA . 'Persistent') && !is_link(FLOW_PATH_DATA . 'Persistent')) {
			if (!@mkdir(FLOW_PATH_DATA . 'Persistent')) {
				echo('Flow could not create the directory "' . FLOW_PATH_DATA . 'Persistent". Please check the file permissions manually or run "sudo ./flow flow:core:setfilepermissions" to fix the problem. (Error #1347526553)');
				exit(1);
			}
		}
	}

	/**
	 * Tries to find an environment setting with the following fallback chain:
	 *
	 * - getenv with $variableName
	 * - getenv with REDIRECT_ . $variableName (this is for php cgi where environment variables from the http server get prefixed)
	 * - $_SERVER[$variableName] (this is an alternative to set FLOW_* environment variables if passing environment variables is not possible)
	 * - $_SERVER[REDIRECT_ . $variableName] (again for php cgi environments)
	 *
	 * @param string $variableName
	 * @return string or NULL if this variable was not set at all.
	 */
	static public function getEnvironmentConfigurationSetting($variableName) {
		$variableValue = getenv($variableName);
		if ($variableValue !== FALSE) {
			return $variableValue;
		}

		$variableValue = getenv('REDIRECT_' . $variableName);
		if ($variableValue !== FALSE) {
			return $variableValue;
		}

		if (isset($_SERVER[$variableName])) {
			return $_SERVER[$variableName];
		}

		if (isset($_SERVER['REDIRECT_' . $variableName])) {
			return $_SERVER['REDIRECT_' . $variableName];
		}

		return NULL;
	}
}
