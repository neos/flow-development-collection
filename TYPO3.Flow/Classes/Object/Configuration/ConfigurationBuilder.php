<?php
namespace F3\FLOW3\Object\Configuration;

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
 * Object Configuration Builder which can build object configuration objects
 * from information collected by reflection combined with arrays of configuration
 * options as defined in an Objects.yaml file.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @proxy disable
 */
class ConfigurationBuilder {

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Traverses through the given class and interface names and builds a base object configuration
	 * for all of them. Then parses the provided extra configuration and merges the result
	 * into the overall configuration. Finally autowires dependencies of arguments and properties
	 * which can be resolved automatically.
	 *
	 * @param array $availableClassNames An array of available class names
	 * @param array $rawObjectconfigurationsByPackages An array of package keys and their raw (ie. unparsed) object configurations
	 * @return array<F3\FLOW3\Object\Configuration\Configuration> Object configurations
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildObjectConfigurations(array $availableClassNames, array $rawObjectConfigurationsByPackages) {
		$objectConfigurations = array();

		foreach ($availableClassNames as $className) {
			$objectName = $className;

			if ($this->reflectionService->isClassFinal($className)) {
				continue;
			}

			if (interface_exists($className)) {
				$interfaceName = $className;
				$className = $this->reflectionService->getDefaultImplementationClassNameForInterface($interfaceName);
				if ($className === FALSE) {
					continue;
				}
				if ($this->reflectionService->isClassTaggedWith($interfaceName, 'scope')) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException(sprintf('@scope annotations in interfaces don\'t have any effect, therefore you better remove it from %s in order to avoid confusion.', $interfaceName), 1299095595);
				}
			}

			$rawObjectConfiguration = array('className' => $className);
			$rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($className, $rawObjectConfiguration);
			$objectConfigurations[$objectName] = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'automatically registered class');
		}

		foreach ($rawObjectConfigurationsByPackages as $packageKey => $rawObjectConfigurations) {
			foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
				$objectName = str_replace('_', '\\', $objectName);
				if (!is_array($rawObjectConfiguration)) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Configuration of object "' . $objectName . '" in package "' . $packageKey. '" is not an array, please check your Objects.yaml for syntax errors.', 1295954338);
				}

				$existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : NULL;
				if (isset($rawObjectConfiguration['className'])) {
					$rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($rawObjectConfiguration['className'], $rawObjectConfiguration);
				}
				$newObjectConfiguration = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'configuration of package ' . $packageKey . ', definition for object "' . $objectName . '"', $existingObjectConfiguration);

				if (!isset($objectConfigurations[$objectName]) && !interface_exists($objectName, TRUE) && !class_exists($objectName, FALSE)) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey. '". Please check your Objects.yaml.', 1184926175);
				}

				if ($objectName !== $newObjectConfiguration->getClassName() && !interface_exists($objectName, TRUE)) {
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Tried to set a differing class name for class "' . $objectName . '" in the object configuration of package "' . $packageKey . '". Setting "className" is only allowed for interfaces, please check your Objects.yaml."', 1295954589);
				}

				$objectConfigurations[$objectName] = $newObjectConfiguration;
			}
		}

		$this->autowireArguments($objectConfigurations);
		$this->autowireProperties($objectConfigurations);

		return $objectConfigurations;
	}

	/**
	 * Builds a raw configuration array by parsing possible scope and autowiring annotations from the given class or
	 * interface.
	 *
	 * @param  $className
	 * @return array
	 */
	protected function enhanceRawConfigurationWithAnnotationOptions($className, array $rawObjectConfiguration) {
		if ($this->reflectionService->isClassTaggedWith($className, 'scope')) {
			$rawObjectConfiguration['scope'] = implode('', $this->reflectionService->getClassTagValues($className, 'scope'));
		}
		if ($this->reflectionService->isClassTaggedWith($className, 'autowiring')) {
			$rawObjectConfiguration['autowiring'] = implode('', $this->reflectionService->getClassTagValues($className, 'autowiring'));
		}
		return $rawObjectConfiguration;
	}

	/**
	 * Builds an object configuration object from a generic configuration container.
	 *
	 * @param string $objectName Name of the object
	 * @param array configurationArray The configuration array with options for the object configuration
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @param \F3\FLOW3\Object\Configuration\Configuration existingObjectConfiguration If set, this object configuration object will be used instead of creating a fresh one
	 * @return \F3\FLOW3\Object\Configuration\Configuration The object configuration object
	 * @author Robert Lemke <robert@typo3.org>
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
							if (isset($propertyValue['value'])) {
								$property = new ConfigurationProperty($propertyName, $propertyValue['value'], ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE);
							} elseif (isset($propertyValue['object'])) {
								$property = $this->parsePropertyOfTypeObject($propertyName, $propertyValue['object'], $configurationSourceHint);
							} elseif (isset($propertyValue['setting'])) {
								$property = new ConfigurationProperty($propertyName, $propertyValue['setting'], ConfigurationProperty::PROPERTY_TYPES_SETTING);
							} else {
								throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for property "' . $propertyName . '", instead found "' . (is_array($propertyValue) ? implode(', ', array_keys($propertyValue)) : $propertyValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563249);
							}
							$objectConfiguration->setProperty($property);
						}
					}
				break;
				case 'arguments':
					if (is_array($optionValue)) {
						foreach ($optionValue as $argumentName => $argumentValue) {
							if (isset($argumentValue['value'])) {
								$argument = new ConfigurationArgument($argumentName, $argumentValue['value'], ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
							} elseif (isset($argumentValue['object'])) {
								$argument = $this->parseArgumentOfTypeObject($argumentName, $argumentValue['object'], $configurationSourceHint);
							} elseif (isset($argumentValue['setting'])) {
								$argument = new ConfigurationArgument($argumentName, $argumentValue['setting'], ConfigurationArgument::ARGUMENT_TYPES_SETTING);
							} else {
								throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for argument "' . $argumentName . '", instead found "' . (is_array($argumentValue) ? implode(', ', array_keys($argumentValue)) : $argumentValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563250);
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
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid configuration option "' . $optionName . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1167574981);
			}
		}
		return $objectConfiguration;
	}

	/**
	 * Parses the value of the option "scope"
	 *
	 * @param  string $value Value of the option
	 * @return integer The scope translated into a scope constant
	 * @throws \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException if an invalid scope has been specified
	 * @author Robert Lemke <robert@typo3.org>
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
				throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid scope "' . $value . '"', 1167574991);
		}
	}

	/**
	 * Parses the value of the option "autowiring"
	 *
	 * @param  string $value Value of the option
	 * @return boolean The autowiring option translated into a boolean
	 * @throws \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException if an invalid option has been specified
	 * @author Robert Lemke <robert@typo3.org>
	 */
	static protected function parseAutowiring($value) {
		switch ($value) {
			case 'on':
			case Configuration::AUTOWIRING_MODE_ON:
				return Configuration::AUTOWIRING_MODE_ON;
			case 'off':
			case Configuration::AUTOWIRING_MODE_OFF:
				return Configuration::AUTOWIRING_MODE_OFF;
			default:
				throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Invalid autowiring declaration', 1283866757);
		}
	}

	/**
	 * Parses the configuration for properties of type OBJECT
	 *
	 * @param string $propertyName Name of the property
	 * @param mixed $objectNameOrConfiguration Value of the "object" section of the property configuration - either a string or an array
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \F3\FLOW3\Object\Configuration\ConfigurationProperty A configuration property of type object
	 * @author Robert Lemke <robert@typo3.org>
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
					throw new \F3\FLOW3\Object\Exception\InvalidObjectConfigurationException('Object configuration for property "' . $propertyName . '" contains neither object name nor factory object name in '. $configurationSourceHint, 1297097815);
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
	 * @param string configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
	 * @return \F3\FLOW3\Object\Configuration\ConfigurationArgument A configuration argument of type object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function parseArgumentOfTypeObject($argumentName, $objectNameOrConfiguration, $configurationSourceHint) {
		if (is_array($objectNameOrConfiguration)) {
			$objectName = $objectNameOrConfiguration['name'];
			unset($objectNameOrConfiguration['name']);
			$objectConfiguration = $this->parseConfigurationArray($objectName, $objectNameOrConfiguration, $configurationSourceHint . ', argument "' . $argumentName .'"');
			$argument = new ConfigurationArgument($argumentName,  $objectConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		} else {
			$argument = new ConfigurationArgument($argumentName,  $objectNameOrConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
		}
		return $argument;
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire
	 * them if possible.
	 *
	 * @param array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
							if ($defaultValue !== NULL) {
								$arguments[$index] = new ConfigurationArgument($index, $defaultValue, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
							}
						} elseif ($parameterInformation['class'] !== NULL && isset($objectConfigurations[$parameterInformation['class']])) {
							$arguments[$index] = new ConfigurationArgument($index, $parameterInformation['class'], ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
						} elseif ($parameterInformation['allowsNull'] === TRUE) {
							$arguments[$index] = new ConfigurationArgument($index, NULL, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
						} elseif (interface_exists($parameterInformation['class'])) {
							$debuggingHint = sprintf('No default implementation for the required interface %s was configured, therefore no specific class name could be used for this dependency. ', $parameterInformation['class']);
						}

						$methodTagsAndValues = $this->reflectionService->getMethodTagsValues($className, '__construct');
						if (isset ($arguments[$index]) && ($objectConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF
								|| isset($methodTagsAndValues['autowiring']) && $methodTagsAndValues['autowiring'] === array('off'))) {
							$arguments[$index]->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
							$arguments[$index]->set($index, NULL);
						}

						if (!isset($arguments[$index]) && $objectConfiguration->getScope() === Configuration::SCOPE_SINGLETON) {
							throw new \F3\FLOW3\Object\Exception\UnresolvedDependenciesException(sprintf('Could not autowire required constructor argument $%s for singleton class %s. %sCheck the type hint of that argument and your Objects.yaml configuration.', $parameterName, $className, $debuggingHint), 1298629392);
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
	 * @param array
	 * @return void
	 * @throws \F3\FLOW3\Object\Exception\CannotBuildObjectException if a required property could not be autowired.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autowireProperties(array &$objectConfigurations) {
		foreach ($objectConfigurations as $objectConfiguration) {
			$className = $objectConfiguration->getClassName();
			$properties = $objectConfiguration->getProperties();

			foreach (get_class_methods($className) as $methodName) {
				if (substr($methodName, 0, 6) === 'inject') {
					$propertyName = strtolower(substr($methodName, 6, 1)) . substr($methodName, 7);

					$methodTagsAndValues = $this->reflectionService->getMethodTagsValues($className, $methodName);
					if (isset($methodTagsAndValues['autowiring']) && $methodTagsAndValues['autowiring'] === array('off')) {
						continue;
					}

					if ($methodName === 'injectSettings') {
						$classNameParts = explode('\\', $className);
						if (count($classNameParts) > 1) {
							$properties[$propertyName] = new ConfigurationProperty($propertyName, $classNameParts[1], ConfigurationProperty::PROPERTY_TYPES_SETTING);
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

			foreach ($this->reflectionService->getPropertyNamesByTag($className, 'inject') as $propertyName) {
				if (!array_key_exists($propertyName, $properties)) {
					$objectName = trim(implode('', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
					$properties[$propertyName] =  new ConfigurationProperty($propertyName, $objectName, ConfigurationProperty::PROPERTY_TYPES_OBJECT);
				}
			}

			$objectConfiguration->setProperties($properties);
		}
	}
}
?>