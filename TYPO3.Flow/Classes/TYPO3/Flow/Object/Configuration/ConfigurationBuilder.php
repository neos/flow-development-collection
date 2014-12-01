<?php
namespace TYPO3\Flow\Object\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Object Configuration Builder which can build object configuration objects
 * from information collected by reflection combined with arrays of configuration
 * options as defined in an Objects.yaml file.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ConfigurationBuilder {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Traverses through the given class and interface names and builds a base object configuration
	 * for all of them. Then parses the provided extra configuration and merges the result
	 * into the overall configuration. Finally autowires dependencies of arguments and properties
	 * which can be resolved automatically.
	 *
	 * @param array $availableClassNamesByPackage An array of available class names, grouped by package key
	 * @param array $rawObjectConfigurationsByPackages An array of package keys and their raw (ie. unparsed) object configurations
	 * @return array<TYPO3\Flow\Object\Configuration\Configuration> Object configurations
	 * @throws \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException
	 */
	public function buildObjectConfigurations(array $availableClassNamesByPackage, array $rawObjectConfigurationsByPackages) {
		$objectConfigurations = array();

		foreach ($availableClassNamesByPackage as $packageKey => $classNames) {
			foreach ($classNames as $className) {
				$objectName = $className;

				if ($this->reflectionService->isClassUnconfigurable($className)) {
					continue;
				}

				if ($this->reflectionService->isClassFinal($className)) {
					continue;
				}

				if (interface_exists($className)) {
					$interfaceName = $className;
					$className = $this->reflectionService->getDefaultImplementationClassNameForInterface($interfaceName);
					if ($className === FALSE) {
						continue;
					}
					if ($this->reflectionService->isClassAnnotatedWith($interfaceName, 'TYPO3\Flow\Annotations\Scope')) {
						throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException(sprintf('Scope annotations in interfaces don\'t have any effect, therefore you better remove it from %s in order to avoid confusion.', $interfaceName), 1299095595);
					}
				}

				$rawObjectConfiguration = array('className' => $className);
				$rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($className, $rawObjectConfiguration);
				$objectConfigurations[$objectName] = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'automatically registered class');
				$objectConfigurations[$objectName]->setPackageKey($packageKey);
			}
		}

		foreach ($rawObjectConfigurationsByPackages as $packageKey => $rawObjectConfigurations) {
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '\\', $objectName);
				if (!is_array($rawObjectConfiguration)) {
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Configuration of object "' . $objectName . '" in package "' . $packageKey . '" is not an array, please check your Objects.yaml for syntax errors.', 1295954338);
				}

				$existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : NULL;
				if (isset($rawObjectConfiguration['className'])) {
					$rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($rawObjectConfiguration['className'], $rawObjectConfiguration);
				}
				$newObjectConfiguration = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'configuration of package ' . $packageKey . ', definition for object "' . $objectName . '"', $existingObjectConfiguration);

				if (!isset($objectConfigurations[$objectName]) && !interface_exists($objectName, TRUE) && !class_exists($objectName, FALSE)) {
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey . '". Please check your Objects.yaml.', 1184926175);
				}

				if ($objectName !== $newObjectConfiguration->getClassName() && !interface_exists($objectName, TRUE)) {
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Tried to set a differing class name for class "' . $objectName . '" in the object configuration of package "' . $packageKey . '". Setting "className" is only allowed for interfaces, please check your Objects.yaml."', 1295954589);
				}

				$objectConfigurations[$objectName] = $newObjectConfiguration;
				if ($objectConfigurations[$objectName]->getPackageKey() === NULL) {
					$objectConfigurations[$objectName]->setPackageKey($packageKey);
				}
			}
		}

		$this->autowireArguments($objectConfigurations);
		$this->autowireProperties($objectConfigurations);

		return $objectConfigurations;
	}

	/**
	 * Builds a raw configuration array by parsing possible scope and autowiring
	 * annotations from the given class or interface.
	 *
	 * @param string $className
	 * @param array $rawObjectConfiguration
	 * @return array
	 */
	protected function enhanceRawConfigurationWithAnnotationOptions($className, array $rawObjectConfiguration) {
		if ($this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Scope')) {
			$rawObjectConfiguration['scope'] = $this->reflectionService->getClassAnnotation($className, 'TYPO3\Flow\Annotations\Scope')->value;
		}
		if ($this->reflectionService->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Autowiring')) {
			$rawObjectConfiguration['autowiring'] = $this->reflectionService->getClassAnnotation($className, 'TYPO3\Flow\Annotations\Autowiring')->enabled;
		}
		return $rawObjectConfiguration;
	}

	/**
	 * Builds an object configuration object from a generic configuration container.
	 *
	 * @param string $objectName Name of the object
	 * @param array $rawConfigurationOptions The configuration array with options for the object configuration
	 * @param string $configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @param \TYPO3\Flow\Object\Configuration\Configuration existingObjectConfiguration If set, this object configuration object will be used instead of creating a fresh one
	 * @return \TYPO3\Flow\Object\Configuration\Configuration The object configuration object
	 * @throws \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException if errors occurred during parsing
	 */
	protected function parseConfigurationArray($objectName, array $rawConfigurationOptions, $configurationSourceHint = '', $existingObjectConfiguration = NULL) {
		$className = (isset($rawConfigurationOptions['className']) ? $rawConfigurationOptions['className'] : $objectName);
		$objectConfiguration = ($existingObjectConfiguration instanceof Configuration) ? $existingObjectConfiguration : new Configuration($objectName, $className);
		$objectConfiguration->setConfigurationSourceHint($configurationSourceHint);

		foreach ($rawConfigurationOptions as $optionName => $optionValue) {
			switch ($optionName) {
				case 'scope':
					$objectConfiguration->setScope($this->parseScope($optionValue));
				break;
				case 'properties':
					if (is_array($optionValue)) {
						foreach ($optionValue as $propertyName => $propertyValue) {
							if (array_key_exists('value', $propertyValue)) {
								$property = new ConfigurationProperty($propertyName, $propertyValue['value'], ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
							} elseif (array_key_exists('object', $propertyValue)) {
								$property = $this->parsePropertyOfTypeObject($propertyName, $propertyValue['object'], $configurationSourceHint);
							} elseif (array_key_exists('setting', $propertyValue)) {
								$property = new ConfigurationProperty($propertyName, $propertyValue['setting'], ConfigurationProperty::PROPERTY_TYPES_SETTING);
							} else {
								throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for property "' . $propertyName . '", instead found "' . (is_array($propertyValue) ? implode(', ', array_keys($propertyValue)) : $propertyValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563249);
							}
							$objectConfiguration->setProperty($property);
						}
					}
				break;
				case 'arguments':
					if (is_array($optionValue)) {
						foreach ($optionValue as $argumentName => $argumentValue) {
							if (array_key_exists('value', $argumentValue)) {
								$argument = new ConfigurationArgument($argumentName, $argumentValue['value'], ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
							} elseif (array_key_exists('object', $argumentValue)) {
								$argument = $this->parseArgumentOfTypeObject($argumentName, $argumentValue['object'], $configurationSourceHint);
							} elseif (array_key_exists('setting', $argumentValue)) {
								$argument = new ConfigurationArgument($argumentName, $argumentValue['setting'], ConfigurationArgument::ARGUMENT_TYPES_SETTING);
							} else {
								throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for argument "' . $argumentName . '", instead found "' . (is_array($argumentValue) ? implode(', ', array_keys($argumentValue)) : $argumentValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563250);
							}
							$objectConfiguration->setArgument($argument);
						}
					}
				break;
				case 'className':
				case 'factoryObjectName' :
				case 'factoryMethodName' :
				case 'lifecycleInitializationMethodName':
				case 'lifecycleShutdownMethodName':
					$methodName = 'set' . ucfirst($optionName);
					$objectConfiguration->$methodName(trim($optionValue));
				break;
				case 'autowiring':
					$objectConfiguration->setAutowiring($this->parseAutowiring($optionValue));
				break;
				default:
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Invalid configuration option "' . $optionName . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1167574981);
			}
		}
		return $objectConfiguration;
	}

	/**
	 * Parses the value of the option "scope"
	 *
	 * @param  string $value Value of the option
	 * @return integer The scope translated into a Configuration::SCOPE_* constant
	 * @throws \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException if an invalid scope has been specified
	 */
	protected function parseScope($value) {
		switch ($value) {
			case 'singleton':
				return Configuration::SCOPE_SINGLETON;
			case 'prototype':
				return Configuration::SCOPE_PROTOTYPE;
			case 'session':
				return Configuration::SCOPE_SESSION;
			default:
				throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Invalid scope "' . $value . '"', 1167574991);
		}
	}

	/**
	 * Parses the value of the option "autowiring"
	 *
	 * @param  mixed $value Value of the option
	 * @return integer The autowiring option translated into one of Configuration::AUTOWIRING_MODE_*
	 * @throws \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException if an invalid option has been specified
	 */
	static protected function parseAutowiring($value) {
		switch ($value) {
			case TRUE:
			case Configuration::AUTOWIRING_MODE_ON:
				return Configuration::AUTOWIRING_MODE_ON;
			case FALSE:
			case Configuration::AUTOWIRING_MODE_OFF:
				return Configuration::AUTOWIRING_MODE_OFF;
			default:
				throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Invalid autowiring declaration', 1283866757);
		}
	}

	/**
	 * Parses the configuration for properties of type OBJECT
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $objectNameOrConfiguration Value of the "object" section of the property configuration - either a string or an array
	 * @param string $configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \TYPO3\Flow\Object\Configuration\ConfigurationProperty A configuration property of type object
	 * @throws \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException
	 */
	protected function parsePropertyOfTypeObject($propertyName, $objectNameOrConfiguration, $configurationSourceHint) {
		if (is_array($objectNameOrConfiguration)) {
			if (isset($objectNameOrConfiguration['name'])) {
				$objectName = $objectNameOrConfiguration['name'];
				unset($objectNameOrConfiguration['name']);
			} else {
				if (isset($objectNameOrConfiguration['factoryObjectName'])) {
					$objectName = NULL;
				} else {
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Object configuration for property "' . $propertyName . '" contains neither object name nor factory object name in ' . $configurationSourceHint, 1297097815);
				}
			}
			$objectConfiguration = $this->parseConfigurationArray($objectName, $objectNameOrConfiguration, $configurationSourceHint . ', property "' . $propertyName .'"');
			$property = new ConfigurationProperty($propertyName, $objectConfiguration, ConfigurationProperty::PROPERTY_TYPES_OBJECT);
		} else {
			$property = new ConfigurationProperty($propertyName, $objectNameOrConfiguration, ConfigurationProperty::PROPERTY_TYPES_OBJECT);
		}
		return $property;
	}

	/**
	 * Parses the configuration for arguments of type OBJECT
	 *
	 * @param string $argumentName Name of the argument
	 * @param mixed $objectNameOrConfiguration Value of the "object" section of the argument configuration - either a string or an array
	 * @param string $configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \TYPO3\Flow\Object\Configuration\ConfigurationArgument A configuration argument of type object
	 */
	protected function parseArgumentOfTypeObject($argumentName, $objectNameOrConfiguration, $configurationSourceHint) {
		if (is_array($objectNameOrConfiguration)) {
			if (isset($objectNameOrConfiguration['name'])) {
				$objectName = $objectNameOrConfiguration['name'];
				unset($objectNameOrConfiguration['name']);
			} else {
				if (isset($objectNameOrConfiguration['factoryObjectName'])) {
					$objectName = NULL;
				} else {
					throw new \TYPO3\Flow\Object\Exception\InvalidObjectConfigurationException('Object configuration for argument "' . $argumentName . '" contains neither object name nor factory object name in ' . $configurationSourceHint, 1417431742);
				}
			}
			$objectConfiguration = $this->parseConfigurationArray($objectName, $objectNameOrConfiguration, $configurationSourceHint . ', argument "' . $argumentName . '"');
			$argument = new ConfigurationArgument($argumentName, $objectConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		} else {
			$argument = new ConfigurationArgument($argumentName, $objectNameOrConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		}
		return $argument;
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire
	 * them if possible.
	 *
	 * @param array &$objectConfigurations
	 * @return void
	 * @throws \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException
	 */
	protected function autowireArguments(array &$objectConfigurations) {
		foreach ($objectConfigurations as $objectConfiguration) {
			$className = $objectConfiguration->getClassName();
			$arguments = $objectConfiguration->getArguments();

			if ($this->reflectionService->hasMethod($className, '__construct')) {
				foreach ($this->reflectionService->getMethodParameters($className, '__construct') as $parameterName => $parameterInformation) {
					$debuggingHint = '';
					$index = $parameterInformation['position'] + 1;
					if (!isset($arguments[$index])) {
						if ($parameterInformation['optional'] === TRUE) {
							$defaultValue = (isset($parameterInformation['defaultValue'])) ? $parameterInformation['defaultValue'] : NULL;
							$arguments[$index] = new ConfigurationArgument($index, $defaultValue, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} elseif ($parameterInformation['class'] !== NULL && isset($objectConfigurations[$parameterInformation['class']])) {
							$arguments[$index] = new ConfigurationArgument($index, $parameterInformation['class'], ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
						} elseif ($parameterInformation['allowsNull'] === TRUE) {
							$arguments[$index] = new ConfigurationArgument($index, NULL, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} elseif (interface_exists($parameterInformation['class'])) {
							$debuggingHint = sprintf('No default implementation for the required interface %s was configured, therefore no specific class name could be used for this dependency. ', $parameterInformation['class']);
						}

						$autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, '__construct', 'TYPO3\Flow\Annotations\Autowiring');
						if (isset ($arguments[$index]) && ($objectConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF
								|| $autowiringAnnotation !== NULL && $autowiringAnnotation->enabled === FALSE)) {
							$arguments[$index]->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
							$arguments[$index]->set($index, NULL);
						}

						if (!isset($arguments[$index]) && $objectConfiguration->getScope() === Configuration::SCOPE_SINGLETON) {
							throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException(sprintf('Could not autowire required constructor argument $%s for singleton class %s. %sCheck the type hint of that argument and your Objects.yaml configuration.', $parameterName, $className, $debuggingHint), 1298629392);
						}
					}
				}
			}
			$objectConfiguration->setArguments($arguments);
		}
	}

	/**
	 * This function tries to find yet unmatched dependencies which need to be injected via "inject*" setter methods.
	 *
	 * @param array &$objectConfigurations
	 * @return void
	 * @throws \TYPO3\Flow\Object\Exception if an injected property is private
	 */
	protected function autowireProperties(array &$objectConfigurations) {
		foreach ($objectConfigurations as $objectConfiguration) {
			$className = $objectConfiguration->getClassName();
			$properties = $objectConfiguration->getProperties();

			$classMethodNames = get_class_methods($className);
			if (!is_array($classMethodNames)) {
				if (!class_exists($className)) {
					throw new \TYPO3\Flow\Object\Exception\UnknownClassException(sprintf('The class "%s" defined in the object configuration for object "%s", defined in package: %s, does not exist.', $className, $objectConfiguration->getObjectName(), $objectConfiguration->getPackageKey()), 1352371371);
				} else {
					throw new \TYPO3\Flow\Object\Exception\UnknownClassException(sprintf('Could not autowire properties of class "%s" because names of methods contained in that class could not be retrieved using get_class_methods().', $className), 1352386418);
				}
			}
			foreach ($classMethodNames as $methodName) {
				if (strlen($methodName) > 6 && substr($methodName, 0, 6) === 'inject' && $methodName[6] === strtoupper($methodName[6])) {
					$propertyName = lcfirst(substr($methodName, 6));

					$autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, $methodName, 'TYPO3\Flow\Annotations\Autowiring');
					if ($autowiringAnnotation !== NULL && $autowiringAnnotation->enabled === FALSE) {
						continue;
					}

					if ($methodName === 'injectSettings') {
						$packageKey = $objectConfiguration->getPackageKey();
						if ($packageKey !== NULL) {
							$properties[$propertyName] = new ConfigurationProperty($propertyName, $packageKey, ConfigurationProperty::PROPERTY_TYPES_SETTING);
						}
					} else {
						if (array_key_exists($propertyName, $properties)) {
							continue;
						}
						$methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
						if (count($methodParameters) !== 1) {
							$this->systemLogger->log(sprintf('Could not autowire property %s because %s() expects %s instead of exactly 1 parameter.', "$className::$propertyName", $methodName, (count($methodParameters) ?: 'none')), LOG_DEBUG);
							continue;
						}
						$methodParameter = array_pop($methodParameters);
						if ($methodParameter['class'] === NULL) {
							$this->systemLogger->log(sprintf('Could not autowire property %s because the method parameter in %s() contained no class type hint.', "$className::$propertyName", $methodName), LOG_DEBUG);
							continue;
						}
						$properties[$propertyName] = new ConfigurationProperty($propertyName, $methodParameter['class'], ConfigurationProperty::PROPERTY_TYPES_OBJECT);
					}
				}
			}

			foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, 'TYPO3\Flow\Annotations\Inject') as $propertyName) {
				if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
					$exceptionMessage = 'The property "' . $propertyName . '" in class "' . $className . '" must not be private when annotated for injection.';
					throw new \TYPO3\Flow\Object\Exception($exceptionMessage, 1328109641);
				}
				if (!array_key_exists($propertyName, $properties)) {
					$objectName = trim(implode('', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
					$properties[$propertyName] =  new ConfigurationProperty($propertyName, $objectName, ConfigurationProperty::PROPERTY_TYPES_OBJECT);
				}
			}

			$objectConfiguration->setProperties($properties);
		}
	}
}
