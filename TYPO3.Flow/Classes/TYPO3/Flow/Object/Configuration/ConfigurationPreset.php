<?php
namespace TYPO3\Flow\Object\Configuration;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\Exception\InvalidConfigurationException;

/**
 * Flow Object Configuration
 *
 * @Flow\Proxy(false)
 */
class ConfigurationPreset implements ConfigurationInterface
{
    /**
     * @var string
     */
    protected $presetName;

    /**
     * @var Configuration
     */
    protected $baseConfiguration;

    /**
     * Name of the class the object is based on
     * @var string $className
     */
    protected $className = null;

    /**
     * If set, specifies the factory object name used to create this object
     * @var string
     */
    protected $factoryObjectName = null;

    /**
     * Name of the factory method. Only used if $factoryObjectName is set.
     * @var string
     */
    protected $factoryMethodName = null;

    /**
     * @var string
     */
    protected $scope = null;

    /**
     * Arguments of the constructor detected by reflection
     * @var array
     */
    protected $arguments = null;

    /**
     * Array of properties which are injected into the object
     * @var array
     */
    protected $properties = null;

    /**
     * Mode of the autowiring feature. One of the AUTOWIRING_MODE_* constants
     * @var integer
     */
    protected $autowiring = null;

    /**
     * Name of the method to call during the initialization of the object (after dependencies are injected)
     * @var string
     */
    protected $lifecycleInitializationMethodName = null;

    /**
     * Name of the method to call during the shutdown of the framework
     * @var string
     */
    protected $lifecycleShutdownMethodName = null;

    /**
     * @param string $presetName
     * @param Configuration $baseConfiguration
     */
    public function __construct($presetName, Configuration $baseConfiguration)
    {
        $this->presetName = $presetName;
        $this->baseConfiguration = $baseConfiguration;
    }

    /**
     * @return string
     */
    public function getPresetName()
    {
        return $this->presetName;
    }

    /**
     * Returns the object name
     *
     * @return string object name
     */
    public function getObjectName()
    {
        return $this->baseConfiguration->getObjectName() . ':' . $this->presetName;
    }

    /**
     * @param string $className
     * @return void
     */
    public function setClassName($className)
    {
        $this->className = $className;
    }

    /**
     * Returns the class name
     *
     * @return string Name of the implementing class of this object
     */
    public function getClassName()
    {
        return $this->className !== null ? $this->className : $this->baseConfiguration->getClassName();
    }

    /**
     * @param string $factoryObjectName
     * @return void
     */
    public function setFactoryObjectName($factoryObjectName)
    {
        $this->factoryObjectName = $factoryObjectName;
    }

    /**
     * Returns the class name of the factory for this object, if any
     *
     * @return string The factory class name
     */
    public function getFactoryObjectName()
    {
        return $this->factoryObjectName !== null ? $this->factoryObjectName : $this->baseConfiguration->getFactoryObjectName();
    }

    /**
     * Returns the factory method name
     *
     * @return string The factory method name
     */
    public function getFactoryMethodName()
    {
        return $this->factoryMethodName !== null ? $this->factoryMethodName : $this->baseConfiguration->getFactoryMethodName();
    }

    /**
     * @param string $factoryMethodName
     * @return void
     */
    public function setFactoryMethodName($factoryMethodName)
    {
        $this->factoryMethodName = $factoryMethodName;
    }

    /**
     * Returns true if factoryObjectName and factoryMethodName are defined.
     *
     * @return boolean
     */
    public function isCreatedByFactory()
    {
        if ($this->factoryObjectName !== null && $this->factoryObjectName !== '' && $this->factoryMethodName !== null && $this->factoryMethodName !== '') {
            return true;
        }
        return $this->baseConfiguration->isCreatedByFactory();
    }

    /**
     * @param string $scope
     * @return void
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Returns the scope for this object
     *
     * @return string The scope, one of the SCOPE constants
     */
    public function getScope()
    {
        return $this->scope !== null ? $this->scope : $this->baseConfiguration->getScope();
    }

    /**
     * @param integer $autowiring One of the AUTOWIRING_MODE_* constants
     * @return void
     */
    public function setAutowiring($autowiring)
    {
        $this->autowiring = $autowiring;
    }

    /**
     * Returns the autowiring mode for the configured object
     *
     * @return integer Value of one of the AUTOWIRING_MODE_* constants
     */
    public function getAutowiring()
    {
        return $this->autowiring !== null ? $this->autowiring : $this->baseConfiguration->getAutowiring();
    }

