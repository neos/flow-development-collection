<?php
declare(ENCODING = 'utf-8');
namespace F3;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @version $Id$
 */

if (version_compare(phpversion(), F3::FLOW3::MINIMUM_PHP_VERSION, '<')) {
	die('FLOW3 requires PHP version ' . F3::FLOW3::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
}
if (version_compare(PHP_VERSION, F3::FLOW3::MAXIMUM_PHP_VERSION, '>')) {
	die('FLOW3 requires PHP version ' . F3::FLOW3::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . PHP_VERSION . '. (Error #1172215790)');
}

/**
 * Utility_Files is needed before the autoloader is active
 */
require(__DIR__ . '/Utility/F3_FLOW3_Utility_Files.php');

define('FLOW3_PATH_FLOW3', F3::FLOW3::Utility::Files::getUnixStylePath(__DIR__ . '/'));
define('FLOW3_PATH_PACKAGES', F3::FLOW3::Utility::Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3 . '../../') . '/'));
define('FLOW3_PATH_CONFIGURATION', F3::FLOW3::Utility::Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3 . '../../../Configuration/') . '/'));
define('FLOW3_PATH_DATA', F3::FLOW3::Utility::Files::getUnixStylePath(realpath(FLOW3_PATH_FLOW3 . '../../../Data/') . '/'));

