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
final class T3_FLOW3 {

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
	 * @var T3_FLOW3_Component_ManagerInterface An instance of the component manager
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
		'T3_FLOW3_AOP_.*',
		'T3_FLOW3_Component.*',
		'T3_FLOW3_Package.*',
		'T3_FLOW3_Reflection.*',
	);

	/**
	 * @var T3_FLOW3_Configuration_Container The FLOW3 base configuration  (for this class)
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
	 * @throws T3_FLOW3_Exception if the framework has already been initialized.
	 */
	public function initialize() {
		if ($this->initializationLevel > self::INITIALIZATION_LEVEL_CONSTRUCT) throw new T3_FLOW3_Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1169546671);

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
	 * @throws T3_FLOW3_Exception if the framework has already been initialized.
	 * @see initialize()
	 */
	public function initializeFLOW3() {
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_FLOW3) throw new T3_FLOW3_Exception('FLOW3 has already been initialized (up to level ' . $this->initializationLevel . ').', 1205759075);

		require_once(FLOW3_PATH_FLOW3 . 'Configuration/T3_FLOW3_Configuration_Manager.php');
		$configurationManager = new T3_FLOW3_Configuration_Manager();
		$this->configuration = $configurationManager->getConfiguration('FLOW3', T3_FLOW3_Configuration_Manager::CONFIGURATION_TYPE_FLOW3, $this->context);

		require_once(FLOW3_PATH_FLOW3 . 'Error/T3_FLOW3_Error_ErrorHandler.php');
		require_once(FLOW3_PATH_FLOW3 . 'Error/T3_FLOW3_Error_ExceptionHandler.php');
		require_once(FLOW3_PATH_FLOW3 . 'Resource/T3_FLOW3_Resource_Manager.php');
		require_once(FLOW3_PATH_FLOW3 . 'Cache/T3_FLOW3_Cache_Manager.php');

		new T3_FLOW3_Error_ErrorHandler();
		new T3_FLOW3_Error_ExceptionHandler();
		$resourceManager = new T3_FLOW3_Resource_Manager();

		$this->componentManager = new T3_FLOW3_Component_Manager();
		$this->componentManager->setContext($this->context);
		$this->componentManager->registerComponent('T3_FLOW3_Configuration_Manager', 'T3_FLOW3_Configuration_Manager', $configurationManager);
		$this->componentManager->registerComponent('T3_FLOW3_Utility_Environment');
		$this->componentManager->registerComponent('T3_FLOW3_Resource_Manager', 'T3_FLOW3_Resource_Manager', $resourceManager);
		$this->componentManager->registerComponent('T3_FLOW3_AOP_Framework', 'T3_FLOW3_AOP_Framework');
		$this->componentManager->registerComponent('T3_FLOW3_Package_ManagerInterface', 'T3_FLOW3_Package_Manager');
		$this->componentManager->registerComponent('T3_FLOW3_Cache_Backend_File');

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
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_PACKAGES) throw new T3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760768);
		$this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface')->initialize();
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
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_COMPONENTS) throw new T3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760769);

		$cacheBackend = $this->componentManager->getComponent('T3_FLOW3_Cache_Backend_File', $this->context);
		$packageManager = $this->componentManager->getComponent('T3_FLOW3_Package_ManagerInterface');

		$componentConfigurationsCache = new T3_FLOW3_Cache_VariableCache('FLOW3_Component_Configurations', $cacheBackend);
		if ($componentConfigurationsCache->has('componentConfigurations') && $this->context == 'Production') {
			$componentConfigurations = $componentConfigurationsCache->load('componentConfigurations');
			$this->componentManager->setComponentConfigurations($componentConfigurations);
		} else {
			$this->registerAndConfigureAllPackageComponents($packageManager->getPackages());
			$this->componentManager->getComponent('T3_FLOW3_AOP_Framework')->initialize();
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
		if ($this->initializationLevel >= self::INITIALIZATION_LEVEL_SETTINGS) throw new T3_FLOW3_Exception('FLOW3 has already been initialized up to level ' . $this->initializationLevel . '.', 1205760770);
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
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_SETTINGS) throw new T3_FLOW3_Exception('Cannot run FLOW3 because it is not fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759259);

		$requestHandlerResolver = $this->componentManager->getComponent('T3_FLOW3_MVC_RequestHandlerResolver');
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();
		$requestHandler->handleRequest();
	}

	/**
	 * Returns an instance of the active component manager. This method is and should only
	 * be used by unit tests and special cases. In almost any other case, a reference to the
	 * component manager can be injected.
	 *
 	 * @return	T3_FLOW3_Component_ManagerInterface
 	 * @author	Robert Lemke <robert@typo3.org>
	 */
	public function getComponentManager() {
		if ($this->initializationLevel < self::INITIALIZATION_LEVEL_FLOW3) throw new T3_FLOW3_Exception('FLOW3 has not yet been fully initialized (current initialization level: ' . $this->initializationLevel . ').', 1205759260);
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

		$masterComponentConfigurations = $this->componentManager->getComponentConfigurations();
		foreach ($packages as $packageKey => $package) {
			foreach ($package->getClassFiles() as $className => $relativePathAndFilename) {
				if (!$this->classNameIsBlacklisted($className)) {
					if (substr($className, -9, 9) != 'Interface') {
						$componentName = $className;
						if (!$this->componentManager->isComponentRegistered($componentName)) {
							$class = new T3_FLOW3_Reflection_Class($className);
							if (!$class->isAbstract()) {
								$this->componentManager->registerComponent($componentName, $className);
							}
						}
					}
				}
			}
			foreach ($package->getComponentConfigurations() as $componentName => $componentConfiguration) {
				if (!$this->componentManager->isComponentRegistered($componentName)) {
					throw new T3_FLOW3_Package_Exception_InvalidComponentConfiguration('Tried to configure unknown component "' . $componentName . '" in package "' . $package->getPackageKey() . '". The configuration came from ' . $componentConfiguration->getConfigurationSourceHint() .'.', 1184926175);
				}
				$masterComponentConfigurations[$componentName] = $componentConfiguration;
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

}

?>