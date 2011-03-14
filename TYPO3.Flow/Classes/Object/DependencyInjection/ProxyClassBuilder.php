<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\DependencyInjection;

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
 * A Proxy Class Builder which integrates Dependency Injection.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @proxy disable
 */
class ProxyClassBuilder {

	/**
	 * @var F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Object\Proxy\Compiler
	 */
	protected $compiler;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \F3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array<\F3\FLOW3\Object\Configuration\Configuration>
	 */
	protected $objectConfigurations;

	/**
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \F3\FLOW3\Object\Proxy\Compiler $compiler
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectCompiler(\F3\FLOW3\Object\Proxy\Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * @param \F3\FLOW3\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
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
	 * @param \F3\FLOW3\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Analyzes the Object Configuration provided by the compiler and builds the necessary PHP code for the proxy classes
	 * to realize dependency injection.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build() {
		$this->objectConfigurations = $this->objectManager->getObjectConfigurations();

		foreach ($this->objectConfigurations as $objectName => $objectConfiguration) {
			$className = $objectConfiguration->getClassName();
			if ($this->compiler->hasCacheEntryForClass($className) === TRUE) {
				continue;
			}

			if ($objectName !== $className || $this->reflectionService->isClassAbstract($className) || $this->reflectionService->isClassFinal($className)) {
				continue;
			}
			$proxyClass = $this->compiler->getProxyClass($className);
			if ($proxyClass === FALSE) {
				continue;
			}

			$constructorPreCode = '';
			$constructorPostCode = '';

			$constructorPreCode .= $this->buildSetInstanceCode($objectConfiguration);
			$constructorPreCode .= $this->buildConstructorInjectionCode($objectConfiguration);

			$wakeupMethod = $proxyClass->getMethod('__wakeup');
			$wakeupMethod->addPreParentCallCode($this->buildSetInstanceCode($objectConfiguration));
			$wakeupMethod->addPostParentCallCode($this->buildLifecycleInitializationCode($objectConfiguration, \F3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED));

			$injectPropertiesCode = $this->buildPropertyInjectionCode($objectConfiguration);
			if ($injectPropertiesCode !== '') {
				$constructorPostCode .= '		$this->FLOW3_Proxy_injectProperties();' . "\n";
				$proxyClass->getMethod('FLOW3_Proxy_injectProperties')->addPreParentCallCode($injectPropertiesCode);
				$wakeupMethod->addPreParentCallCode("		\$this->FLOW3_Proxy_injectProperties();\n");
			}

			$constructorPostCode .= $this->buildLifecycleInitializationCode($objectConfiguration, \F3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);

			$constructor = $proxyClass->getConstructor();
			$constructor->addPreParentCallCode($constructorPreCode);
			$constructor->addPostParentCallCode($constructorPostCode);
		}
	}

	/**
	 * Renders additional code for the constructor which registers the instance of the proxy class at the Object Manager
	 * before constructor injection is executed.
	 *
	 * This also makes sure that object creation does not end in an endless loop due to bi-directional dependencies.
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildSetInstanceCode(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		if ($objectConfiguration->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
			return '';
		}

		$code = '		\F3\FLOW3\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $objectConfiguration->getObjectName() . '\', $this);' . "\n";

		$className = $objectConfiguration->getClassName();
		foreach ($this->objectConfigurations as $otherObjectConfiguration) {
			if	 ($otherObjectConfiguration !== $objectConfiguration && $otherObjectConfiguration->getClassName() === $className) {
				$code .= '		\F3\FLOW3\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $otherObjectConfiguration->getObjectName() . '\', $this);' . "\n";
			}
		}


		return $code;
	}

	/**
	 * Renders additional code for the __construct() method of the Proxy Class which realizes constructor injection.
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @return string The built code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildConstructorInjectionCode(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$assignments = array();

		$argumentConfigurations = $objectConfiguration->getArguments();

		foreach ($argumentConfigurations as $argumentNumber => $argumentConfiguration) {
			if ($argumentConfiguration === NULL) {
				continue;
			}
			$argumentValue = $argumentConfiguration->getValue();
			if ($argumentValue !== NULL) {
				$assignmentPrologue = 'if (!isset($arguments[' . ($argumentNumber - 1) . '])) $arguments[' . ($argumentNumber - 1) . '] = ';

				switch ($argumentConfiguration->getType()) {
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$assignments[] = $assignmentPrologue . 'new \\' . $argumentValueObjectName . '(' . $this->buildMethodParametersCode($argumentValue->getArguments()) . ')';
							} else {
								$assignments[] = $assignmentPrologue . '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
							}
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array_shift($settingPath));
								$argumentValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
							}
							if (!isset($this->objectConfigurations[$argumentValue])) {
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $argumentValue . '" which was specified as an argument in the object configuration of object "' . $objectConfiguration->getObjectName() . '" does not exist.', 1264669967);
							}
							$assignments[] = $assignmentPrologue . '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
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
		$code = count($assignments) > 0 ? "\n\t\t" . implode(";\n\t\t", $assignments) . ";\n" : '';

		$index = 0;
		foreach($this->reflectionService->getMethodParameters($objectConfiguration->getClassName(), '__construct') as $parameterName => $parameterInfo) {
			if ($parameterInfo['optional'] === TRUE) {
				break;
			}
			if ($objectConfiguration->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_SINGLETON) {
				$code .= '		if (!isset($arguments[' . $index . '])) throw new \F3\FLOW3\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. ' . 'Please check your calling code and Dependency Injection configuration.\', 1296143787);' . "\n";
			} else {
				$code .= '		if (!isset($arguments[' . $index . '])) throw new \F3\FLOW3\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. ' . 'Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.\', 1296143788);' . "\n";
			}
			$index ++;
		}

		return $code;
	}

	/**
	 * Builds the code necessary to inject setter based dependencies.
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration (needed to produce helpful exception message)
	 * @return string The built code
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildPropertyInjectionCode(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration) {
		$commands = array();
		$className = $objectConfiguration->getClassName();
		$objectName = $objectConfiguration->getObjectName();

		foreach ($objectConfiguration->getProperties() as $propertyName => $propertyConfiguration) {
			if ($propertyConfiguration->getAutowiring() === \F3\FLOW3\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF) {
				continue;
			}

			$propertyValue = $propertyConfiguration->getValue();
			switch ($propertyConfiguration->getType()) {
				case \F3\FLOW3\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT:
					if ($propertyValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
						$propertyClassName = $propertyValue->getClassName();
						if ($propertyClassName === NULL) {
							$preparedSetterArgument = $this->buildCustomFactoryCall($propertyValue->getFactoryObjectName(), $propertyValue->getFactoryMethodName(), $propertyValue->getArguments());
						} else {
							if (!is_string($propertyClassName) || !isset($this->objectConfigurations[$propertyClassName])) {
								$configurationSource = $objectConfiguration->getConfigurationSourceHint();
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Unknown class "' . $propertyClassName . '", specified as property "' . $propertyName . '" in the object configuration of object "' . $objectName . '" (' . $configurationSource . ').', 1296130876);
							}
							if ($this->objectConfigurations[$propertyClassName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($propertyValue->getArguments()) . ')';
							} else {
								$preparedSetterArgument = '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyClassName . '\')';
							}
						}
					} else {
						if (strpos($propertyValue, '.') !== FALSE) {
							$settingPath = explode('.', $propertyValue);
							$settings = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, array_shift($settingPath));
							$propertyValue = \F3\FLOW3\Utility\Arrays::getValueByPath($settings, $settingPath);
						}
						if (!isset($this->objectConfigurations[$propertyValue])) {
							$configurationSource = $objectConfiguration->getConfigurationSourceHint();
							if ($propertyValue[0] === '\\') {
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object name "' . $propertyValue . '" which was specified as a property in the object configuration of object "' . $objectName . '" (' . $configurationSource . ') starts with a leading backslash.', 1277827579);
							} else {
								throw new \F3\FLOW3\Object\Exception\UnknownObjectException('The object "' . $propertyValue . '" which was specified as a property in the object configuration of object "' . $objectName . '" (' . $configurationSource . ') does not exist. Check for spelling mistakes and if that dependency is correctly configured.', 1265213849);
							}
						}
						$propertyClassName = $this->objectConfigurations[$propertyValue]->getClassName();
						if ($this->objectConfigurations[$propertyValue]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
							$preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($this->objectConfigurations[$propertyValue]->getArguments()) . ')';
						} else {
							$preparedSetterArgument = '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyValue . '\')';
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
					$preparedSetterArgument = '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'F3\FLOW3\Configuration\ConfigurationManager\')->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, \'' . $propertyValue . '\')';
				break;
			}
			$setterMethodName = 'inject' . ucfirst($propertyName);
			if ($this->reflectionService->hasMethod($className, $setterMethodName)) {
				$commands[] = "\$this->$setterMethodName($preparedSetterArgument);";
				continue;
			}
			$setterMethodName = 'set' . ucfirst($propertyName);
			if ($this->reflectionService->hasMethod($className, $setterMethodName)) {
				$commands[] = "\$this->$setterMethodName($preparedSetterArgument);";
				continue;
			}
			if (property_exists($className, $propertyName)) {
				$commands[] = "\$this->$propertyName = $preparedSetterArgument;";
			}
		}
		return count($commands) > 0 ? "\t\t" . implode("\n\t\t", $commands) . "\n" : '';
	}

	/**
	 * Builds code which calls the lifecycle initialization method, if any.
	 *
	 * @param \F3\FLOW3\Object\Configuration\Configuration $objectConfiguration
	 * @param integer $cause a \F3\FLOW3\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_* constant which is the cause of the initialization command being called.
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildLifecycleInitializationCode(\F3\FLOW3\Object\Configuration\Configuration $objectConfiguration, $cause) {
		$lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
		if (!$this->reflectionService->hasMethod($objectConfiguration->getClassName(), $lifecycleInitializationMethodName)) {
			return '';
		}
		return "\n" . '		$this->' . $lifecycleInitializationMethodName . '(' . $cause . ');' . "\n";
	}

	/**
	 * FIXME: Not yet completely refactored to new proxy mechanism
	 *
	 * @param array $argumentConfigurations
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildMethodParametersCode(array $argumentConfigurations) {
		$preparedArguments = array();

		foreach ($argumentConfigurations as $argument) {
			if ($argument === NULL) {
				$preparedArguments[] = 'NULL';
			} else {
				$argumentValue = $argument->getValue();

				switch ($argument->getType()) {
					case \F3\FLOW3\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \F3\FLOW3\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \F3\FLOW3\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedArguments[] = '$this->getPrototype(\'' . $argumentValueObjectName . '\', array(' . $this->buildMethodParametersCode($argumentValue->getArguments(), $this->objectConfigurations) . '))';
							} else {
								$preparedArguments[] = '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
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
								$preparedArguments[] = '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
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
			}
		}
		return implode(', ', $preparedArguments);
	}

	/**
	 * @param string $customFactoryObjectName
	 * @param string $customFactoryMethodName
	 * @param array $arguments
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function buildCustomFactoryCall($customFactoryObjectName, $customFactoryMethodName, array $arguments) {
		$parametersCode = $this->buildMethodParametersCode($arguments);
		return '\F3\FLOW3\Core\Bootstrap::$staticObjectManager->get(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $parametersCode . ')';
	}

}
?>
