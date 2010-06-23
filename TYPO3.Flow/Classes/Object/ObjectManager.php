<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * Implementation of the default FLOW3 Object Manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class ObjectManager implements \F3\FLOW3\Object\ObjectManagerInterface {

	/**
	 * Name of the current context
	 * @var string
	 */
	protected $context = 'Development';

	/**
	 * @var \F3\FLOW3\Resource\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var \F3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var string
	 */
	protected $staticObjectContainerPathAndFilename;

	/**
	 * @var string
	 */
	protected $staticObjectContainerClassName = 'F3\FLOW3\Object\Container\StaticObjectContainer';

	/**
	 * @var \F3\FLOW3\Object\ObjectContainerInterface
	 */
	protected $objectContainer;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\PhpFrontend
	 */
	protected $objectContainerClassesCache;

	/**
	 * @var boolean
	 */
	protected $sessionInitialized = FALSE;

	/**
	 * Injects the class loader
	 * 
	 * @param \F3\FLOW3\Resource\ClassLoader $classLoader
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectClassLoader(\F3\FLOW3\Resource\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Pre-initializes the Object Manager: In case a static object container already exists,
	 * it is loaded and intialized. If that is not the case, the dynamic object container
	 * is instantiated and used.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'FLOW3');

		$environment = new \F3\FLOW3\Utility\Environment();
		$environment->setContext($this->context);
		$environment->setTemporaryDirectoryBase($settings['utility']['environment']['temporaryDirectoryBase']);
		$environment->initializeObject();

		$this->staticObjectContainerPathAndFilename = $environment->getPathToTemporaryDirectory() . 'StaticObjectContainer.php';

		if ($settings['monitor']['detectClassChanges'] === FALSE && file_exists($this->staticObjectContainerPathAndFilename)) {
			require_once($this->staticObjectContainerPathAndFilename);
			if (class_exists($this->staticObjectContainerClassName, FALSE)) {
				$this->objectContainer = new $this->staticObjectContainerClassName;
			}
		}

		if ($this->objectContainer === NULL) {
			$rawFLOW3ObjectConfigurations = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, 'FLOW3');
			$parsedObjectConfigurations = array();
			foreach ($rawFLOW3ObjectConfigurations as $objectName => $rawFLOW3ObjectConfiguration) {
				$parsedObjectConfigurations[$objectName] = \F3\FLOW3\Object\Configuration\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawFLOW3ObjectConfiguration, 'FLOW3 Object Manager (pre-initialization)');
			}
			$this->objectContainer = new \F3\FLOW3\Object\Container\DynamicObjectContainer();
			$this->objectContainer->setObjectConfigurations($parsedObjectConfigurations);
		}

		$this->objectContainer->injectSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
		$this->objectContainer->setInstance('F3\FLOW3\Object\ObjectManagerInterface', $this);
		$this->objectContainer->setInstance('F3\FLOW3\Resource\ClassLoader', $this->classLoader);
		$this->objectContainer->setInstance('F3\FLOW3\Utility\Environment', $environment);

		$this->objectContainer->setInstance('F3\FLOW3\Configuration\ConfigurationManager', $this->configurationManager);
		$this->configurationManager->injectEnvironment($environment);
	}

	/**
	 * Initializes the object container for further use by other packages.
	 *
	 * If the static object container is already active, this method will only load the
	 * AOP proxy classes and inject the settings of all packages into the static object
	 * container.
	 *
	 * If the dynamic object container is still in charge, a static object container is
	 * built and all runtime information is transfered from the dynamic object container
	 * to the freshly built static object container.
	 * After the transfer this method will switch to the static object container.
	 *
	 * @param array $activePackages An array of active packages of which objects should be considered
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeObjectContainer(array $activePackages) {
		if (!class_exists($this->staticObjectContainerClassName, FALSE)) {
			$objectConfigurations = $this->buildPackageObjectConfigurations($activePackages);

			$this->get('F3\FLOW3\AOP\Framework')->initialize($objectConfigurations);

			$objectContainerBuilder = $this->get('F3\FLOW3\Object\Container\ObjectContainerBuilder');
			file_put_contents($this->staticObjectContainerPathAndFilename, $objectContainerBuilder->buildObjectContainer($objectConfigurations));
		} else {
			$this->get('F3\FLOW3\AOP\Framework')->loadProxyClasses();
		}

		if (!$this->objectContainer instanceof $this->staticObjectContainerClassName) {
			require($this->staticObjectContainerPathAndFilename);
			$newObjectContainer = new $this->staticObjectContainerClassName;
			$newObjectContainer->import($this->objectContainer);
			$this->objectContainer = $newObjectContainer;
		}


		$this->objectContainer->injectSettings($this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS));
	}

	/**
	 * Initializes the session scope of the object container
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeSession() {
		$this->objectContainer->initializeSession();
		$this->sessionInitialized = TRUE;
	}

	/**
	 * Returns TRUE if the session has been initialized
	 *
	 * @return boolean TRUE if the session has been initialized
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isSessionInitialized() {
		return $this->sessionInitialized;
	}

	/**
	 * Sets the Object ObjectManager to a specific context. All operations related to objects
	 * will be carried out based on the configuration for the current context.
	 *
	 * The context should be set as early as possible, preferably before any object has been
	 * instantiated.
	 *
	 * By default the context is set to "default". Although the context can be freely chosen,
	 * the following contexts are explicitly supported by FLOW3:
	 * "Production", "Development", "Testing", "Profiling", "Staging"
	 *
	 * @param  string $context Name of the context
	 * @return void
	 * @throws \InvalidArgumentException if $context is not a valid string.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext($context) {
		if (!is_string($context)) throw new \InvalidArgumentException('Context must be given as string.', 1210857671);
		$this->context = $context;
	}

	/**
	 * Returns the name of the currently set context.
	 *
	 * @return  string Name of the current context
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getContext() {
		return $this->context;
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Manager's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * Note: Additional arguments to this function are only passed to the object
	 * container's get method for when the object is a prototype. Any argument
	 * besides $objectName is ignored if the target object is in singleton or session
	 * scope.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function get($objectName) {
		return call_user_func_array(array($this->objectContainer, 'get'), func_get_args());
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * This factory method can only create objects of the scope prototype.
	 * Singleton objects must be either injected by some type of Dependency Injection or
	 * if that is not possible, be retrieved by the get() method of the
	 * Object Manager
	 *
	 * You must use either Dependency Injection or this factory method for instantiation
	 * of your objects if you need FLOW3's object management capabilities (including
	 * AOP, Security and Persistence). It is absolutely okay and often advisable to
	 * use the "new" operator for instantiation in your automated tests.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws \InvalidArgumentException if the object name starts with a backslash
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScopeException if the specified object is not configured as Prototype
	 * @author Robert Lemke <robert@typo3.org>
	 * @since 1.0.0 alpha 8
	 * @api
	 */
	public function create($objectName) {
		return call_user_func_array(array($this->objectContainer, 'create'), func_get_args());
	}

	/**
	 * Creates an instance of the specified object without calling its constructor.
	 * Subsequently reinjects the object's dependencies.
	 *
	 * This method is mainly used by the persistence and the session sub package.
	 *
	 * Note: The object must be of scope prototype or session which means that
	 *       the object container won't store an instance of the recreated object.
	 *
	 * @param string $objectName Name of the object to create a skeleton for
	 * @return object The recreated, uninitialized (ie. w/ uncalled constructor) object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function recreate($objectName) {
		return $this->objectContainer->recreate($objectName);
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @since 1.0.0 alpha 8
	 * @api
	 */
	public function isRegistered($objectName) {
		return $this->objectContainer->isRegistered($objectName);
	}

	/**
	 * Returns the case sensitive object name of an object specified by a
	 * case insensitive object name. If no object of that name exists,
	 * FALSE is returned.
	 *
	 * In general, the case sensitive variant is used everywhere in FLOW3,
	 * however there might be special situations in which the
	 * case sensitive name is not available. This method helps you in these
	 * rare cases.
	 *
	 * @param  string $caseInsensitiveObjectName The object name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName) {
		return $this->objectContainer->getCaseSensitiveObjectName($caseInsensitiveObjectName);
	}

	/**
	 * Returns the object name corresponding to a given class name.
	 *
	 * @param string $className The class name
	 * @return string The object name corresponding to the given class name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @api
	 */
	public function getObjectNameByClassName($className) {
		return $this->objectContainer->getObjectNameByClassName($className);
	}

	/**
	 * Returns the implementation class name for the specified object
	 *
	 * @param string $objectName The object name
	 * @return string The class name corresponding to the given object name or FALSE if no such object is registered
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassNameByObjectName($objectName) {
		return $this->objectContainer->getClassNameByObjectName($objectName);

	}

	/**
	 * Returns the scope of the specified object.
	 * 
	 * @param string $objectName The object name
	 * @return integer One of the Configuration::SCOPE_ constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScope($objectName) {
		return $this->objectContainer->getScope($objectName);
	}

	/**
	 * Discards the cached Static Object Container in order to rebuild it on the
	 * next script run.
	 * 
	 * This method is called by a signal emitted when files change.
	 *
	 * @param string $signalName Name of the signal which triggered this method
	 * @param string $monitorIdentifier Identifier of the file monitor
	 * @param array $changedFiles Path and file name of the changed files
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flushStaticObjectContainer($signalName, $monitorIdentifier, array $changedFiles) {
		if ($monitorIdentifier === 'FLOW3_ClassFiles' && file_exists($this->staticObjectContainerPathAndFilename)) {
			unlink ($this->staticObjectContainerPathAndFilename);
		}
	}

	/**
	 * Shuts the object manager down and calls the shutdown methods of all objects
	 * which are configured for it.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdown() {
		$this->objectContainer->shutdown();
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * Important:
	 *
	 * If possible, instances of Prototype objects should always be created with the
	 * Object Factory's create() method and Singleton objects should rather be
	 * injected by some type of Dependency Injection.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @deprecated since 1.0.0 alpha 8
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObject($objectName) {
		return call_user_func_array(array($this->objectContainer, 'get'), func_get_args());
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @deprecated since 1.0.0 alpha 8
	 */
	public function isObjectRegistered($objectName) {
		return $this->isRegistered($objectName);
	}

	/**
	 * Traverses through all active packages and builds a base object configuration
	 * for all of their classes. Finally merges additional objects configurations
	 * into the overall configuration and returns the result.
	 *
	 * @param array $packages The packages whose classes should be registered
	 * @return array<F3\FLOW3\Object\Configuration\Configuration> Object configurations
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildPackageObjectConfigurations(array $packages) {
		$reflectionService = $this->get('F3\FLOW3\Reflection\ReflectionService');

		$objectConfigurations = array();
		$availableClassNames = array();

		foreach ($packages as $packageKey => $package) {
			foreach (array_keys($package->getClassFiles()) as $className) {
				if (substr($className, -9, 9) !== 'Exception' && substr($className, 0, 8) !== 'Abstract') {
					$availableClassNames[] = $className;
				}
			}
		}

		foreach ($availableClassNames as $className) {
			$objectName = $className;

			if (substr($className, -9, 9) === 'Interface') {
				$className = $reflectionService->getDefaultImplementationClassNameForInterface($className);
				if ($className === FALSE) {
					continue;
				}
			}
			$rawObjectConfiguration = array('className' => $className);

			if ($reflectionService->isClassTaggedWith($className, 'scope')) {
				$rawObjectConfiguration['scope'] = implode('', $reflectionService->getClassTagValues($className, 'scope'));
			}
			$objectConfigurations[$objectName] = \F3\FLOW3\Object\Configuration\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawObjectConfiguration, 'Automatic class registration');
		}

		foreach (array_keys($packages) as $packageKey) {
			$rawObjectConfigurations = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS, $packageKey);
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '\\', $objectName);
				if (!isset($objectConfigurations[$objectName]) && substr($objectName, -9, 9) !== 'Interface') {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey. '".', 1184926175);
				}
				if (is_array($rawObjectConfiguration)) {
					$existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : NULL;
					$objectConfigurations[$objectName] = \F3\FLOW3\Object\Configuration\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawObjectConfiguration, 'Package ' . $packageKey, $existingObjectConfiguration);
				}
			}
		}
		return $objectConfigurations;
	}
}

?>