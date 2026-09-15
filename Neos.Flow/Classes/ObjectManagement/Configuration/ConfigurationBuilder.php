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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\Annotations\InjectCache;
use Neos\Flow\Annotations\InjectConfiguration;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;
use Neos\Flow\ObjectManagement\Exception as ObjectException;
use Neos\Flow\ObjectManagement\Exception\InvalidObjectConfigurationException;
use Neos\Flow\ObjectManagement\Exception\UnknownClassException;
use Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException;
use Neos\Flow\ObjectManagement\ObjectManager;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\Exception\InvalidClassException;
use Neos\Flow\Reflection\ReflectionService;
use Psr\Log\LoggerInterface;

/**
 * Object Configuration Builder which can build object configuration objects
 * from information collected by reflection combined with arrays of configuration
 * options as defined in an Objects.yaml file.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
readonly class ConfigurationBuilder
{
    /**
     * @param ReflectionService $reflectionService
     * @param LoggerInterface $logger
     * @param array<string> $excludeClassesFromConstructorAutowiring An array of object names for which constructor injection autowiring should be disabled
     * Note that the object names are regular expressions.
     */
    public function __construct(
        protected ReflectionService $reflectionService,
        protected ConfigurationParser $configurationParser,
        protected LoggerInterface $logger,
        protected array $excludeClassesFromConstructorAutowiring = []
    ) {
    }

    /**
     * Traverses through the given class and interface names and builds a base object configuration
     * for all of them. Then parses the provided extra configuration and merges the result
     * into the overall configuration. Finally autowires dependencies of arguments and properties
     * which can be resolved automatically.
     *
     * @param array<string,array<int,class-string>> $availableClassAndInterfaceNamesByPackage An array of available class names, grouped by package key
     * @param array<string,array<string,array{
     *       className?: class-string,
     *       scope?: string,
     *       factoryObjectName?: class-string,
     *       factoryMethodName?: string,
     *       arguments?: array<mixed>,
     *       properties?: array<string,array{
     *           object?: class-string|array{
     *               factoryObjectName: class-string,
     *               factoryMethodName?: string,
     *               arguments?: array<mixed>
     *           }
     *       }>,
     *   }|mixed>> $rawObjectConfigurationsByPackages An array of package keys and their raw (ie. unparsed) object configurations
     * @return array<Configuration> Object configurations
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException
     * @throws ObjectException
     * @throws UnknownClassException
     * @throws UnresolvedDependenciesException
     * @throws \ReflectionException
     */
    public function buildObjectConfigurations(array $availableClassAndInterfaceNamesByPackage, array $rawObjectConfigurationsByPackages): array
    {
        $objectConfigurations = [];
        $interfaceNames = [];

        foreach ($availableClassAndInterfaceNamesByPackage as $packageKey => $classAndInterfaceNames) {
            foreach ($classAndInterfaceNames as $classOrInterfaceName) {
                $objectName = $classOrInterfaceName;

                if ($this->reflectionService->isClassUnconfigurable($classOrInterfaceName)) {
                    continue;
                }

                $implementationClassName = $classOrInterfaceName;
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
                }

                $rawObjectConfiguration = ['className' => $implementationClassName];
                $rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($classOrInterfaceName, $rawObjectConfiguration);
                $objectConfigurations[$objectName] = $this->configurationParser->parseConfigurationArray($objectName, $rawObjectConfiguration, 'automatically registered class');
                $objectConfigurations[$objectName]->setPackageKey($packageKey);
            }
        }

        foreach ($rawObjectConfigurationsByPackages as $packageKey => $rawObjectConfigurations) {
            foreach ($rawObjectConfigurations as $objectName => $rawObjectConfiguration) {
                /** @var class-string $objectName */
                $objectName = str_replace('_', '\\', $objectName);
                if (!is_array($rawObjectConfiguration)) {
                    throw new InvalidObjectConfigurationException('Configuration of object "' . $objectName . '" in package "' . $packageKey . '" is not an array, please check your Objects.yaml for syntax errors.', 1295954338);
                }

                $existingObjectConfiguration = $objectConfigurations[$objectName] ?? null;
                if (isset($rawObjectConfiguration['className'])) {
                    $rawObjectConfiguration = $this->enhanceRawConfigurationWithAnnotationOptions($rawObjectConfiguration['className'], $rawObjectConfiguration);
                }
                // Virtual objects are determined by a colon ":" in the name (e.g. "Some.Package:Some.Virtual.Object")
                $isVirtualObject = str_contains($objectName, ':') !== false;
                if ($isVirtualObject && empty($rawObjectConfiguration['className'])) {
                    throw new InvalidObjectConfigurationException(sprintf('Missing className for virtual object configuration "%s" of package %s. Please check your Objects.yaml.', $objectName, $packageKey), 1585758850);
                }
                if ($isVirtualObject && !isset($rawObjectConfiguration['factoryObjectName']) && !isset($rawObjectConfiguration['factoryMethodName'])) {
                    $rawObjectConfiguration['factoryObjectName'] = ObjectManager::class;
                    $rawObjectConfiguration['factoryMethodName'] = 'get';
                    $newArguments = [1 => ['value' => $rawObjectConfiguration['className']]];
                    if (isset($rawObjectConfiguration['arguments'])) {
                        foreach ($rawObjectConfiguration['arguments'] as $index => $value) {
                            $newArguments[$index + 1] = $value;
                        }
                    }
                    $rawObjectConfiguration['arguments'] = $newArguments;
                }
                $newObjectConfiguration = $this->configurationParser->parseConfigurationArray($objectName, $rawObjectConfiguration, 'configuration of package ' . $packageKey . ', definition for object "' . $objectName . '"', $existingObjectConfiguration);

                if (!$isVirtualObject && !isset($objectConfigurations[$objectName]) && !interface_exists($objectName, true) && !class_exists($objectName, false)) {
                    throw new InvalidObjectConfigurationException('Tried to configure unknown object "' . $objectName . '" in package "' . $packageKey . '". Please check your Objects.yaml.', 1184926175);
                }

                if (!$isVirtualObject && $objectName !== $newObjectConfiguration->getClassName() && !interface_exists($objectName, true)) {
                    throw new InvalidObjectConfigurationException('Tried to set a differing class name for class "' . $objectName . '" in the object configuration of package "' . $packageKey . '". Setting "className" is only allowed for interfaces, please check your Objects.yaml."', 1295954589);
                }

                if (empty($newObjectConfiguration->getClassName()) && !$newObjectConfiguration->isCreatedByFactory()) {
                    $count = count($this->reflectionService->getAllImplementationClassNamesForInterface($objectName));
                    $hint = ($count ? 'It seems like there is no class which implements that interface, maybe the object configuration is obsolete?' : sprintf('There are %s classes implementing that interface, therefore you must specify a specific class in your object configuration.', $count));
                    throw new InvalidObjectConfigurationException('The object configuration for "' . $objectName . '" in the object configuration of package "' . $packageKey . '" lacks a "className" entry. ' . $hint, 1422566751);
                }

                $objectConfigurations[$objectName] = $newObjectConfiguration;
                if ($objectConfigurations[$objectName]->getPackageKey() === '') {
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

        $objectConfigurations = $this->autowireArguments($objectConfigurations);
        $objectConfigurations = $this->autowireProperties($objectConfigurations);
        $objectConfigurations = $this->wireFactoryArguments($objectConfigurations);

        return $objectConfigurations;
    }

    /**
     * Builds a raw configuration array by parsing possible scope and autowiring
     * annotations from the given class or interface.
     *
     * @param class-string $className
     * @param array<string, mixed> $rawObjectConfiguration
     * @return array<string, mixed>
     */
    protected function enhanceRawConfigurationWithAnnotationOptions($className, array $rawObjectConfiguration): array
    {
        if ($this->reflectionService->isClassAnnotatedWith($className, Flow\Scope::class)) {
            $annotation = $this->reflectionService->getClassAnnotation($className, Flow\Scope::class);
            $rawObjectConfiguration['scope'] = $annotation->value ?? null;
        }
        if ($this->reflectionService->isClassAnnotatedWith($className, Flow\Autowiring::class)) {
            $annotation = $this->reflectionService->getClassAnnotation($className, Flow\Autowiring::class);
            $rawObjectConfiguration['autowiring'] = $annotation->enabled ?? null;
        }
        return $rawObjectConfiguration;
    }

    /**
     * Creates a "virtual object configuration" for factory arguments, turning:
     *
     * 'Some\Class\Name':
     *   factoryObjectName: 'Some\Factory\Class'
     *   arguments:
     *     1:
     *       object:
     *         factoryObjectName: 'Some\Other\Factory\Class'
     *
     * into:
     *
     * 'Some\Class\Name':
     *   factoryObjectName: 'Some\Factory\Class'
     *   arguments:
     *     1:
     *       object: 'Some\Class\Name:argument:1'
     *
     * 'Some\Class\Name:argument:1':
     *   factoryObjectName: 'Some\Other\Factory\Class'
     *
     *
     * @param array<Configuration> &$objectConfigurations
     * @return array<Configuration>
     */
    protected function wireFactoryArguments(array $objectConfigurations): array
    {
        foreach ($objectConfigurations as $objectConfiguration) {
            foreach ($objectConfiguration->getFactoryArguments() as $index => $argument) {
                if ($argument === null) {
                    continue;
                }
                if ($argument->getType() !== ConfigurationArgument::ARGUMENT_TYPES_OBJECT) {
                    continue;
                }
                $argumentValue = $argument->getValue();
                if (!$argumentValue instanceof Configuration) {
                    continue;
                }
                $argumentObjectName = $objectConfiguration->getObjectName() . ':argument:' . $index;
                $argumentValue->setObjectName($argumentObjectName);
                $objectConfigurations[$argumentObjectName] = $argument->getValue();
                $objectConfiguration->setFactoryArgument(new ConfigurationArgument($argument->getIndex(), $argumentObjectName, ConfigurationArgument::ARGUMENT_TYPES_OBJECT, $argument->getAutowiring()));
            }
        }

        return $objectConfigurations;
    }

    /**
     * If mandatory constructor arguments have not been defined yet, this function tries to autowire
     * them if possible.
     *
     * @param array<Configuration> $objectConfigurations
     * @return array<Configuration>
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidConfigurationException
     * @throws InvalidObjectConfigurationException
     * @throws UnresolvedDependenciesException
     * @throws \ReflectionException
     */
    protected function autowireArguments(array $objectConfigurations): array
    {
        foreach ($objectConfigurations as $objectConfiguration) {
            $className = $objectConfiguration->getClassName();

            if ($className === '') {
                continue;
            }

            if ($objectConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF) {
                continue;
            }

            if ($objectConfiguration->isCreatedByFactory()) {
                continue;
            }

            if (!$this->reflectionService->hasMethod($className, '__construct')) {
                continue;
            }

            foreach ($this->excludeClassesFromConstructorAutowiring as $excludeClassNameRegex) {
                if ((preg_match('/' . $excludeClassNameRegex . '/', $className) === 1) && $objectConfiguration->getScope() === Configuration::SCOPE_PROTOTYPE) {
                    $objectConfiguration->setAutowiring(Configuration::AUTOWIRING_MODE_OFF);
                    continue 2;
                }
            }

            /** @var Flow\Autowiring $autowiringAnnotation */
            $autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, '__construct', Flow\Autowiring::class);
            if ($autowiringAnnotation !== null && $autowiringAnnotation->enabled === false) {
                continue;
            }

            $arguments = $objectConfiguration->getArguments();
            foreach ($this->reflectionService->getMethodParameters($className, '__construct') as $parameterName => $parameterInformation) {
                $debuggingHint = '';
                $index = $parameterInformation['position'] + 1;
                if (!isset($arguments[$index])) {
                    $injectConfigurationAnnotation = $parameterInformation['annotations'][InjectConfiguration::class][0] ?? null;
                    if ($injectConfigurationAnnotation instanceof InjectConfiguration) {
                        if ($injectConfigurationAnnotation->type !== ConfigurationManager::CONFIGURATION_TYPE_SETTINGS) {
                            throw new InvalidObjectConfigurationException(sprintf('InjectConfiguration for constructor arguments currently only supports settings. Got type "%s" in constructor argument %s of class %s.', $injectConfigurationAnnotation->type, $index, $className), 1710409120);
                        }
                        $arguments[$index] = new ConfigurationArgument(
                            $index,
                            $injectConfigurationAnnotation->getFullConfigurationPath($objectConfiguration->getPackageKey()),
                            ConfigurationArgument::ARGUMENT_TYPES_SETTING
                        );
                    } elseif ($parameterInformation['optional'] === true) {
                        $defaultValue = $parameterInformation['defaultValue'] ?? null;
                        $arguments[$index] = new ConfigurationArgument($index, $defaultValue, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE, Configuration::AUTOWIRING_MODE_OFF);
                    } elseif ($parameterInformation['class'] !== null && isset($objectConfigurations[$parameterInformation['class']])) {
                        $arguments[$index] = new ConfigurationArgument($index, $parameterInformation['class'], ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
                    } elseif ($parameterInformation['allowsNull'] === true) {
                        $arguments[$index] = new ConfigurationArgument($index, null, ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE, Configuration::AUTOWIRING_MODE_OFF);
                    } elseif (is_string($parameterInformation['class']) && interface_exists($parameterInformation['class'])) {
                        $debuggingHint = sprintf('No default implementation for the required interface %s was configured, therefore no specific class name could be used for this dependency. ', $parameterInformation['class']);
                    }
                }

                if (!isset($arguments[$index]) && $objectConfiguration->getScope() === Configuration::SCOPE_SINGLETON) {
                    throw new UnresolvedDependenciesException(sprintf('Could not autowire required constructor argument $%s for singleton class %s. %sCheck the type hint of that argument and your Objects.yaml configuration.', $parameterName, $className, $debuggingHint), 1298629392);
                }
            }

            $objectConfiguration->setArguments($arguments);
        }

        return $objectConfigurations;
    }

    /**
     * This function tries to find yet unmatched dependencies which need to be injected via "inject*" setter methods.
     *
     * @param array<Configuration> $objectConfigurations
     * @return array<Configuration>
     * @throws ObjectException if an injected property is private
     * @throws UnknownClassException
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws \ReflectionException
     */
    protected function autowireProperties(array $objectConfigurations): array
    {
        foreach ($objectConfigurations as $objectConfiguration) {
            $className = $objectConfiguration->getClassName();
            $properties = $objectConfiguration->getProperties();

            if ($className === '') {
                continue;
            }

            if ($objectConfiguration->getAutowiring() === Configuration::AUTOWIRING_MODE_OFF) {
                continue;
            }

            try {
                $classMethodNames = get_class_methods($className);
            } catch (\TypeError $error) {
                throw new UnknownClassException(sprintf('The class "%s" defined in the object configuration for object "%s", defined in package: %s, does not exist.', $className, $objectConfiguration->getObjectName(), $objectConfiguration->getPackageKey()), 1352371372);
            }
            if (!is_array($classMethodNames)) {
                if (!class_exists($className)) {
                    throw new UnknownClassException(sprintf('The class "%s" defined in the object configuration for object "%s", defined in package: %s, does not exist.', $className, $objectConfiguration->getObjectName(), $objectConfiguration->getPackageKey()), 1352371371);
                }
                throw new UnknownClassException(sprintf('Could not autowire properties of class "%s" because names of methods contained in that class could not be retrieved using get_class_methods().', $className), 1352386418);
            }
            foreach ($classMethodNames as $methodName) {
                if (isset($methodName[6]) && str_starts_with($methodName, 'inject') && $methodName[6] === strtoupper($methodName[6])) {
                    $propertyName = lcfirst(substr($methodName, 6));

                    /** @var Flow\Autowiring $autowiringAnnotation */
                    $autowiringAnnotation = $this->reflectionService->getMethodAnnotation($className, $methodName, Flow\Autowiring::class);
                    if ($autowiringAnnotation !== null && $autowiringAnnotation->enabled === false) {
                        continue;
                    }

                    if ($methodName === 'injectSettings') {
                        $packageKey = $objectConfiguration->getPackageKey();
                        if ($packageKey !== '') {
                            $properties[$propertyName] = new ConfigurationProperty($propertyName, ['type' => ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'path' => $packageKey], ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION);
                        }
                    } else {
                        if (array_key_exists($propertyName, $properties)) {
                            continue;
                        }
                        $methodParameters = $this->reflectionService->getMethodParameters($className, $methodName);
                        if (count($methodParameters) !== 1) {
                            $this->logger->debug(sprintf('Could not autowire property %s because %s() expects %s instead of exactly 1 parameter.', $className . '::' . $propertyName, $methodName, (count($methodParameters) ?: 'none')));
                            continue;
                        }
                        $methodParameter = array_pop($methodParameters);
                        if ($methodParameter['class'] === null) {
                            $this->logger->debug(sprintf('Could not autowire property %s because the method parameter in %s() contained no class type hint.', $className . '::' . $propertyName, $methodName));
                            continue;
                        }
                        $properties[$propertyName] = new ConfigurationProperty($propertyName, $methodParameter['class'], ConfigurationProperty::PROPERTY_TYPES_OBJECT);
                    }
                }
            }

            foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, Inject::class) as $propertyName) {
                if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
                    throw new ObjectException(sprintf('The property "%s" in class "%s" must not be private when annotated for injection.', $propertyName, $className), 1328109641);
                }
                if (!array_key_exists($propertyName, $properties)) {
                    /** @var Inject $injectAnnotation */
                    $injectAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, Inject::class);
                    $enableLazyInjection = $injectAnnotation->lazy;
                    $objectName = $injectAnnotation->name;
                    if ($objectName === null) {
                        $objectName = $this->reflectionService->getPropertyType($className, $propertyName);
                        if ($objectName !== null) {
                            $enableLazyInjection = false; # See:  https://github.com/neos/flow-development-collection/issues/2114
                        }
                    }
                    if ($objectName === null) {
                        $objectName = trim(implode('', $this->reflectionService->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
                    }
                    $configurationProperty = new ConfigurationProperty($propertyName, $objectName, ConfigurationProperty::PROPERTY_TYPES_OBJECT, null, $enableLazyInjection);
                    $properties[$propertyName] = $configurationProperty;
                }
            }

            foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, InjectConfiguration::class) as $propertyName) {
                if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
                    throw new ObjectException(sprintf('The property "%s" in class "%s" must not be private when annotated for configuration injection.', $propertyName, $className), 1416765599);
                }
                if ($this->reflectionService->isPropertyPromoted($className, $propertyName)) {
                    continue;
                }
                if (array_key_exists($propertyName, $properties)) {
                    continue;
                }
                /** @var InjectConfiguration $injectConfigurationAnnotation */
                $injectConfigurationAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, InjectConfiguration::class);
                $properties[$propertyName] = new ConfigurationProperty(
                    $propertyName,
                    [
                        'type' => $injectConfigurationAnnotation->type,
                        'path' => $injectConfigurationAnnotation->getFullConfigurationPath($objectConfiguration->getPackageKey())
                    ],
                    ConfigurationProperty::PROPERTY_TYPES_CONFIGURATION
                );
            }

            foreach ($this->reflectionService->getPropertyNamesByAnnotation($className, InjectCache::class) as $propertyName) {
                if ($this->reflectionService->isPropertyPrivate($className, $propertyName)) {
                    throw new ObjectException(sprintf('The property "%s" in class "%s" must not be private when annotated for cache injection.', $propertyName, $className), 1416765599);
                }
                if (array_key_exists($propertyName, $properties)) {
                    continue;
                }
                /** @var InjectCache $injectCacheAnnotation */
                $injectCacheAnnotation = $this->reflectionService->getPropertyAnnotation($className, $propertyName, InjectCache::class);
                $properties[$propertyName] = new ConfigurationProperty($propertyName, ['identifier' => $injectCacheAnnotation->identifier], ConfigurationProperty::PROPERTY_TYPES_CACHE);
            }

            $objectConfiguration->setProperties($properties);
        }

        return $objectConfigurations;
    }
}