    /**
     * @param string $lifecycleInitializationMethodName
     * @return void
     */
    public function setLifecycleInitializationMethodName($lifecycleInitializationMethodName)
    {
        $this->lifecycleInitializationMethodName = $lifecycleInitializationMethodName;
    }

    /**
     * Returns the name of the lifecycle initialization method for this object
     *
     * @return string The name of the initialization method
     */
    public function getLifecycleInitializationMethodName()
    {
        return $this->lifecycleInitializationMethodName !== null ? $this->lifecycleInitializationMethodName : $this->baseConfiguration->getLifecycleInitializationMethodName();
    }

    /**
     * @param string $lifecycleShutdownMethodName
     * @return void
     */
    public function setLifecycleShutdownMethodName($lifecycleShutdownMethodName)
    {
        $this->lifecycleShutdownMethodName = $lifecycleShutdownMethodName;
    }

    /**
     * Returns the name of the lifecycle shutdown method for this object
     *
     * @return string The name of the shutdown method
     */
    public function getLifecycleShutdownMethodName()
    {
        return $this->lifecycleShutdownMethodName !== null ? $this->lifecycleShutdownMethodName : $this->baseConfiguration->getLifecycleShutdownMethodName();
    }

    /**
     * Setter function for injection properties. If an empty array is passed to this
     * method, all (possibly) defined properties are removed from the configuration.
     *
     * @param array $properties Array of \TYPO3\Flow\Object\Configuration\ConfigurationProperty
     * @throws InvalidConfigurationException
     * @return void
     */
    public function setProperties(array $properties)
    {
        if ($this->properties === null) {
            $this->properties = [];
        }
        foreach ($properties as $value) {
            if (!$value instanceof ConfigurationProperty) {
                throw new InvalidConfigurationException(sprintf('Only ConfigurationProperty instances are allowed, "%s" given', is_object($value) ? get_class($value) : gettype($value)), 1476710083);
            }
            $this->setProperty($value);
        }
    }

    /**
     * Returns the currently set injection properties of the object
     *
     * @return array<TYPO3\Flow\Object\Configuration\ConfigurationProperty>
     */
    public function getProperties()
    {
        return $this->properties !== null ? $this->properties : $this->baseConfiguration->getProperties();
    }

    /**
     * Setter function for a single injection property
     *
     * @param ConfigurationProperty $property
     * @return void
     */
    public function setProperty(ConfigurationProperty $property)
    {
        if ($this->properties === null) {
            $this->properties = [];
        }
        $this->properties[$property->getName()] = $property;
    }

    /**
     * Setter function for injection constructor arguments. If an empty array is passed to this
     * method, all (possibly) defined constructor arguments are removed from the configuration.
     *
     * @param array<TYPO3\Flow\Object\Configuration\ConfigurationArgument> $arguments
     * @throws InvalidConfigurationException
     * @return void
     */
    public function setArguments(array $arguments)
    {
        if ($this->arguments === null) {
            $this->arguments = [];
        }
        foreach ($arguments as $argument) {
            if ($argument === null || !$argument instanceof ConfigurationArgument) {
                throw new InvalidConfigurationException(sprintf('Only ConfigurationArgument instances are allowed, "%s" given', is_object($argument) ? get_class($argument) : gettype($argument)), 1449217803);
            }
            $this->setArgument($argument);
        }
    }

    /**
     * Setter function for a single constructor argument
     *
     * @param ConfigurationArgument $argument The argument
     * @return void
     */
    public function setArgument(ConfigurationArgument $argument)
    {
        if ($this->arguments === null) {
            $this->arguments = [];
        }
        $this->arguments[$argument->getIndex()] = $argument;
    }

    /**
     * Returns a sorted array of constructor arguments indexed by position (starting with "1")
     *
     * @return array A sorted array of \TYPO3\Flow\Object\Configuration\ConfigurationArgument objects with the argument position as index
     */
    public function getArguments()
    {
        if ($this->arguments === null) {
            return $this->baseConfiguration->getArguments();
        }
        asort($this->arguments);
        $lastArgument = end($this->arguments);
        $argumentsCount = $lastArgument->getIndex();
        $sortedArguments = array();
        for ($index = 1; $index <= $argumentsCount; $index++) {
            $sortedArguments[$index] = isset($this->arguments[$index]) ? $this->arguments[$index] : null;
        }
        return $sortedArguments;
    }

    /**
     * @return string
     */
    public function getPackageKey()
    {
        return $this->baseConfiguration->getPackageKey();
    }

    /**
     * @return string
     */
    public function getConfigurationSourceHint()
    {
        return $this->baseConfiguration->getConfigurationSourceHint() . ' (preset "' . $this->presetName . '")';
    }
}
