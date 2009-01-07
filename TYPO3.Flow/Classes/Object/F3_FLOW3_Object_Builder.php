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
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\Builder.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * The Object Object Builder takes care of the whole building (instantiation) process of an
 * object. It resolves dependencies, instantiates other objects if necessary, instantiates
 * the specified object, injects constructor and setter arguments and calls lifecycle methods.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\Builder.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Builder {

	/**
	 * A reference to the object manager - used for fetching other objects while solving dependencies
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Object\FactoryInterface
	 */
	protected $objectFactory;

	/**
	 * @var \F3\FLOW3\Configuration\Manager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Reflection\Service A reference to the reflection service
	 */
	protected $reflectionService;

	/**
	 * A little registry of object names which are currently being built. Used to prevent endless loops due to circular dependencies.
	 * @var array
	 */
	protected $objectsBeingBuilt = array();

	/**
	 * @var array
	 */
	protected $debugMessages = array();

	/**
	 * Injects the Reflection Service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService The Reflection Service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\Manager $objectManager The object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the object factory
	 * Note that the object builder and object registry must have been injected before the object factory
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
	 * Creates and returns a ready-to-use object of the specified type.
	 * During the building process all depencencies are resolved and injected.
	 *
	 * @param string $objectName: Name of the object to create an object for
	 * @param \F3\FLOW3\Object\Configuration $objectConfiguration: The object configuration
	 * @param array $overridingArguments: An array of \F3\FLOW3\Object\Argument which override possible autowired arguments. Numbering starts with 1! Index == 1 is the first argument, index == 2 to the second etc.
	 * @return object
	 * @throws \F3\FLOW3\Object\Exception\CannotBuildObject
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function createObject($objectName, \F3\FLOW3\Object\Configuration $objectConfiguration, array $overridingArguments = array()) {
		if (isset ($this->objectsBeingBuilt[$objectName])) throw new \F3\FLOW3\Object\Exception\CannotBuildObject('Circular object dependency for object "' . $objectName . '".', 1168505928);
		try {
			$this->objectsBeingBuilt[$objectName] = TRUE;
			$className = $objectConfiguration->getClassName();
			$customFactoryClassName = $objectConfiguration->getFactoryClassName();
			if (class_exists($className) === FALSE && $customFactoryClassName === NULL) throw new \F3\FLOW3\Object\Exception\UnknownClass('Class "' . $className . '" which was specified in the object configuration of object "' . $objectName . '" does not exist.', 1229967561);

			$arguments = $objectConfiguration->getArguments();
			foreach ($overridingArguments as $index => $value) {
				$arguments[$index] = $value;
			}

			$setterProperties = $objectConfiguration->getProperties();

			if ($objectConfiguration->getAutoWiringMode() === \F3\FLOW3\Object\Configuration::AUTOWIRING_MODE_ON && $className !== NULL) {
				$arguments = $this->autoWireArguments($arguments, $className);
				$setterProperties = $this->autoWireSetterProperties($setterProperties, $className);
			}
			$preparedArguments = array();
			$this->injectArguments($arguments, $preparedArguments);

			if ($customFactoryClassName !== NULL) {
				$customFactory = $this->objectManager->getObject($customFactoryClassName);
				$object = call_user_func_array(array($customFactory, $objectConfiguration->getFactoryMethodName()), $preparedArguments);
			} else {
				if (count($preparedArguments) > 0) {
					$class = new \ReflectionClass($className);
					$object = $class->newInstanceArgs($preparedArguments);
				} else {
					$object = new $className();
				}
			}

			if (!is_object($object)) {
				$errorMessage = error_get_last();
				throw new \F3\FLOW3\Object\Exception\CannotBuildObject('A parse error ocurred while trying to build a new object of type ' . $className . ' (' . $errorMessage['message'] . ').', 1187164523);
			}

			$this->injectSetterProperties($setterProperties, $object);
			$this->callLifecycleInitializationMethod($object, $objectConfiguration);
		} catch (\Exception $exception) {
			unset ($this->objectsBeingBuilt[$objectName]);
			throw $exception;
		}
		unset ($this->objectsBeingBuilt[$objectName]);
		return $object;
	}

	/**
	 * Reconstitutes the specified object and fills it with the given properties.
	 *
	 * @param string $objectName Name of the object to reconstitute
	 * @param \F3\FLOW3\Object\Configuration $objectConfiguration The object configuration
	 * @param array $properties The names of properties and their values which should be set during the reconstitution
	 * @return object The reconstituted object
	 * @throws \F3\FLOW3\Object\Exception\CannotReconstituteObject if the class cannot be reconstituted or a circular dependency ocurred.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function reconstituteObject($objectName, \F3\FLOW3\Object\Configuration $objectConfiguration, array $properties = array()) {
		if (isset ($this->objectsBeingBuilt[$objectName])) throw new \F3\FLOW3\Object\Exception\CannotReconstituteObject('Circular object dependency for object "' . $objectName . '".', 1216742543);
		$this->objectsBeingBuilt[$objectName] = TRUE;

		$className = $objectConfiguration->getClassName();
		if (!in_array('F3\FLOW3\AOP\ProxyInterface', class_implements($className))) throw new \F3\FLOW3\Object\Exception\CannotReconstituteObject('Cannot reconstitute the class "' . $className . '" because it does not implement the AOP Proxy Interface.', 1216738485);

			// those objects will be fetched from within the __wakeup() method of the object...
		$GLOBALS['reconstituteObject']['objectFactory'] = $this->objectFactory;
		$GLOBALS['reconstituteObject']['objectManager'] = $this->objectManager;
		$GLOBALS['reconstituteObject']['properties'] = $properties;
		$object = unserialize('O:' . strlen($className) . ':"' . $className . '":0:{};');
		unset($GLOBALS['reconstituteObject']);

		unset ($this->objectsBeingBuilt[$objectName]);
		return $object;
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire
	 * them if possible.
	 *
	 * @param array $arguments Array of \F3\FLOW3\Object\ConfigurationArgument for the current object
	 * @param string $className Class name of the object object which contains the methods supposed to be analyzed
	 * @return array The modified array of \F3\FLOW3\Object\ConfigurationArgument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autoWireArguments(array $arguments, $className) {
		$constructorName = $this->reflectionService->getClassConstructorName($className);
		if ($constructorName !== NULL) {
			foreach ($this->reflectionService->getMethodParameters($className, $constructorName) as $parameterName => $parameterInformation) {
				$index = $parameterInformation['position'] + 1;
				if (!isset($arguments[$index])) {
					if ($parameterInformation['optional'] === TRUE) {
						$defaultValue = (isset($parameterInformation['defaultValue'])) ? $parameterInformation['defaultValue'] : NULL;
						$arguments[$index] = new \F3\FLOW3\Object\ConfigurationArgument($index, $defaultValue, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
					} elseif ($parameterInformation['class'] !== NULL) {
						$arguments[$index] = new \F3\FLOW3\Object\ConfigurationArgument($index, $parameterInformation['class'], \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
					} elseif ($parameterInformation['allowsNull'] === TRUE) {
						$arguments[$index] = new \F3\FLOW3\Object\ConfigurationArgument($index, NULL, \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
					} else {
						$this->debugMessages[] = 'Tried everything to autowire parameter $' . $parameterName . ' in ' . $className . '::' . $constructorName . '() but I saw no way.';
					}
				} else {
					$this->debugMessages[] = 'Did not try to autowire parameter $' . $parameterName . ' in ' . $className . '::' . $constructorName. '() because it was already set.';
				}
			}
		} else {
			$this->debugMessages[] = 'Autowiring for class ' . $className . ' disabled because no constructor was found.';
		}
		return $arguments;
	}


	/**
	 * This function tries to find yet unmatched dependencies which need to be injected via "inject*" setter methods.
	 *
	 * @param array $setterProperties Array of \F3\FLOW3\Object\ConfigurationProperty for the current object
	 * @param string $className Name of the class which contains the methods supposed to be analyzed
	 * @return array The modified array of \F3\FLOW3\Object\ConfigurationProperty
	 * @throws \F3\FLOW3\Object\Exception\CannotBuildObject if a required property could not be autowired.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autoWireSetterProperties(array $setterProperties, $className) {
		foreach (get_class_methods($className) as $methodName) {
			if (substr($methodName, 0, 6) === 'inject') {
				$propertyName = strtolower(substr($methodName, 6, 1)) . substr($methodName, 7);
				if ($methodName === 'injectSettings') {
					$classNameParts = explode('\\', $className);
					if (count($classNameParts) > 1) {
						$setterProperties[$propertyName] = new \F3\FLOW3\Object\ConfigurationProperty($propertyName, $classNameParts[1], \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_SETTING);
					}
				} else {
					if (array_key_exists($propertyName, $setterProperties)) {
						$this->debugMessages[] = 'Did not try to autowire property $' . $propertyName . ' in ' . $className .  ' because it was already set.';
						continue;
					}
					$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
					if (count($methodParameters) != 1) {
						$this->debugMessages[] = 'Could not autowire property $' . $propertyName . ' in ' . $className .  ' because it had not exactly one parameter.';
						continue;
					}
					$methodParameter = array_pop($methodParameters);
					if ($methodParameter['class'] === NULL) {
						$this->debugMessages[] = 'Could not autowire property $' . $propertyName . ' in ' . $className .  ' because I could not determine the class of the setter\'s parameter.';
						continue;
					}
					$setterProperties[$propertyName] = new \F3\FLOW3\Object\ConfigurationProperty($propertyName, $methodParameter['class'], \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_OBJECT);
				}
			}
		}
		return $setterProperties;
	}

	/**
	 * Checks and resolves dependencies of the constructor arguments (objects) and prepares an array of constructor
	 * arguments (strings) which can be used in a "new" statement to instantiate the object.
	 *
	 * @param array $arguments Array of \F3\FLOW3\Object\ConfigurationArgument for the current object
	 * @param array &$preparedArguments An empty array passed by reference: Will contain constructor parameters as strings to be used in a new statement
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectArguments($arguments, &$preparedArguments) {
		foreach ($arguments as $argument) {
			if ($argument !== NULL) {
				$argumentValue = $argument->getValue();
				switch ($argument->getType()) {
					case \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration) {
							$preparedArguments[] = $this->createObject($argumentValue->getObjectName(), $argumentValue);
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = $this->configurationManager->getSettings(array_shift($settingPath));
								$argumentValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
							}
							$preparedArguments[] = $this->objectManager->getObject($argumentValue);
						}
					break;
					case \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$preparedArguments[] = $argumentValue;
					break;
					case \F3\FLOW3\Object\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						if (strpos($argumentValue, '.') !== FALSE) {
							$settingPath = explode('.', $argumentValue);
							$settings = $this->configurationManager->getSettings(array_shift($settingPath));
							$value = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
						} else {
							$value = $this->configurationManager->getSettings($argumentValue);
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
	 * Checks, resolves and injects dependencies through calling the setter method of the registered properties.
	 *
	 * @param array $setterProperties: Array of \F3\FLOW3\Object\ConfigurationProperty for the current object
	 * @param object $object: The recently created instance of the current object. Dependencies will be injected to it.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function injectSetterProperties($setterProperties, $object) {
		foreach ($setterProperties as $propertyName => $property) {
			$propertyValue = $property->getValue();
			switch ($property->getType()) {
				case \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_OBJECT:
					if ($propertyValue instanceof \F3\FLOW3\Object\Configuration) {
						$preparedValue = $this->createObject($propertyValue->getObjectName(), $propertyValue);
					} else {
						if (strpos($propertyValue, '.') !== FALSE) {
							$settingPath = explode('.', $propertyValue);
							$settings = $this->configurationManager->getSettings(array_shift($settingPath));
							$propertyValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
						}
						$preparedValue = $this->objectManager->getObject($propertyValue);
					}
				break;
				case \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					$preparedValue = $propertyValue;
				break;
				case \F3\FLOW3\Object\ConfigurationProperty::PROPERTY_TYPES_SETTING:
					if (strpos($propertyValue, '.') !== FALSE) {
						$settingPath = explode('.', $propertyValue);
						$settings = $this->configurationManager->getSettings(array_shift($settingPath));
						$preparedValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
					} else {
						$preparedValue = $this->configurationManager->getSettings($propertyValue);
					}
				break;
				default:
					var_dump($propertyValue);
			}
			$setterMethodName = 'inject' . ucfirst($propertyName);
			if (method_exists($object, $setterMethodName)) {
				$object->$setterMethodName($preparedValue);
			} else {
				$setterMethodName = 'set' . ucfirst($propertyName);
				if (method_exists($object, $setterMethodName)) {
					$object->$setterMethodName($preparedValue);
				}
			}
		}
	}

	/**
	 * Calls the lifecycle initialization method (if any) of the object
	 *
	 * @param object $object: The instance of the recently created object.
	 * @param \F3\FLOW3\Object\Configuration $objectConfiguration: The object configuration
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function callLifecycleInitializationMethod($object, \F3\FLOW3\Object\Configuration $objectConfiguration) {
		$lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
		if (method_exists($object, $lifecycleInitializationMethodName)) {
			$object->$lifecycleInitializationMethodName();
		}
	}
}
?>