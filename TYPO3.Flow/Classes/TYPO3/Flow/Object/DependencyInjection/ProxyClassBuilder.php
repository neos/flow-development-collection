<?php
namespace TYPO3\Flow\Object\DependencyInjection;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Object\Configuration\Configuration;
use TYPO3\Flow\Object\Configuration\ConfigurationArgument;
use TYPO3\Flow\Object\Configuration\ConfigurationProperty;
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
class ProxyClassBuilder
{
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
     * @var array
     */
    protected $classesWithCompileStaticAnnotation;

    /**
     * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param \TYPO3\Flow\Object\Proxy\Compiler $compiler
     * @return void
     */
    public function injectCompiler(\TYPO3\Flow\Object\Proxy\Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @param \TYPO3\Flow\Configuration\ConfigurationManager $configurationManager
     * @return void
     */
    public function injectConfigurationManager(\TYPO3\Flow\Configuration\ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * @param \TYPO3\Flow\Object\CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(\TYPO3\Flow\Object\CompileTimeObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Analyzes the Object Configuration provided by the compiler and builds the necessary PHP code for the proxy classes
     * to realize dependency injection.
     *
     * @return void
     */
    public function build()
    {
        $this->objectConfigurations = $this->objectManager->getObjectConfigurations();

        foreach ($this->objectConfigurations as $objectName => $objectConfiguration) {
            $className = $objectConfiguration->getClassName();
            if ($className === '' || $this->compiler->hasCacheEntryForClass($className) === true) {
                continue;
            }

            if ($objectName !== $className || $this->reflectionService->isClassAbstract($className) || $this->reflectionService->isClassFinal($className)) {
                continue;
            }
            $proxyClass = $this->compiler->getProxyClass($className);
            if ($proxyClass === false) {
                continue;
            }
            $this->systemLogger->log('Building DI proxy for "' . $className . '".', LOG_DEBUG);

            $constructorPreCode = '';
            $constructorPostCode = '';

            $constructorPreCode .= $this->buildSetInstanceCode($objectConfiguration);
            $constructorPreCode .= $this->buildConstructorInjectionCode($objectConfiguration);

            $setRelatedEntitiesCode = '';
            if (!$this->reflectionService->hasMethod($className, '__sleep')) {
                $proxyClass->addTraits(['\TYPO3\Flow\Object\Proxy\ObjectSerializationTrait']);
                $sleepMethod = $proxyClass->getMethod('__sleep');
                $sleepMethod->addPostParentCallCode($this->buildSerializeRelatedEntitiesCode($objectConfiguration));

                $setRelatedEntitiesCode = "\n        " . '$this->Flow_setRelatedEntities();' . "\n";
            }

            $wakeupMethod = $proxyClass->getMethod('__wakeup');
            $wakeupMethod->addPreParentCallCode($this->buildSetInstanceCode($objectConfiguration));
            $wakeupMethod->addPreParentCallCode($setRelatedEntitiesCode);
            $wakeupMethod->addPostParentCallCode($this->buildLifecycleInitializationCode($objectConfiguration, \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_RECREATED));
            $wakeupMethod->addPostParentCallCode($this->buildLifecycleShutdownCode($objectConfiguration));

            $injectPropertiesCode = $this->buildPropertyInjectionCode($objectConfiguration);
            if ($injectPropertiesCode !== '') {
                $proxyClass->addTraits(['\TYPO3\Flow\Object\DependencyInjection\PropertyInjectionTrait']);
                $proxyClass->getMethod('Flow_Proxy_injectProperties')->addPreParentCallCode($injectPropertiesCode);
                $proxyClass->getMethod('Flow_Proxy_injectProperties')->overrideMethodVisibility('private');
                $wakeupMethod->addPreParentCallCode("        \$this->Flow_Proxy_injectProperties();\n");

                $constructorPostCode .= '        if (\'' . $className . '\' === get_class($this)) {' . "\n";
                $constructorPostCode .= '            $this->Flow_Proxy_injectProperties();' . "\n";
                $constructorPostCode .= '        }' . "\n";
            }

            $constructorPostCode .= $this->buildLifecycleInitializationCode($objectConfiguration, \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_CREATED);
            $constructorPostCode .= $this->buildLifecycleShutdownCode($objectConfiguration);

            $constructor = $proxyClass->getConstructor();
            $constructor->addPreParentCallCode($constructorPreCode);
            $constructor->addPostParentCallCode($constructorPostCode);

            if ($this->objectManager->getContext()->isProduction()) {
                $this->compileStaticMethods($className, $proxyClass);
            }
        }
    }

    /**
     * Renders additional code which registers the instance of the proxy class at the Object Manager
     * before constructor injection is executed. Used in constructors and wakeup methods.
     *
     * This also makes sure that object creation does not end in an endless loop due to bi-directional dependencies.
     *
     * @param Configuration $objectConfiguration
     * @return string
     */
    protected function buildSetInstanceCode(Configuration $objectConfiguration)
    {
        if ($objectConfiguration->getScope() === Configuration::SCOPE_PROTOTYPE) {
            return '';
        }

        $code = '        if (get_class($this) === \'' . $objectConfiguration->getClassName() . '\') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $objectConfiguration->getObjectName() . '\', $this);' . "\n";

        $className = $objectConfiguration->getClassName();
        foreach ($this->objectConfigurations as $otherObjectConfiguration) {
            if ($otherObjectConfiguration !== $objectConfiguration && $otherObjectConfiguration->getClassName() === $className) {
                $code .= '        if (get_class($this) === \'' . $otherObjectConfiguration->getClassName() . '\') \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->setInstance(\'' . $otherObjectConfiguration->getObjectName() . '\', $this);' . "\n";
            }
        }

        return $code;
    }

    /**
     * Renders code to create identifier/type information from related entities in an object.
     * Used in sleep methods.
     *
     * @param Configuration $objectConfiguration
     * @return string
     */
    protected function buildSerializeRelatedEntitiesCode(Configuration $objectConfiguration)
    {
        $className = $objectConfiguration->getClassName();
        $code = '';
        if ($this->reflectionService->hasMethod($className, '__sleep') === false) {
            $transientProperties = $this->reflectionService->getPropertyNamesByAnnotation($className, 'TYPO3\Flow\Annotations\Transient');
            $propertyVarTags = [];
            foreach ($this->reflectionService->getPropertyNamesByTag($className, 'var') as $propertyName) {
                $varTagValues = $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var');
                $propertyVarTags[$propertyName] = isset($varTagValues[0]) ? $varTagValues[0] : null;
            }
            $code = "        \$this->Flow_Object_PropertiesToSerialize = array();

        \$transientProperties = " . var_export($transientProperties, true) . ";
        \$propertyVarTags = " . var_export($propertyVarTags, true) . ";
        \$result = \$this->Flow_serializeRelatedEntities(\$transientProperties, \$propertyVarTags);\n";
        }
        return $code;
    }

    /**
     * Renders additional code for the __construct() method of the Proxy Class which realizes constructor injection.
     *
     * @param Configuration $objectConfiguration
     * @return string The built code
     * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
     */
    protected function buildConstructorInjectionCode(Configuration $objectConfiguration)
    {
        $assignments = array();

        $argumentConfigurations = $objectConfiguration->getArguments();
        $constructorParameterInfo = $this->reflectionService->getMethodParameters($objectConfiguration->getClassName(), '__construct');
        $argumentNumberToOptionalInfo = array();
        foreach ($constructorParameterInfo as $parameterInfo) {
            $argumentNumberToOptionalInfo[($parameterInfo['position'] + 1)] = $parameterInfo['optional'];
        }

        $highestArgumentPositionWithAutowiringEnabled = -1;
        foreach ($argumentConfigurations as $argumentNumber => $argumentConfiguration) {
            if ($argumentConfiguration === null) {
                continue;
            }
            $argumentPosition = $argumentNumber - 1;
            if ($argumentConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_ON) {
                $highestArgumentPositionWithAutowiringEnabled = $argumentPosition;
            }

            $argumentValue = $argumentConfiguration->getValue();
            $assignmentPrologue = 'if (!array_key_exists(' . ($argumentNumber - 1) . ', $arguments)) $arguments[' . ($argumentNumber - 1) . '] = ';
            if ($argumentValue === null && isset($argumentNumberToOptionalInfo[$argumentNumber]) && $argumentNumberToOptionalInfo[$argumentNumber] === true) {
                $assignments[$argumentPosition] = $assignmentPrologue . 'NULL';
            } else {
                switch ($argumentConfiguration->getType()) {
                    case ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
                        if ($argumentValue instanceof Configuration) {
                            $argumentValueObjectName = $argumentValue->getObjectName();
                            $argumentValueClassName = $argumentValue->getClassName();
                            if ($argumentValueClassName === null) {
                                $preparedArgument = $this->buildCustomFactoryCall($argumentValue->getFactoryObjectName(), $argumentValue->getFactoryMethodName(), $argumentValue->getArguments());
                                $assignments[$argumentPosition] = $assignmentPrologue . $preparedArgument;
                            } else {
                                if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === Configuration::SCOPE_PROTOTYPE) {
                                    $assignments[$argumentPosition] = $assignmentPrologue . 'new \\' . $argumentValueObjectName . '(' . $this->buildMethodParametersCode($argumentValue->getArguments()) . ')';
                                } else {
                                    $assignments[$argumentPosition] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
                                }
                            }
                        } else {
                            if (strpos($argumentValue, '.') !== false) {
                                $settingPath = explode('.', $argumentValue);
                                $settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
                                $argumentValue = Arrays::getValueByPath($settings, $settingPath);
                            }
                            if (!isset($this->objectConfigurations[$argumentValue])) {
                                throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object "' . $argumentValue . '" which was specified as an argument in the object configuration of object "' . $objectConfiguration->getObjectName() . '" does not exist.', 1264669967);
                            }
                            $assignments[$argumentPosition] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
                        }
                    break;

                    case ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
                        $assignments[$argumentPosition] = $assignmentPrologue . var_export($argumentValue, true);
                    break;

                    case ConfigurationArgument::ARGUMENT_TYPES_SETTING:
                        $assignments[$argumentPosition] = $assignmentPrologue . '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getSettingsByPath(explode(\'.\', \'' . $argumentValue . '\'))';
                    break;
                }
            }
        }

        for ($argumentCounter = count($assignments) - 1; $argumentCounter > $highestArgumentPositionWithAutowiringEnabled; $argumentCounter--) {
            unset($assignments[$argumentCounter]);
        }

        $code = $argumentCounter >= 0 ? "\n        " . implode(";\n        ", $assignments) . ";\n" : '';

        $index = 0;
        foreach ($constructorParameterInfo as $parameterName => $parameterInfo) {
            if ($parameterInfo['optional'] === true) {
                break;
            }
            if ($objectConfiguration->getScope() === Configuration::SCOPE_SINGLETON) {
                $code .= '        if (!array_key_exists(' . $index . ', $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. Please check your calling code and Dependency Injection configuration.\', 1296143787);' . "\n";
            } else {
                $code .= '        if (!array_key_exists(' . $index . ', $arguments)) throw new \TYPO3\Flow\Object\Exception\UnresolvedDependenciesException(\'Missing required constructor argument $' . $parameterName . ' in class \' . __CLASS__ . \'. Note that constructor injection is only support for objects of scope singleton (and this is not a singleton) – for other scopes you must pass each required argument to the constructor yourself.\', 1296143788);' . "\n";
            }
            $index++;
        }

        return $code;
    }

    /**
     * Builds the code necessary to inject setter based dependencies.
     *
     * @param Configuration $objectConfiguration (needed to produce helpful exception message)
     * @return string The built code
     * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
     */
    protected function buildPropertyInjectionCode(Configuration $objectConfiguration)
    {
        $commands = array();
        $injectedProperties = array();
        foreach ($objectConfiguration->getProperties() as $propertyName => $propertyConfiguration) {
            /* @var $propertyConfiguration ConfigurationProperty */
            if ($propertyConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF) {
                continue;
            }

            $propertyValue = $propertyConfiguration->getValue();
            switch ($propertyConfiguration->getType()) {
                case ConfigurationProperty::PROPERTY_TYPES_OBJECT:
                    if ($propertyValue instanceof Configuration) {
                        $commands = array_merge($commands, $this->buildPropertyInjectionCodeByConfiguration($objectConfiguration, $propertyName, $propertyValue));
                    } else {
                        $commands = array_merge($commands, $this->buildPropertyInjectionCodeByString($objectConfiguration, $propertyConfiguration, $propertyName, $propertyValue));
                    }

                break;
                case ConfigurationProperty::PROPERTY_TYPES_STRAIGHTVALUE:
                    if (is_string($propertyValue)) {
                        $preparedSetterArgument = '\'' . str_replace('\'', '\\\'', $propertyValue) . '\'';
                    } elseif (is_array($propertyValue)) {
                        $preparedSetterArgument = var_export($propertyValue, true);
                    } elseif (is_bool($propertyValue)) {
                        $preparedSetterArgument = $propertyValue ? 'TRUE' : 'FALSE';
                    } else {
                        $preparedSetterArgument = $propertyValue;
                    }
                    $commands[] = 'if (\TYPO3\Flow\Reflection\ObjectAccess::setProperty($this, \'' . $propertyName . '\', ' . $preparedSetterArgument . ') === FALSE) { $this->' . $propertyName . ' = ' . $preparedSetterArgument . ';}';
                    break;
                case ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION:
                    $configurationType = $propertyValue['type'];
                    if (!in_array($configurationType, $this->configurationManager->getAvailableConfigurationTypes())) {
                        throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The configuration injection specified for property "' . $propertyName . '" in the object configuration of object "' . $objectConfiguration->getObjectName() . '" refers to the unknown configuration type "' . $configurationType . '".', 1420736211);
                    }
                    $commands = array_merge($commands, $this->buildPropertyInjectionCodeByConfigurationTypeAndPath($objectConfiguration, $propertyName, $configurationType, $propertyValue['path']));
                break;
            }
            $injectedProperties[] = $propertyName;
        }

        if (count($commands) > 0) {
            $commandString = "    " . implode("\n    ", $commands) . "\n";
            $commandString .= '        $this->Flow_Injected_Properties = ' . var_export($injectedProperties, true) . ";\n";
        } else {
            $commandString = '';
        }

        return $commandString;
    }

    /**
     * Builds code which injects an object which was specified by its object configuration
     *
     * @param Configuration $objectConfiguration Configuration of the object to inject into
     * @param string $propertyName Name of the property to inject
     * @param Configuration $propertyConfiguration Configuration of the object to inject
     * @return array PHP code
     * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
     */
    protected function buildPropertyInjectionCodeByConfiguration(Configuration $objectConfiguration, $propertyName, Configuration $propertyConfiguration)
    {
        $className = $objectConfiguration->getClassName();
        $propertyObjectName = $propertyConfiguration->getObjectName();
        $propertyClassName = $propertyConfiguration->getClassName();
        if ($propertyClassName === null) {
            $preparedSetterArgument = $this->buildCustomFactoryCall($propertyConfiguration->getFactoryObjectName(), $propertyConfiguration->getFactoryMethodName(), $propertyConfiguration->getArguments());
        } else {
            if (!is_string($propertyClassName) || !isset($this->objectConfigurations[$propertyClassName])) {
                $configurationSource = $objectConfiguration->getConfigurationSourceHint();
                throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('Unknown class "' . $propertyClassName . '", specified as property "' . $propertyName . '" in the object configuration of object "' . $objectConfiguration->getObjectName() . '" (' . $configurationSource . ').', 1296130876);
            }
            if ($this->objectConfigurations[$propertyClassName]->getScope() === Configuration::SCOPE_PROTOTYPE) {
                $preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($propertyConfiguration->getArguments()) . ')';
            } else {
                $preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyClassName . '\')';
            }
        }

        $result = $this->buildSetterInjectionCode($className, $propertyName, $preparedSetterArgument);
        if ($result !== null) {
            return $result;
        }

        return $this->buildLazyPropertyInjectionCode($propertyObjectName, $propertyClassName, $propertyName, $preparedSetterArgument);
    }

    /**
     * Builds code which injects an object which was specified by its object name
     *
     * @param Configuration $objectConfiguration Configuration of the object to inject into
     * @param ConfigurationProperty $propertyConfiguration
     * @param string $propertyName Name of the property to inject
     * @param string $propertyObjectName Object name of the object to inject
     * @return array PHP code
     * @throws \TYPO3\Flow\Object\Exception\UnknownObjectException
     */
    public function buildPropertyInjectionCodeByString(Configuration $objectConfiguration, ConfigurationProperty $propertyConfiguration, $propertyName, $propertyObjectName)
    {
        $className = $objectConfiguration->getClassName();

        if (strpos($propertyObjectName, '.') !== false) {
            $settingPath = explode('.', $propertyObjectName);
            $settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
            $propertyObjectName = Arrays::getValueByPath($settings, $settingPath);
        }

        if (!isset($this->objectConfigurations[$propertyObjectName])) {
            $configurationSource = $objectConfiguration->getConfigurationSourceHint();
            if (!isset($propertyObjectName[0])) {
                throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('Malformed DocComent block for a property in class "' . $className . '".', 1360171313);
            }
            if ($propertyObjectName[0] === '\\') {
                throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object name "' . $propertyObjectName . '" which was specified as a property in the object configuration of object "' . $objectConfiguration->getObjectName() . '" (' . $configurationSource . ') starts with a leading backslash.', 1277827579);
            } else {
                throw new \TYPO3\Flow\Object\Exception\UnknownObjectException('The object "' . $propertyObjectName . '" which was specified as a property in the object configuration of object "' . $objectConfiguration->getObjectName() . '" (' . $configurationSource . ') does not exist. Check for spelling mistakes and if that dependency is correctly configured.', 1265213849);
            }
        }
        $propertyClassName = $this->objectConfigurations[$propertyObjectName]->getClassName();
        if ($this->objectConfigurations[$propertyObjectName]->getScope() === Configuration::SCOPE_PROTOTYPE && !$this->objectConfigurations[$propertyObjectName]->isCreatedByFactory()) {
            $preparedSetterArgument = 'new \\' . $propertyClassName . '(' . $this->buildMethodParametersCode($this->objectConfigurations[$propertyObjectName]->getArguments()) . ')';
        } else {
            $preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $propertyObjectName . '\')';
        }

        $result = $this->buildSetterInjectionCode($className, $propertyName, $preparedSetterArgument);
        if ($result !== null) {
            return $result;
        }

        if ($propertyConfiguration->isLazyLoading() && $this->objectConfigurations[$propertyObjectName]->getScope() !== Configuration::SCOPE_PROTOTYPE) {
            return $this->buildLazyPropertyInjectionCode($propertyObjectName, $propertyClassName, $propertyName, $preparedSetterArgument);
        } else {
            return array('    $this->' . $propertyName . ' = ' . $preparedSetterArgument . ';');
        }
    }

    /**
     * Builds code which assigns the value stored in the specified configuration into the given class property.
     *
     * @param Configuration $objectConfiguration Configuration of the object to inject into
     * @param string $propertyName Name of the property to inject
     * @param string $configurationType the configuration type of the injected property (one of the ConfigurationManager::CONFIGURATION_TYPE_* constants)
     * @param string $configurationPath Path with "." as separator specifying the setting value to inject or NULL if the complete configuration array should be injected
     * @return array PHP code
     */
    public function buildPropertyInjectionCodeByConfigurationTypeAndPath(Configuration $objectConfiguration, $propertyName, $configurationType, $configurationPath = null)
    {
        $className = $objectConfiguration->getClassName();
        if ($configurationPath !== null) {
            $preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Configuration\ConfigurationManager::class)->getConfiguration(\'' . $configurationType . '\', \'' . $configurationPath . '\')';
        } else {
            $preparedSetterArgument = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\TYPO3\Flow\Configuration\ConfigurationManager::class)->getConfiguration(\'' . $configurationType . '\')';
        }

        $result = $this->buildSetterInjectionCode($className, $propertyName, $preparedSetterArgument);
        if ($result !== null) {
            return $result;
        }
        return array('    $this->' . $propertyName . ' = ' . $preparedSetterArgument . ';');
    }

    /**
     * Builds code which injects a DependencyProxy instead of the actual dependency
     *
     * @param string $propertyObjectName Object name of the dependency to inject
     * @param string $propertyClassName Class name of the dependency to inject
     * @param string $propertyName Name of the property in the class to inject into
     * @param string $preparedSetterArgument PHP code to use for retrieving the value to inject
     * @return array PHP code
     */
    protected function buildLazyPropertyInjectionCode($propertyObjectName, $propertyClassName, $propertyName, $preparedSetterArgument)
    {
        $setterArgumentHash = "'" . md5($preparedSetterArgument) . "'";
        $commands[] = '    $this->Flow_Proxy_LazyPropertyInjection(\'' . $propertyObjectName . '\', \'' . $propertyClassName . '\', \'' . $propertyName . '\', ' . $setterArgumentHash . ', function() { return ' . $preparedSetterArgument . '; });';

        return $commands;
    }

    /**
     * Builds a code snippet which tries to inject the specified property first through calling the related
     * inject*() method and then the set*() method. If neither exists and the property doesn't exist either,
     * an empty array is returned.
     *
     * If neither inject*() nor set*() exists, but the property does exist, NULL is returned
     *
     * @param string $className Name of the class to inject into
     * @param string $propertyName Name of the property to inject
     * @param string $preparedSetterArgument PHP code to use for retrieving the value to inject
     * @return array PHP code
     */
    protected function buildSetterInjectionCode($className, $propertyName, $preparedSetterArgument)
    {
        $setterMethodName = 'inject' . ucfirst($propertyName);
        if ($this->reflectionService->hasMethod($className, $setterMethodName)) {
            return array('    $this->' . $setterMethodName . '(' . $preparedSetterArgument . ');');
        }
        $setterMethodName = 'set' . ucfirst($propertyName);
        if ($this->reflectionService->hasMethod($className, $setterMethodName)) {
            return array('    $this->' . $setterMethodName . '(' . $preparedSetterArgument . ');');
        }
        if (!property_exists($className, $propertyName)) {
            return array();
        }
        return null;
    }

    /**
     * Builds code which calls the lifecycle initialization method, if any.
     *
     * @param Configuration $objectConfiguration
     * @param integer $cause a \TYPO3\Flow\Object\ObjectManagerInterface::INITIALIZATIONCAUSE_* constant which is the cause of the initialization command being called.
     * @return string
     */
    protected function buildLifecycleInitializationCode(Configuration $objectConfiguration, $cause)
    {
        $lifecycleInitializationMethodName = $objectConfiguration->getLifecycleInitializationMethodName();
        if (!$this->reflectionService->hasMethod($objectConfiguration->getClassName(), $lifecycleInitializationMethodName)) {
            return '';
        }
        $className = $objectConfiguration->getClassName();
        $code = "\n" . '        if (get_class($this) === \'' . $className . '\') {' . "\n";
        $code .= '            $this->' . $lifecycleInitializationMethodName . '(' . $cause . ');' . "\n";
        $code .= '        }' . "\n";
        return $code;
    }

    /**
     * Builds code which registers the lifecycle shutdown method, if any.
     *
     * @param Configuration $objectConfiguration
     * @return string
     */
    protected function buildLifecycleShutdownCode(Configuration $objectConfiguration)
    {
        $lifecycleShutdownMethodName = $objectConfiguration->getLifecycleShutdownMethodName();
        if (!$this->reflectionService->hasMethod($objectConfiguration->getClassName(), $lifecycleShutdownMethodName)) {
            return '';
        }
        $className = $objectConfiguration->getClassName();
        $code = "\n" . '        if (get_class($this) === \'' . $className . '\') {' . "\n";
        $code .= '        \TYPO3\Flow\Core\Bootstrap::$staticObjectManager->registerShutdownObject($this, \'' . $lifecycleShutdownMethodName . '\');' . PHP_EOL;
        $code .= '        }' . "\n";
        return $code;
    }

    /**
     * FIXME: Not yet completely refactored to new proxy mechanism
     *
     * @param array $argumentConfigurations
     * @return string
     */
    protected function buildMethodParametersCode(array $argumentConfigurations)
    {
        $preparedArguments = array();

        foreach ($argumentConfigurations as $argument) {
            if ($argument === null) {
                $preparedArguments[] = 'NULL';
            } else {
                $argumentValue = $argument->getValue();

                switch ($argument->getType()) {
                    case ConfigurationArgument::ARGUMENT_TYPES_OBJECT:
                        if ($argumentValue instanceof Configuration) {
                            $argumentValueObjectName = $argumentValue->getObjectName();
                            if ($this->objectConfigurations[$argumentValueObjectName]->getScope() === Configuration::SCOPE_PROTOTYPE) {
                                $preparedArguments[] = 'new \\' . $argumentValueObjectName . '(' . $this->buildMethodParametersCode($argumentValue->getArguments(), $this->objectConfigurations) . ')';
                            } else {
                                $preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValueObjectName . '\')';
                            }
                        } else {
                            if (strpos($argumentValue, '.') !== false) {
                                $settingPath = explode('.', $argumentValue);
                                $settings = Arrays::getValueByPath($this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS), array_shift($settingPath));
                                $argumentValue = Arrays::getValueByPath($settings, $settingPath);
                            }
                            $preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $argumentValue . '\')';
                        }
                    break;

                    case ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
                        $preparedArguments[] = var_export($argumentValue, true);
                    break;

                    case ConfigurationArgument::ARGUMENT_TYPES_SETTING:
                        $preparedArguments[] = '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->getSettingsByPath(explode(\'.\', \'' . $argumentValue . '\'))';
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
    protected function buildCustomFactoryCall($customFactoryObjectName, $customFactoryMethodName, array $arguments)
    {
        $parametersCode = $this->buildMethodParametersCode($arguments);
        return '\TYPO3\Flow\Core\Bootstrap::$staticObjectManager->get(\'' . $customFactoryObjectName . '\')->' . $customFactoryMethodName . '(' . $parametersCode . ')';
    }

    /**
     * Compile the result of methods marked with CompileStatic into the proxy class
     *
     * @param string $className
     * @param \TYPO3\Flow\Object\Proxy\ProxyClass $proxyClass
     * @return void
     */
    protected function compileStaticMethods($className, $proxyClass)
    {
        if ($this->classesWithCompileStaticAnnotation === null) {
            $this->classesWithCompileStaticAnnotation = array_flip($this->reflectionService->getClassesContainingMethodsAnnotatedWith(\TYPO3\Flow\Annotations\CompileStatic::class));
        }
        if (!isset($this->classesWithCompileStaticAnnotation[$className])) {
            return;
        }

        $methodNames = get_class_methods($className);
        foreach ($methodNames as $methodName) {
            if ($this->reflectionService->isMethodStatic($className, $methodName) && $this->reflectionService->isMethodAnnotatedWith($className, $methodName, \TYPO3\Flow\Annotations\CompileStatic::class)) {
                $compiledMethod = $proxyClass->getMethod($methodName);

                $value = call_user_func(array($className, $methodName), $this->objectManager);
                $compiledResult = var_export($value, true);
                $compiledMethod->setMethodBody('return ' . $compiledResult . ';');
            }
        }
    }
}
