<?php

namespace Neos\Flow\ObjectManagement\Configuration;

use function GuzzleHttp\json_encode;

use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\ObjectManagement\Exception\InvalidObjectConfigurationException;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\Exception\InvalidClassException;
use Neos\Flow\Reflection\ReflectionService;

/**
 *
 */
readonly class ConfigurationParser
{
    public function __construct(
        private ReflectionService $reflectionService
    ) {
    }

    /**
     * Builds an object configuration object from a generic configuration container.
     *
     * @param string $objectName Name of the object
     * @param array<string, mixed> $rawConfigurationOptions The configuration array with options for the object configuration
     * @param string $configurationSourceHint A human readable hint on the original source of the configuration (for troubleshooting)
     * @param Configuration|null $existingObjectConfiguration If set, this object configuration object will be used instead of creating a fresh one
     * @return Configuration The object configuration object
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException if errors occurred during parsing
     * @throws \ReflectionException
     */
    public function parseConfigurationArray(string $objectName, array $rawConfigurationOptions, string $configurationSourceHint = '', Configuration|null $existingObjectConfiguration = null): Configuration
    {
        $className = $rawConfigurationOptions['className'] ?? $objectName;
        $objectConfiguration = ($existingObjectConfiguration instanceof Configuration) ? $existingObjectConfiguration : new Configuration($objectName, $className);
        $objectConfiguration->setConfigurationSourceHint($configurationSourceHint);

        foreach ($rawConfigurationOptions as $optionName => $optionValue) {
            switch ($optionName) {
                case 'scope':
                    $objectConfiguration->setScope(self::parseScope($optionValue));
                    break;
                case 'properties':
                    if (is_array($optionValue)) {
                        $objectConfiguration = $this->parsePropertyConfiguration($objectConfiguration, $optionValue);
                    }
                    break;
                case 'arguments':
                    if (is_array($optionValue)) {
                        $objectConfiguration = $this->parseArgumentsConfiguration($objectConfiguration, $rawConfigurationOptions, $optionValue);
                    }
                    break;
                case 'className':
                    $objectConfiguration->setClassName($rawConfigurationOptions['className']);
                    break;
                case 'factoryObjectName':
                    $objectConfiguration->setFactoryObjectName(trim((string)$optionValue));
                    break;
                case 'factoryMethodName':
                    $objectConfiguration->setFactoryMethodName(trim((string)$optionValue));
                    break;
                case 'lifecycleInitializationMethodName':
                    $objectConfiguration->setLifecycleInitializationMethodName($optionValue);
                    break;
                case 'lifecycleShutdownMethodName':
                    $objectConfiguration->setLifecycleShutdownMethodName($optionValue);
                    break;
                case 'autowiring':
                    $objectConfiguration->setAutowiring(self::parseAutowiring($optionValue));
                    break;
                default:
                    throw new InvalidObjectConfigurationException('Invalid configuration option "' . $optionName . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1167574981);
            }
        }
        return $objectConfiguration;
    }

    /**
     * @param Configuration $objectConfiguration
     * @param array<string, mixed> $optionValue
     * @return Configuration
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException
     * @throws \ReflectionException
     */
    protected function parsePropertyConfiguration(Configuration $objectConfiguration, array $optionValue): Configuration
    {
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

        return $objectConfiguration;
    }

    /**
     * @param Configuration $objectConfiguration
     * @param array<string, mixed> $rawConfigurationOptions
     * @param array<string, mixed> $optionValue
     * @return Configuration
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException
     * @throws \ReflectionException
     */
    protected function parseArgumentsConfiguration(Configuration $objectConfiguration, array $rawConfigurationOptions, array $optionValue): Configuration
    {
        foreach ($optionValue as $argumentName => $argumentValue) {
            $argumentIndex = (int)$argumentName;
            if (array_key_exists('value', $argumentValue)) {
                $argument = new ConfigurationArgument($argumentIndex, $argumentValue['value'], ConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE);
            } elseif (array_key_exists('object', $argumentValue)) {
                $argument = $this->parseArgumentOfTypeObject($argumentName, $argumentValue['object'], $objectConfiguration);
            } elseif (array_key_exists('setting', $argumentValue)) {
                $argument = new ConfigurationArgument($argumentIndex, $argumentValue['setting'], ConfigurationArgument::ARGUMENT_TYPES_SETTING);
            } else {
                throw new InvalidObjectConfigurationException('Invalid configuration syntax. Expecting "value", "object" or "setting" as value for argument "' . $argumentName . '", instead found "' . (is_array($argumentValue) ? implode(', ', array_keys($argumentValue)) : $argumentValue) . '" (source: ' . $objectConfiguration->getConfigurationSourceHint() . ')', 1230563250);
            }
            if (isset($rawConfigurationOptions['factoryObjectName']) || isset($rawConfigurationOptions['factoryMethodName'])) {
                $objectConfiguration->setFactoryArgument($argument);
            } else {
                $objectConfiguration->setArgument($argument);
            }
        }

        return $objectConfiguration;
    }

    /**
     * Parses the configuration for arguments of type OBJECT
     *
     * @param string $argumentName Name of the argument
     * @param mixed $objectNameOrConfiguration Value of the "object" section of the argument configuration - either a string or an array
     * @param Configuration $parentObjectConfiguration The Configuration object this property belongs to
     * @return ConfigurationArgument A configuration argument of type object
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException
     * @throws \ReflectionException
     */
    protected function parseArgumentOfTypeObject(string $argumentName, mixed $objectNameOrConfiguration, Configuration $parentObjectConfiguration): ConfigurationArgument
    {
        $objectName = null;
        if (is_array($objectNameOrConfiguration)) {
            if (isset($objectNameOrConfiguration['name'])) {
                $objectName = $objectNameOrConfiguration['name'];
                unset($objectNameOrConfiguration['name']);
            } else {
                $className = $parentObjectConfiguration->getClassName();
                if ($className === '') {
                    $objectName = null;
                } else {
                    $arguments = $this->reflectionService->getMethodParameters($className, '__construct');
                    if (is_numeric($argumentName)) {
                        foreach ($arguments as $argument) {
                            if ($argument['position'] === ((int)$argumentName - 1)) {
                                $objectName = $argument['type'];
                            }
                        }
                    } else {
                        $objectName = $arguments[$argumentName]['type'];
                    }
                }
            }

            if ($objectName === null) {
                // This is a weird object configuration, we create a magic virtual object for it, throwing might be stricter,
                // but this can happen in the real world. So safer for now. Also global Objects.yaml is problematic.
                $objectName = 'Runtime.Virtual.Object:' . md5(json_encode($objectNameOrConfiguration, JSON_THROW_ON_ERROR));
            }

            $objectConfiguration = $this->parseConfigurationArray($objectName, $objectNameOrConfiguration, $parentObjectConfiguration->getConfigurationSourceHint() . ', argument "' . $argumentName . '"');
            $argument = new ConfigurationArgument((int)$argumentName, $objectConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
        } else {
            $argument = new ConfigurationArgument((int)$argumentName, $objectNameOrConfiguration, ConfigurationArgument::ARGUMENT_TYPES_OBJECT);
        }
        return $argument;
    }

    /**
     * Parses the configuration for properties of type OBJECT
     *
     * @param string $propertyName Name of the property
     * @param mixed $objectNameOrConfiguration Value of the "object" section of the property configuration - either a string or an array
     * @param Configuration $parentObjectConfiguration The Configuration object this property belongs to
     * @return ConfigurationProperty A configuration property of type object
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws InvalidObjectConfigurationException
     * @throws \ReflectionException
     */
    protected function parsePropertyOfTypeObject(string $propertyName, mixed $objectNameOrConfiguration, Configuration $parentObjectConfiguration): ConfigurationProperty
    {
        if (is_array($objectNameOrConfiguration)) {
            if (isset($objectNameOrConfiguration['name'])) {
                $objectName = $objectNameOrConfiguration['name'];
                unset($objectNameOrConfiguration['name']);
            } else {
                $parentClassName = $parentObjectConfiguration->getClassName();
                if ($parentClassName === '') {
                    throw new InvalidObjectConfigurationException(sprintf('Object %s (%s), for property "%s", contains neither object name, nor factory object name, and nor is the property properly @var - annotated.', $parentObjectConfiguration->getClassName(), $parentObjectConfiguration->getConfigurationSourceHint(), $propertyName), 1297097815);
                } else {
                    $propertyType = $this->reflectionService->getPropertyType($parentClassName, $propertyName);
                    $objectName = $propertyType;
                    if ($objectName === null) {
                        $annotations = $this->reflectionService->getPropertyTagValues($parentClassName, $propertyName, 'var');
                        if (count($annotations) !== 1) {
                            throw new InvalidObjectConfigurationException(sprintf('Object %s (%s), for property "%s", contains neither object name, nor factory object name, and nor is the property properly @var - annotated.', $parentObjectConfiguration->getClassName(), $parentObjectConfiguration->getConfigurationSourceHint(), $propertyName), 1297097815);
                        }
                        $objectName = $annotations[0];
                    }
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
     * Parses the value of the option "scope"
     *
     * @param string $value Value of the option
     * @return integer The scope translated into a Configuration::SCOPE_* constant
     * @throws InvalidObjectConfigurationException if an invalid scope has been specified
     */
    protected static function parseScope(string $value): int
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
     * @param bool|int $value Value of the option
     * @return integer The autowiring option translated into one of Configuration::AUTOWIRING_MODE_*
     * @throws InvalidObjectConfigurationException if an invalid option has been specified
     */
    protected static function parseAutowiring(bool|int $value): int
    {
        if ($value === true || $value === Configuration::AUTOWIRING_MODE_ON) {
            return Configuration::AUTOWIRING_MODE_ON;
        }

        if ($value === false || $value === Configuration::AUTOWIRING_MODE_OFF) {
            return Configuration::AUTOWIRING_MODE_OFF;
        }

        throw new InvalidObjectConfigurationException('Invalid autowiring declaration', 1283866757);
    }
}
