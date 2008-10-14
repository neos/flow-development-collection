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
	 * Constants reflecting the initialization levels
	 */
	const INITIALIZATION_LEVEL_CONSTRUCT = 1;
	const INITIALIZATION_LEVEL_CLASSLOADER = 2;
	const INITIALIZATION_LEVEL_CONFIGURATION = 3;
	const INITIALIZATION_LEVEL_FLOW3 = 4;
	const INITIALIZATION_LEVEL_PACKAGES = 5;
	const INITIALIZATION_LEVEL_COMPONENTS = 6;
	const INITIALIZATION_LEVEL_RESOURCES = 7;
	const INITIALIZATION_LEVEL_READY = 10;

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
	 * An instance of the component manager
	 * @var F3::FLOW3::Component::ManagerInterface
	 */
	protected $componentManager;

	/**
	 * A reference to the component factory
	 *
	 * @var F3::FLOW3::Component::FactoryInterface
	 */
	protected $componentFactory;

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
	 * Flag which states up to which level FLOW3 has been initialized
	 * @var integer
	 */
	protected $initializationLevel;

	/**
	 * Array of class names which must not be registered as components automatically. Class names may also be regular expressions.
	 * @var array
	 */
	protected $componentRegistrationClassBlacklist = array(
		'F3::FLOW3::AOP::.*',
		'F3::FLOW3::Component.*',
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
	 * Some interfaces (component types) which need to be defined before the reflection
	 * service is initialized.
	 * @var array
	 */
	protected $predefinedInterfaceImplementations = array(
		'F3::FLOW3::Security::ContextHolderInterface' => array('F3::FLOW3::Security::ContextHolderSession'),
		'F3::FLOW3::MVC::Web::Routing::RouterInterface' => array('F3::FLOW3::MVC::Web::Routing::Router')
	);

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
		$this->initializationLevel = self::INITIALIZATION_LEVEL_CONSTRUCT;
	}

	/**
	 * Explicitly initializes all necessary FLOW3 components by invoking the various initialize* methods.
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
		if ($this->initializationLevel > self::INITIALIZATION_LEVEL_CONSTRUCT) throw new F3::FLOW3::Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1169546671);

		$this->initializeClassLoader();
		$this->initializeConfiguration();
		$this->initializeFLOW3();
		$this->initializePackages();
		$this->initializeComponents();
		$this->initializeAOP();
		$this->initializeLocale();
		$this->initializeSession();
		$this->initializePersistence();
		$this->initializeResources();
	}

	/**
	 * Initializes the class loader
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @throws F3::FLOW3::Exception if the class loader has already been initialized.
	 * @see initialize()
	 */
	public function initializeClassLoader() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_CLASSLOADER) throw new F3::FLOW3::Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1210150008);

		if (!class_exists('F3::FLOW3::Resource::ClassLoader')) {
			require(__DIR__ . '/Resource/F3_FLOW3_Resource_ClassLoader.php');
		}
		$this->classLoader = new F3::FLOW3::Resource::ClassLoader(FLOW3_PATH_PACKAGES);
		spl_autoload_register(array($this->classLoader, 'loadClass'));

		$this->initializationLevel = self::INITIALIZATION_LEVEL_CLASSLOADER;
	}

	/**
	 * Initializes the configuration manager and the FLOW3 settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Exception if the configuration has already been initialized.
	 * @see initialize()
	 */
	public function initializeConfiguration() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_CONFIGURATION) throw new F3::FLOW3::Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1214489744);

		$configurationSource = new F3::FLOW3::Configuration::Source::PHP();
		$this->configurationManager = new F3::FLOW3::Configuration::Manager($this->context, $configurationSource);
		$this->configurationManager->loadFLOW3Settings();
		$this->settings = $this->configurationManager->getSettings('FLOW3');

		$this->initializationLevel = self::INITIALIZATION_LEVEL_CONFIGURATION;
	}

	/**
	 * Initializes the FLOW3 core.
	 *
	 * Usually this method is only called from unit tests or other applications which need a more fine grained control over
	 * the initialization and request handling process. Most other applications just call the run() method.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Exception if the framework has already been initialized.
	 * @see initialize()
	 */
	public function initializeFLOW3() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_FLOW3) throw new F3::FLOW3::Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1205759075);

		$errorHandler = new F3::FLOW3::Error::ErrorHandler();
		$errorHandler->setExceptionalErrors($this->settings->errorHandler->exceptionalErrors);
		new $this->settings->exceptionHandler->className;

		$environment = new F3::FLOW3::Utility::Environment($this->settings->utility->environment);

		$this->reflectionService = new F3::FLOW3::Reflection::Service();
		foreach ($this->predefinedInterfaceImplementations as $interfaceName => $classNames) {
			$this->reflectionService->setInterfaceImplementations($interfaceName, $classNames);
		}

		$this->componentManager = new F3::FLOW3::Component::Manager($this->reflectionService);
		$this->componentManager->setContext($this->context);
		$this->componentFactory = $this->componentManager->getComponentFactory();

		$this->componentManager->registerComponent('F3::FLOW3::Configuration::Manager', NULL, $this->configurationManager);
		$this->componentManager->registerComponent('F3::FLOW3::Utility::Environment', NULL, $environment);
		$this->componentManager->registerComponent('F3::FLOW3::AOP::Framework');
		$this->componentManager->registerComponent('F3::FLOW3::Package::ManagerInterface', 'F3::FLOW3::Package::Manager');
		$this->componentManager->registerComponent('F3::FLOW3::Cache::Factory');
		$this->componentManager->registerComponent('F3::FLOW3::Cache::Manager');
		$this->componentManager->registerComponent('F3::FLOW3::Cache::Backend::File');
		$this->componentManager->registerComponent('F3::FLOW3::Cache::Backend::Memcached');
		$this->componentManager->registerComponent('F3::FLOW3::Cache::VariableCache');
		$this->componentManager->registerComponent('F3::FLOW3::Reflection::Service', NULL, $this->reflectionService);
		$this->componentManager->registerComponent('F3::FLOW3::Resource::Manager', 'F3::FLOW3::Resource::Manager', new F3::FLOW3::Resource::Manager($this->classLoader, $this->componentFactory));

		$this->componentManager->registerComponent('F3::FLOW3::Session::SessionInterface', $this->settings->session->backend->className);

		$this->cacheFactory = $this->componentFactory->getComponent('F3::FLOW3::Cache::Factory');

		if ($this->settings->reflection->cache->enable === TRUE) {
			$this->reflectionCache = $this->cacheFactory->create('FLOW3_Reflection', 'F3::FLOW3::Cache::VariableCache', $this->settings->reflection->cache->backend, $this->settings->reflection->cache->backendOptions);
			if ($this->reflectionCache->has('reflectionServiceData')) {
				$this->reflectionService->import($this->reflectionCache->load('reflectionServiceData'));
			}
		}

		$this->initializationLevel = self::INITIALIZATION_LEVEL_FLOW3;
	}

	/**
	 * Initializes the package system and loads the package configuration and settings
	 * provided by the packages.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Exception if the package system has already been initialized.
	 * @see initialize()
	 */
	public function initializePackages() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_PACKAGES) throw new F3::FLOW3::Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760768);

		$packageManager = $this->componentFactory->getComponent('F3::FLOW3::Package::ManagerInterface');

		$packageManager->initialize();
		$activePackages = $packageManager->getActivePackages();
		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $this->configurationManager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_PACKAGES, $packageKey);
			$this->evaluatePackageConfiguration($package, $packageConfiguration);
		}

		$this->configurationManager->loadGlobalSettings(array_keys($activePackages));
		$this->configurationManager->loadRoutesSettings(array_keys($activePackages));

		$this->initializationLevel = self::INITIALIZATION_LEVEL_PACKAGES;
	}

	/**
	 * Initializes the component framework and loads the component configuration
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Exception if the component system has already been initialized.
	 * @see initialize()
	 */
	public function initializeComponents() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_COMPONENTS) throw new F3::FLOW3::Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760769);

		$componentConfigurations = NULL;

		if ($this->settings->component->configurationCache->enable === TRUE) {
			$componentConfigurationsCache = $this->cacheFactory->create('FLOW3_Component_Configurations', 'F3::FLOW3::Cache::VariableCache', $this->settings->component->configurationCache->backend, $this->settings->component->configurationCache->backendOptions);
			if ($componentConfigurationsCache->has('baseComponentConfigurations')) {
				$componentConfigurations = $componentConfigurationsCache->load('baseComponentConfigurations');
			}
		}

		if ($componentConfigurations === NULL) {
			$packageManager = $this->componentFactory->getComponent('F3::FLOW3::Package::ManagerInterface');
			$this->registerAndConfigureAllPackageComponents($packageManager->getActivePackages());
			$componentConfigurations = $this->componentManager->getComponentConfigurations();
			if ($this->settings->component->configurationCache->enable) {
				$componentConfigurationsCache->save('baseComponentConfigurations', $componentConfigurations);
			}
		}

		$this->componentManager->setComponentConfigurations($componentConfigurations);

		$this->initializationLevel = self::INITIALIZATION_LEVEL_COMPONENTS;
	}

	/**
	 * Initializes the AOP framework
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeAOP() {
		if ($this->settings->aop->enable === TRUE) {

			$componentConfigurations = $this->componentManager->getComponentConfigurations();

			$AOPFramework = $this->componentFactory->getComponent('F3::FLOW3::AOP::Framework');
			$AOPFramework->initialize($componentConfigurations);

			$this->componentManager->setComponentConfigurations($componentConfigurations);
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
		$this->componentFactory->getComponent('F3::FLOW3::Locale::Service', $this->settings)->initialize();
	}

	/**
	 * Initializes the session
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSession() {
		$session = $this->componentFactory->getComponent('F3::FLOW3::Session::SessionInterface');
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
		if ($this->settings->persistence->enable === TRUE) {
			$persistenceManager = $this->componentFactory->getComponent('F3::FLOW3::Persistence::Manager');
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
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_RESOURCES) throw new F3::FLOW3::Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1210080996);
		$this->initializationLevel = self::INITIALIZATION_LEVEL_RESOURCES;

		$packageManager = $this->componentFactory->getComponent('F3::FLOW3::Package::ManagerInterface');

		$metadataCache = $this->cacheFactory->create('FLOW3_Resource_MetaData', 'F3::FLOW3::Cache::VariableCache', 'F3::FLOW3::Cache::Backend::File');
		$statusCache = $this->cacheFactory->create('FLOW3_Resource_Status', 'F3::FLOW3::Cache::StringCache', 'F3::FLOW3::Cache::Backend::File');
		$environment = $this->componentFactory->getComponent('F3::FLOW3::Utility::Environment');
		$requestType = ($environment->getSAPIName() == 'cli') ? 'CLI' : 'Web';

		$resourcePublisher = $this->componentFactory->getComponent('F3::FLOW3::Resource::Publisher');
		$resourcePublisher->initializeMirrorDirectory($this->settings->resource->cache->publicPath . $requestType . '/');
		$resourcePublisher->setMetadataCache($metadataCache);
		$resourcePublisher->setStatusCache($statusCache);
		$resourcePublisher->setCacheStrategy($this->settings->resource->cache->strategy);

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
		if ($this->initializationLevel == self::INITIALIZATION_LEVEL_CONSTRUCT) $this->initialize();
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_RESOURCES) throw new F3::FLOW3::Exception('Cannot run FLOW3 because it is not fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759259);

		$requestHandlerResolver = $this->componentFactory->getComponent('F3::FLOW3::MVC::RequestHandlerResolver', $this->settings);
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();

		if ($this->settings->persistence->enable === TRUE) {
			$this->componentFactory->getComponent('F3::FLOW3::Persistence::Manager')->persistAll();
		}

		$session = $this->componentFactory->getComponent('F3::FLOW3::Session::SessionInterface');
		$session->close();
	}

	/**
	 * Returns an instance of the active component manager. This method is and should only
	 * be used by unit tests and special cases. In almost any other case, a reference to the
	 * component manager can be injected.
	 *
 	 * @return F3::FLOW3::Component::ManagerInterface
 	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManager() {
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_FLOW3) throw new F3::FLOW3::Exception('FLOW3 has not yet been fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759260);
		return $this->componentManager;
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
	 * components at the component manager. Finally the component configuration
	 * defined by the package is loaded and applied to the registered components.
	 *
	 * @param array $packages The packages whose classes should be registered
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerAndConfigureAllPackageComponents(array $packages) {
		$componentTypes = array();

		foreach ($packages as $packageKey => $package) {
			foreach (array_keys($package->getClassFiles()) as $className) {
				if (!$this->classNameIsBlacklisted($className)) {
					$availableClassNames[] = $className;
				}
			}
		}

		if (!$this->reflectionService->isInitialized()) {
			$this->reflectionService->initialize($availableClassNames);
			if ($this->settings->reflection->cache->enable === TRUE) {
				$this->reflectionCache->save('reflectionServiceData', $this->reflectionService->export());
			}
		}

		foreach ($availableClassNames as $className) {
			if (substr($className, -9, 9) == 'Interface') {
				$componentTypes[] = $className;
				if (!$this->componentManager->isComponentRegistered($className)) {
					$this->componentManager->registerComponentType($className);
				}
			}
		}

		foreach ($availableClassNames as $className) {
			if (substr($className, -9, 9) != 'Interface') {
				$componentName = $className;
				if (!$this->componentManager->isComponentRegistered($componentName)) {
					if (!$this->reflectionService->isClassAbstract($className)) {
						$this->componentManager->registerComponent($componentName, $className);
					}
				}
			}
		}

		$componentConfigurations = $this->componentManager->getComponentConfigurations();
		foreach ($packages as $packageKey => $package) {
			$rawComponentConfigurations = $this->configurationManager->getSpecialConfiguration(F3::FLOW3::Configuration::Manager::CONFIGURATION_TYPE_COMPONENTS, $packageKey);
			foreach ($rawComponentConfigurations as $componentName => $rawComponentConfiguration) {
				$componentName = str_replace('_', '::', $componentName);
				if (!$this->componentManager->isComponentRegistered($componentName)) {
					throw new F3::FLOW3::Component::Exception::InvalidComponentConfiguration('Tried to configure unknown component "' . $componentName . '" in package "' . $package->getPackageKey() . '".', 1184926175);
				}
				$existingComponentConfiguration = (isset($componentConfigurations[$componentName])) ? $componentConfigurations[$componentName] : NULL;
				$componentConfigurations[$componentName] = F3::FLOW3::Component::ConfigurationBuilder::buildFromConfigurationContainer($componentName, $rawComponentConfiguration, 'Package ' . $packageKey, $existingComponentConfiguration);
			}
		}

		foreach ($componentTypes as $componentType) {
			$defaultImplementationClassName = $this->reflectionService->getDefaultImplementationClassNameForInterface($componentType);
			if ($defaultImplementationClassName !== FALSE) {
				$componentConfigurations[$componentType]->setClassName($defaultImplementationClassName);
			}
		}
		$this->componentManager->setComponentConfigurations($componentConfigurations);
	}

	/**
	 * Checks if the given class name appears on in the component blacklist.
	 *
	 * @param string $className The class name to check. May be a regular expression.
	 * @return boolean TRUE if the class has been blacklisted, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function classNameIsBlacklisted($className) {
		foreach ($this->componentRegistrationClassBlacklist as $blacklistedClassName) {
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
	 * @param F3::FLOW3::Configuration::Container $packageConfiguration The configuration to evaluate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo needs refactoring and be moved to elsewhere (resource manager, package manager etc.)
	 */
	protected function evaluatePackageConfiguration(F3::FLOW3::Package::Package $package, F3::FLOW3::Configuration::Container $packageConfiguration) {
		if (isset($packageConfiguration->resourceManager)) {
			if (isset($packageConfiguration->resourceManager->specialClassNameAndPaths)) {
				$resourceManager = $this->componentFactory->getComponent('F3::FLOW3::Resource::Manager');
				foreach ($packageConfiguration->resourceManager->specialClassNameAndPaths as $className => $classFilePathAndName) {
					$classFilePathAndName = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $classFilePathAndName);
					$classFilePathAndName = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $classFilePathAndName);
					$resourceManager->registerClassFile($className, $classFilePathAndName);
				}
			}

			if (isset($packageConfiguration->resourceManager->includePaths)) {
				foreach ($packageConfiguration->resourceManager->includePaths as $includePath) {
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