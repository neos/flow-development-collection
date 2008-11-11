<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

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
 * @subpackage Object
 * @version $Id$
 */

/**
 * Implementation of the default FLOW3 Object Manager
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::Manager.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Manager implements F3::FLOW3::Object::ManagerInterface {

	/**
	 * @var string Name of the current context
	 */
	protected $context = 'Development';

	/**
	 * @var F3::FLOW3::Reflection::Service
	 */
	protected $reflectionService;

	/**
	 * @var F3::FLOW3::Object::ObjectCacheInterface Holds an instance of the Object Object Cache
	 */
	protected $objectCache;

	/**
	 * @var F3::FLOW3::Object::FactoryInterface A Reference to the object factory
	 */
	protected $objectFactory;

	/**
	 * @var array An array of all registered objects. The case sensitive object name is the key, a lower-cased variant is the value.
	 */
	protected $registeredObjects = array();

	/**
	 * @var array An array of all registered object configurations
	 */
	protected $objectConfigurations = array();

	/**
	 * @var F3::FLOW3::Object::Builder Holds an instance of the Object Object Builder
	 */
	protected $objectBuilder;

	/**
	 * Constructor. Instantiates the object cache and object builder.
	 *
	 * @param F3::FLOW3::Reflection::Service $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3::FLOW3::Reflection::Service $reflectionService) {
		$this->reflectionService = $reflectionService;
		$this->objectCache = new F3::FLOW3::Object::TransientObjectCache();
		$this->registerObject('F3::FLOW3::Object::ManagerInterface', __CLASS__, $this);


		$this->objectFactory = new F3::FLOW3::Object::Factory();
		$this->objectBuilder = new F3::FLOW3::Object::Builder($this, $this->objectFactory, $this->reflectionService);
		$this->objectFactory->injectObjectManager($this);
		$this->objectFactory->injectObjectBuilder($this->objectBuilder);
		$this->objectFactory->injectObjectCache($this->objectCache);
		$this->registerObject('F3::FLOW3::Object::FactoryInterface', 'F3::FLOW3::Object::Factory', $this->objectFactory);
		$this->registerObject('F3::FLOW3::Object::Builder', 'F3::FLOW3::Object::Builder', $this->objectBuilder);
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
	 * @throws InvalidArgumentException if $context is not a valid string.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setContext($context) {
		if (!is_string($context)) throw new InvalidArgumentException('Context must be given as string.', 1210857671);
		$this->context = $context;
	}

	/**
	 * Returns the name of the currently set context.
	 *
	 * @return  string Name of the current context
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getContext() {
		return $this->context;
	}


	/**
	 * Returns a reference to the object factory used by the object manager.
	 *
	 * @return F3::FLOW3::Object::FactoryInterface
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
	 * @throws F3::FLOW3::Object::Exception::UnknownObject if an object with the given name does not exist
	 */
	public function getObject($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new F3::FLOW3::Object::Exception::UnknownObject('Object "' . $objectName . '" is not registered.', 1166550023);

		switch ($this->objectConfigurations[$objectName]->getScope()) {
			case 'prototype' :
				$object = call_user_func_array(array($this->objectFactory, 'create'), func_get_args());
				break;
			case 'singleton' :
				if ($this->objectCache->objectExists($objectName)) {
					$object = $this->objectCache->getObject($objectName);
				} else {
					$arguments = array_slice(func_get_args(), 1);
					$overridingConstructorArguments = $this->getOverridingConstructorArguments($arguments);
					$object = $this->objectBuilder->createObject($objectName, $this->objectConfigurations[$objectName], $overridingConstructorArguments);
					$this->objectCache->putObject($objectName, $object);
				}
				break;
			default :
				throw new F3::FLOW3::Object::Exception('Support for scope "' . $this->objectConfigurations[$objectName]->getScope() . '" has not been implemented (yet)', 1167484148);
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
	 * @throws F3::FLOW3::Object::Exception::ObjectAlreadyRegistered if the object has already been registered
	 * @throws F3::FLOW3::Object::Exception::InvalidObject if the passed $object is not a valid instance of $className
	 */
	public function registerObject($objectName, $className = NULL, $object = NULL) {
		if ($this->isObjectRegistered($objectName)) throw new F3::FLOW3::Object::Exception::ObjectAlreadyRegistered('The object ' . $objectName . ' is already registered.', 1184160573);
		if ($className === NULL) {
			$className = $objectName;
		}
		if (!class_exists($className, TRUE)) throw new F3::FLOW3::Object::Exception::UnknownClass('The specified class "' . $className . '" does not exist (or is no class) and therefore cannot be registered as an object.', 1200239063);
		$useReflectionService = $this->reflectionService->isInitialized();
		if (!$useReflectionService) $class = new F3::FLOW3::Reflection::ClassReflection($className);

		$classIsAbstract = $useReflectionService ? $this->reflectionService->isClassAbstract($className) : $class->isAbstract();
		if ($classIsAbstract) throw new F3::FLOW3::Object::Exception::InvalidClass('Cannot register the abstract class "' . $className . '" as an object.', 1200239129);

		if ($object !== NULL) {
			if (!is_object($object) || !$object instanceof $className) throw new F3::FLOW3::Object::Exception::InvalidObject('The object instance must be a valid instance of the specified class (' . $className . ').', 1183742379);
			$this->objectCache->putObject($objectName, $object);
		}

		$this->objectConfigurations[$objectName] = new F3::FLOW3::Object::Configuration($objectName, $className);

		if ($useReflectionService) {
			if ($this->reflectionService->isClassTaggedWith($className, 'scope')) {
				$scope = trim(implode('', $this->reflectionService->getClassTagValues($className, 'scope')));
				$this->objectConfigurations[$objectName]->setScope($scope);
			}
		} elseif ($class->isTaggedWith('scope')) {
			$scope = trim(implode('', $class->getTagValues('scope')));
			$this->objectConfigurations[$objectName]->setScope($scope);
		}
		$this->registeredObjects[$objectName] = F3::PHP6::Functions::strtolower($objectName);
	}

	/**
	 * Register the given interface as an object type
	 *
	 * @param  string $objectType: The unique identifier of the object (-type)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function registerObjectType($objectName) {
		$className = $this->reflectionService->getDefaultImplementationClassNameForInterface($objectName);
		$objectConfiguration = new F3::FLOW3::Object::Configuration($objectName);
		if ($className !== FALSE) {
			$objectConfiguration->setClassName($className);

			$useReflectionService = $this->reflectionService->isInitialized();
			if (!$useReflectionService) $class = new F3::FLOW3::Reflection::ClassReflection($className);

			if ($useReflectionService) {
				if ($this->reflectionService->isClassTaggedWith($className, 'scope')) {
					$scope = trim(implode('', $this->reflectionService->getClassTagValues($className, 'scope')));
					$objectConfiguration->setScope($scope);
				}
			} elseif ($class->isTaggedWith('scope')) {
				$scope = trim(implode('', $class->getTagValues('scope')));
				$objectConfiguration->setScope($scope);
			}
		}
		$this->registeredObjects[$objectName] = F3::PHP6::Functions::strtolower($objectName);
		$this->objectConfigurations[$objectName] = $objectConfiguration;
	}

	/**
	 * Unregisters the specified object
	 *
	 * @param string $objectName: The explicit object name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Object::Exception::UnknownObject if the specified object has not been registered before
	 */
	public function unregisterObject($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new F3::FLOW3::Object::Exception::UnknownObject('Object "' . $objectName . '" is not registered.', 1167473433);
		if ($this->objectCache->objectExists($objectName)) {
			$this->objectCache->removeObject($objectName);
		}
		unset($this->registeredObjects[$objectName]);
		unset($this->objectConfigurations[$objectName]);
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName: Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws InvalidArgumentException if $objectName is not a valid string
	 */
	public function isObjectRegistered($objectName) {
		if (!is_string($objectName)) throw new InvalidArgumentException('The object name must be of type string, ' . gettype($objectName) . ' given.', 1181907931);
		return isset($this->registeredObjects[$objectName]);
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
	 * @throws InvalidArgumentException if $caseInsensitiveObjectName is not a valid string
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName) {
		if (!is_string($caseInsensitiveObjectName)) throw new InvalidArgumentException('The object name must be of type string, ' . gettype($caseInsensitiveObjectName) . ' given.', 1186655552);
		return array_search(F3::PHP6::Functions::strtolower($caseInsensitiveObjectName), $this->registeredObjects);
	}

	/**
	 * Returns an array of object names of all registered objects.
	 * The mixed case object name are used as the array's keys while each
	 * value is the lower cased variant of its respective key.
	 *
	 * @return array An array of object names - mixed case in the key and lower case in the value.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRegisteredObjects() {
		return $this->registeredObjects;
	}

	/**
	 * Returns an array of configuration objects for all registered objects.
	 *
	 * @return arrray Array of F3::FLOW3::Object::Configuration objects, indexed by object name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfigurations() {
		return $this->objectConfigurations;
	}

	/**
	 * Returns the configuration object of a certain object
	 *
	 * @param string $objectName: Name of the object to fetch the configuration for
	 * @return F3::FLOW3::Object::Configuration The object configuration
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Object::Exception::UnknownObject if the specified object has not been registered
	 */
	public function getObjectConfiguration($objectName) {
		if (!$this->isObjectRegistered($objectName)) throw new F3::FLOW3::Object::Exception::UnknownObject('Object "' . $objectName . '" is not registered.', 1167993004);
		return clone $this->objectConfigurations[$objectName];
	}

	/**
	 * Sets the object configurations for all objects found in the
	 * $newObjectConfigurations array.
	 *
	 * @param array $newObjectConfigurations: Array of $objectName => F3::FLOW3::Object::configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfigurations(array $newObjectConfigurations) {
		foreach ($newObjectConfigurations as $newObjectConfiguration) {
			if (!$newObjectConfiguration instanceof F3::FLOW3::Object::Configuration) throw new InvalidArgumentException('The new object configuration must be an instance of F3::FLOW3::Object::Configuration', 1167826954);
			$objectName = $newObjectConfiguration->getObjectName();
			if (!isset($this->objectConfigurations[$objectName]) || $this->objectConfigurations[$objectName] !== $newObjectConfiguration) {
				$this->setObjectConfiguration($newObjectConfiguration);
			}
		}
	}

	/**
	 * Sets the object configuration for a specific object.
	 *
	 * @param F3::FLOW3::Object::Configuration $newObjectConfiguration: The new object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setObjectConfiguration(F3::FLOW3::Object::Configuration $newObjectConfiguration) {
		$objectName = $newObjectConfiguration->getObjectName();
		$this->objectConfigurations[$newObjectConfiguration->getObjectName()] = clone $newObjectConfiguration;
		$this->registeredObjects[$objectName] = F3::PHP6::Functions::strtolower($objectName);
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
	 * @throws F3::FLOW3::Object::Exception::UnknownObject on trying to set the class name of an unknown object
	 * @throws F3::FLOW3::Object::Exception::UnknownClass if the class does not exist
	 */
	public function setObjectClassName($objectName, $className) {
		if (!$this->isObjectRegistered($objectName)) throw new F3::FLOW3::Object::Exception::UnknownObject('Tried to set class name of non existent object "' . $objectName . '"', 1185524488);
		if (!class_exists($className)) throw new F3::FLOW3::Object::Exception::UnknownClass('Tried to set the class name of object "' . $objectName . '" but a class "' . $className . '" does not exist.', 1185524499);
		$objectConfiguration = $this->getObjectConfiguration($objectName);
		$objectConfiguration->setClassName($className);
		$this->setObjectConfiguration($objectConfiguration);
	}

	/**
	 * Returns straight-value constructor arguments for an object by creating appropriate
	 * F3::FLOW3::Object::ConfigurationArgument objects.
	 *
	 * @param array $arguments: Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of F3::FLOW3::Object::ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 * @see getObject()
	 */
	protected function getOverridingConstructorArguments(array $arguments) {
		$constructorArguments = array();
		foreach ($arguments as $index => $value) {
			$constructorArguments[$index + 1] = new F3::FLOW3::Object::ConfigurationArgument($index + 1, $value, F3::FLOW3::Object::ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $constructorArguments;
	}

	/**
	 * Controls cloning of the object manager. Cloning should only be used within unit tests.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		$this->objectCache = clone $this->objectCache;

		$this->objectFactory = clone $this->objectFactory;
		$this->objectFactory->injectObjectManager($this);
		$this->objectFactory->injectObjectCache($this->objectCache);
	}
}

?>