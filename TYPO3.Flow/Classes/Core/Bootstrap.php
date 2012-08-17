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
require_once(__DIR__ . '/ApplicationContext.php');
require_once(__DIR__ . '/../Exception.php');
require_once(__DIR__ . '/../Utility/Files.php');
require_once(__DIR__ . '/../Package/PackageInterface.php');
require_once(__DIR__ . '/../Package/Package.php');
require_once(__DIR__ . '/../Package/PackageManagerInterface.php');
require_once(__DIR__ . '/../Package/PackageManager.php');
require_once(__DIR__ . '/Booting/Scripts.php');

use TYPO3\FLOW3\Annotations as FLOW3;
use TYPO3\FLOW3\Core\Booting\Step;
use TYPO3\FLOW3\Core\Booting\Sequence;
use TYPO3\FLOW3\Core\Booting\Scripts;
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
	 * @var \TYPO3\FLOW3\Core\ApplicationContext
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
	 * @var \TYPO3\FLOW3\Core\RequestHandlerInterface
	 */
	protected $activeRequestHandler;

	/**
	 * The same instance like $objectManager, but static, for use in the proxy classes.
	 *
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
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
	 * Constructor
	 *
	 * @param string $context The application context, for example "Production" or "Development"
	 */
	public function __construct($context) {
		$this->defineConstants();
		$this->ensureRequiredEnvironment();

		$this->context = new ApplicationContext($context);
		if ($this->context->isTesting()) {
			require_once('PHPUnit/Autoload.php');
			require_once(FLOW3_PATH_FLOW3 . 'Tests/BaseTestCase.php');
			require_once(FLOW3_PATH_FLOW3 . 'Tests/FunctionalTestCase.php');
		}
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
	 * modified data and commit other tasks necessary to cleanly exit FLOW3.
	 *
	 * This method should be called by a request handler after a successful run.
	 * Control is returned to the request handler which can exit the application
	 * as it sees fit.
	 *
	 * @param string $runlevel The runlevel the request ran in – must be either "Runtime" or "Compiletime"
	 * @return void
	 * @api
	 */
	public function shutdown($runlevel) {
		switch($runlevel) {
			case 'Compiletime':
				$this->emitFinishedCompiletimeRun();
			break;
			case 'Runtime':
				$this->emitFinishedRuntimeRun();
			break;
		}
		$this->emitBootstrapShuttingDown($runlevel);
	}

	/**
	 * Returns the context this bootstrap was started in.
	 *
	 * @return \TYPO3\FLOW3\Core\ApplicationContext The context encapsulated in an object, for example "Development" or "Development/MyDeployment"
	 * @api
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Registers a request handler which can possibly handle a request.
	 *
	 * All registered request handlers will be queried if they can handle a request
	 * when the bootstrap's run() method is called.
	 *
	 * @param \TYPO3\FLOW3\Core\RequestHandlerInterface $requestHandler
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
	 * @return \TYPO3\FLOW3\Core\RequestHandlerInterface
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
	 * @param \TYPO3\FLOW3\Core\RequestHandlerInterface $requestHandler
	 * @return void
	 */
	public function setActiveRequestHandler(\TYPO3\FLOW3\Core\RequestHandlerInterface $requestHandler) {
		$this->activeRequestHandler = $requestHandler;
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
	 * Builds a boot sequence with the minimum modules necessary for both, compiletime
	 * and runtime.
	 *
	 * @return \TYPO3\FLOW3\Core\Booting\Sequence
	 * @api
	 */
	public function buildEssentialsSequence() {
		$sequence = new Sequence();
		$sequence->addStep(new Step('typo3.flow3:configuration', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeConfiguration')));
		$sequence->addStep(new Step('typo3.flow3:systemlogger', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeSystemLogger')), 'typo3.flow3:configuration');

		if ($this->context->isProduction()) {
			$sequence->addStep(new Step('typo3.flow3:lockmanager', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeLockManager')), 'typo3.flow3:systemlogger');
		}

		$sequence->addStep(new Step('typo3.flow3:errorhandling', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeErrorHandling')), 'typo3.flow3:systemlogger');
		$sequence->addStep(new Step('typo3.flow3:cachemanagement', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeCacheManagement')), 'typo3.flow3:systemlogger');
		return $sequence;
	}

	/**
	 * Builds a boot sequence starting all modules necessary for the compiletime state.
	 * This includes all of the "essentials" sequence.
	 *
	 * @return \TYPO3\FLOW3\Core\Booting\Sequence
	 * @api
	 */
	public function buildCompiletimeSequence() {
		$sequence = $this->buildEssentialsSequence();

		if ($this->context->isProduction()) {
			$bootstrap = $this;
			$sequence->addStep(new Step('typo3.flow3:lockmanager:locksiteorexit', function() use ($bootstrap) { $bootstrap->getEarlyInstance('TYPO3\FLOW3\Core\LockManager')->lockSiteOrExit(); } ), 'typo3.flow3:systemlogger');
		}

		$sequence->addStep(new Step('typo3.flow3:cachemanagement:forceflush', array('TYPO3\FLOW3\Core\Booting\Scripts', 'forceFlushCachesIfNeccessary')), 'typo3.flow3:systemlogger');
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:compiletime:create', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeObjectManagerCompileTimeCreate')), 'typo3.flow3:systemlogger');
		$sequence->addStep(new Step('typo3.flow3:systemfilemonitor', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeSystemFileMonitor')), 'typo3.flow3:objectmanagement:compiletime:create');
		$sequence->addStep(new Step('typo3.flow3:reflectionservice', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow3:systemfilemonitor');
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:compiletime:finalize', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeObjectManagerCompileTimeFinalize')), 'typo3.flow3:reflectionservice');
		return $sequence;
	}

	/**
	 * Builds a boot sequence starting all modules necessary for the runtime state.
	 * This includes all of the "essentials" sequence.
	 *
	 * @return \TYPO3\FLOW3\Core\Booting\Sequence
	 * @api
	 */
	public function buildRuntimeSequence() {
		$sequence = $this->buildEssentialsSequence();
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:proxyclasses', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeProxyClasses')), 'typo3.flow3:systemlogger');
		$sequence->addStep(new Step('typo3.flow3:classloader:cache', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeClassLoaderClassesCache')), 'typo3.flow3:objectmanagement:proxyclasses');
		$sequence->addStep(new Step('typo3.flow3:objectmanagement:runtime', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeObjectManager')), 'typo3.flow3:classloader:cache');

		if (!$this->context->isProduction()) {
			$sequence->addStep(new Step('typo3.flow3:systemfilemonitor', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeSystemFileMonitor')), 'typo3.flow3:objectmanagement:runtime');
		}

		$sequence->addStep(new Step('typo3.flow3:reflectionservice', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeReflectionService')), 'typo3.flow3:objectmanagement:runtime');
		$sequence->addStep(new Step('typo3.flow3:persistence', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializePersistence')), 'typo3.flow3:reflectionservice');
		$sequence->addStep(new Step('typo3.flow3:session', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeSession')), 'typo3.flow3:persistence');
		$sequence->addStep(new Step('typo3.flow3:resources', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeResources')), 'typo3.flow3:session');
		$sequence->addStep(new Step('typo3.flow3:i18n', array('TYPO3\FLOW3\Core\Booting\Scripts', 'initializeI18n')), 'typo3.flow3:resources');
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
	 * @return \TYPO3\FLOW3\SignalSlot\Dispatcher
	 * @api
	 */
	public function getSignalSlotDispatcher() {
		return $this->earlyInstances['TYPO3\FLOW3\SignalSlot\Dispatcher'];
	}

	/**
	 * Returns an instance which was registered earlier through setEarlyInstance()
	 *
	 * @param string $objectName Object name of the registered instance
	 * @return object
	 * @throws \TYPO3\FLOW3\Exception
	 * @api
	 */
	public function getEarlyInstance($objectName) {
		if (!isset($this->earlyInstances[$objectName])) {
			throw new \TYPO3\FLOW3\Exception('Unknown early instance "' . $objectName . '"', 1322581449);
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
	 * @return \TYPO3\FLOW3\Object\ObjectManagerInterface
	 * @throws \TYPO3\FLOW3\Exception
	 */
	public function getObjectManager() {
		if (!isset($this->earlyInstances['TYPO3\FLOW3\Object\ObjectManagerInterface'])) {
			debug_print_backtrace();
			throw new \TYPO3\FLOW3\Exception('The Object Manager is not available at this stage of the bootstrap run.', 1301120788);
		}
		return $this->earlyInstances['TYPO3\FLOW3\Object\ObjectManagerInterface'];
	}

	/**
	 * Iterates over the registered request handlers and determines which one fits best.
	 *
	 * @return \TYPO3\FLOW3\Core\RequestHandlerInterface A request handler
	 * @throws \TYPO3\FLOW3\Exception
	 */
	protected function resolveRequestHandler() {
		if ($this->preselectedRequestHandlerClassName !== NULL && isset($this->requestHandlers[$this->preselectedRequestHandlerClassName])) {
			$requestHandler = $this->requestHandlers[$this->preselectedRequestHandlerClassName];
			if ($requestHandler->canHandleRequest()) {
				return $requestHandler;
			}
		}

		foreach ($this->requestHandlers as $requestHandler) {
			if ($requestHandler->canHandleRequest() > 0) {
				$priority = $requestHandler->getPriority();
				if (isset($suitableRequestHandlers[$priority])) {
					throw new \TYPO3\FLOW3\Exception('More than one request handler with the same priority can handle the request, but only one handler may be active at a time!', 1176475350);
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
	 * @FLOW3\Signal
	 */
	protected function emitFinishedCompiletimeRun() {
		$this->earlyInstances['TYPO3\FLOW3\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'finishedCompiletimeRun', array());
	}

	/**
	 * Emits a signal that the runtime run was finished.
	 *
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitFinishedRuntimeRun() {
		$this->earlyInstances['TYPO3\FLOW3\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'finishedRuntimeRun', array());
	}

	/**
	 * Emits a signal that the bootstrap finished and is shutting down.
	 *
	 * @param string $runLevel
	 * @return void
	 * @FLOW3\Signal
	 */
	protected function emitBootstrapShuttingDown($runLevel) {
		$this->earlyInstances['TYPO3\FLOW3\SignalSlot\Dispatcher']->dispatch(__CLASS__, 'bootstrapShuttingDown', array($runLevel));
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

		define('FLOW3_VERSION_BRANCH', '1.2');
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

		if (!extension_loaded('Reflection')) {
			echo('The PHP extension "Reflection" is required by FLOW3.' . PHP_EOL);
			exit(1);
		}
		$method = new \ReflectionMethod(__CLASS__, __FUNCTION__);
		if ($method->getDocComment() === FALSE || $method->getDocComment() === '') {
			echo('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.' . PHP_EOL);
			exit(1);
		}

		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');

		if (ini_get('date.timezone') === '') {
			echo('FLOW3 requires the PHP setting "date.timezone" to be set. (Error #1342087777)');
			exit(1);
		}

		if (version_compare(PHP_VERSION, '5.4', '<') && get_magic_quotes_gpc() === 1) {
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
