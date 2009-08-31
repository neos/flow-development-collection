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
 */
class Manager implements \F3\FLOW3\Object\ManagerInterface {

	/**
	 * Name of the current context
	 * @var string
	 */
	protected $context = 'Development';

	/**
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Object\TransientRegistry
	 */
	protected $singletonObjectsRegistry;

	/**
	 * @var \F3\FLOW3\Object\SessionRegistry
	 */
	protected $sessionObjectsRegistry;

	/**
	 * @var \F3\FLOW3\Object\Builder
	 */
	protected $objectBuilder;

	/**
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * An array of all registered objects. The case sensitive object name is the key, a lower-cased variant is the value.
	 * @var array
	 */
	protected $registeredObjects = array();

	/**
	 * An array of all registered classes. The class name is the key, the object name the value.
	 * @var array
	 */
	protected $registeredClasses = array();

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
	 * An array of all registered object configurations
	 * @var array
	 */
	protected $objectConfigurations = array();

	/**
	 * Objects whose shutdown method should be called on shutdown. Each entry is an array with an object / shutdown method name pair.
	 */
	protected $shutdownObjects = array();

	/**
	 * Injects the singleton objects registry
	 *
	 * @param \F3\FLOW3\Object\TransientRegistry $singletonObjectsRegistry The singleton objects registry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSingletonObjectsRegistry(\F3\FLOW3\Object\TransientRegistry $singletonObjectsRegistry) {
		$this->singletonObjectsRegistry = $singletonObjectsRegistry;
	}

	/**
	 * Injects the session objects registry
	 *
	 * @param \F3\FLOW3\Object\SessionRegistry $sessionObjectsRegistry The session objects registry
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSessionObjectsRegistry(\F3\FLOW3\Object\SessionRegistry $sessionObjectsRegistry) {
		$this->sessionObjectsRegistry = $sessionObjectsRegistry;
	}

	/**
	 * Injects the object builder
	 *
	 * @param \F3\FLOW3\Object\Builder $objectBuilder The object builder
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectBuilder(\F3\FLOW3\Object\Builder $objectBuilder) {
		$this->objectBuilder = $objectBuilder;
	}

	/**
	 * Injects the Reflection Service
	 *
	 * The singleton object registry and object builder must have been injected before the Reflection Service
	 * can be injected.
	 *
	 * This method will usually be called twice during one boot sequence: The first time a preliminary
	 * reflection service is injected which is yet uninitialized and does not provide caching. After
	 * the most important FLOW3 objects have been registered, the final reflection service is injected,
	 * this time with caching.
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService The Reflection Service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		if (!is_object($this->singletonObjectsRegistry) && !is_object($this->objectBuilder)) throw new \F3\FLOW3\Object\Exception\UnresolvedDependencies('No Object Registry or Object Builder has been injected into the Object Manager', 1231252863);
		$this->reflectionService = $reflectionService;
		if (!isset($this->registeredObjects['F3\FLOW3\Reflection\Service'])) {
			$this->registeredObjects['F3\FLOW3\Reflection\Service'] = 'f3\flow3\reflection\service';
			$this->registeredClasses['F3\FLOW3\Reflection\Service'] = 'F3\FLOW3\Reflection\Service';
			$this->objectConfigurations['F3\FLOW3\Reflection\Service'] = new \F3\FLOW3\Object\Configuration\Configuration('F3\FLOW3\Reflection\Service');
		}
		$this->singletonObjectsRegistry->putObject('F3\FLOW3\Reflection\Service', $this->reflectionService);
		$this->objectBuilder->injectReflectionService($this->reflectionService);
	}

	/**
	 * Injects the object factory
	 * Note that the object builder and singleton object registry must have been injected before the object factory
	 * can be injected.
	 *
	 * @param \F3\FLOW3\Object\FactoryInterface $objectFactory The object factory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectFactory(\F3\FLOW3\Object\FactoryInterface $objectFactory) {
		$this->objectFactory = $objectFactory;
	}

	/**
	 * Injects the configuration manager
	 *
	 * @param \F3\FLOW3\Configuration\Manager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\Manager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the cache for storing object configurations
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $objectConfigurationsCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectConfigurationsCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $objectConfigurationsCache) {
		$this->objectConfigurationsCache = $objectConfigurationsCache;
	}

	/**
	 * Initializes the Object Manager and its collaborators
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initializeManager() {
		$rawFLOW3ObjectConfigurations = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, 'FLOW3');
		foreach ($rawFLOW3ObjectConfigurations as $objectName => $rawFLOW3ObjectConfiguration) {
			$this->setObjectConfiguration(\F3\FLOW3\Object\Configuration\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawFLOW3ObjectConfiguration, 'FLOW3 Object Manager (pre-initialization)'));
		}

		$this->registerObject('F3\FLOW3\Object\ManagerInterface', __CLASS__, $this);
		$this->registerObject('F3\FLOW3\Object\Builder',  get_class($this->objectBuilder), $this->objectBuilder);
		$this->registerObject('F3\FLOW3\Object\FactoryInterface', get_class($this->objectFactory), $this->objectFactory);
		$this->registerObject('F3\FLOW3\Object\RegistryInterface',  get_class($this->singletonObjectsRegistry), $this->singletonObjectsRegistry);

		$this->objectBuilder->injectObjectManager($this);
		$this->objectBuilder->injectObjectFactory($this->objectFactory);

		$this->objectFactory->injectObjectManager($this);
		$this->objectFactory->injectObjectBuilder($this->objectBuilder);
	}

	/**
	 * Initializes the object framework and loads the object configuration
	 *
	 * @param array $activePackages An array of active packages of which objects should be considered
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @see initialize()
	 */
	public function initializeObjects(array $activePackages) {
		if ($this->objectConfigurationsCache->has('baseObjectConfigurations')) {
			$this->setObjectConfigurations($this->objectConfigurationsCache->get('baseObjectConfigurations'));
		} else {
			$this->registerAndConfigureAllPackageObjects($activePackages);
			$this->objectConfigurationsCache->set('baseObjectConfigurations', $this->objectConfigurations, array($this->objectConfigurationsCache->getClassTag()));
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
		foreach ($this->shutdownObjects as $objectAndMethodName) {
			list($object, $methodName) = $objectAndMethodName;
			if (method_exists($object, $methodName) && is_callable(array($object, $methodName))) {
				$object->$methodName();
			}
		}
	}

	/**
	 * Sets the Object Manager to a specific context. All operations related to objects
	 * will be carried out based on the configuration for the current context.
	 *
	 * The context should be set as early as possible, preferably before any object has been
	 * instantiated.
	 *
	 * By default the context is set to "default". Although the context can be freely chosen,
	 * the following contexts are explicitly supported by FLOW3:
	 * "Production", "Development", "Testing", "Profiling", "Staging"
	 *
	 * @param  string $context: Name of the context
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
	 * Returns a reference to the object factory used by the object manager.
	 *
	 * @return \F3\FLOW3\Object\FactoryInterface
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectFactory() {
		return $this->objectFactory;
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if an object with the given name does not exist
	 * @api
	 */
	public function getObject($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Object "' . $objectName . '" is not registered.', 1166550023);

		switch ($this->objectConfigurations[$objectName]->getScope()) {
			case 'prototype' :
				$object = call_user_func_array(array($this->objectFactory, 'create'), func_get_args());
				break;
			case 'singleton' :
				if ($this->singletonObjectsRegistry->objectExists($objectName)) {
					$object = $this->singletonObjectsRegistry->getObject($objectName);
				} else {
					$arguments = array_slice(func_get_args(), 1);
					$overridingArguments = $this->getOverridingArguments($arguments);
					$object = $this->objectBuilder->createObject($objectName, $this->objectConfigurations[$objectName], $overridingArguments);
					$this->singletonObjectsRegistry->putObject($objectName, $object);
					$this->registerShutdownObject($object, $this->objectConfigurations[$objectName]->getLifecycleShutdownMethodName());
				}
				break;
			case 'session' :
				if ($this->sessionObjectsRegistry === NULL) throw new \F3\FLOW3\Object\Exception('The session objects registry has not been injected correctly into the object manager.', 1247211113);

				if ($this->sessionObjectsRegistry->objectExists($objectName)) {
					$object = $this->sessionObjectsRegistry->getObject($objectName);
				} else {
					$arguments = array_slice(func_get_args(), 1);
					$overridingArguments = $this->getOverridingArguments($arguments);
					$object = $this->objectBuilder->createObject($objectName, $this->objectConfigurations[$objectName], $overridingArguments);
					$this->sessionObjectsRegistry->putObject($objectName, $object);
					$this->registerShutdownObject($object, $this->objectConfigurations[$objectName]->getLifecycleShutdownMethodName());
				}
				break;
		}

		return $object;
	}

	/**
	 * Registers the given class as an object
	 *
	 * @param string $objectName: The unique identifier of the object
	 * @param string $className: The class name which provides the functionality for this object. Same as object name by default.
	 * @param object $object: If the object has been instantiated prior to registration (which should be avoided whenever possible), it can be passed here.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\ObjectAlreadyRegistered if the object has already been registered
	 * @throws \F3\FLOW3\Object\Exception\InvalidObject if the passed $object is not a valid instance of $className
	 * @api
	 */
	public function registerObject($objectName, $className = NULL, $object = NULL) {
		if ($this->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\ObjectAlreadyRegistered('The object ' . $objectName . ' is already registered.', 1184160573);
		if ($className === NULL) {
			$className = $objectName;
		}
		if (!class_exists($className, TRUE) || interface_exists($className)) throw new \F3\FLOW3\Object\Exception\UnknownClass('The specified class "' . $className . '" does not exist (or is no class) and therefore cannot be registered as an object.', 1200239063);

		if ($object !== NULL) {
			if (!is_object($object) || !$object instanceof $className) throw new \F3\FLOW3\Object\Exception\InvalidObject('The object instance must be a valid instance of the specified class (' . $className . ').', 1183742379);
			$this->singletonObjectsRegistry->putObject($objectName, $object);
		}

		$this->objectConfigurations[$objectName] = new \F3\FLOW3\Object\Configuration\Configuration($objectName, $className);

		if ($this->reflectionService->isClassTaggedWith($className, 'scope')) {
			$scope = trim(implode('', $this->reflectionService->getClassTagValues($className, 'scope')));
			$this->objectConfigurations[$objectName]->setScope($scope);
		}
		$this->registeredObjects[$objectName] = strtolower($objectName);
		$this->registeredClasses[$className] = $objectName;
	}

	/**
	 * Register the given interface as an object type
	 *
	 * @param  string $objectName The name of the object type
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function registerObjectType($objectName) {
		$className = $this->reflectionService->getDefaultImplementationClassNameForInterface($objectName);
		$objectConfiguration = new \F3\FLOW3\Object\Configuration\Configuration($objectName);
		if ($className !== FALSE) {
			$objectConfiguration->setClassName($className);
			if ($this->reflectionService->isClassTaggedWith($className, 'scope')) {
				$scope = trim(implode('', $this->reflectionService->getClassTagValues($className, 'scope')));
				$objectConfiguration->setScope($scope);
			}
			$this->registeredClasses[$className] = $objectName;
		} else {
		}
		$this->registeredObjects[$objectName] = strtolower($objectName);
		$this->objectConfigurations[$objectName] = $objectConfiguration;
	}

	/**
	 * Unregisters the specified object
	 *
	 * @param string $objectName The explicit object name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if the specified object has not been registered before
	 * @api
	 */
	public function unregisterObject($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Object "' . $objectName . '" is not registered.', 1167473433);
		if ($this->singletonObjectsRegistry->objectExists($objectName)) {
			$this->singletonObjectsRegistry->removeObject($objectName);
		}
		unset($this->registeredClasses[$this->objectConfigurations[$objectName]->getClassName()]);
		unset($this->registeredObjects[$objectName]);
		unset($this->objectConfigurations[$objectName]);
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException if $objectName is not a valid string
	 * @api
	 */
	public function isObjectRegistered($objectName) {
		if (!is_string($objectName)) throw new \InvalidArgumentException('The object name must be of type string, ' . gettype($objectName) . ' given.', 1181907931);
		return isset($this->registeredObjects[$objectName]);
	}

	/**
	 * Registers an object so that its shutdown method is called when the object framework
	 * is being shut down.
	 *
	 * Note that objects are registered automatically by the Object Manager and the
	 * Object Factory and this method usually is not needed by user code.
	 *
	 * @param object $object The object to register
	 * @param string $shutdownMethodName Name of the shutdown method to call
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function registerShutdownObject($object, $shutdownMethodName) {
		$this->shutdownObjects[spl_object_hash($object)] = array($object, $shutdownMethodName);
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
	 * @param  string $caseInsensitiveObjectName: The object name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \InvalidArgumentException if $caseInsensitiveObjectName is not a valid string
	 * @api
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName) {
		if (!is_string($caseInsensitiveObjectName)) throw new \InvalidArgumentException('The object name must be of type string, ' . gettype($caseInsensitiveObjectName) . ' given.', 1186655552);
		return array_search(strtolower($caseInsensitiveObjectName), $this->registeredObjects);
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
		if (!isset($this->registeredClasses[$className])) throw new \F3\FLOW3\Object\Exception\UnknownClass('The given class "' . $className . '" is not registered.', 1247133805);
		return $this->registeredClasses[$className];
	}

	/**
	 * Returns an array of object names of all registered objects.
	 * The mixed case object name are used as the array's keys while each
	 * value is the lower cased variant of its respective key.
	 *
	 * @return array An array of object names - mixed case in the key and lower case in the value.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getRegisteredObjects() {
		return $this->registeredObjects;
	}

	/**
	 * Returns an array of configuration objects for all registered objects.
	 *
	 * @return array Array of \F3\FLOW3\Object\Configuration\Configuration objects, indexed by object name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfigurations() {
		return $this->objectConfigurations;
	}

	/**
	 * Returns the configuration object of a certain object
	 *
	 * @param string $objectName Name of the object to fetch the configuration for
	 * @return \F3\FLOW3\Object\Configuration\Configuration The object configuration
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject if the specified object has not been registered
	 */
	public function getObjectConfiguration($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Object "' . $objectName . '" is not registered.', 1167993004);
		return clone $this->objectConfigurations[$objectName];
	}

	/**
	 * Sets the object configurations for all objects found in the
	 * $newObjectConfigurations array.
	 *
	 * @param array $newObjectConfigurations Array of \F3\FLOW3\Object\Configuration\Configuration instances
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurations(array $newObjectConfigurations) {
		foreach ($newObjectConfigurations as $newObjectConfiguration) {
			if (!$newObjectConfiguration instanceof \F3\FLOW3\Object\Configuration\Configuration) throw new \InvalidArgumentException('The new object configuration must be an instance of \F3\FLOW3\Object\Configuration\Configuration', 1167826954);
			$objectName = $newObjectConfiguration->getObjectName();
			if (!isset($this->objectConfigurations[$objectName]) || $this->objectConfigurations[$objectName] !== $newObjectConfiguration) {
				$this->setObjectConfiguration($newObjectConfiguration);
			}
		}
	}

	/**
	 * Sets the object configuration for a specific object.
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $newObjectConfiguration The new object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfiguration(\F3\FLOW3\Object\Configuration\Configuration $newObjectConfiguration) {
		$objectName = $newObjectConfiguration->getObjectName();

		if (isset($this->objectConfigurations[$objectName])) {
			$oldClassName = $this->objectConfigurations[$objectName]->getClassName();
			unset($this->registeredClasses[$oldClassName]);
		}

		$this->registeredClasses[$newObjectConfiguration->getClassName()] = $objectName;
		$this->objectConfigurations[$objectName] = clone $newObjectConfiguration;
		$this->registeredObjects[$objectName] = strtolower($objectName);
	}

	/**
	 * Sets the name of the class implementing the specified object.
	 * This is a convenience method which loads the configuration of the given
	 * object, sets the class name and saves the configuration again.
	 *
	 * @param string $objectName: Name of the object to set the class name for
	 * @param string $className: Name of the class to set
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObject on trying to set the class name of an unknown object
	 * @throws \F3\FLOW3\Object\Exception\UnknownClass if the class does not exist
	 */
	public function setObjectClassName($objectName, $className) {
		if (!$this->isObjectRegistered($objectName)) throw new \F3\FLOW3\Object\Exception\UnknownObject('Tried to set class name of non existent object "' . $objectName . '"', 1185524488);
		if (!class_exists($className)) throw new \F3\FLOW3\Object\Exception\UnknownClass('Tried to set the class name of object "' . $objectName . '" but a class "' . $className . '" does not exist.', 1185524499);
		$objectConfiguration = $this->getObjectConfiguration($objectName);
		$objectConfiguration->setClassName($className);
		$this->setObjectConfiguration($objectConfiguration);
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
				if (!isset($this->registeredObjects[$className])) {
					$this->registerObjectType($className);
				}
			} else {
				if (!isset($this->registeredObjects[$className])) {
					if (!$this->reflectionService->isClassAbstract($className)) {
						$this->registerObject($className, $className);
					}
				}
			}
		}

		foreach (array_keys($packages) as $packageKey) {
			$rawObjectConfigurations = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\Manager::CONFIGURATION_TYPE_OBJECTS, $packageKey);
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '\\', $objectName);
				if (!isset($this->registeredObjects[$objectName])) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfiguration('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey. '".', 1184926175);
				}
				if (is_array($rawObjectConfiguration)) {
					$existingObjectConfiguration = (isset($this->objectConfigurations[$objectName])) ? $this->objectConfigurations[$objectName] : NULL;
					$this->objectConfigurations[$objectName] = \F3\FLOW3\Object\Configuration\ConfigurationBuilder::buildFromConfigurationArray($objectName, $rawObjectConfiguration, 'Package ' . $packageKey, $existingObjectConfiguration);
				}
			}
		}
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
		if ($className === $blacklistedClassName || preg_match('/^' . str_replace('\\', '\\\\', $blacklistedClassName) . '$/', $className)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns straight-value constructor arguments for an object by creating appropriate
	 * \F3\FLOW3\Object\Configuration\ConfigurationArgument objects.
	 *
	 * @param array $argumentValues: Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of \F3\FLOW3\Object\Configuration\ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see create()
	 */
	protected function getOverridingArguments(array $argumentValues) {
		$argumentObjects = array();
		foreach ($argumentValues as $index => $value) {
			$argumentObjects[$index + 1] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index + 1, $value, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $argumentObjects;
	}

	/**
	 * Controls cloning of the object manager. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->singletonObjectsRegistry = clone $this->singletonObjectsRegistry;

		$this->objectFactory = clone $this->objectFactory;
		$this->objectFactory->injectObjectManager($this);
	}
}

?>