<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3;

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

/**
 * @package FLOW3
 * @version $Id$
 */

if (version_compare(phpversion(), \F3\FLOW3\FLOW3::MINIMUM_PHP_VERSION, '<')) {
	die('FLOW3 requires PHP version ' . \F3\FLOW3\FLOW3::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
}
if (version_compare(PHP_VERSION, \F3\FLOW3\FLOW3::MAXIMUM_PHP_VERSION, '>')) {
	die('FLOW3 requires PHP version ' . \F3\FLOW3\FLOW3::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)');
}

/**
 * Utility_Files is needed before the autoloader is active
 */
require(__DIR__ . '/Utility/Files.php');
require(__DIR__ . '/Package/PackageInterface.php');
require(__DIR__ . '/Package/Package.php');

define('FLOW3_PATH_FLOW3', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(__DIR__ . '/../') . '/'));
define('FLOW3_PATH_CONFIGURATION', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_PUBLIC . '../Configuration/') . '/'));
define('FLOW3_PATH_DATA', \F3\FLOW3\Utility\Files::getUnixStylePath(realpath(FLOW3_PATH_PUBLIC . '../Data/') . '/'));

/**
 * General purpose central core hyper FLOW3 class
 *
 * @package FLOW3
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
final class FLOW3 {

	/**
	 * The version of the FLOW3 framework
	 */
	const VERSION = '0.2.0';

	const MINIMUM_PHP_VERSION = '5.3.0beta1';
	const MAXIMUM_PHP_VERSION = '5.9.9';

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

	/**
	 * The configuration manager
	 *
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

	/**
	 * An instance of the object manager
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * A reference to the object factory
	 *
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * A reference to the package manager
	 *
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * A reference to the FLOW3 package which can be used before the Package Manager is initialized
	 * @var \F3\FLOW3\Package\Package
	 */
	protected $FLOW3Package;

	/**
	 * Instance of the class loader
	 *
	 * @var \F3\FLOW3\Resource\ClassLoader
	 */
	protected $classLoader;

	/**
	 * Instance of the reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Error\ExceptionHandlerInterface
	 */
	protected $exceptionHandler;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Array of class names which must not be registered as objects automatically. Class names may also be regular expressions.
	 * @var array
	 */
	protected $objectRegistrationClassBlacklist = array(
		'F3\FLOW3\AOP\.*',
		'F3\FLOW3\Object.*',
		'F3\FLOW3\Package.*',
		'F3\FLOW3\Reflection.*'
	);

	/**
	 * The settings for the FLOW3 package
	 * @var \F3\FLOW3\Configuration\Container
	 */
	protected $settings;

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context = 'Production') {
		$this->checkEnvironment();
		$this->context = $context;
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
	 */
	public function initialize() {
		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeError();
		$this->initializeObjectFramework();
		$this->initializeSystemLogger();
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
		$this->initializeSession();
		$this->initializePersistence();
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
			require(__DIR__ . '/Resource/ClassLoader.php');
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
		$configurationSources = array(
			new \F3\FLOW3\Configuration\Source\PHP(),
			new \F3\FLOW3\Configuration\Source\YAML()
		);
		$this->configurationManager = new \F3\FLOW3\Configuration\Manager($this->context, $configurationSources);
		$this->configurationManager->loadFLOW3Settings();
		$this->settings = $this->configurationManager->getSettings('FLOW3');
	}

	/**
	 * Initializes the Error component
	 *
	 * @return ovid
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeError() {
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
	public function initializeObjectFramework() {
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
		$this->objectManager->setContext($this->context);

		$objectConfigurations = array();
		$rawFLOW3ObjectConfigurations = $this->configurationManager->getSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, $this->FLOW3Package);
		foreach ($rawFLOW3ObjectConfigurations as $objectName => $rawFLOW3ObjectConfiguration) {
			$objectConfigurations[$objectName] = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawFLOW3ObjectConfiguration, 'Package FLOW3 (pre-initialization)');
		}
		$this->objectManager->setObjectConfigurations($objectConfigurations);
		$this->objectManager->initialize();

			// Remove the preliminary reflection service and rebuild it, this time with the proper object configuration:
		$singletonObjectsRegistry->removeObject('F3\FLOW3\Reflection\Service');
		$this->objectManager->injectReflectionService($this->objectManager->getObject('F3\FLOW3\Reflection\Service'));

		$singletonObjectsRegistry->putObject('F3\FLOW3\Resource\ClassLoader', $this->classLoader);
		$singletonObjectsRegistry->putObject('F3\FLOW3\Configuration\Manager', $this->configurationManager);
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

		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $this->configurationManager->getSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_PACKAGES, $package);
			$this->evaluatePackageConfiguration($package, $packageConfiguration);
		}

		$this->configurationManager->loadGlobalSettings($activePackages);
		$this->configurationManager->loadSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_ROUTES, $activePackages);
		$this->configurationManager->loadSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS, $activePackages);
		$this->configurationManager->loadSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES, $activePackages);
	}

	/**
	 * Initializes the Signals and Slots mechanism
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see intialize()
	 */
	public function initializeSignalsSlots() {
		$dispatcher = $this->objectManager->getObject('F3\FLOW3\SignalSlot\Dispatcher');

		$signalsSlotsConfiguration = $this->configurationManager->getSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_SIGNALSSLOTS);
		foreach ($signalsSlotsConfiguration as $signalClassName => $signalSubConfiguration) {
			if (is_array($signalSubConfiguration)) {
				foreach ($signalSubConfiguration as $signalMethodName => $slotConfigurations) {
					$signalMethodName = 'emit' . ucfirst($signalMethodName);
					if (is_array($slotConfigurations)) {
						foreach ($slotConfigurations as $slotConfiguration) {
							if (is_array($slotConfiguration)) {
								if (isset($slotConfiguration[0]) && isset($slotConfiguration[1])) {
									$omitSignalInformation = (isset($slotConfiguration[2])) ? $slotConfiguration[2] : FALSE;
									$dispatcher->connect($signalClassName, $signalMethodName, $slotConfiguration[0], $slotConfiguration[1], $omitSignalInformation);
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
		$this->cacheManager->setCacheConfigurations($this->configurationManager->getSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_CACHES));
		$this->cacheManager->initialize();

		$cacheFactory = $this->objectManager->getObject('F3\FLOW3\Cache\Factory');
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
		if ($this->settings['monitor']['fileMonitor']['enable'] === TRUE) {
			$this->monitorClassFiles();
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
			foreach($package->getClassFiles() as $className => $classFileName) {
				$monitor->monitorFile($classesPath . $classFileName);
			}
		}

		$classFileCache = $this->cacheManager->getCache('FLOW3_Cache_ClassFiles');
		$cacheManager = $this->cacheManager;
		$atLeastOneClassFileChanged = FALSE;
		$cacheFlushingSlot = function() use ($classFileCache, $cacheManager, &$atLeastOneClassFileChanged) {
			list($signalName, $monitorIdentifier, $pathAndFilename, $status) = func_get_args();
			if ($monitorIdentifier === 'FLOW3_ClassFiles') {
				$matches = array();
				if (1 === preg_match('/.+\/(.+)\/Classes\/(.+)\.php/', $pathAndFilename, $matches)) {
					$className = 'F3\\' . $matches[1] . '\\' . str_replace('/', '\\', $matches[2]);
					$cacheManager->flushCachesByTag($classFileCache->getClassTag($className));
					$atLeastOneClassFileChanged = TRUE;
				}
			}
		};

		$signalSlotDispatcher = $this->objectManager->getObject('F3\FLOW3\SignalSlot\Dispatcher');
		$signalSlotDispatcher->connect('F3\FLOW3\Monitor\FileMonitor', 'emitFileHasChanged', $cacheFlushingSlot);

		$monitor->detectChanges();

		if ($atLeastOneClassFileChanged) {
			$this->cacheManager->flushCachesByTag($classFileCache->getClassTag());
		}
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

		$availableClassNames = array();
		foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
			foreach (array_keys($package->getClassFiles()) as $className) {
				$availableClassNames[] = $className;
			}
		}
		$this->reflectionService->initialize($availableClassNames);
	}

	/**
	 * Initializes the object framework and loads the object configuration
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjects() {
		$objectConfigurations = NULL;

		$objectConfigurationsCache = $this->cacheManager->getCache('FLOW3_Object_Configurations');
		if ($objectConfigurationsCache->has('baseObjectConfigurations')) {
			$objectConfigurations = $objectConfigurationsCache->get('baseObjectConfigurations');
		}

		if ($objectConfigurations === NULL) {
			$this->registerAndConfigureAllPackageObjects($this->packageManager->getActivePackages());
			$objectConfigurations = $this->objectManager->getObjectConfigurations();
			$objectConfigurationsCache->set('baseObjectConfigurations', $objectConfigurations, array($objectConfigurationsCache->getClassTag()));
		}

		$this->objectManager->setObjectConfigurations($objectConfigurations);
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
	 */
	public function initializeSession() {
		$session = $this->objectManager->getObject('F3\FLOW3\Session\SessionInterface');
		$session->start();
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
			$persistenceManager = $this->objectManager->getObject('F3\FLOW3\Persistence\Manager');
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
		$environment = $this->objectManager->getObject('F3\FLOW3\Utility\Environment');
		if ($environment->getSAPIName() !== 'cli') {
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
	 */
	public function run() {
		$requestHandlerResolver = $this->objectManager->getObject('F3\FLOW3\MVC\RequestHandlerResolver');
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();

		if ($this->settings['persistence']['enable'] === TRUE) {
			$this->objectManager->getObject('F3\FLOW3\Persistence\Manager')->persistAll();
		}
		$this->objectManager->getObject('F3\FLOW3\Session\SessionInterface')->close();
		$this->systemLogger->log(sprintf('Shutting down FLOW3 ...', $this->context), LOG_INFO);
		$this->objectManager->shutdown();

	}

	/**
	 * Checks PHP version and other parameters of the environment
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal RL: The version check should be replaced by a more fine grained check done by the package manager, taking the package's requirements into account.
	 */
	protected function checkEnvironment() {
		if (version_compare(PHP_VERSION, '6.0.0', '<') && !extension_loaded('mbstring')) {
			die('FLOW3 requires the PHP extension "mbstring" for PHP versions below 6.0.0 (Error #1207148809)');
		}

		if (!extension_loaded('Reflection')) throw new \F3\FLOW3\Exception('The PHP extension "Reflection" is required by FLOW3.', 1218016725);
		$method = new \ReflectionMethod(__CLASS__, 'checkEnvironment');
		if ($method->getDocComment() == '') throw new \F3\FLOW3\Exception('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.', 1218016727);

		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
		#locale_set_default('en_UK');
		if (ini_get('date.timezone') == '') {
			date_default_timezone_set('Europe/Copenhagen');
		}

		if (ini_get('magic_quotes_gpc') == '1' || ini_get('magic_quotes_gpc') == 'On') {
			die('FLOW3 requires the PHP setting "magic_quotes_gpc" set to Off. (Error #1224003190)');
		}
	}

	/**
	 * Traverses through all active packages and registers their classes as
	 * objects at the object manager. Finally the object configuration
	 * defined by the package is loaded and applied to the registered objects.
	 *
	 * @param array $packages The packages whose classes should be registered
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerAndConfigureAllPackageObjects(array $packages) {
		$objectTypes = array();
		$availableClassNames = array();

		foreach ($packages as $packageKey => $package) {
			foreach (array_keys($package->getClassFiles()) as $className) {
				if (!$this->classNameIsBlacklisted($className)) {
					$availableClassNames[] = $className;
				}
			}
		}

		foreach ($availableClassNames as $className) {
			if (substr($className, -9, 9) === 'Interface') {
				$objectTypes[] = $className;
				if (!$this->objectManager->isObjectRegistered($className)) {
					$this->objectManager->registerObjectType($className);
				}
			} else {
				$objectName = $className;
				if (!$this->objectManager->isObjectRegistered($objectName)) {
					if (!$this->reflectionService->isClassAbstract($className)) {
						$this->objectManager->registerObject($objectName, $className);
					}
				}
			}
		}

		$objectConfigurations = $this->objectManager->getObjectConfigurations();
		foreach ($packages as $packageKey => $package) {
			$rawObjectConfigurations = $this->configurationManager->getSpecialConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, $package);
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '\\', $objectName);
				if (!$this->objectManager->isObjectRegistered($objectName)) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfiguration('Tried to configure unknown object "' . $objectName . '" in package "' . $package->getPackageKey() . '".', 1184926175);
				}
				if (is_array($rawObjectConfiguration)) {
					$existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : NULL;
					$objectConfigurations[$objectName] = \F3\FLOW3\Object\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawObjectConfiguration, 'Package ' . $packageKey, $existingObjectConfiguration);
				}
			}
		}

		foreach ($objectTypes as $objectType) {
			$defaultImplementationClassName = $this->reflectionService->getDefaultImplementationClassNameForInterface($objectType);
			if ($defaultImplementationClassName !== FALSE) {
				$objectConfigurations[$objectType]->setClassName($defaultImplementationClassName);
			}
		}
		$this->objectManager->setObjectConfigurations($objectConfigurations);
	}

	/**
	 * Checks if the given class name appears on in the object blacklist.
	 *
	 * @param string $className The class name to check. May be a regular expression.
	 * @return boolean TRUE if the class has been blacklisted, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function classNameIsBlacklisted($className) {
		foreach ($this->objectRegistrationClassBlacklist as $blacklistedClassName) {
		if ($className == $blacklistedClassName || preg_match('/^' . str_replace('\\', '\\\\', $blacklistedClassName) . '$/', $className)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * (For now) evaluates the package configuration
	 *
	 * @param \F3\FLOW3\Package\Package $package The package
	 * @param array The configuration to evaluate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo needs refactoring and be moved to elsewhere (resource manager, package manager etc.)
	 */
	protected function evaluatePackageConfiguration(\F3\FLOW3\Package\Package $package, array $packageConfiguration) {
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