<?php
declare(ENCODING="utf-8");

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

define('FLOW3_PATH_FLOW3', str_replace('\\', '/', dirname(__FILE__)) . '/' );
define('FLOW3_PATH_PACKAGES', realpath(FLOW3_PATH_FLOW3 . '../../') . '/');

/**
 * @package FLOW3
 * @version $Id: $
 */

/**
 * General purpose central core hyper FLOW3 class
 *
 * @package FLOW3
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
final class F3_FLOW3 {

	/**
	 * The version of the FLOW3 framework
	 */
	const VERSION = '0.2.0svn';

	const MINIMUM_PHP_VERSION = '5.2.0';
	const MAXIMUM_PHP_VERSION = '5.9.9';

	/**
	 * Constants reflected the initialization levels
	 */
	const INITIALIZATION_LEVEL_CONSTRUCT = 1;
	const INITIALIZATION_LEVEL_FLOW3 = 2;
	const INITIALIZATION_LEVEL_PACKAGES = 3;
	const INITIALIZATION_LEVEL_COMPONENTS = 4;
	const INITIALIZATION_LEVEL_SETTINGS = 5;
	const INITIALIZATION_LEVEL_READY = 10;

	/**
	 * @var string The application context
	 */
	protected $context;

	/**
	 * @var F3_FLOW3_Component_ManagerInterface An instance of the component manager
	 */
	protected $componentManager;

	/**
	 * @var integer Flag which states up to which level FLOW3 has been initialized
	 */
	protected $initializationLevel;

	/**
	 * @var array Array of class names which must not be registered as components automatically. Class names may also be regular expressions.
	 */
	protected $componentRegistrationClassBlacklist = array(
		'F3_FLOW3_AOP_.*',
		'F3_FLOW3_Component.*',
		'F3_FLOW3_Package.*',
		'F3_FLOW3_Reflection.*',
	);

	/**
	 * @var F3_FLOW3_Configuration_Container The FLOW3 base configuration  (for this class)
	 */
	protected $configuration;

	/**
	 * Constructor
	 *
	 * @param string $context The application context
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($context = 'Development') {
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
	 * @throws F3_FLOW3_Exception if the framework has already been initialized.
	 */
	public function initialize() {
		if ($this->initializationLevel > self::INITIALIZATION_LEVEL_CONSTRUCT) throw new F3_FLOW3_Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1169546671);

		$this->initializeFLOW3();
		$this->initializePackages();
		$this->initializeComponents();
		$this->initializeSettings();
	}

	/**
	 * Initializes the FLOW3 core.
	 *
	 * Usually this method is only called from unit tests or other applications which need a more fine grained control over
	 * the initialization and request handling process. Most other applications just call the run() method.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3_FLOW3_Exception if the framework has already been initialized.
	 * @see initialize()
	 */
	public function initializeFLOW3() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_FLOW3) throw new F3_FLOW3_Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1205759075);

		require_once(FLOW3_PATH_FLOW3 . 'Configuration/F3_FLOW3_Configuration_Manager.php');
		$configurationManager = new F3_FLOW3_Configuration_Manager($this->context);
		$this->configuration = $configurationManager->getConfiguration('FLOW3', F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3, $this->context);

		require_once(FLOW3_PATH_FLOW3 . 'Error/F3_FLOW3_Error_ErrorHandler.php');
		require_once(FLOW3_PATH_FLOW3 . 'Error/F3_FLOW3_Error_ExceptionHandler.php');
		require_once(FLOW3_PATH_FLOW3 . 'Resource/F3_FLOW3_Resource_Manager.php');
		require_once(FLOW3_PATH_FLOW3 . 'Cache/F3_FLOW3_Cache_Manager.php');

		new F3_FLOW3_Error_ErrorHandler();
		new F3_FLOW3_Error_ExceptionHandler();
		$resourceManager = new F3_FLOW3_Resource_Manager();

		$this->componentManager = new F3_FLOW3_Component_Manager();
		$this->componentManager->setContext($this->context);
		$this->componentManager->registerComponent('F3_FLOW3_Configuration_Manager', 'F3_FLOW3_Configuration_Manager', $configurationManager);
		$this->componentManager->registerComponent('F3_FLOW3_Utility_Environment');
		$this->componentManager->registerComponent('F3_FLOW3_Resource_Manager', 'F3_FLOW3_Resource_Manager', $resourceManager);
		$this->componentManager->registerComponent('F3_FLOW3_AOP_Framework', 'F3_FLOW3_AOP_Framework');
		$this->componentManager->registerComponent('F3_FLOW3_Package_ManagerInterface', 'F3_FLOW3_Package_Manager');
		$this->componentManager->registerComponent('F3_FLOW3_Cache_Backend_File');

		$this->initializationLevel = self::INITIALIZATION_LEVEL_FLOW3;
	}

	/**
	 * Initializes the package system and loads the package configuration.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializePackages() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_PACKAGES) throw new F3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760768);
		$packageManager = $this->componentManager->getComponent('F3_FLOW3_Package_ManagerInterface');
		$configurationManager = $this->componentManager->getComponent('F3_FLOW3_Configuration_Manager');

		$packageManager->initialize();
		$activePackages = $packageManager->getActivePackages();
		foreach ($activePackages as $packageKey => $package) {
			$packageConfiguration = $configurationManager->getConfiguration($packageKey, F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_PACKAGES, $this->context);
			$this->evaluatePackageConfiguration($package, $packageConfiguration);
		}

		$this->initializationLevel = self::INITIALIZATION_LEVEL_PACKAGES;
	}

	/**
	 * Initializes the component framework and loads the component configuration
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeComponents() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_COMPONENTS) throw new F3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760769);

		$cacheBackend = $this->componentManager->getComponent('F3_FLOW3_Cache_Backend_File', $this->context);
		$packageManager = $this->componentManager->getComponent('F3_FLOW3_Package_ManagerInterface');

		$componentConfigurationsCache = new F3_FLOW3_Cache_VariableCache('FLOW3_Component_Configurations', $cacheBackend);
		if ($componentConfigurationsCache->has('componentConfigurations') && $this->context == 'Production') {
			$componentConfigurations = $componentConfigurationsCache->load('componentConfigurations');
			$this->componentManager->setComponentConfigurations($componentConfigurations);
		} else {
			$this->registerAndConfigureAllPackageComponents($packageManager->getActivePackages());
			$this->componentManager->getComponent('F3_FLOW3_AOP_Framework')->initialize();
			$componentConfigurations = $this->componentManager->getComponentConfigurations();
			$componentConfigurationsCache->save('componentConfigurations', $componentConfigurations);
		}

		$this->initializationLevel = self::INITIALIZATION_LEVEL_COMPONENTS;
	}

	/**
	 * Loads and initializes the settings
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeSettings() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_SETTINGS) throw new F3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760770);
		$this->initializationLevel = self::INITIALIZATION_LEVEL_SETTINGS;
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
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_SETTINGS) throw new F3_FLOW3_Exception('Cannot run FLOW3 because it is not fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759259);

		$requestHandlerResolver = $this->componentManager->getComponent('F3_FLOW3_MVC_RequestHandlerResolver');
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();
	}

	/**
	 * Returns an instance of the active component manager. This method is and should only
	 * be used by unit tests and special cases. In almost any other case, a reference to the
	 * component manager can be injected.
	 *
 	 * @return	F3_FLOW3_Component_ManagerInterface
 	 * @author	Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManager() {
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_FLOW3) throw new F3_FLOW3_Exception('FLOW3 has not yet been fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759260);
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
		if (extension_loaded('eAccelerator')) {
			eaccelerator_caching(FALSE);
			eaccelerator_clear();
		}
		if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION, '<')) {
			die ('FLOW3 requires PHP version ' . self::MINIMUM_PHP_VERSION . ' or higher but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		if (version_compare(phpversion(), self::MAXIMUM_PHP_VERSION, '>')) {
			die ('FLOW3 requires PHP version ' . self::MAXIMUM_PHP_VERSION . ' or lower but your installed version is currently ' . phpversion() . '. (Error #1172215790)');
		}
		set_time_limit(0);
		ini_set('unicode.output_encoding', 'utf-8');
		ini_set('unicode.stream_encoding', 'utf-8');
		ini_set('unicode.runtime_encoding', 'utf-8');
#		locale_set_default('en_UK');
		if (ini_get('date.timezone') == '') {
			date_default_timezone_set('Europe/Copenhagen');
		}
	}

	/**
	 * Traverses through all active packages and registers their classes as
	 * components at the component manager. Finally the component configuration
	 * defined by the package is loaded and applied to the registered components.
	 *
	 * @param array $packages: The packages whose classes should be registered
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerAndConfigureAllPackageComponents(array $packages) {
		$componentTypes = array();

		foreach ($packages as $packageKey => $package) {
			foreach ($package->getClassFiles() as $className => $relativePathAndFilename) {
				if (!$this->classNameIsBlacklisted($className)) {
					if (substr($className, -9, 9) == 'Interface') {
						$componentTypes[] = $className;
						if (!$this->componentManager->isComponentRegistered($className)) {
							$this->componentManager->registerComponentType($className);
						}
					}
				}
			}
		}

		foreach ($packages as $packageKey => $package) {
			foreach ($package->getClassFiles() as $className => $relativePathAndFilename) {
				if (!$this->classNameIsBlacklisted($className)) {
					if (substr($className, -9, 9) != 'Interface') {
						$componentName = $className;
						if (!$this->componentManager->isComponentRegistered($componentName)) {
							$class = new F3_FLOW3_Reflection_Class($className);
							if (!$class->isAbstract()) {
								$this->componentManager->registerComponent($componentName, $className);
							}
						}
					}
				}
			}
		}

		$masterComponentConfigurations = $this->componentManager->getComponentConfigurations();
		$configurationManager = $this->componentManager->getComponent('F3_FLOW3_Configuration_Manager');
		foreach ($packages as $packageKey => $package) {
			$rawComponentConfigurations = $configurationManager->getConfiguration($packageKey, F3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_COMPONENTS);
			foreach ($rawComponentConfigurations as $componentName => $rawComponentConfiguration) {
				if (!$this->componentManager->isComponentRegistered($componentName)) {
					throw new F3_FLOW3_Package_Exception_InvalidComponentConfiguration('Tried to configure unknown component "' . $componentName . '" in package "' . $package->getPackageKey() . '". The configuration came from ' . $componentConfiguration->getConfigurationSourceHint() .'.', 1184926175);
				}
				$masterComponentConfigurations[$componentName] = F3_FLOW3_Component_ConfigurationBuilder::buildFromConfigurationContainer($componentName, $rawComponentConfiguration);
			}
		}

		foreach ($componentTypes as $componentType) {
			$defaultImplementationClassName = $this->componentManager->getDefaultImplementationClassNameForInterface($componentType);
			if ($defaultImplementationClassName !== FALSE) {
				$masterComponentConfigurations[$componentType]->setClassName($defaultImplementationClassName);
			}
		}

		$this->componentManager->setComponentConfigurations($masterComponentConfigurations);
	}

	/**
	 * Checks if the given class name appears on in the component blacklist.
	 *
	 * @param string $className: The class name to check. May be a regular expression.
	 * @return boolean TRUE if the class has been blacklisted, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function classNameIsBlacklisted($className) {
		$isBlacklisted = FALSE;
		foreach ($this->componentRegistrationClassBlacklist as $blacklistedClassName) {
			if ($className == $blacklistedClassName || preg_match('/^' . $blacklistedClassName . '$/', $className)) {
				$isBlacklisted = TRUE;
			}
		}
		return $isBlacklisted;
	}

	/**
	 * (For now) evaluates the package configuration
	 *
	 * @param F3_FLOW3_Package_Package $package The package
	 * @param F3_FLOW3_Configuration_Container $packageConfiguration The configuration to evaluate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @todo needs refactoring and be moved to elsewhere (resource manager, package manager etc.)
	 */
	protected function evaluatePackageConfiguration(F3_FLOW3_Package_Package $package, F3_FLOW3_Configuration_Container $packageConfiguration) {
		if (isset($packageConfiguration->resourceManager->specialClassNameAndPaths)) {
			$resourceManager = $this->componentManager->getComponent('F3_FLOW3_Resource_Manager');
			foreach ($packageConfiguration->resourceManager->specialClassNameAndPaths as $className => $classFilePathAndName) {
				$classFilePathAndName = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $classFilePathAndName);
				$classFilePathAndName = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $classFilePathAndName);
				$classFilePathAndName = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $classFilePathAndName);
				$resourceManager->registerClassFile($className, $classFilePathAndName);
			}
		}

		if (isset($packageConfiguration->resourceManager->includePaths)) {
			foreach ($packageConfiguration->resourceManager->includePaths as $includePathName => $includePath) {
				$includePath = str_replace('%PATH_PACKAGE%', $package->getPackagePath(), $includePath);
				$includePath = str_replace('%PATH_PACKAGE_CLASSES%', $package->getClassesPath(), $includePath);
				$includePath = str_replace('%PATH_PACKAGE_RESOURCES%', $package->getResourcesPath(), $includePath);
				$includePath = str_replace('/', DIRECTORY_SEPARATOR, $includePath);
				set_include_path(get_include_path() . PATH_SEPARATOR . $includePath);
			}
		}
	}
}

?>