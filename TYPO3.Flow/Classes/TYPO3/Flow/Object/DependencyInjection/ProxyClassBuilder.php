<?php
namespace TYPO3\Flow\Object\DependencyInjection;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Utility\Arrays;
use TYPO3\Flow\Configuration\ConfigurationManager;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * A Proxy Class Builder which integrates Dependency Injection.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ProxyClassBuilder {

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\Flow\Object\Proxy\Compiler
	 */
	protected $compiler;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\Flow\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\Flow\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * @var array<\TYPO3\Flow\Object\Configuration\Configuration>
	 */
	protected $objectConfigurations;

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\Flow\Object\Proxy\Compiler $compiler
	 * @return void
	 */
	public function injectCompiler(\TYPO3\Flow\Object\Proxy\Compiler $compiler) {
		$this->compiler = $compiler;
	}

	/**
	 * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\Flow\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * @param \TYPO3\Flow\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Analyzes the Object Configuration provided by the compiler and builds the necessary PHP code for the proxy classes
	 * to realize dependency injection.
	 *
	 * @return void
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
			$this->systemLogger->log('Building DI proxy for "' . $className . '".', LOG_DEBUG);

			$constructorPreCode = '';
			$constructorPostCode = '';

			$constructorPreCode .= $this->buildSetInstanceCode($objectConfiguration);
			$constructorPreCode .= $this->buildConstructorInjectionCode($objectConfiguration);

			$wakeupMethod = $proxyClass->getMethod('__wakeup');
			$wakeupMethod->addPreParentCallCode($this->buildSetInstanceCode($objectConfiguration));
			$wakeupMethod->addPreParentCallCode($this->buildSetRelatedEntitiesCode());
			$wakeupMethod->addPostParentCallCode($this->buildLifecycleInitializationCode($objectConfiguration, \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED));
			$wakeupMethod->addPostParentCallCode($this->buildLifecycleShutdownCode($objectConfiguration));

			$sleepMethod = $proxyClass->getMethod('__sleep');
			$sleepMethod->addPostParentCallCode($this->buildSerializeRelatedEntitiesCode($objectConfiguration));

			$searchForEntitiesAndStoreIdentifierArrayMethod = $proxyClass->getMethod('searchForEntitiesAndStoreIdentifierArray');
			$searchForEntitiesAndStoreIdentifierArrayMethod->setMethodParametersCode('$path, $propertyValue, $originalPropertyName');
			$searchForEntitiesAndStoreIdentifierArrayMethod->overrideMethodVisibility('private');
			$searchForEntitiesAndStoreIdentifierArrayMethod->addPreParentCallCode($this->buildSearchForEntitiesAndStoreIdentifierArrayCode());

			$injectPropertiesCode = $this->buildPropertyInjectionCode($objectConfiguration);
			if ($injectPropertiesCode !== '') {
				$proxyClass->getMethod('Flow_Proxy_injectProperties')->addPreParentCallCode($injectPropertiesCode);
				$proxyClass->getMethod('Flow_Proxy_injectProperties')->overrideMethodVisibility('private');
				$wakeupMethod->addPreParentCallCode("		\$this->Flow_Proxy_injectProperties();\n");

				$constructorPostCode .= '		if (\'' . $className . '\' === get_class($this)) {' . "\n";
				$constructorPostCode .= '			$this->Flow_Proxy_injectProperties();' . "\n";
				$constructorPostCode .= '		}' . "\n";
			}

			$constructorPostCode .= $this->buildLifecycleInitializationCode($objectConfiguration, \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
			$constructorPostCode .= $this->buildLifecycleShutdownCode($objectConfiguration);

			$constructor = $proxyClass->getConstructor();
			$constructor->addPreParentCallCode($constructorPreCode);
			$constructor->addPostParentCallCode($constructorPostCode);
		}
	}

	/**
	 * Renders additional code which registers the instance of the proxy class at the Object Manager
	 * before constructor injection is executed. Used in constructors and wakeup methods.
	 *
	 * This also makes sure that object creation does not end in an endless loop due to bi-directional dependencies.
	 *
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 */
	protected function buildSetInstanceCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration) {
		if ($objectConfiguration->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
			return '';
		}

		$code = '		if (get_class($this) === \'' . $objectConfiguration->getClassName() . '\') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $objectConfiguration->getObjectName() . '\', $this);' . "\n";

		$className = $objectConfiguration->getClassName();
		foreach ($this->objectConfigurations as $otherObjectConfiguration) {
			if	 ($otherObjectConfiguration !== $objectConfiguration && $otherObjectConfiguration->getClassName() === $className) {
				$code .= '		if (get_class($this) === \'' . $otherObjectConfiguration->getClassName() . '\') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $otherObjectConfiguration->getObjectName() . '\', $this);' . "\n";
			}
		}

		return $code;
	}

	/**
	 * Renders code to set related entities in an object from identifier/type information.
	 * Used in wakeup methods.
	 *
	 * @return string
	 */
	protected function buildSetRelatedEntitiesCode() {
		return "
	if (property_exists(\$this, 'Flow_Persistence_RelatedEntities') && is_array(\$this->Flow_Persistence_RelatedEntities)) {
		\$persistenceManager = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface');
		foreach (\$this->Flow_Persistence_RelatedEntities as \$entityInformation) {
			\$entity = \$persistenceManager->getObjectByIdentifier(\$entityInformation['identifier'], \$entityInformation['entityType'], TRUE);
			if (isset(\$entityInformation['entityPath'])) {
				\$this->\$entityInformation['propertyName'] = \\TYPO3\\Flow\\Utility\\Arrays::setValueByPath(\$this->\$entityInformation['propertyName'], \$entityInformation['entityPath'], \$entity);
			} else {
				\$this->\$entityInformation['propertyName'] = \$entity;
			}
		}
		unset(\$this->Flow_Persistence_RelatedEntities);
	}
		";
	}

	/**
	 * Renders code to create identifier/type information from related entities in an object.
	 * Used in sleep methods.
	 *
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 */
	protected function buildSerializeRelatedEntitiesCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration) {
		$className = $objectConfiguration->getClassName();
		$code = '';
		if ($this->reflectionService->hasMethod($className, '__sleep') === FALSE) {

			$code = "\t\t\$this->Flow_Object_PropertiesToSerialize = array();
	\$reflectionService = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Reflection\\ReflectionService');
	\$reflectedClass = new \\ReflectionClass('".$className."');
	\$allReflectedProperties = \$reflectedClass->getProperties();
	foreach (\$allReflectedProperties as \$reflectionProperty) {
		\$propertyName = \$reflectionProperty->name;
		if (in_array(\$propertyName, array('Flow_Aop_Proxy_targetMethodsAndGroupedAdvices', 'Flow_Aop_Proxy_groupedAdviceChains', 'Flow_Aop_Proxy_methodIsInAdviceMode'))) continue;
		if (\$reflectionService->isPropertyTaggedWith('".$className."', \$propertyName, 'transient')) continue;
		if (is_array(\$this->\$propertyName) || (is_object(\$this->\$propertyName) && (\$this->\$propertyName instanceof \\ArrayObject || \$this->\$propertyName instanceof \\SplObjectStorage ||\$this->\$propertyName instanceof \\Doctrine\\Common\\Collections\\Collection))) {
			foreach (\$this->\$propertyName as \$key => \$value) {
				\$this->searchForEntitiesAndStoreIdentifierArray((string)\$key, \$value, \$propertyName);
			}
		}
		if (is_object(\$this->\$propertyName) && !\$this->\$propertyName instanceof \\Doctrine\\Common\\Collections\\Collection) {
			if (\$this->\$propertyName instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
				\$className = get_parent_class(\$this->\$propertyName);
			} else {
				\$className = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->getObjectNameByClassName(get_class(\$this->\$propertyName));
			}
			if (\$this->\$propertyName instanceof \\TYPO3\\Flow\\Persistence\\Aspect\\PersistenceMagicInterface && !\\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->isNewObject(\$this->\$propertyName) || \$this->\$propertyName instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
				if (!property_exists(\$this, 'Flow_Persistence_RelatedEntities') || !is_array(\$this->Flow_Persistence_RelatedEntities)) {
					\$this->Flow_Persistence_RelatedEntities = array();
					\$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
				}
				\$identifier = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->getIdentifierByObject(\$this->\$propertyName);
				if (!\$identifier && \$this->\$propertyName instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
					\$identifier = current(\\TYPO3\\Flow\\Reflection\\ObjectAccess::getProperty(\$this->\$propertyName, '_identifier', TRUE));
				}
				\$this->Flow_Persistence_RelatedEntities[\$propertyName] = array(
					'propertyName' => \$propertyName,
					'entityType' => \$className,
					'identifier' => \$identifier
				);
				continue;
			}
			if (\$className !== FALSE && \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->getScope(\$className) === \\TYPO3\\Flow\\Object\\Configuration\\Configuration::SCOPE_SINGLETON) {
				continue;
			}
		}
		\$this->Flow_Object_PropertiesToSerialize[] = \$propertyName;
	}
	\$result = \$this->Flow_Object_PropertiesToSerialize;\n";
		}
		return $code;
	}

	/**
	 * Renders the code needed to serialize entities that are inside an array or SplObjectStorage
	 *
	 * @return string
	 */
	protected function buildSearchForEntitiesAndStoreIdentifierArrayCode() {
		$code = "
		if (is_array(\$propertyValue) || (is_object(\$propertyValue) && (\$propertyValue instanceof \\ArrayObject || \$propertyValue instanceof \\SplObjectStorage))) {
			foreach (\$propertyValue as \$key => \$value) {
				\$this->searchForEntitiesAndStoreIdentifierArray(\$path . '.' . \$key, \$value, \$originalPropertyName);
			}
		} elseif (\$propertyValue instanceof \\TYPO3\\Flow\\Persistence\\Aspect\\PersistenceMagicInterface && !\\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->isNewObject(\$propertyValue) || \$propertyValue instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
			if (!property_exists(\$this, 'Flow_Persistence_RelatedEntities') || !is_array(\$this->Flow_Persistence_RelatedEntities)) {
				\$this->Flow_Persistence_RelatedEntities = array();
				\$this->Flow_Object_PropertiesToSerialize[] = 'Flow_Persistence_RelatedEntities';
			}
			if (\$propertyValue instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
				\$className = get_parent_class(\$propertyValue);
			} else {
				\$className = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->getObjectNameByClassName(get_class(\$propertyValue));
			}
			\$identifier = \\TYPO3\\Flow\\Core\\Bootstrap::\$staticObjectManager->get('TYPO3\\Flow\\Persistence\\PersistenceManagerInterface')->getIdentifierByObject(\$propertyValue);
			if (!\$identifier && \$propertyValue instanceof \\Doctrine\\ORM\\Proxy\\Proxy) {
				\$identifier = current(\\TYPO3\\Flow\\Reflection\\ObjectAccess::getProperty(\$propertyValue, '_identifier', TRUE));
			}
			\$this->Flow_Persistence_RelatedEntities[\$originalPropertyName . '.' . \$path] = array(
				'propertyName' => \$originalPropertyName,
				'entityType' => \$className,
				'identifier' => \$identifier,
				'entityPath' => \$path
			);
			\$this->\$originalPropertyName = \\TYPO3\\Flow\\Utility\\Arrays::setValueByPath(\$this->\$originalPropertyName, \$path, NULL);
		}
		";
		return $code;
	}

	/**
	 * Renders additional code for the __construct() method of the Proxy Class which realizes constructor injection.
	 *
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration
	 * @return string The built code
	 * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	protected function buildConstructorInjectionCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration) {
		$assignments = array();

		$argumentConfigurations = $objectConfiguration->getArguments();
		$constructorParameterInfo = $this->reflectionService->getMethodParameters($objectConfiguration->getClassName(), '__construct');
		$argumentNumberToOptionalInfo = array();
		foreach ($constructorParameterInfo as $parameterInfo) {
			$argumentNumberToOptionalInfo[($parameterInfo['position'] +1)] = $parameterInfo['optional'];
		}

		foreach ($argumentConfigurations as $argumentNumber => $argumentConfiguration) {
			if ($argumentConfiguration === NULL) {
				continue;
			}
			$argumentValue = $argumentConfiguration->getValue();
			$assignmentPrologue = 'if (!array_key_exists(' . ($argumentNumber - 1) . ', $arguments)) $arguments[' . ($argumentNumber - 1) . '] = ';
			if ($argumentValue === NULL && isset($argumentNumberToOptionalInfo[$argumentNumber]) && $argumentNumberToOptionalInfo[$argumentNumber] === TRUE) {
				$assignments[] = $assignmentPrologue . 'NULL';
			} else {
				switch ($argumentConfiguration->getType()) {
					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \TYPO3\Flow\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$assignments[] = $assignmentPrologue . 'new \\' . $argumentValueObjectName . '(' . $this->buildMethodParametersCode($argumentValue->getArguments()) . ')';
							} else {
								$assignments[] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
							}
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
								$argumentValue = Arrays::getValueByPath($settings, $settingPath);
							}
							if (!isset($this->objectConfigurations[$argumentValue])) {
								throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object "' . $argumentValue . '" which was specified as an argument in the object configuration of object "' . $objectConfiguration->getObjectName() . '" does not exist.', 1264669967);
							}
							$assignments[] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
						}
					break;

					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$assignments[] = $assignmentPrologue . var_export($argumentValue, TRUE);
					break;

					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						$assignments[] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getSettingsByPath(explode(\'.\', \''. $argumentValue . '\'))';
					break;
				}
			}
		}
		$code = count($assignments) > 0 ? "\n\t\t" . implode(";\n\t\t", $assignments) . ";\n" : '';

		$index = 0;
		foreach ($constructorParameterInfo as $parameterName => $parameterInfo) {
			if ($parameterInfo['optional'] === TRUE) {
				break;
			}
			if ($objectConfiguration->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_SINGLETON) {
				$code .= '		if (!array_key_exists(' . $index . ', $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. ' . 'Please check your calling code and Dependency Injection configuration.\', 1296143787);' . "\n";
			} else {
				$code .= '		if (!array_key_exists(' . $index . ', $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. ' . 'Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.\', 1296143788);' . "\n";
			}
			$index ++;
		}

		return $code;
	}

	/**
	 * Builds the code necessary to inject setter based dependencies.
	 *
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration (needed to produce helpful exception message)
	 * @return string The built code
	 * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
	 */
	protected function buildPropertyInjectionCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration) {
		$commands = array();
		$className = $objectConfiguration->getClassName();
		$objectName = $objectConfiguration->getObjectName();

		foreach ($objectConfiguration->getProperties() as $propertyName => $propertyConfiguration) {
			if ($propertyConfiguration->getAutowiring() === \TYPO3\Flow\Object\Configuration\Configuration::AUTOWIRING_MODE_OFF) {
				continue;
			}

			$propertyValue = $propertyConfiguration->getValue();
			switch ($propertyConfiguration->getType()) {
				case \TYPO3\Flow\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_OBJECT:
					if ($propertyValue instanceof \TYPO3\Flow\Object\Configuration\Configuration) {
						$propertyClassName = $propertyValue->getClassName();
						if ($propertyClassName === NULL) {
							$preparedSetterArgument = $this->buildCustomFactoryCall($propertyValue->getFactoryObjectName(), $propertyValue->getFactoryMethodName(), $propertyValue->getArguments());
						} else {
							if (!is_string($propertyClassName) || !isset($this->objectConfigurations[$propertyClassName])) {
								$configurationSource = $objectConfiguration->getConfigurationSourceHint();
								throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('Unknown class "' . $propertyClassName . '", specified as property "' . $propertyName . '" in the object configuration of object "' . $objectName . '" (' . $configurationSource . ').', 1296130876);
							}
							if ($this->objectConfigurations[$propertyClassName]->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($propertyValue->getArguments()) . ')';
							} else {
								$preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyClassName . '\')';
							}
						}
					} else {
						if (strpos($propertyValue, '.') !== FALSE) {
							$settingPath = explode('.', $propertyValue);
							$settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
							$propertyValue = Arrays::getValueByPath($settings, $settingPath);
						}
						if (!isset($this->objectConfigurations[$propertyValue])) {
							$configurationSource = $objectConfiguration->getConfigurationSourceHint();
							if (!isset($propertyValue[0])) {
								throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('Malformed DocComent block for a property in class "' . $$className . '".', 1360171313);
							}
							if ($propertyValue[0] === '\\') {
								throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object name "' . $propertyValue . '" which was specified as a property in the object configuration of object "' . $objectName . '" (' . $configurationSource . ') starts with a leading backslash.', 1277827579);
							} else {
								throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object "' . $propertyValue . '" which was specified as a property in the object configuration of object "' . $objectName . '" (' . $configurationSource . ') does not exist. Check for spelling mistakes and if that dependency is correctly configured.', 1265213849);
							}
						}
						$propertyClassName = $this->objectConfigurations[$propertyValue]->getClassName();
						if ($this->objectConfigurations[$propertyValue]->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
							$preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($this->objectConfigurations[$propertyValue]->getArguments()) . ')';
						} else {
							$preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyValue . '\')';
						}
					}
				break;
				case \TYPO3\Flow\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
					if (is_string($propertyValue)) {
						$preparedSetterArgument = '\'' . str_replace('\'', '\\\'', $propertyValue) . '\'';
					} elseif (is_array($propertyValue)) {
						$preparedSetterArgument = var_export($propertyValue, TRUE);
					} elseif (is_bool($propertyValue)) {
						$preparedSetterArgument = $propertyValue ? 'TRUE' : 'FALSE';
					} else {
						$preparedSetterArgument = $propertyValue;
					}
				break;
				case \TYPO3\Flow\Object\Configuration\ConfigurationProperty::PROPERTY_TYPES_SETTING:
					$preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'TYPO3\Flow\Configuration\ConfigurationManager\')->getConfiguration(\TYPO3\Flow\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, \'' . $propertyValue . '\')';
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
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration
	 * @param integer $cause a \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_* constant which is the cause of the initialization command being called.
	 * @return string
	 */
	protected function buildLifecycleInitializationCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration, $cause) {
		$lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
		if (!$this->reflectionService->hasMethod($objectConfiguration->getClassName(), $lifecycleInitializationMethodName)) {
			return '';
		}
		return "\n" . '		$this->' . $lifecycleInitializationMethodName . '(' . $cause . ');' . "\n";
	}

	/**
	 * Builds code which registers the lifecycle shutdown method, if any.
	 *
	 * @param \TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration
	 * @return string
	 */
	protected function buildLifecycleShutdownCode(\TYPO3\Flow\Object\Configuration\Configuration $objectConfiguration) {
		$lifecycleShutdownMethodName = $objectConfiguration->getLifecycleShutdownMethodName();
		if (!$this->reflectionService->hasMethod($objectConfiguration->getClassName(), $lifecycleShutdownMethodName)) {
			return '';
		}
		return "\n" . '		\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, \'' . $lifecycleShutdownMethodName . '\');' . PHP_EOL;
	}

	/**
	 * FIXME: Not yet completely refactored to new proxy mechanism
	 *
	 * @param array $argumentConfigurations
	 * @return string
	 */
	protected function buildMethodParametersCode(array $argumentConfigurations) {
		$preparedArguments = array();

		foreach ($argumentConfigurations as $argument) {
			if ($argument === NULL) {
				$preparedArguments[] = 'NULL';
			} else {
				$argumentValue = $argument->getValue();

				switch ($argument->getType()) {
					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
						if ($argumentValue instanceof \TYPO3\Flow\Object\Configuration\Configuration) {
							$argumentValueObjectName = $argumentValue->getObjectName();
							if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === \TYPO3\Flow\Object\Configuration\Configuration::SCOPE_PROTOTYPE) {
								$preparedArguments[] = '$this->getPrototype(\'' . $argumentValueObjectName . '\', array(' . $this->buildMethodParametersCode($argumentValue->getArguments(), $this->objectConfigurations) . '))';
							} else {
								$preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
							}
						} else {
							if (strpos($argumentValue, '.') !== FALSE) {
								$settingPath = explode('.', $argumentValue);
								$settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
								$argumentValue = Arrays::getValueByPath($settings, $settingPath);
							}
							$preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
						}
					break;

					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
						$preparedArguments[] = var_export($argumentValue, TRUE);
					break;

					case \TYPO3\Flow\Object\Configuration\ConfigurationArgument::ARGUMENT_TYPES_SETTING:
						$preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getSettingsByPath(explode(\'.\', \''. $argumentValue . '\'))';
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
	 * @return string
	 */
	protected function buildCustomFactoryCall($customFactoryObjectName, $customFactoryMethodName, array $arguments) {
		$parametersCode = $this->buildMethodParametersCode($arguments);
		return '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $parametersCode . ')';
	}
}
?>
