<?php
namespace TYPO3\Flow\Object\Configuration;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * Flow Object Configuration
 *
 * @Flow\Proxy(false)
 */
class Configuration {

	const AUTOWIRING_MODE_OFF = 0;
	const AUTOWIRING_MODE_ON = 1;

	const SCOPE_PROTOTYPE = 1;
	const SCOPE_SINGLETON = 2;
	const SCOPE_SESSION = 3;

	/**
	 * Name of the object
	 * @var string $objectName
	 */
	protected $objectName;

	/**
	 * Name of the class the object is based on
	 * @var string $className
	 */
	protected $className;

	/**
	 * Key of the package the specified object is part of
	 * @var string
	 */
	protected $packageKey;

	/**
	 * If set, specifies the factory class used to create this object
	 * @var string
	 */
	protected $factoryObjectName = '';

	/**
	 * Name of the factory method. Only used if $factoryObjectName is set.
	 * @var string
	 */
	protected $factoryMethodName = 'create';

	/**
	 * @var string
	 */
	protected $scope = self::SCOPE_PROTOTYPE;

	/**
	 * Arguments of the constructor detected by reflection
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * Array of properties which are injected into the object
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Mode of the autowiring feature. One of the AUTOWIRING_MODE_* constants
	 * @var integer
	 */
	protected $autowiring = self::AUTOWIRING_MODE_ON;

	/**
	 * Name of the method to call during the initialization of the object (after dependencies are injected)
	 * @var string
	 */
	protected $lifecycleInitializationMethodName = 'initializeObject';

	/**
	 * Name of the method to call during the shutdown of the framework
	 * @var string
	 */
	protected $lifecycleShutdownMethodName = 'shutdownObject';

	/**
	 * Information about where this configuration has been created. Used in error messages to make debugging easier.
	 * @var string
	 */
	protected $configurationSourceHint = '< unknown >';

	/**
	 * The constructor
	 *
	 * @param string $objectName The unique identifier of the object
	 * @param string $className Name of the class which provides the functionality of this object
	 */
	public function __construct($objectName, $className = NULL) {
		$backtrace = debug_backtrace();
		if (isset($backtrace[1]['object'])) {
			$this->configurationSourceHint = get_class($backtrace[1]['object']);
		} elseif (isset($backtrace[1]['class'])) {
			$this->configurationSourceHint = $backtrace[1]['class'];
		}

		$this->objectName = $objectName;
		$this->className = ($className === NULL ? $objectName : $className);
	}

	/**
	 * Returns the object name
	 *
	 * @return string object name
	 */
	public function getObjectName() {
		return $this->objectName;
	}

	/**
	 * Setter function for property "className"
	 *
	 * @param string $className Name of the class which provides the functionality for this object
	 * @return void
	 */
	public function setClassName($className) {
		$this->className = $className;
	}

	/**
	 * Returns the class name
	 *
	 * @return string Name of the implementing class of this object
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Sets the package key
	 *
	 * @param string $packageKey Key of the package this object is part of
	 * @return void
	 */
	public function setPackageKey($packageKey) {
		$this->packageKey = $packageKey;
	}

	/**
	 * Returns the package key
	 *
	 * @return string Key of the package this object is part of
	 */
	public function getPackageKey() {
		return $this->packageKey;
	}

	/**
	 * Sets the class name of a factory which is in charge of instantiating this object
	 *
	 * @param string $className Valid class name of a factory
	 * @return void
	 * @throws \TYPO3\Flow\Object\Exception\InvalidClassException
	 */
	public function setFactoryObjectName($className) {
		if (!class_exists($className, TRUE)) {
			throw new \TYPO3\Flow\Object\Exception\InvalidClassException('"' . $className . '" is not a valid class name or a class of that name does not exist.', 1229697796);
		}
		$this->factoryObjectName = $className;
	}

	/**
	 * Returns the class name of the factory for this object, if any
	 *
	 * @return string The factory class name
	 */
	public function getFactoryObjectName() {
		return $this->factoryObjectName;
	}

	/**
	 * Sets the name of the factory method
	 *
	 * @param string $methodName The factory method name
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function setFactoryMethodName($methodName) {
		if (!is_string($methodName) || $methodName === '') {
			throw new \InvalidArgumentException('No valid factory method name specified.', 1229700126);
		}
		$this->factoryMethodName = $methodName;
	}

	/**
	 * Returns the factory method name
	 *
	 * @return string The factory method name
	 */
	public function getFactoryMethodName() {
		return $this->factoryMethodName;
	}

	/**
	 * Setter function for property "scope"
	 *
	 * @param integer $scope Name of the scope
	 * @return void
	 */
	public function setScope($scope) {
		$this->scope = $scope;
	}

