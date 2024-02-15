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
use Neos\Flow\Configuration\Exception\InvalidConfigurationException;

/**
 * Flow Object Configuration
 *
 * @Flow\Proxy(false)
 */
class Configuration
{
    public const AUTOWIRING_MODE_OFF = 0;

    public const AUTOWIRING_MODE_ON = 1;

    public const SCOPE_PROTOTYPE = 1;

    public const SCOPE_SINGLETON = 2;

    public const SCOPE_SESSION = 3;

    /**
     * Name of the object
     *
     * @var string $objectName
     */
    protected string $objectName;

    /**
     * Name of the class the object is based on
     *
     * @var string $className
     */
    protected string $className;

    /**
     * Key of the package the specified object is part of
     * @var string
     */
    protected string $packageKey = '';

    /**
     * If set, specifies the factory object name used to create this object
     * @var string
     */
    protected string $factoryObjectName = '';

    /**
     * Name of the factory method.
     * @var string
     */
    protected string $factoryMethodName = '';

    /**
     * Arguments of the factory method
     * @var array
     */
    protected array $factoryArguments = [];

    /**
     * @var int
     */
    protected int $scope = self::SCOPE_PROTOTYPE;

    /**
     * Arguments of the constructor detected by reflection
     * @var array
     */
    protected array $arguments = [];

    /**
     * Array of properties which are injected into the object
     * @var array
     */
    protected array $properties = [];

    /**
     * Mode of the autowiring feature. One of the AUTOWIRING_MODE_* constants
     * @var int
     */
    protected int $autowiring = self::AUTOWIRING_MODE_ON;

    /**
     * Name of the method to call during the initialization of the object (after dependencies are injected)
     * @var string
     */
    protected string $lifecycleInitializationMethodName = 'initializeObject';

    /**
     * Name of the method to call during the shutdown of the framework
     * @var string
     */
    protected string $lifecycleShutdownMethodName = 'shutdownObject';

    /**
     * Information about where this configuration has been created. Used in error messages to make debugging easier.
     * @var string
     */
    protected string $configurationSourceHint = '< unknown >';

    /**
     * The constructor
     *
     * @param string $objectName The unique identifier of the object
     * @param string $className Name of the class which provides the functionality of this object
     */
    public function __construct(string $objectName, string $className)
    {
        $backtrace = debug_backtrace();
        if (isset($backtrace[1]['object'])) {
            $this->configurationSourceHint = get_class($backtrace[1]['object']);
        } elseif (isset($backtrace[1]['class'])) {
            $this->configurationSourceHint = $backtrace[1]['class'];
        }

        $this->objectName = $objectName;
        $this->className = $className;
    }

    /**
     * Sets the object name
     *
     * @param string $objectName
     * @return void
     */
    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    /**
     * Returns the object name
     *
     * @return string object name
     */
    public function getObjectName(): string
    {
        return $this->objectName;
    }

    /**
     * Setter function for property "className"
     *
     * @param string $className Name of the class which provides the functionality for this object
     * @return void
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * Returns the class name
     *
     * @return string Name of the implementing class of this object
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Sets the package key
     *
     * @param string $packageKey Key of the package this object is part of
     * @return void
     */
    public function setPackageKey(string $packageKey): void
    {
        $this->packageKey = $packageKey;
    }

    /**
     * Returns the package key
     *
     * @return string Key of the package this object is part of
     */
    public function getPackageKey(): string
    {
        return $this->packageKey;
    }

    /**
     * Sets the class name of a factory which is in charge of instantiating this object
     *
     * @param string $objectName Valid object name of a factory
     * @return void
     */
    public function setFactoryObjectName(string $objectName): void
    {
        $this->factoryObjectName = $objectName;
        if ($this->factoryMethodName === '') {
            // Needed for b/c because all configured factory objects should default to 'create' method, but not having
            // a factory object should not lead to a global static 'create' factory method
            $this->factoryMethodName = 'create';
        }
    }

    /**
     * Returns the class name of the factory for this object, if any
     *
     * @return string The factory class name
     */
    public function getFactoryObjectName(): string
    {
        return $this->factoryObjectName;
    }

    /**
     * Sets the name of the factory method
     *
     * @param string $methodName The factory method name
     * @return void
     * @throws \InvalidArgumentException
     */
    public function setFactoryMethodName(string $methodName): void
    {
        if ($methodName === '') {
            throw new \InvalidArgumentException('No valid factory method name specified.', 1229700126);
        }
        $this->factoryMethodName = $methodName;
    }

    /**
     * Returns the factory method name
     *
     * @return string The factory method name
     */
    public function getFactoryMethodName(): string
    {
        return $this->factoryMethodName;
    }

    /**
     * Returns true if factoryObjectName or factoryMethodName are defined.
     *
     * @return boolean
     */
    public function isCreatedByFactory(): bool
    {
        return ($this->factoryObjectName !== '' || $this->factoryMethodName !== '');
    }

