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
require(__DIR__ . '/../Utility/Files.php');
require(__DIR__ . '/../Package/PackageInterface.php');
require(__DIR__ . '/../Package/Package.php');

BootStrap::defineConstants();

/**
 * General purpose central core hyper FLOW3 bootstrap class
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
final class Bootstrap {

	/**
	 * FLOW3's Subversion revision
	 */
	const REVISION = '$Revision: 2956$';

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
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
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
	 * @var \F3\FLOW3\Reflection\Service
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
	 * @var \F3\FLOW3\Configuration\Container
	 */
	protected $settings;

	/**
	 * Defines various path constants used by FLOW3 and if no root path or web root was
	 * specified by an environment variable, exits with a respective error message.
	 *
	 * @return void
	 */
	public static function defineConstants() {
		define('FLOW3_SAPITYPE', (PHP_SAPI === 'cli' ? 'CLI' : 'Web'));
		define('FLOW3_PATH_FLOW3', str_replace('//', '/', str_replace('\\', '/', (realpath(__DIR__ . '/../../') . '/'))));

		if (isset($_SERVER['FLOW3_ROOTPATH'])) {
			$rootPath = str_replace('//', '/', str_replace('\\', '/', (realpath($_SERVER['FLOW3_ROOTPATH'])))) . '/';
			$testPath = str_replace('//', '/', str_replace('\\', '/', (realpath($rootPath . 'Packages/Framework/FLOW3')))) . '/';
			if ($testPath !== FLOW3_PATH_FLOW3) {
				exit('FLOW3: Invalid root path. (Error #1248964375)' . PHP_EOL . '"' . $rootPath . 'Packages/Framework/FLOW3' .'" does not lead to' . PHP_EOL . '"' . FLOW3_PATH_FLOW3 .'"' . PHP_EOL);
			}
			define('FLOW3_PATH_ROOT', $rootPath);
			unset($rootPath);
			unset($testPath);
		}

		if (FLOW3_SAPITYPE === 'cli') {
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
		$this->initializeObjectManager();
		$this->initializeSystemLogger();

#		$this->initializeLockManager();
		if ($this->siteLocked === TRUE) return;

		$this->initializePackages();

		if ($this->packageManager->isPackageActive('FirePHP')) {
			$this->objectManager->registerObject('F3\FirePHP\Core');
			$this->objectManager->getObject('F3\FirePHP\Core');
		}

		$this->initializeSignalsSlots();
		$this->initializeCache();
		$this->initializeFileMonitor();
		$this->initializeReflection();
		$this->initializeObjects();
		$this->initializeAOP();
		$this->initializePersistence();
		$this->initializeSession();
		$this->initializeResources();
		$this->initializeLocale();
	}

	/**
	 * Initializes the class loader
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	public function initializeClassLoader() {
		if (!class_exists('F3\FLOW3\Resource\ClassLoader')) {
			require(FLOW3_PATH_FLOW3 . 'Classes/Resource/ClassLoader.php');
		}

		$initialPackages = array(
			'FLOW3' => $this->FLOW3Package,
			'YAML' => new \F3\FLOW3\Package\Package('YAML', FLOW3_PATH_FLOW3 . '../YAML/')
		);

		$this->classLoader = new \F3\FLOW3\Resource\ClassLoader();
		$this->classLoader->setPackages($initialPackages);
		spl_autoload_register(array($this->classLoader, 'loadClass'));
	}

	/**
	 * Initializes the configuration manager and the FLOW3 settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeConfiguration() {
		$this->configurationManager = new \F3\FLOW3\Configuration\Manager($this->context);
		$this->configurationManager->injectConfigurationSource(new \F3\FLOW3\Configuration\Source\YAMLSource());
		$this->configurationManager->setPackages(array('FLOW3' => $this->FLOW3Package));

		$this->settings = $this->configurationManager->getSettings('FLOW3');
	}

	/**
	 * Initializes the Error component
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeErrorHandling() {
		$errorHandler = new $this->settings['error']['errorHandler']['className'];
		$errorHandler->setExceptionalErrors($this->settings['error']['errorHandler']['exceptionalErrors']);
		$this->exceptionHandler = new $this->settings['error']['exceptionHandler']['className'];
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjectManager() {
		$this->objectFactory = new \F3\FLOW3\Object\Factory();

		$objectBuilder = new \F3\FLOW3\Object\Builder;
		$objectBuilder->injectConfigurationManager($this->configurationManager);

		$singletonObjectsRegistry = new \F3\FLOW3\Object\TransientRegistry;

		$preliminaryReflectionService = new \F3\FLOW3\Reflection\Service();

		$this->objectManager = new \F3\FLOW3\Object\Manager();
		$this->objectManager->injectSingletonObjectsRegistry($singletonObjectsRegistry);
		$this->objectManager->injectObjectBuilder($objectBuilder);
		$this->objectManager->injectObjectFactory($this->objectFactory);
		$this->objectManager->injectReflectionService($preliminaryReflectionService);
		$this->objectManager->injectConfigurationManager($this->configurationManager);
		$this->objectManager->setContext($this->context);

		$this->objectManager->initializeManager();

			// Remove the preliminary reflection service and rebuild it, this time with the proper object configuration:
		$singletonObjectsRegistry->removeObject('F3\FLOW3\Reflection\Service');
		$this->objectManager->injectReflectionService($this->objectManager->getObject('F3\FLOW3\Reflection\Service'));

		$singletonObjectsRegistry->putObject('F3\FLOW3\Resource\ClassLoader', $this->classLoader);
		$singletonObjectsRegistry->putObject('F3\FLOW3\Configuration\Manager', $this->configurationManager);
		$this->configurationManager->injectEnvironment($this->objectManager->getObject('F3\FLOW3\Utility\Environment'));
	}

	/**
	 * Initializes the system logger
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSystemLogger() {
		$this->systemLogger = $this->objectManager->getObject('F3\FLOW3\Log\SystemLoggerInterface');
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
		$lockManager = $this->objectManager->getObject('F3\FLOW3\Core\LockManager');
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
		$this->packageManager = $this->objectManager->getObject('F3\FLOW3\Package\ManagerInterface');
		$this->packageManager->initialize();
		$activePackages = $this->packageManager->getActivePackages();
		$this->classLoader->setPackages($activePackages);
		$this->configurationManager->setPackages($activePackages);

		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGE, $packageKey);
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
		$this->signalSlotDispatcher = $this->objectManager->getObject('F3\FLOW3\SignalSlot\Dispatcher');

		$signalsSlotsConfiguration = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS);
		foreach ($signalsSlotsConfiguration as $signalClassName => $signalSubConfiguration) {
			if (is_array($signalSubConfiguration)) {
				foreach ($signalSubConfiguration as $signalMethodName => $slotConfigurations) {
					$signalMethodName = 'emit' . ucfirst($signalMethodName);
					if (is_array($slotConfigurations)) {
						foreach ($slotConfigurations as $slotConfiguration) {
							if (is_array($slotConfiguration)) {
								if (isset($slotConfiguration[0]) && isset($slotConfiguration[1])) {
									$omitSignalInformation = (isset($slotConfiguration[2])) ? $slotConfiguration[2] : FALSE;
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
		$this->cacheManager = $this->objectManager->getObject('F3\FLOW3\Cache\Manager');
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES));
		$this->cacheManager->initialize();

		$cacheFactory = $this->objectManager->getObject('F3\FLOW3\Cache\Factory');
		$cacheFactory->setCacheManager($this->cacheManager);

		$coreCache= $this->cacheManager->getCache('FLOW3_Core');
		$cachedRevision = ($coreCache->has('revision')) ? $coreCache->get('revision') : NULL;
		$currentRevision = $this->packageManager->getPackage('FLOW3')->getPackageMetaData()->getVersion() . ' (r' . substr(self::REVISION, 11, -2) . ')';
		if ($cachedRevision !== $currentRevision) {
			$this->systemLogger->log('The caches are based on FLOW3 ' . $cachedRevision . ' not matching ' . $currentRevision . ', therefore flushing all caches.', LOG_NOTICE);
			$this->cacheManager->flushCaches();
			$coreCache->set('revision', $currentRevision);
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
		if ($this->settings['monitor']['fileMonitor']['enable'] === TRUE) {
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
		$monitor = $this->objectManager->getObject('F3\FLOW3\Monitor\FileMonitor', 'FLOW3_ClassFiles');

		foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
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
		$monitor = $this->objectManager->getObject('F3\FLOW3\Monitor\FileMonitor', 'FLOW3_RoutesConfigurationFiles');
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
		$this->reflectionService = $this->objectManager->getObject('F3\FLOW3\Reflection\Service');
		$this->reflectionService->setCache($this->cacheManager->getCache('FLOW3_Reflection'));
		$this->reflectionService->injectSystemLogger($this->systemLogger);

		$availableClassNames = array();
		foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
			foreach (array_keys($package->getClassFiles()) as $className) {
				$availableClassNames[] = $className;
			}
		}
		$this->reflectionService->initialize($availableClassNames);
	}

	/**
	 * Initializes the object configuration
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjects() {
		$this->objectManager->injectObjectConfigurationsCache($this->cacheManager->getCache('FLOW3_Object_Configurations'));
		$this->objectManager->initializeObjects($this->packageManager->getActivePackages());
	}

	/**
	 * Initializes the AOP framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeAOP() {
		if ($this->settings['aop']['enable'] === TRUE) {
			$objectConfigurations = $this->objectManager->getObjectConfigurations();
			$AOPFramework = $this->objectManager->getObject('F3\FLOW3\AOP\Framework', $this->objectManager, $this->objectFactory);
			$AOPFramework->initialize($objectConfigurations);
			$this->objectManager->setObjectConfigurations($objectConfigurations);
		}
	}

	/**
	 * Initializes the Locale service
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	public function initializeLocale() {
		$this->objectManager->getObject('F3\FLOW3\Locale\Service', $this->settings)->initialize();
	}

	/**
	 * Initializes the session
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initializeSession() {
		$session = $this->objectManager->getObject('F3\FLOW3\Session\SessionInterface');
		$session->start();

		$sessionObjectsRegistry = $this->objectManager->getObject('F3\FLOW3\Object\SessionRegistry');
		$sessionObjectsRegistry->initialize();
		$this->objectManager->injectSessionObjectsRegistry($sessionObjectsRegistry);
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializePersistence() {
		if ($this->settings['persistence']['enable'] === TRUE) {
			$repository = $this->objectManager->getObject('F3\PHPCR\RepositoryInterface');
			$session = $repository->login();
			$persistenceBackend = $this->objectManager->getObject('F3\FLOW3\Persistence\BackendInterface', $session);
			$persistenceManager = $this->objectManager->getObject('F3\FLOW3\Persistence\ManagerInterface');
			$persistenceManager->initialize();
		}
	}

	/**
	 * Checks if resources (ie. files in the Resource directory of a package) have been altered and if so flushes
	 * the related caches.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	protected function detectAlteredResources() {
	}

	/**
	 * Publishes the public resources of all found packages
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @see initialize()
	 */
	public function initializeResources() {
		if (FLOW3_SAPITYPE === 'Web') {
			$this->detectAlteredResources();
			$metadataCache = $this->cacheManager->getCache('FLOW3_Resource_MetaData');
			$statusCache = $this->cacheManager->getCache('FLOW3_Resource_Status');

			$resourcePublisher = $this->objectManager->getObject('F3\FLOW3\Resource\Publisher');
			$resourcePublisher->initializeMirrorDirectory($this->settings['resource']['cache']['publicPath']);
			$resourcePublisher->setMetadataCache($metadataCache);
			$resourcePublisher->setStatusCache($statusCache);
			$resourcePublisher->setCacheStrategy($this->settings['resource']['cache']['strategy']);

			$activePackages = $this->packageManager->getActivePackages();
			foreach ($activePackages as $packageKey => $package) {
				$resourcePublisher->mirrorResourcesDirectory($package->getResourcesPath() . 'Public/', 'Packages/' . $packageKey . '/');
			}
			$resourcePublisher->mirrorResourcesDirectory(FLOW3_PATH_DATA . 'Resources/Public/', 'Static/');
		}
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
			$requestHandlerResolver = $this->objectManager->getObject('F3\FLOW3\MVC\RequestHandlerResolver');
			$requestHandler = $requestHandlerResolver->resolveRequestHandler();
			$requestHandler->handleRequest();

			if ($this->settings['persistence']['enable'] === TRUE) {
				$this->objectManager->getObject('F3\FLOW3\Persistence\ManagerInterface')->persistAll();
			}

			$this->emitFinishedNormalRun();
			$this->systemLogger->log('Shutting down ...', LOG_INFO);

			$this->configurationManager->shutdown();
			$this->objectManager->shutdown();
			$this->reflectionService->shutdown();

			$this->objectManager->getObject('F3\FLOW3\Object\SessionRegistry')->writeDataToSession();
			$this->objectManager->getObject('F3\FLOW3\Session\SessionInterface')->close();
		} else {
			header('HTTP/1.1 503 Service Temporarily Unavailable');
			readfile(FLOW3_PATH_FLOW3 . 'Resources/Private/Core/LockHoldingStackPage.html');
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
	 * @todo needs refactoring and be moved to elsewhere (resource manager, package manager etc.)
	 */
	protected function evaluatePackageConfiguration(\F3\FLOW3\Package\PackageInterface $package, array $packageConfiguration) {
		if (isset($packageConfiguration['resourceManager'])) {
			if (isset($packageConfiguration['resourceManager']['specialClassNameAndPaths'])) {
				$resourceManager = $this->objectManager->getObject('F3\FLOW3\Resource\Manager');
				foreach ($packageConfiguration['resourceManager']['specialClassNameAndPaths'] as $className => $classFilePathAndName) {
					$classFilePathAndName = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $classFilePathAndName);
					$resourceManager->registerClassFile($className, $classFilePathAndName);
				}
			}

			if (isset($packageConfiguration['resourceManager']['includePaths'])) {
				foreach ($packageConfiguration['resourceManager']['includePaths'] as $includePath) {
					$includePath = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $includePath);
					$includePath = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $includePath);
					$includePath = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $includePath);
					$includePath = str_replace('/', DIRECTORY_SEPARATOR, $includePath);
					set_include_path($includePath . PATH_SEPARATOR . get_include_path());
				}
			}
		}
	}
}

?>