	/**
	 * Returns the scope for this object
	 *
	 * @return string The scope, one of the SCOPE constants
	 */
	public function getScope() {
		return $this->scope;
	}

	/**
	 * Setter function for property "autowiring"
	 *
	 * @param integer $autowiring One of the AUTOWIRING_MODE_* constants
	 * @return void
	 */
	public function setAutowiring($autowiring) {
		$this->autowiring = $autowiring;
	}

	/**
	 * Returns the autowiring mode for the configured object
	 *
	 * @return integer Value of one of the AUTOWIRING_MODE_* constants
	 */
	public function getAutowiring() {
		return $this->autowiring;
	}

	/**
	 * Setter function for property "lifecycleInitializationMethodName"
	 *
	 * @param string $lifecycleInitializationMethodName Name of the method to call after setter injection
	 * @return void
	 */
	public function setLifecycleInitializationMethodName($lifecycleInitializationMethodName) {
		$this->lifecycleInitializationMethodName = $lifecycleInitializationMethodName;
	}

	/**
	 * Returns the name of the lifecycle initialization method for this object
	 *
	 * @return string The name of the initialization method
	 */
	public function getLifecycleInitializationMethodName() {
		return $this->lifecycleInitializationMethodName;
	}

	/**
	 * Setter function for property "lifecycleShutdownMethodName"
	 *
	 * @param string $lifecycleShutdownMethodName Name of the method to call during shutdown of the framework
	 * @return void
	 */
	public function setLifecycleShutdownMethodName($lifecycleShutdownMethodName) {
		$this->lifecycleShutdownMethodName = $lifecycleShutdownMethodName;
	}

	/**
	 * Returns the name of the lifecycle shutdown method for this object
	 *
	 * @return string The name of the shutdown method
	 */
	public function getLifecycleShutdownMethodName() {
		return $this->lifecycleShutdownMethodName;
	}

	/**
	 * Setter function for injection properties. If an empty array is passed to this
	 * method, all (possibly) defined properties are removed from the configuration.
	 *
	 * @param array $properties Array of \TYPO3\Flow\Object\Configuration\ConfigurationProperty
	 * @return void
	 */
	public function setProperties(array $properties) {
		if ($properties === array()) {
			$this->properties = array();
		} else {
			foreach ($properties as $value) {
				$this->setProperty($value);
			}
		}
	}

	/**
	 * Returns the currently set injection properties of the object
	 *
	 * @return array<TYPO3\Flow\Object\Configuration\ConfigurationProperty>
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Setter function for a single injection property
	 *
	 * @param \TYPO3\Flow\Object\Configuration\ConfigurationProperty $property
	 * @return void
	 */
	public function setProperty(\TYPO3\Flow\Object\Configuration\ConfigurationProperty $property) {
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * Setter function for injection constructor arguments. If an empty array is passed to this
	 * method, all (possibly) defined constructor arguments are removed from the configuration.
	 *
	 * @param array<TYPO3\Flow\Object\Configuration\ConfigurationArgument> $arguments
	 * @return void
	 */
	public function setArguments(array $arguments) {
		if ($arguments === array()) {
			$this->arguments = array();
		} else {
			foreach ($arguments as $argument) {
				if ($argument !== NULL) {
					$this->setArgument($argument);
				}
			}
		}
	}

	/**
	 * Setter function for a single constructor argument
	 *
	 * @param \TYPO3\Flow\Object\Configuration\ConfigurationArgument $argument The argument
	 * @return void
	 */
	public function setArgument(\TYPO3\Flow\Object\Configuration\ConfigurationArgument $argument) {
		$this->arguments[$argument->getIndex()] = $argument;
	}

	/**
	 * Returns a sorted array of constructor arguments indexed by position (starting with "1")
	 *
	 * @return array A sorted array of \TYPO3\Flow\Object\Configuration\ConfigurationArgument objects with the argument position as index
	 */
	public function getArguments() {
		if (count($this->arguments) < 1 ) {
			return array();
		}

		asort($this->arguments);
		$lastArgument = end($this->arguments);
		$argumentsCount = $lastArgument->getIndex();
		$sortedArguments = array();
		for ($index = 1; $index <= $argumentsCount; $index++) {
			$sortedArguments[$index] = isset($this->arguments[$index]) ? $this->arguments[$index] : NULL;
		}
		return $sortedArguments;
	}

	/**
	 * Sets some information (hint) about where this configuration has been created.
	 *
	 * @param string $hint The hint - e.g. the filename of the configuration file
	 * @return void
	 */
	public function setConfigurationSourceHint($hint) {
		$this->configurationSourceHint = $hint;
	}

	/**
	 * Returns some information (if any) about where this configuration has been created.
	 *
	 * @return string The hint - e.g. the filename of the configuration file
	 */
	public function getConfigurationSourceHint() {
		return $this->configurationSourceHint;
	}
}

?>