/**
 * General purpose central core hyper FLOW3 class
 *
 * @package FLOW3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
final class FLOW3 {

	/**
	 * The version of the FLOW3 framework
	 */
	const VERSION = '0.2.0';

	const MINIMUM_PHP_VERSION = '5.3.0alpha1';
	const MAXIMUM_PHP_VERSION = '5.9.9';

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

	/**
	 * The configuration manager
	 *
	 * @var F3::FLOW3::Configuration::Manager
	 */
	protected $configurationManager;

	/**
	 * An instance of the object manager
	 * @var F3::FLOW3::Object::ManagerInterface
	 */
	protected $objectManager;

	/**
	 * A reference to the object factory
	 *
	 * @var F3::FLOW3::Object::FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * Instance of the class loader
	 *
	 * @var F3::FLOW3::Resource::ClassLoader
	 */
	protected $classLoader;

	/**
	 * Instance of the reflection service
	 *
	 * @var F3::FLOW3::Reflection::Service
	 */
	protected $reflectionService;

	/**
	 * Instance of the cache factory
	 *
	 * @var F3::FLOW3::Cache::Factory
	 */
	protected $cacheFactory;

	/**
	 * Array of class names which must not be registered as objects automatically. Class names may also be regular expressions.
	 * @var array
	 */
	protected $objectRegistrationClassBlacklist = array(
		'F3::FLOW3::AOP::.*',
		'F3::FLOW3::Object.*',
		'F3::FLOW3::Package.*',
		'F3::FLOW3::Reflection.*',
		'F3::FLOW3::Session.*'
	);

	/**
	 * The settings for the FLOW3 package
	 * @var F3::FLOW3::Configuration::Container
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
	 * @throws F3::FLOW3::Exception if the framework has already been initialized.
	 */
	public function initialize() {
		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeError();
		$this->initializeObjectFramework();
		$this->initializeEnvironment();
		$this->initializeCache();
		$this->initializePackages();
		$this->detectAlteredClasses();
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
		if (!class_exists('F3::FLOW3::Resource::ClassLoader')) {
			require(__DIR__ . '/Resource/F3_FLOW3_Resource_ClassLoader.php');
		}
		$this->classLoader = new F3::FLOW3::Resource::ClassLoader(FLOW3_PATH_PACKAGES);
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
			new F3::FLOW3::Configuration::Source::PHP(),
			new F3::FLOW3::Configuration::Source::YAML()
		);
		$this->configurationManager = new F3::FLOW3::Configuration::Manager($this->context, $configurationSources);
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
		new $this->settings['error']['exceptionHandler']['className'];
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjectFramework() {
		$this->reflectionService = new F3::FLOW3::Reflection::Service();
		$this->objectFactory = new F3::FLOW3::Object::Factory();

		$this->objectManager = new F3::FLOW3::Object::Manager();
		$this->objectManager->injectReflectionService($this->reflectionService);
		$this->objectManager->injectObjectRegistry(new F3::FLOW3::Object::TransientRegistry);
		$this->objectManager->injectObjectBuilder(new F3::FLOW3::Object::Builder);
		$this->objectManager->injectObjectFactory($this->objectFactory);
		$this->objectManager->setContext($this->context);
		$this->objectManager->initialize();

		$this->objectManager->registerObject('F3::FLOW3::Resource::ClassLoader', NULL, $this->classLoader);
		$this->objectManager->registerObject('F3::FLOW3::Configuration::Manager', NULL, $this->configurationManager);
	}

	/**
	 * Initializes the environment utility class.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeEnvironment() {
		$environment = new F3::FLOW3::Utility::Environment;
		$this->objectManager->registerObject('F3::FLOW3::Utility::Environment', NULL, $environment);
		$environment->setTemporaryDirectoryBase($this->settings['utility']['environment']['temporaryDirectoryBase']);
	}

	/**
	 * Initializes the cache framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeCache() {
		$this->objectManager->registerObject('F3::FLOW3::Cache::Factory');
		$this->objectManager->registerObject('F3::FLOW3::Cache::Manager');
		$this->objectManager->registerObject('F3::FLOW3::Cache::Backend::File');
		$this->objectManager->registerObject('F3::FLOW3::Cache::Backend::Memcached');
		$this->objectManager->registerObject('F3::FLOW3::Cache::VariableCache');

		$property = new F3::FLOW3::Object::ConfigurationProperty('environment', 'F3::FLOW3::Utility::Environment', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE);
		$configuration = $this->objectManager->getObjectConfiguration('F3::FLOW3::Cache::Backend::File');
		$configuration->setProperty($property);
		$this->objectManager->setObjectConfiguration($configuration);

		$this->cacheFactory = $this->objectManager->getObject('F3::FLOW3::Cache::Factory');
		$this->cacheManager = $this->objectManager->getObject('F3::FLOW3::Cache::Manager');

		$this->cacheFactory->create('FLOW3_Package_ClassFiles', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::File');
		$this->cacheFactory->create('FLOW3_Object_Configurations', 'F3::FLOW3::Cache::VariableCache', $this->settings['object']['configurationCache']['backend'], $this->settings['object']['configurationCache']['backendOptions']);
		$this->cacheFactory->create('FLOW3_Reflection', 'F3::FLOW3::Cache::VariableCache', $this->settings['reflection']['cache']['backend'], $this->settings['reflection']['cache']['backendOptions']);
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
		$this->objectManager->registerObject('F3::FLOW3::Resource::Publisher');
		$this->objectManager->registerObject('F3::FLOW3::Resource::Manager');

		$this->objectManager->registerObject('F3::FLOW3::Package::ManagerInterface', 'F3::FLOW3::Package::Manager');
		$packageManager = $this->objectManager->getObject('F3::FLOW3::Package::ManagerInterface');
		$packageManager->initialize();
		$activePackages = $packageManager->getActivePackages();

		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $this->configurationManager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_PACKAGES, $packageKey);
			$this->evaluatePackageConfiguration($package, $packageConfiguration);
		}

		$this->configurationManager->loadGlobalSettings(array_keys($activePackages));
		$this->configurationManager->loadRoutesSettings(array_keys($activePackages));
	}

	/**
	 * Checks if classes (ie. php files containing classes) have been altered and if so flushes
	 * the related caches.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function detectAlteredClasses() {
		if ($this->settings['cache']['classAlterationMonitoring']['enable'] !== TRUE) return;

		$packageManager = $this->objectManager->getObject('F3::FLOW3::Package::ManagerInterface');
		$classFileCache = $this->cacheManager->getCache('FLOW3_Package_ClassFiles');
		$classFileModificationTimes = ($classFileCache->has('classFileModificationTimes')) ? $classFileCache->get('classFileModificationTimes') : array();
		$classFileModificationTimesWereModified = FALSE;

		foreach ($packageManager->getActivePackages() as $packageKey => $package) {
			foreach($package->getClassFiles() as $className => $classFileName) {
				$fullClassFileName = $package->getClassesPath() . $classFileName;
				if (file_exists($fullClassFileName)) {
					$currentFileModificationTime = filemtime($fullClassFileName);
					$cachedFileModificationTime = isset($classFileModificationTimes[$fullClassFileName]) ? $classFileModificationTimes[$fullClassFileName] : NULL;
					if ($cachedFileModificationTime === NULL || $currentFileModificationTime > $cachedFileModificationTime) {
						$classFileModificationTimes[$fullClassFileName] = $currentFileModificationTime;
						$tag = $classFileCache->getClassTag($className);
						$this->cacheManager->flushCachesByTag($tag);
						$classFileModificationTimesWereModified = TRUE;
					}
				} else {
					$this->cacheManager->flushCachesByTag($classFileCache->getClassTag($className));
					unset($classFileModificationTimes[$deletedClassFileName]);
					$classFileModificationTimesWereModified = TRUE;
				}
			}
		}

		if ($classFileModificationTimesWereModified) $classFileCache->set('classFileModificationTimes', $classFileModificationTimes);
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

		if ($this->settings['object']['configurationCache']['enable'] === TRUE) {
			$objectConfigurationsCache = $this->cacheManager->getCache('FLOW3_Object_Configurations');
			if ($objectConfigurationsCache->has('baseObjectConfigurations')) {
				$objectConfigurations = $objectConfigurationsCache->get('baseObjectConfigurations');
			}
		}

		if ($objectConfigurations === NULL) {
			$packageManager = $this->objectManager->getObject('F3::FLOW3::Package::ManagerInterface');
			$this->registerAndConfigureAllPackageObjects($packageManager->getActivePackages());
			$objectConfigurations = $this->objectManager->getObjectConfigurations();
			if ($this->settings['object']['configurationCache']['enable']) {
				$objectConfigurationsCache->set('baseObjectConfigurations', $objectConfigurations);
			}
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

			$this->objectManager->registerObject('F3::FLOW3::AOP::Framework');
			$objectConfiguration = $this->objectManager->getObjectConfiguration('F3::FLOW3::AOP::Framework');

			$properties = array(
				'reflectionService' => new F3::FLOW3::Object::ConfigurationProperty('reflectionService', 'F3::FLOW3::Reflection::Service', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
				'pointcutExpressionParser' => new F3::FLOW3::Object::ConfigurationProperty('pointcutExpressionParser', 'F3::FLOW3::AOP::PointcutExpressionParser', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
				'cacheFactory' => new F3::FLOW3::Object::ConfigurationProperty('cacheFactory', 'F3::FLOW3::Cache::Factory', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE),
				'configurationManager' => new F3::FLOW3::Object::ConfigurationProperty('configurationManager', 'F3::FLOW3::Configuration::Manager', F3::FLOW3::Object::ConfigurationProperty::PROPERTY_TYPES_REFERENCE)
			);
			$objectConfiguration->setProperties($properties);
			$this->objectManager->setObjectConfiguration($objectConfiguration);

			$objectConfigurations = $this->objectManager->getObjectConfigurations();

			$AOPFramework = $this->objectManager->getObject('F3::FLOW3::AOP::Framework');
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
		$this->objectManager->getObject('F3::FLOW3::Locale::Service', $this->settings)->initialize();
	}

	/**
	 * Initializes the session
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSession() {
		if (!$this->objectManager->isObjectRegistered('F3::FLOW3::Session::SessionInterface')) {
			$this->objectManager->registerObject('F3::FLOW3::Session::SessionInterface', $this->settings['session']['backend']['className']);
		}
		$session = $this->objectManager->getObject('F3::FLOW3::Session::SessionInterface');
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
			$repository = $this->objectManager->getObject('F3::PHPCR::RepositoryInterface');
			$session = $repository->login();
			$persistenceBackend = $this->objectManager->getObject('F3::FLOW3::Persistence::BackendInterface', $session);
			$persistenceManager = $this->objectManager->getObject('F3::FLOW3::Persistence::Manager');
			$persistenceManager->initialize();
		}

	}
	/**
	 * Publishes the public resources of all found packages
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws F3::FLOW3::Exception if the resource system has already been initialized.
	 * @see initialize()
	 */
	public function initializeResources() {
		$packageManager = $this->objectManager->getObject('F3::FLOW3::Package::ManagerInterface');

		$metadataCache = $this->cacheFactory->create('FLOW3_Resource_MetaData', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::File');
		$statusCache = $this->cacheFactory->create('FLOW3_Resource_Status', 'F3::FLOW3::Cache::StringCache', 'F3::FLOW3::Cache::Backend::File');
		$environment = $this->objectManager->getObject('F3::FLOW3::Utility::Environment');
		$requestType = ($environment->getSAPIName() == 'cli') ? 'CLI' : 'Web';

		$resourcePublisher = $this->objectManager->getObject('F3::FLOW3::Resource::Publisher');
		$resourcePublisher->initializeMirrorDirectory($this->settings['resource']['cache']['publicPath'] . $requestType . '/');
		$resourcePublisher->setMetadataCache($metadataCache);
		$resourcePublisher->setStatusCache($statusCache);
		$resourcePublisher->setCacheStrategy($this->settings['resource']['cache']['strategy']);

		$activePackages = $packageManager->getActivePackages();
		foreach (array_keys($activePackages) as $packageKey) {
			$resourcePublisher->mirrorPublicPackageResources($packageKey);
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
		$requestHandlerResolver = $this->objectManager->getObject('F3::FLOW3::MVC::RequestHandlerResolver', $this->settings);
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();

		if ($this->settings['persistence']['enable'] === TRUE) {
			$this->objectManager->getObject('F3::FLOW3::Persistence::Manager')->persistAll();
		}

		$session = $this->objectManager->getObject('F3::FLOW3::Session::SessionInterface');
		$session->close();
	}

	/**
	 * Returns an instance of the active object manager. This method is and should only
	 * be used by unit tests and special cases. In almost any other case, a reference to the
	 * object manager can be injected.
	 *
		* @return F3::FLOW3::Object::ManagerInterface
		* @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectManager() {
		return $this->objectManager;
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

		if (!extension_loaded('Reflection')) throw new F3::FLOW3::Exception('The PHP extension "Reflection" is required by FLOW3.', 1218016725);
		$method = new ReflectionMethod(__CLASS__, 'checkEnvironment');
		if ($method->getDocComment() == '') throw new F3::FLOW3::Exception('Reflection of doc comments is not supported by your PHP setup. Please check if you have installed an accelerator which removes doc comments.', 1218016727);

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
		$this->reflectionService->setCache($this->cacheManager->getCache('FLOW3_Reflection'));
		$this->reflectionService->initialize($availableClassNames);

		foreach ($availableClassNames as $className) {
			if (substr($className, -9, 9) == 'Interface') {
				$objectTypes[] = $className;
				if (!$this->objectManager->isObjectRegistered($className)) {
					$this->objectManager->registerObjectType($className);
				}
			}
		}

		foreach ($availableClassNames as $className) {
			if (substr($className, -9, 9) != 'Interface') {
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
			$rawObjectConfigurations = $this->configurationManager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_COMPONENTS, $packageKey);
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '::', $objectName);
				if (!$this->objectManager->isObjectRegistered($objectName)) {
					throw new F3::FLOW3::Object::Exception::InvalidObjectConfiguration('Tried to configure unknown object "' . $objectName . '" in package "' . $package->getPackageKey() . '".', 1184926175);
				}
				$existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : NULL;
				$objectConfigurations[$objectName] = F3::FLOW3::Object::ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawObjectConfiguration, 'Package ' . $packageKey, $existingObjectConfiguration);
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
			if ($className == $blacklistedClassName || preg_match('/^' . $blacklistedClassName . '$/', $className)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * (For now) evaluates the package configuration
	 *
	 * @param F3::FLOW3::Package::Package $package The package
	 * @param array The configuration to evaluate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo needs refactoring and be moved to elsewhere (resource manager, package manager etc.)
	 */
	protected function evaluatePackageConfiguration(F3::FLOW3::Package::Package $package, array $packageConfiguration) {
		if (isset($packageConfiguration['resourceManager'])) {
			if (isset($packageConfiguration['resourceManager']['specialClassNameAndPaths'])) {
				$resourceManager = $this->objectManager->getObject('F3::FLOW3::Resource::Manager');
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