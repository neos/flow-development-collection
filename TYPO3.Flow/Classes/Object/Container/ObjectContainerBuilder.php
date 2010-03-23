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
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ObjectContainerBuilder {

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService A reference to the reflection service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $createdMethodNumbers = array();

	/**
	 * @var array
	 */
	protected $objectConfigurations;

	/**
	 * Injects the Reflection Service
	 *
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService The Reflection Service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
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
	 * Generates PHP code of a static object container reflecting the given object
	 * configurations.
	 *
	 * @param array $objectConfigurations
	 * @return string The static object container class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildObjectContainer(array $objectConfigurations) {
		$this->objectConfigurations = $objectConfigurations;

		$tokens = array(
			'OBJECTS_ARRAY' => $this->buildObjectsArray(),
			'BUILD_METHODS' => $this->buildBuildMethods(),
			'FLOW3_REVISION' => \F3\FLOW3\Core\Bootstrap::REVISION
		);

		$objectContainerCode = file_get_contents(FLOW3_PATH_FLOW3 . 'Resources/Private/Object/StaticObjectContainerTemplate.phpt');
		foreach ($tokens as $token => $value) {
			$objectContainerCode = str_replace('###' . $token . '###', $value, $objectContainerCode);
		}
		return $objectContainerCode;
	}

	/**
	 * Builds the PHP code of the container's objects array which contains information
	 * about the registered objects, their scope, class, built method etc.
	 *
	 * @return string PHP code of the objects array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildObjectsArray() {
		$objectsArrayCode = '';
		$i = 0;
		foreach ($this->objectConfigurations as $objectConfiguration) {
			$objectName = $objectConfiguration->getObjectName();
			$classNameAssignment = ($objectConfiguration->getClassName() !== $objectName) ? "'c'=>'" . $objectConfiguration->getClassName() . "'," : '';
			$lowercasedObjectName = strtolower ($objectName);
			$scope = $objectConfiguration->getScope();
			$this->createdMethodNumbers[$objectName] = str_pad($i, 4, '0', STR_PAD_LEFT);

			$objectsArrayCode .= "\n\t\t'$objectName'=>array('l'=>'$lowercasedObjectName',$classNameAssignment's'=>$scope,'m'=>'" . $this->createdMethodNumbers[$objectName] . "'),";
			$i++;
		}
		return substr($objectsArrayCode, 0, -1) . "\n\t";
	}

	/**
	 * Generates the PHP code of the various build methods for the registered
	 * objects.
	 *
	 * @return string PHP code of the build methods
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildBuildMethods() {
		$buildMethodsCode = '';
		foreach ($this->objectConfigurations as $objectConfiguration) {
			$objectName = $objectConfiguration->getObjectName();
			$className = $objectConfiguration->getClassName();
			$customFactoryObjectName = $objectConfiguration->getFactoryObjectName();
			$customFactoryMethodName = $objectConfiguration->getFactoryMethodName();

			if ($customFactoryObjectName === NULL) {
				if (interface_exists($className) === TRUE ) continue;
				if (class_exists($className) === FALSE) throw new \F3\FLOW3\Object\Exception\UnknownClassException('Class "' . $className . '" which was specified in the object configuration of object "' . $objectConfiguration->getObjectName() . '" does not exist.', 1264590458);
			}

			$setterProperties = $objectConfiguration->getProperties();
			$arguments = $objectConfiguration->getArguments();

			if ($objectConfiguration->getAutowiring() === \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_ON && $className !== NULL) {
				$arguments = $this->autowireArguments($arguments, $className);
				$setterProperties = $this->autowireProperties($setterProperties, $className);
			}

			$methodNameNumber = $this->createdMethodNumbers[$objectConfiguration->getObjectName()];
			$methodArguments = (TRUE || count($arguments) > 0) ? '$a=array()' : '';
			$createInstanceArgumentsAssignments = $this->buildCreateInstanceArgumentsAssignments($arguments);
			$propertyInjectionCommands = $this->buildPropertyInjectionCommands($objectName, $className, $setterProperties);
			$createInstanceCommand = $this->buildCreateInstanceCommand($className, $arguments, $customFactoryObjectName, $customFactoryMethodName);
			$recreateInstanceCommand = $this->buildRecreateInstanceCommand($className);

			$lifecycleInitializationCommand = $this->buildLifecycleInitializationCommand($objectConfiguration);
			$lifecycleShutdownRegistrationCommand = $this->buildLifecycleShutdownRegistrationCommand($objectConfiguration);

			$buildMethodsCode .= '
	protected function c' . $methodNameNumber . '(' . $methodArguments . ') {' .
		$createInstanceArgumentsAssignments . 
		$createInstanceCommand . '
		$this->i' . $methodNameNumber .'($o); ' .
		$lifecycleInitializationCommand .
		$lifecycleShutdownRegistrationCommand . '
		return $o;
	}
	protected function r' . $methodNameNumber .'() {' .
		$recreateInstanceCommand . '
		$this->i' . $methodNameNumber .'($o); ' .
		$lifecycleShutdownRegistrationCommand . '
		return $o;
	}
	protected function i' . $methodNameNumber . '($o) {' .
		$propertyInjectionCommands . '
	}';
		}
		return $buildMethodsCode;
	}

	/**
	 * If mandatory constructor arguments have not been defined yet, this function tries to autowire
	 * them if possible.
	 *
	 * @param array $arguments Array of \F3\FLOW3\Object\Configuration\ConfigurationArgument for the current object
	 * @param string $className Class name of the object object which contains the methods supposed to be analyzed
	 * @return array The modified array of \F3\FLOW3\Object\Configuration\ConfigurationArgument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autowireArguments(array $arguments, $className) {
		$constructorName = $this->reflectionService->getClassConstructorName($className);
		if ($constructorName !== NULL) {
			foreach ($this->reflectionService->getMethodParameters($className, $constructorName) as $parameterName => $parameterInformation) {
				$index = $parameterInformation['position'] + 1;
				if (!isset($arguments[$index])) {
					if ($parameterInformation['optional'] === TRUE) {
						$defaultValue = (isset($parameterInformation['defaultValue'])) ? $parameterInformation['defaultValue'] : NULL;
						$arguments[$index] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index, $defaultValue, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
					} elseif ($parameterInformation['class'] !== NULL && isset($this->objectConfigurations[$parameterInformation['class']])) {
						$arguments[$index] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index, $parameterInformation['class'], \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
					} elseif ($parameterInformation['allowsNull'] === TRUE) {
						$arguments[$index] = new \F3\FLOW3\Object\Configuration\ConfigurationArgument($index, NULL, \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
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
	 * @param array $setterProperties Array of \F3\FLOW3\Object\Configuration\ConfigurationProperty for the current object
	 * @param string $className Name of the class which contains the methods supposed to be analyzed
	 * @return array The modified array of \F3\FLOW3\Object\Configuration\ConfigurationProperty
	 * @throws \F3\FLOW3\Object\Exception\CannotBuildObjectException if a required property could not be autowired.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function autowireProperties(array $setterProperties, $className) {
		foreach (get_class_methods($className) as $methodName) {
			if (substr($methodName, 0, 6) === 'inject') {
				$propertyName = strtolower(substr($methodName, 6, 1)) . substr($methodName, 7);
				if ($methodName === 'injectSettings') {
					$classNameParts = explode('\\', $className);
					if (count($classNameParts) > 1) {
						$setterProperties[$propertyName] = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $classNameParts[1], \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING);
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
					$setterProperties[$propertyName] = new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $methodParameter['class'], \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT);
				}
			}
		}

		foreach ($this->reflectionService->getPropertyNamesByTag($className, 'inject') as $propertyName) {
			if (!array_key_exists($propertyName, $setterProperties)) {
				$objectName = trim(implode('', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
				$setterProperties[$propertyName] =  new \F3\FLOW3\Object\Configuration\ConfigurationProperty($propertyName, $objectName, \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT);
			}
		}
		return $setterProperties;
	}

	/**
	 *
	 * @param array $arguments
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildCreateInstanceArgumentsAssignments(array $arguments) {
		$assignments = array();

		foreach ($arguments as $argument) {
			$argumentValue = $argument->getValue();
			if ($argumentValue !== NULL) {
				$index = $argument->getIndex() - 1;
				$assignmentPrologue = 'if (!isset($a[' . $index . '])) $a[' . $index . '] = ';

				switch ($argument->getType()) {
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$assignments[] = $assignmentPrologue . '$this->getPrototype(\'' . $argumentValueObjectName . '\', array(' . $this->buildMethodParametersCode($argumentValue->getArguments()) . '))';
							} else {
								$assignments[] = $assignmentPrologue . '$this->getSingleton(\'' . $argumentValueObjectName . '\')';
							}
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array_shift($settingPath));
								$argumentValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
							}
							if (!isset($this->objectConfigurations[$argumentValue])) {
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $argumentValue . '" which was specified as an argument in the object configuration of object "X" does not exist.', 1264669967);
							}
							if ($this->objectConfigurations[$argumentValue]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$assignments[] = $assignmentPrologue . '$this->getPrototype(\'' . $argumentValue . '\')';
							} else {
								$assignments[] = $assignmentPrologue . '$this->getSingleton(\'' . $argumentValue . '\')';
							}
						}
					break;

					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$assignments[] = $assignmentPrologue . var_export($argumentValue, TRUE);
					break;

					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						if (strpos($argumentValue, '.') !== FALSE) {
							$settingPath = explode('.', $argumentValue);
							$assignments[] = $assignmentPrologue . 'isset($this->settings[\'' . implode('\'][\'', $settingPath) . '\']) ? $this->settings[\'' . implode('\'][\'', $settingPath) . '\'] : array()';
						} else {
							$assignments[] = $assignmentPrologue . 'isset($this->settings[\''. $argumentValue . '\']) ? $this->settings[\''. $argumentValue . '\'] : array()';
						}
					break;
				}
			}
		}
		return count($assignments) > 0 ? "\n\t\t" . implode(";\n\t\t", $assignments) . ";" : '';
	}

	/**
	 *
	 * @param string $objectName
	 * @param string $className
	 * @param array $properties
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildPropertyInjectionCommands($objectName, $className, array $properties) {
		$commands = array();

		foreach ($properties as $propertyName => $property) {
			$propertyValue = $property->getValue();
			switch ($property->getType()) {
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT:
					if ($propertyValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
						$propertyObjectName = $propertyValue->getObjectName();
						if ($propertyObjectName !== NULL) {
							if ($this->objectConfigurations[$propertyObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedSetterArgument = '$this->getPrototype(\'' . $propertyObjectName . '\', array(' . $this->buildMethodParametersCode($propertyValue->getArguments()) . '))';
							} else {
								$preparedSetterArgument = '$this->getSingleton(\'' . $propertyObjectName . '\')';
							}
						} else {
							$preparedSetterArgument = $this->buildCustomFactoryCall($propertyValue->getFactoryObjectName(), $propertyValue->getFactoryMethodName(), $propertyValue->getArguments());
						}
					} else {
						if (strpos($propertyValue, '.') !== FALSE) {
							$settingPath = explode('.', $propertyValue);
							$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array_shift($settingPath));
							$propertyValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
						}
						if (!isset($this->objectConfigurations[$propertyValue])) {
							throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $propertyValue . '" which was specified as a property in the object configuration of object "' . $objectName . '" does not exist.', 1265213849);
						}
						if ($this->objectConfigurations[$propertyValue]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
							$preparedSetterArgument = '$this->getPrototype(\'' . $propertyValue . '\', array(' . $this->buildMethodParametersCode($this->objectConfigurations[$propertyValue]->getArguments()) . '))';
						} else {
							$preparedSetterArgument = '$this->getSingleton(\'' . $propertyValue . '\')';
						}
					}
				break;
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					if (is_string($propertyValue)) {
						$preparedSetterArgument = '\'' . str_replace('\'', '\\\'', $propertyValue) . '\'';
					} else {
						$preparedSetterArgument = $propertyValue;
					}
				break;
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING:
					$preparedSetterArgument = "isset(\$this->settings['" . str_replace('.', "']['", $propertyValue) . "']) ? \$this->settings['" . str_replace('.', "']['", $propertyValue) . "'] : array()";
				break;
			}
			$setterMethodName = 'inject' . ucfirst($propertyName);
			if (method_exists($className, $setterMethodName)) {
				$commands[] = "\$o->$setterMethodName($preparedSetterArgument);";
				continue;
			}
			$setterMethodName = 'set' . ucfirst($propertyName);
			if (method_exists($className, $setterMethodName)) {
				$commands[] = "\$o->$setterMethodName($preparedSetterArgument);";
				continue;
			}
			if (property_exists($className, $propertyName)) {
				$commands[] = "\$p = new \ReflectionProperty('$className', '$propertyName');";
				$commands[] = "\$p->setAccessible(TRUE);";
				$commands[] = "\$p->setValue(\$o, $preparedSetterArgument);";
			}
		}
		return count($commands) > 0 ? "\n\t\t" . implode("\n\t\t", $commands) : '';
	}

	/**
	 *
	 * @param string $className
	 * @param string $arguments
	 * @param string $customFactoryObjectName
	 * @param string $customFactoryMethodName
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildCreateInstanceCommand($className, $arguments, $customFactoryObjectName, $customFactoryMethodName) {
		if (!isset($this->objectConfigurations[$customFactoryObjectName])) {
			$argumentsCount = count($this->reflectionService->getMethodParameters($className, $this->reflectionService->getClassConstructorName($className)));
		} else {
			$argumentsCount = count($this->reflectionService->getMethodParameters($customFactoryObjectName, $customFactoryMethodName));
		}

		$argumentsCode = '';
		for ($i=0; $i < $argumentsCount; $i++) {
			$argumentsCode .= '$a[' . $i . '],';
		}
		$argumentsCode = substr($argumentsCode, 0, -1);

		if ($customFactoryObjectName === NULL) {
			$command = '$o=new \\' . $className . '(' . $argumentsCode . ');';
		} else {
			if (!isset($this->objectConfigurations[$customFactoryObjectName])) throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Factory "' . $customFactoryObjectName . '" which was specified in the object configuration of object "' . $objectName . '" does not exist.', 1264612860);
			if ($this->objectConfigurations[$customFactoryObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
				$command = '$o=$this->getPrototype(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $argumentsCode . ');';
			} else {
				$command = '$o=$this->getSingleton(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $argumentsCode . ');';
			}
		}
		if (method_exists($className, 'FLOW3_AOP_Proxy_construct')) {
			$command .= "\n\t\t\$o->FLOW3_AOP_Proxy_setProperty('FLOW3_AOP_Proxy_objectManager', \$this->get('F3\FLOW3\Object\ObjectManagerInterface'));";
			$command .= "\n\t\t\$o->FLOW3_AOP_Proxy_construct();";
		}
		return "\n\t\t$command";
	}

	/**
	 *
	 * @param string $className
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildRecreateInstanceCommand($className) {
		$command = "\n\t\t\$o = unserialize('O:" . strlen($className) . ":\"$className\":0:{};');";

		if (in_array('F3\FLOW3\AOP\ProxyInterface', class_implements($className))) {
			$command .= "\n\t\t\$o->FLOW3_AOP_Proxy_setProperty('FLOW3_AOP_Proxy_objectManager', \$this->get('F3\FLOW3\Object\ObjectManagerInterface'));";
			$command .= "\n\t\t\$o->FLOW3_AOP_Proxy_declareMethodsAndAdvices();";
		}
		return $command;
	}

	/**
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildLifecycleInitializationCommand(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$command = '';
		$lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
		if (method_exists($objectConfiguration->getClassName(), $lifecycleInitializationMethodName)) {
			$command = "\n\t\t\$o->$lifecycleInitializationMethodName();";
		}
		return $command;
	}

	/**
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildLifecycleShutdownRegistrationCommand(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$command = '';
		$lifecycleShutdownMethodName = $objectConfiguration->getLifecycleShutdownMethodName();
		if (method_exists($objectConfiguration->getClassName(), $lifecycleShutdownMethodName)) {
			$command = "\n\t\t\$this->shutdownObjects[\$o]='$lifecycleShutdownMethodName';";
		}
		return $command;
	}

	/**
	 * @param string $customFactoryObjectName
	 * @param string $customFactoryMethodName
	 * @param array $arguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildCustomFactoryCall($customFactoryObjectName, $customFactoryMethodName, array $arguments) {
		$argumentsCode = $this->buildMethodParametersCode($arguments);

		if ($this->objectConfigurations[$customFactoryObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
			$customFactoryCall = '$this->getPrototype(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $argumentsCode . ')';
		} else {
			$customFactoryCall = '$this->getSingleton(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $argumentsCode . ')';
		}
		return $customFactoryCall;
	}

	/**
	 *
	 * @param array $arguments
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildMethodParametersCode(array $arguments) {
		$preparedArguments = array();

		foreach ($arguments as $argument) {
			if ($argument !== NULL) {
				$argumentValue = $argument->getValue();

				switch ($argument->getType()) {
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedArguments[] = '$this->getPrototype(\'' . $argumentValueObjectName . '\', array(' . $this->buildMethodParametersCode($argumentValue->getArguments(), $this->objectConfigurations) . '))';
							} else {
								$preparedArguments[] = '$this->getSingleton(\'' . $argumentValueObjectName . '\')';
							}
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array_shift($settingPath));
								$argumentValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
							}
							if (!isset($this->objectConfigurations[$argumentValue])) {
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $argumentValue . '" which was specified as an argument in the object configuration of object "' . $objectconfiguration->getObjectName() . '" does not exist.', 1264669967);
							}
							if ($this->objectConfigurations[$argumentValue]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedArguments[] = 'X';
							} else {
								$preparedArguments[] = '$this->getSingleton(\'' . $argumentValue . '\')';
							}
						}
					break;

					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$preparedArguments[] = var_export($argumentValue, TRUE);
					break;

					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						if (strpos($argumentValue, '.') !== FALSE) {
							$settingPath = explode('.', $argumentValue);
							$preparedArguments[] = '$this->settings[\'' . implode('\'][\'', $settingPath) . '\']';
						} else {
							$preparedArguments[] = '$this->settings[\''. $argumentValue . '\']';
						}
					break;
				}
			} else {
				$preparedArguments[] = 'NULL';
			}
		}
		return implode(', ', $preparedArguments);
	}
}
?>