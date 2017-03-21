<?php
namespace Neos\Flow\ObjectManagement\Configuration;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Annotations\InjectConfiguration;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\Exception as ObjectException;
use Neos\Flow\ObjectManagement\Exception\InvalidObjectConfigurationException;
use Neos\Flow\ObjectManagement\Exception\UnknownClassException;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\Reflection\ReflectionService;

/**
 * Object Configuration Builder which can build object configuration objects
 * from information collected by reflection combined with arrays of configuration
 * options as defined in an Objects.yaml file.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ConfigurationBuilder
{
    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * Traverses through the given class and interface names and builds a base object configuration
     * for all of them. Then parses the provided extra configuration and merges the result
     * into the overall configuration. Finally autowires dependencies of arguments and properties
     * which can be resolved automatically.
     *
     * @param array $availableClassAndInterfaceNamesByPackage An array of available class names, grouped by package key
     * @param array $rawObjectConfigurationsByPackages An array of package keys and their raw (ie. unparsed) object configurations
     * @return array<Configuration> Object configurations
     * @throws InvalidObjectConfigurationException
     */
    public function buildObjectConfigurations(array $availableClassAndInterfaceNamesByPackage, array $rawObjectConfigurationsByPackages)
    {
        $objectConfigurations = [];
        $interfaceNames = [];

        foreach ($availableClassAndInterfaceNamesByPackage as $packageKey => $classAndInterfaceNames) {
            foreach ($classAndInterfaceNames as $classOrInterfaceName) {
                $objectName = $classOrInterfaceName;

                if ($this->reflectionService->isClassUnconfigurable($classOrInterfaceName)) {
                    continue;
                }

                if (interface_exists($classOrInterfaceName)) {
                    $interfaceName = $classOrInterfaceName;
                    $implementationClassName = $this->reflectionService->getDefaultImplementationClassNameForInterface($interfaceName);
                    if (!isset($rawObjectConfigurationsByPackages[$packageKey][$interfaceName]) && $implementationClassName === false) {
                        continue;
                    }
                    if ($this->reflectionService->isClassAnnotatedWith($interfaceName, Flow\Scope::class)) {
                        throw new InvalidObjectConfigurationException(sprintf('Scope annotations in interfaces don\'t have any effect, therefore you better remove it from %s in order to avoid confusion.', $interfaceName), 1299095595);
                    }
                    $interfaceNames[$interfaceName] = true;
                } else {
                    $implementationClassName = $classOrInterfaceName;
                }

                $rawObjectConfiguration = ['className' => $implementationClassName];
                $rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($classOrInterfaceName, $rawObjectConfiguration);
                $objectConfigurations[$objectName] = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'automatically registered class');
                $objectConfigurations[$objectName]->setPackageKey($packageKey);
            }
        }

        foreach ($rawObjectConfigurationsByPackages as $packageKey => $rawObjectConfigurations) {
            foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
                $objectName = str_replace('_', '\\', $objectName);
                if (!is_array($rawObjectConfiguration)) {
                    throw new InvalidObjectConfigurationException('Configuration of object "' . $objectName . '" in package "' . $packageKey . '" is not an array, please check your Objects.yaml for syntax errors.', 1295954338);
                }

                $existingObjectConfiguration = (isset($objectConfigurations[$objectName])) ? $objectConfigurations[$objectName] : null;
                if (isset($rawObjectConfiguration['className'])) {
                    $rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($rawObjectConfiguration['className'], $rawObjectConfiguration);
                }
                $newObjectConfiguration = $this->parseConfigurationArray($objectName, $rawObjectConfiguration, 'configuration of package ' . $packageKey . ', definition for object "' . $objectName . '"', $existingObjectConfiguration);

                if (!isset($objectConfigurations[$objectName]) && !interface_exists($objectName, true) && !class_exists($objectName, false)) {
                    throw new InvalidObjectConfigurationException('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey . '". Please check your Objects.yaml.', 1184926175);
                }

                if ($objectName !== $newObjectConfiguration->getClassName() && !interface_exists($objectName, true)) {
                    throw new InvalidObjectConfigurationException('Tried to set a differing class name for class "' . $objectName . '" in the object configuration of package "' . $packageKey . '". Setting "className" is only allowed for interfaces, please check your Objects.yaml."', 1295954589);
                }

                if (empty($newObjectConfiguration->getClassName()) && empty($newObjectConfiguration->getFactoryObjectName())) {
                    $count = count($this->reflectionService->getAllImplementationClassNamesForInterface($objectName));
                    $hint = ($count ? 'It seems like there is no class which implements that interface, maybe the object configuration is obsolete?' : sprintf('There are %s classes implementing that interface, therefore you must specify a specific class in your object configuration.', $count));
                    throw new InvalidObjectConfigurationException('The object configuration for "' . $objectName . '" in the object configuration of package "' . $packageKey . '" lacks a "className" entry. ' . $hint, 1422566751);
                }

                $objectConfigurations[$objectName] = $newObjectConfiguration;
                if ($objectConfigurations[$objectName]->getPackageKey() === null) {
                    $objectConfigurations[$objectName]->setPackageKey($packageKey);
                }
            }
        }

        // If an implementation class could be determined for an interface object configuration, set the scope for the
        // interface object configuration to the scope found in the implementation class configuration, but
        // only if the interface doesn't have a specifically configured scope (i.e. is prototype so far)
        foreach (array_keys($interfaceNames) as $interfaceName) {
            $implementationClassName = $objectConfigurations[$interfaceName]->getClassName();
            if ($implementationClassName !== '' && isset($objectConfigurations[$implementationClassName]) && $objectConfigurations[$interfaceName]->getScope() === Configuration::SCOPE_PROTOTYPE) {
                $objectConfigurations[$interfaceName]->setScope($objectConfigurations[$implementationClassName]->getScope());
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
    protected function enhanceRawConfigurationWithAnnotationOptions($className, array $rawObjectConfiguration)
    {
        if ($this->reflectionService->isClassAnnotatedWith($className, Flow\Scope::class)) {
            $rawObjectConfiguration['scope'] = $this->reflectionService->getClassAnnotation($className, Flow\Scope::class)->value;
        }
        if ($this->reflectionService->isClassAnnotatedWith($className, Flow\Autowiring::class)) {
            $rawObjectConfiguration['autowiring'] = $this->reflectionService->getClassAnnotation($className, Flow\Autowiring::class)->enabled;
        }
        return $rawObjectConfiguration;
    }

    /**
     * Builds an object configuration object from a generic configuration container.
     *
     * @param string $objectName Name of the object
     * @param array $rawConfigurationOptions The configuration array with options for the object configuration
     * @param string $configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
     * @param Configuration $existingObjectConfiguration If set, this object configuration object will be used instead of creating a fresh one
     * @return Configuration The object configuration object
     * @throws InvalidObjectConfigurationException if errors occurred during parsing
     */
    protected function parseConfigurationArray($objectName, array $rawConfigurationOptions, $configurationSourceHint = '', $existingObjectConfiguration = null)
    {
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
                                $property = $this->parsePropertyOfTypeObject($propertyName, $propertyValue['object'], $objectConfiguration);
                            } elseif (array_key_exists('setting', $propertyValue)) {
                                $property = new ConfigurationProperty($propertyName, ['type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => $propertyValue['setting']], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
                            } else {
                                throw new InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for property "' . $propertyName . '", instead found "' . (is_array($propertyValue) ? implode(', ', array_keys($propertyValue)) : $propertyValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563249);
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
                                throw new InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for argument "' . $argumentName . '", instead found "' . (is_array($argumentValue) ? implode(', ', array_keys($argumentValue)) : $argumentValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563250);
                            }
                            $objectConfiguration->setArgument($argument);
                        }
                    }
                break;
                case 'className':
                case 'factoryObjectName':
                case 'factoryMethodName':
                case 'lifecycleInitializationMethodName':
                case 'lifecycleShutdownMethodName':
                    $methodName = 'set' . ucfirst($optionName);
                    $objectConfiguration->$methodName(trim($optionValue));
                break;
                case 'autowiring':
                    $objectConfiguration->setAutowiring($this->parseAutowiring($optionValue));
                break;
                default:
                    throw new InvalidObjectConfigurationException('Invalid configuration option "' . $optionName . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1167574981);
            }
        }
        return $objectConfiguration;
    }

    /**
     * Parses the value of the option "scope"
     *
     * @param  string $value Value of the option
     * @return integer The scope translated into a Configuration::SCOPE_* constant
     * @throws InvalidObjectConfigurationException if an invalid scope has been specified
     */
    protected function parseScope($value)
    {
        switch ($value) {
            case 'singleton':
                return Configuration::SCOPE_SINGLETON;
            case 'prototype':
                return Configuration::SCOPE_PROTOTYPE;
            case 'session':
                return Configuration::SCOPE_SESSION;
            default:
                throw new InvalidObjectConfigurationException('Invalid scope "' . $value . '"', 1167574991);
        }
    }

    /**
     * Parses the value of the option "autowiring"
     *
     * @param  mixed $value Value of the option
     * @return integer The autowiring option translated into one of Configuration::AUTOWIRING_MODE_*
     * @throws InvalidObjectConfigurationException if an invalid option has been specified
     */
    protected static function parseAutowiring($value)
    {
        switch ($value) {
            case true:
            case Configuration::AUTOWIRING_MODE_ON:
                return Configuration::AUTOWIRING_MODE_ON;
            case false:
            case Configuration::AUTOWIRING_MODE_OFF:
                return Configuration::AUTOWIRING_MODE_OFF;
            default:
                throw new InvalidObjectConfigurationException('Invalid autowiring declaration', 1283866757);
        }
    }

    /**
     * Parses the configuration for properties of type OBJECT
     *
     * @param string $propertyName Name of the property
     * @param mixed $objectNameOrConfiguration Value of the "object" section of the property configuration - either a string or an array
     * @param Configuration $parentObjectConfiguration The Configuration object this property belongs to
     * @return ConfigurationProperty A configuration property of type object
     * @throws InvalidObjectConfigurationException
     */
    protected function parsePropertyOfTypeObject($propertyName, $objectNameOrConfiguration, Configuration $parentObjectConfiguration)
    {
        if (is_array($objectNameOrConfiguration)) {
            if (isset($objectNameOrConfiguration['name'])) {
                $objectName = $objectNameOrConfiguration['name'];
                unset($objectNameOrConfiguration['name']);
            } else {
                if (isset($objectNameOrConfiguration['factoryObjectName'])) {
                    $objectName = null;
                } else {
                    $annotations = $this->reflectionService->getPropertyTagValues($parentObjectConfiguration->getClassName(), $propertyName, 'var');
                    if (count($annotations) !== 1) {
                        throw new InvalidObjectConfigurationException(sprintf('Object %s, for property "%s", contains neither object name, nor factory object name, and nor is the property properly @var - annotated.', $parentObjectConfiguration->getConfigurationSourceHint(), $propertyName, $parentObjectConfiguration->getClassName()), 1297097815);
                    }
                    $objectName = $annotations[0];
                }
            }
            $objectConfiguration = $this->parseConfigurationArray($objectName, $objectNameOrConfiguration, $parentObjectConfiguration->getConfigurationSourceHint() . ', property "' . $propertyName . '"');
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
     * @return ConfigurationArgument A configuration argument of type object
     * @throws InvalidObjectConfigurationException
     */
    protected function parseArgumentOfTypeObject($argumentName, $objectNameOrConfiguration, $configurationSourceHint)
    {
        if (is_array($objectNameOrConfiguration)) {
            if (isset($objectNameOrConfiguration['name'])) {
                $objectName = $objectNameOrConfiguration['name'];
                unset($objectNameOrConfiguration['name']);
            } else {
                if (isset($objectNameOrConfiguration['factoryObjectName'])) {
                    $objectName = null;
                } else {
                    throw new InvalidObjectConfigurationException('Object configuration for argument "' . $argumentName . '" contains neither object name nor factory object name in ' . $configurationSourceHint, 1417431742);
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
     * @throws UnresolvedDependenciesException
     */
    protected function autowireArguments(array &$objectConfigurations)
    {
        foreach ($objectConfigurations as $objectConfiguration) {
            /** @var Configuration $objectConfiguration */
            if ($objectConfiguration->getClassName() === '') {
                continue;
            }

            $className = $objectConfiguration->getClassName();
            if (!$this->reflectionService->hasMethod($className, '__construct')) {
                continue;
            }

            $arguments = $objectConfiguration->getArguments();
            $autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, '__construct', Flow\Autowiring::class);
            foreach ($this->reflectionService->getMethodParameters($className, '__construct') as $parameterName => $parameterInformation) {
                $debuggingHint = '';
                $index = $parameterInformation['position'] + 1;
                if (!isset($arguments[$index])) {
                    if ($parameterInformation['optional'] === true) {
                        $defaultValue = (isset($parameterInformation['defaultValue'])) ? $parameterInformation['defaultValue'] : null;
                        $arguments[$index] = new ConfigurationArgument($index, $defaultValue, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
                        $arguments[$index]->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
                    } elseif ($parameterInformation['class'] !== null && isset($objectConfigurations[$parameterInformation['class']])) {
                        $arguments[$index] = new ConfigurationArgument($index, $parameterInformation['class'], ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
                    } elseif ($parameterInformation['allowsNull'] === true) {
                        $arguments[$index] = new ConfigurationArgument($index, null, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
                        $arguments[$index]->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
                    } elseif (interface_exists($parameterInformation['class'])) {
                        $debuggingHint = sprintf('No default implementation for the required interface %s was configured, therefore no specific class name could be used for this dependency. ', $parameterInformation['class']);
                    }

                    if (isset($arguments[$index]) && ($objectConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF || $autowiringAnnotation !== null && $autowiringAnnotation->enabled === false)) {
                        $arguments[$index]->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
                        $arguments[$index]->set($index, null);
                    }
                }

                if (!isset($arguments[$index]) && $objectConfiguration->getScope() === Configuration::SCOPE_SINGLETON) {
                    throw new UnresolvedDependenciesException(sprintf('Could not autowire required constructor argument $%s for singleton class %s. %sCheck the type hint of that argument and your Objects.yaml configuration.', $parameterName, $className, $debuggingHint), 1298629392);
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
     * @throws ObjectException if an injected property is private
     */
    protected function autowireProperties(array &$objectConfigurations)
    {
        /** @var Configuration $objectConfiguration */
        foreach ($objectConfigurations as $objectConfiguration) {
            $className = $objectConfiguration->getClassName();
            $properties = $objectConfiguration->getProperties();

            if ($className === '') {
                continue;
            }
            $classMethodNames = get_class_methods($className);
            if (!is_array($classMethodNames)) {
                if (!class_exists($className)) {
                    throw new UnknownClassException(sprintf('The class "%s" defined in the object configuration for object "%s", defined in package: %s, does not exist.', $className, $objectConfiguration->getObjectName(), $objectConfiguration->getPackageKey()), 1352371371);
                } else {
                    throw new UnknownClassException(sprintf('Could not autowire properties of class "%s" because names of methods contained in that class could not be retrieved using get_class_methods().', $className), 1352386418);
                }
            }
            foreach ($classMethodNames as $methodName) {
                if (isset($methodName[6]) && strpos($methodName, 'inject') === 0 && $methodName[6] === strtoupper($methodName[6])) {
                    $propertyName = lcfirst(substr($methodName, 6));

                    $autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, $methodName, Flow\Autowiring::class);
                    if ($autowiringAnnotation !== null && $autowiringAnnotation->enabled === false) {
                        continue;
                    }

                    if ($methodName === 'injectSettings') {
                        $packageKey = $objectConfiguration->getPackageKey();
                        if ($packageKey !== null) {
                            $properties[$propertyName] = new ConfigurationProperty($propertyName, ['type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => $packageKey], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
                        }
                    } else {
                        if (array_key_exists($propertyName, $properties)) {
                            continue;
                        }
                        $methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
                        if (count($methodParameters) !== 1) {
                            $this->systemLogger->log(sprintf('Could not autowire property %s because %s() expects %s instead of exactly 1 parameter.', $className . '::' . $propertyName, $methodName, (count($methodParameters) ?: 'none')), LOG_DEBUG);
                            continue;
                        }
                        $methodParameter = array_pop($methodParameters);
                        if ($methodParameter['class'] === null) {
                            $this->systemLogger->log(sprintf('Could not autowire property %s because the method parameter in %s() contained no class type hint.', $className . '::' . $propertyName, $methodName), LOG_DEBUG);
                            continue;
                        }
                        $properties[$propertyName] = new ConfigurationProperty($propertyName, $methodParameter['class'], ConfigurationProperty::PROPERTY_TYPES_OBJECT);
                    }
                }
            }

            foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, Inject::class) as $propertyName) {
                if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
                    throw new ObjectException(sprintf('The property "%%s" in class "%s" must not be private when annotated for injection.', $propertyName, $className), 1328109641);
                }
                if (!array_key_exists($propertyName, $properties)) {
                    /** @var Inject $injectAnnotation */
                    $injectAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, Inject::class);
                    $objectName = trim(implode('', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
                    $configurationProperty =  new ConfigurationProperty($propertyName, $objectName, ConfigurationProperty::PROPERTY_TYPES_OBJECT, null, $injectAnnotation->lazy);
                    $properties[$propertyName] = $configurationProperty;
                }
            }

            foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, InjectConfiguration::class) as $propertyName) {
                if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
                    throw new ObjectException(sprintf('The property "%s" in class "%s" must not be private when annotated for configuration injection.', $propertyName, $className), 1416765599);
                }
                if (array_key_exists($propertyName, $properties)) {
                    continue;
                }
                /** @var InjectConfiguration $injectConfigurationAnnotation */
                $injectConfigurationAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, InjectConfiguration::class);
                if ($injectConfigurationAnnotation->type === ConfigurationManager::CONFIGURATION_TYPE_SETTINGS) {
                    $packageKey = $injectConfigurationAnnotation->package !== null ? $injectConfigurationAnnotation->package : $objectConfiguration->getPackageKey();
                    $configurationPath = rtrim($packageKey . '.' . $injectConfigurationAnnotation->path, '.');
                } else {
                    if ($injectConfigurationAnnotation->package !== null) {
                        throw new ObjectException(sprintf('The InjectConfiguration annotation for property "%s" in class "%s" specifies a "package" key for configuration type "%s", but this is only supported for injection of "Settings".', $propertyName, $className, $injectConfigurationAnnotation->type), 1420811958);
                    }
                    $configurationPath = $injectConfigurationAnnotation->path;
                }
                $properties[$propertyName] = new ConfigurationProperty($propertyName, ['type' => $injectConfigurationAnnotation->type, 'path' => $configurationPath], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
            }
            $objectConfiguration->setProperties($properties);
        }
    }
}
