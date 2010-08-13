<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

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
 * An Object Container, supporting Dependency Injection, can build objects
 * during runtime based on the provided object configuration.
 *
 * This container is used in an early boot stage of the FLOW3 framework when
 * no static object container is available. The Dynamic Object Container is
 * tailored to work with objects of the FLOW3 package and won't work with
 * objects of any other package.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class DynamicObjectContainer extends \F3\FLOW3\Object\Container\AbstractObjectContainer {

	/**
	 * @var array
	 */
	protected $objectsBeingBuilt = array();

	/**
	 * @var array
	 */
	protected $objectConfigurations = array();

	/**
	 * Sets the array of object configurations, containing information about
	 * the registered objects (in an early boot state) and their dependencies.
	 * 
	 * @param array<F3\FLOW3\Object\Configuration\Configuration> $objectConfigurations 
	 */
	public function setObjectConfigurations(array $objectConfigurations) {
		foreach ($objectConfigurations as $objectConfiguration) {
			$this->objects[$objectConfiguration->getObjectName()] = array(
				'c' => $objectConfiguration->getClassName(),
				'l' => strtolower($objectConfiguration->getObjectName()),
				's' => $objectConfiguration->getScope(),
				'm' => 'build' . str_replace('\\', '_', $objectConfiguration->getObjectName())
			);
		}
		$this->objectConfigurations = $objectConfigurations;
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws \InvalidArgumentException if the object name starts with a backslash or is otherwise invalid
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScopeException if the specified object is not configured as Prototype
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($objectName) {
		if (isset($this->objects[$objectName]) === FALSE) {
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.', 1265203123);
		}
		if ($this->objects[$objectName]['s'] !== self::SCOPE_PROTOTYPE) {
			throw new \F3\FLOW3\Object\Exception\WrongScopeException('Object "' . $objectName . '" is of not of scope prototype, but only prototype is supported by create()', 1265203124);
		}
		return (func_num_args() === 1) ? $this->build($this->objectConfigurations[$objectName]) : $this->build($this->objectConfigurations[$objectName], self::convertArgumentValuesToArgumentObjects(array_slice(func_get_args(), 1)));
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 */
	public function get($objectName) {
		if (isset($this->objects[$objectName]) === FALSE) {
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.', 1264589155);
		}
		if (isset($this->objects[$objectName]['i'])) {
			return $this->objects[$objectName]['i'];
		}
		if ($this->objects[$objectName]['s'] === self::SCOPE_PROTOTYPE) {
			return (func_num_args() === 1) ? $this->build($this->objectConfigurations[$objectName]) : $this->build($this->objectConfigurations[$objectName], self::convertArgumentValuesToArgumentObjects(array_slice(func_get_args(), 1)));
		} else {
			$this->objects[$objectName]['i'] = $this->build($this->objectConfigurations[$objectName]);
			return $this->objects[$objectName]['i'];
		}
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isRegistered($objectName) {
		return isset($this->objects[$objectName]);
	}

	/**
	 * Returns an array with all object names which are of scope singleton or session and
	 * already have their instance stored in the internal registry.
	 *
	 * Used for importing instances into the Static Object Container.
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getInstances() {
		$instances = array();
		foreach ($this->objects as $objectName => $information) {
			if (isset($information['i'])) {
				$instances[$objectName] = $information['i'];
			}
		}
		return $instances;
	}

	/**
	 * Returns all instances which need to be shutdown on shutting down the container.
	 *
	 * Used for importing instances into the Static Object Container.
	 *
	 * @return \SplObjectStorage
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getShutdownObjects() {
		return $this->shutdownObjects;
	}

	/**
	 * Method which builds the specified object
	 *
	 * @param F3\FLOW3\Object\Configuration\Configuration $objectConfiguration Configuration of the object to build
	 * @param array $overridingArguments An array of \F3\FLOW3\Object\Argument which override possible prewired arguments. Numbering starts with 1! Index == 1 is the first argument, index == 2 to the second etc.
	 * @return object The built object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function build(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration, array $overridingArguments = array()) {
		$objectName = $objectConfiguration->getObjectName();
		if (isset ($this->objectsBeingBuilt[$objectName])) {
			throw new \F3\FLOW3\Object\Exception\CannotBuildObjectException('Circular object dependency for object "' . $objectName . '".', 1168505928);
		}

		try {
			$this->objectsBeingBuilt[$objectName] = TRUE;
			$className = $objectConfiguration->getClassName();
			$customFactoryObjectName = $objectConfiguration->getFactoryObjectName();
			if ($customFactoryObjectName === NULL) {
				if (interface_exists($className) === TRUE) throw new \F3\FLOW3\Object\Exception\InvalidClassException('No default implementation for the object type "' . $objectName . '" found in the object configuration. Cannot instantiate interface ' . $className . '.', 1238761260);
				if (class_exists($className) === FALSE) throw new \F3\FLOW3\Object\Exception\UnknownClassException('Class "' . $className . '" which was specified in the object configuration of object "' . $objectName . '" does not exist.', 1229967561);
			}

			$arguments = $objectConfiguration->getArguments();

			foreach ($overridingArguments as $index => $value) {
				$arguments[$index] = $value;
			}
			$setterProperties = $objectConfiguration->getProperties();

			$preparedArguments = array();
			$this->injectArguments($arguments, $preparedArguments);

			if ($customFactoryObjectName !== NULL) {
				$customFactory = $this->get($customFactoryObjectName);
				$object = call_user_func_array(array($customFactory, $objectConfiguration->getFactoryMethodName()), $preparedArguments);
				if (!is_object($object)) {
					throw new \F3\FLOW3\Object\Exception\CannotBuildObjectException('Custom factory "' . $customFactoryObjectName . '->' . $objectConfiguration->getFactoryMethodName() . '()" did not return an object while building object "' . $objectName . '" (Configuration source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1257766990);
				}
			} else {
				$object = $this->instantiateClass($className, $preparedArguments);
				if (!is_object($object)) {
					throw new \F3\FLOW3\Object\Exception\CannotBuildObjectException('Could not instantiate class ' . $className . ' while building object "' . $objectName . '"', 1187164523);
				}
			}

			if ($object instanceof \F3\FLOW3\AOP\ProxyInterface) {
				$object->FLOW3_AOP_Proxy_setProperty('objectManager', $this->get('F3\FLOW3\Object\ObjectManagerInterface'));
				$object->FLOW3_AOP_Proxy_construct();
			}

			$this->injectProperties($setterProperties, $object);
			$this->callLifecycleInitializationMethod($object, $objectConfiguration);
			$this->registerLifecycleShutdownMethod($object, $objectConfiguration);
		} catch (\Exception $exception) {
			unset ($this->objectsBeingBuilt[$objectName]);
			throw $exception;
		}
		unset ($this->objectsBeingBuilt[$objectName]);
		return $object;
	}

	/**
	 * Creates a skeleton of the specified object
	 *
	 * @param string $objectName Name of the object to create a skeleton for
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration The object configuration
	 * @return object The object skeleton
	 * @throws \F3\FLOW3\Object\Exception\CannotReconstituteObjectException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createEmptyObject($objectName, \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$className = $objectConfiguration->getClassName();

			// Note: The class_implements() function also invokes autoload to assure that the interfaces
			// and the class are loaded. Would end up with __PHP_Incomplete_Class without it.
		if (!in_array('F3\FLOW3\AOP\ProxyInterface', class_implements($className))) throw new \F3\FLOW3\Object\Exception\CannotReconstituteObjectException('Cannot create empty instance of the class "' . $className . '" because it does not implement the AOP Proxy Interface.', 1234386924);

		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		$object->FLOW3_AOP_Proxy_setProperty('objectManager', $this->objectManager);
		$object->FLOW3_AOP_Proxy_declareMethodsAndAdvices();

		return $object;
	}

	/**
	 * Speed optimized alternative to ReflectionClass::newInstanceArgs()
	 *
	 * @param string $className Name of the class to instantiate
	 * @param array $arguments Arguments to pass to the constructor
	 * @return object The object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function instantiateClass($className, array $arguments) {
		switch (count($arguments)) {
			case 0: return new $className();
			case 1: return new $className($arguments[0]);
			case 2: return new $className($arguments[0], $arguments[1]);
			case 3: return new $className($arguments[0], $arguments[1], $arguments[2]);
			case 4: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
			case 5: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
			case 6: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
		}
		$class = new \ReflectionClass($className);
		return $class->newInstanceArgs($arguments);
	}


	/**
	 * Checks and resolves dependencies of the constructor arguments (objects) and prepares an array of constructor
	 * arguments (strings) which can be used in a "new" statement to instantiate the object.
	 *
	 * @param array $arguments Array of \F3\FLOW3\Object\Configuration\ConfigurationArgument for the current object
	 * @param array &$preparedArguments An empty array passed by reference: Will contain constructor parameters as strings to be used in a new statement
	 * @return void  The result is stored in the $preparedArguments array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectArguments($arguments, &$preparedArguments) {
		foreach ($arguments as $argument) {
			if ($argument !== NULL) {
				$argumentValue = $argument->getValue();
				switch ($argument->getType()) {
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
							$preparedArguments[] = $this->build($argumentValue);
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = array_slice(explode('.', $argumentValue), 1);
								$argumentValue = \F3\FLOW3\Utility\Arrays::getValueByPath($this->settings['FLOW3'], $settingPath);
							}
							$preparedArguments[] = $this->get($argumentValue);
						}
					break;
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$preparedArguments[] = $argumentValue;
					break;
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						if (strpos($argumentValue, '.') !== FALSE) {
							$settingPath = array_slice(explode('.', $argumentValue), 1);
							$value = \F3\FLOW3\Utility\Arrays::getValueByPath($this->settings['FLOW3'], $settingPath);
						} else {
							if ($argumentValue !== 'FLOW3') {
								throw new \F3\FLOW3\Object\Exception\CannotBuildObjectException('Invalid reference to setting "' . $argumentValue . '" in object configuration for Dynamic Object Container.', 1265200443);
							}
							$value = $this->settings['FLOW3'];
						}
						$preparedArguments[] = $value;
					break;
				}
			} else {
				$preparedArguments[] = NULL;
			}
		}
	}

	/**
	 * Checks, resolves and injects dependencies as properties through calling the setter methods or setting
	 * properties directly through reflection.
	 *
	 * @param array $properties Array of \F3\FLOW3\Object\Configuration\ConfigurationProperty for the current object
	 * @param object $object The recently created instance of the current object. Dependencies will be injected to it.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectProperties($properties, $object) {
		foreach ($properties as $propertyName => $property) {
			$propertyValue = $property->getValue();
			switch ($property->getType()) {
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT:
					if ($propertyValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
						$preparedValue = $this->build($propertyValue);
					} else {
						if (strpos($propertyValue, '.') !== FALSE) {
							$settingPath = array_slice(explode('.', $propertyValue), 1);
							$propertyValue = \F3\FLOW3\Utility\Arrays::getValueByPath($this->settings['FLOW3'], $settingPath);
						}
						$preparedValue = $this->get($propertyValue);
					}
				break;
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					$preparedValue = $propertyValue;
				break;
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING:
					if (strpos($propertyValue, '.') !== FALSE) {
						$settingPath = array_slice(explode('.', $propertyValue), 1);
						$preparedValue = \F3\FLOW3\Utility\Arrays::getValueByPath($this->settings['FLOW3'], $settingPath);
					} else {
						if ($propertyValue !== 'FLOW3') {
							throw new \F3\FLOW3\Object\Exception\CannotBuildObjectException('Invalid reference to setting "' . $argumentValue . '" in object configuration for Dynamic Object Container.', 1265200444);
						}
						$preparedValue = $this->settings['FLOW3'];
					}
				break;
			}
			$setterMethodName = 'inject' . ucfirst($propertyName);
			if (method_exists($object, $setterMethodName)) {
				$object->$setterMethodName($preparedValue);
				continue;
			}
			$setterMethodName = 'set' . ucfirst($propertyName);
			if (method_exists($object, $setterMethodName)) {
				$object->$setterMethodName($preparedValue);
				continue;
			}
			if (property_exists($object, $propertyName)) {
				$propertyReflection = new \ReflectionProperty($object, $propertyName);
				$propertyReflection->setAccessible(TRUE);
				$propertyReflection->setValue($object, $preparedValue);
			}
		}
	}

	/**
	 * Calls the lifecycle initialization method (if any) of the object
	 *
	 * @param object $object The instance of the recently created object.
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration The object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callLifecycleInitializationMethod($object, \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
		if (method_exists($object, $lifecycleInitializationMethodName)) {
			$object->$lifecycleInitializationMethodName(\F3\FLOW3\Object\Container\ObjectContainerInterface::INITIALIZATIONCAUSE_CREATED);
		}
	}

	/**
	 * Registers the shutdown method of the given object, if any
	 * 
	 * @param object $object The instance of the recently created object.
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration The object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerLifecycleShutdownMethod($object, \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$lifecycleShutdownMethodName = $objectConfiguration->getLifecycleShutdownMethodName();
		if (method_exists($objectConfiguration->getClassName(), $lifecycleShutdownMethodName)) {
			$this->shutdownObjects[$object] = $lifecycleShutdownMethodName;
		}
	}

	/**
	 * Returns straight-value constructor arguments by creating appropriate
	 * \F3\FLOW3\Object\Configuration\ConfigurationArgument objects.
	 *
	 * @param array $argumentValues Array of argument values. Index must start at "0" for parameter "1" etc.
	 * @return array An array of \F3\FLOW3\Object\Configuration\ConfigurationArgument which can be passed to the object builder
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function convertArgumentValuesToArgumentObjects(array $argumentValues) {
		$argumentObjects = array();
		foreach ($argumentValues as $index => $value) {
			$argumentObjects[$index + 1] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index + 1, $value, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
		}
		return $argumentObjects;
	}

}
?>