    /**
     * Setter function for property "scope"
     *
     * @param integer $scope Name of the scope
     * @return void
     */
    public function setScope(int $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * Returns the scope for this object
     *
     * @return int The scope, one of the SCOPE constants
     */
    public function getScope(): int
    {
        return $this->scope;
    }

    /**
     * Setter function for property "autowiring"
     *
     * @param integer $autowiring One of the AUTOWIRING_MODE_* constants
     * @return void
     */
    public function setAutowiring(int $autowiring): void
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Returns the autowiring mode for the configured object
     *
     * @return integer Value of one of the AUTOWIRING_MODE_* constants
     */
    public function getAutowiring(): int
    {
        return $this->autowiring;
    }

    /**
     * Setter function for property "lifecycleInitializationMethodName"
     *
     * @param string $lifecycleInitializationMethodName Name of the method to call after setter injection
     * @return void
     */
    public function setLifecycleInitializationMethodName(string $lifecycleInitializationMethodName): void
    {
        $this->lifecycleInitializationMethodName = $lifecycleInitializationMethodName;
    }

    /**
     * Returns the name of the lifecycle initialization method for this object
     *
     * @return string The name of the initialization method
     */
    public function getLifecycleInitializationMethodName(): string
    {
        return $this->lifecycleInitializationMethodName;
    }

    /**
     * Setter function for property "lifecycleShutdownMethodName"
     *
     * @param string $lifecycleShutdownMethodName Name of the method to call during shutdown of the framework
     * @return void
     */
    public function setLifecycleShutdownMethodName(string $lifecycleShutdownMethodName): void
    {
        $this->lifecycleShutdownMethodName = $lifecycleShutdownMethodName;
    }

    /**
     * Returns the name of the lifecycle shutdown method for this object
     *
     * @return string The name of the shutdown method
     */
    public function getLifecycleShutdownMethodName(): string
    {
        return $this->lifecycleShutdownMethodName;
    }

    /**
     * Setter function for injection properties. If an empty array is passed to this
     * method, all (possibly) defined properties are removed from the configuration.
     *
     * @param array $properties Array of ConfigurationProperty
     * @throws InvalidConfigurationException
     * @return void
     */
    public function setProperties(array $properties): void
    {
        if ($properties === []) {
            $this->properties = [];
        } else {
            foreach ($properties as $value) {
                if ($value instanceof ConfigurationProperty) {
                    $this->setProperty($value);
                } else {
                    throw new InvalidConfigurationException(sprintf('Only ConfigurationProperty instances are allowed, "%s" given', get_debug_type($value)), 1449217567);
                }
            }
        }
    }

    /**
     * Returns the currently set injection properties of the object
     *
     * @return array<ConfigurationProperty>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Setter function for a single injection property
     *
     * @param ConfigurationProperty $property
     * @return void
     */
    public function setProperty(ConfigurationProperty $property): void
    {
        $this->properties[$property->getName()] = $property;
    }

    /**
     * Setter function for injection constructor arguments. If an empty array is passed to this
     * method, all (possibly) defined constructor arguments are removed from the configuration.
     *
     * @param array<ConfigurationArgument> $arguments
     * @throws InvalidConfigurationException
     * @return void
     */
    public function setArguments(array $arguments): void
    {
        if ($arguments === []) {
            $this->arguments = [];
        } else {
            foreach ($arguments as $argument) {
                if ($argument instanceof ConfigurationArgument) {
                    $this->setArgument($argument);
                } else {
                    throw new InvalidConfigurationException(sprintf('Only ConfigurationArgument instances are allowed, "%s" given', get_debug_type($argument)), 1449217803);
                }
            }
        }
    }

    /**
     * Setter function for a single constructor argument
     *
     * @param ConfigurationArgument $argument The argument
     * @return void
     */
    public function setArgument(ConfigurationArgument $argument): void
    {
        $this->arguments[$argument->getIndex()] = $argument;
    }

    /**
     * Returns a sorted array of constructor arguments indexed by position (starting with "1")
     *
     * @return array<ConfigurationArgument> A sorted array of ConfigurationArgument objects with the argument position as index
     */
    public function getArguments(): array
    {
        if (count($this->arguments) < 1) {
            return [];
        }

        asort($this->arguments);
        $lastArgument = end($this->arguments);
        $argumentsCount = $lastArgument->getIndex();
        $sortedArguments = [];
        for ($index = 1; $index <= $argumentsCount; $index++) {
            $sortedArguments[$index] = $this->arguments[$index] ?? null;
        }
        return $sortedArguments;
    }

    /**
     * Setter function for a single factory method argument
     *
     * @param ConfigurationArgument $argument The argument
     * @return void
     */
    public function setFactoryArgument(ConfigurationArgument $argument): void
    {
        $this->factoryArguments[$argument->getIndex()] = $argument;
    }

    /**
     * Returns a sorted array of factory method arguments indexed by position (starting with "1")
     *
     * @return array<ConfigurationArgument> A sorted array of ConfigurationArgument objects with the argument position as index
     */
    public function getFactoryArguments(): array
    {
        if (count($this->factoryArguments) < 1) {
            return [];
        }

        asort($this->factoryArguments);
        $lastArgument = end($this->factoryArguments);
        $argumentsCount = $lastArgument->getIndex();
        $sortedArguments = [];
        for ($index = 1; $index <= $argumentsCount; $index++) {
            $sortedArguments[$index] = $this->factoryArguments[$index] ?? null;
        }
        return $sortedArguments;
    }

    /**
     * Sets some information (hint) about where this configuration has been created.
     *
     * @param string $hint The hint - e.g. the filename of the configuration file
     * @return void
     */
    public function setConfigurationSourceHint(string $hint): void
    {
        $this->configurationSourceHint = $hint;
    }

    /**
     * Returns some information (if any) about where this configuration has been created.
     *
     * @return string The hint - e.g. the filename of the configuration file
     */
    public function getConfigurationSourceHint(): string
    {
        return $this->configurationSourceHint;
    }
}
