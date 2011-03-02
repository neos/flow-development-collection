<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Configuration;

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
 * FLOW3 Object Configuration
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @proxy disable
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
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectName() {
		return $this->objectName;
	}

	/**
	 * Setter function for property "className"
	 *
	 * @param string $className Name of the class which provides the functionality for this object
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setClassName($className) {
		$this->className = $className;
	}

	/**
	 * Returns the class name
	 *
	 * @return string Name of the implementing class of this object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Sets the class name of a factory which is in charge of instantiating this object
	 *
	 * @param string $className Valid class name of a factory
	 * @return void
	 */
	public function setFactoryObjectName($className) {
		if (!class_exists($className, TRUE)) {
			throw new \F3\FLOW3\Object\Exception\InvalidClassException('"' . $className . '" is not a valid class name or a class of that name does not exist.', 1229697796);
		}
		$this->factoryObjectName= $className;
	}

	/**
	 * Returns the class name of the factory for this object, if any
	 *
	 * @return string The factory class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFactoryObjectName() {
		return $this->factoryObjectName;
	}

	/**
	 * Sets the name of the factory method
	 *
	 * @param string $methodName The factory method name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getFactoryMethodName() {
		return $this->factoryMethodName;
	}

	/**
	 * Setter function for property "scope"
	 *
	 * @param integer $scope Name of the scope
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setScope($scope) {
		$this->scope = $scope;
	}

	/**
	 * Returns the scope for this object
	 *
	 * @return string The scope, one of the SCOPE constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScope() {
		return $this->scope;
	}

	/**
	 * Setter function for property "autowiring"
	 *
	 * @param integer $autowiring One of the AUTOWIRING_MODE_* constants
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setAutowiring($autowiring) {
		$this->autowiring = $autowiring;
	}

	/**
	 * Returns the autowiring mode for the configured object
	 *
	 * @return integer Value of one of the AUTOWIRING_MODE_* constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAutowiring() {
		return $this->autowiring;
	}

	/**
	 * Setter function for property "lifecycleInitializationMethodName"
	 *
	 * @param string $lifecycleInitializationMethodName Name of the method to call after setter injection
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setLifecycleInitializationMethodName($lifecycleInitializationMethodName) {
		$this->lifecycleInitializationMethodName = $lifecycleInitializationMethodName;
	}

	/**
	 * Returns the name of the lifecycle initialization method for this object
	 *
	 * @return string The name of the intialization method
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLifecycleShutdownMethodName() {
		return $this->lifecycleShutdownMethodName;
	}

	/**
	 * Setter function for injection properties. If an empty array is passed to this
	 * method, all (possibly) defined properties are removed from the configuration.
	 *
	 * @param array $properties Array of \F3\FLOW3\Object\Configuration\ConfigurationProperty
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @return array Array of \F3\FLOW3\Object\Configuration\ConfigurationProperty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Setter function for a single injection property
	 *
	 * @param \F3\FLOW3\Object\Configuration\ConfigurationProperty $property
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setProperty(\F3\FLOW3\Object\Configuration\ConfigurationProperty $property) {
		$this->properties[$property->getName()] = $property;
	}

	/**
	 * Setter function for injection constructor arguments. If an empty array is passed to this
	 * method, all (possibly) defined constructor arguments are removed from the configuration.
	 *
	 * @param array $arguments Array of \F3\FLOW3\Object\Configuration\ConfigurationArgument
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
	 * @param \F3\FLOW3\Object\Configuration\ConfigurationArgument $argument The argument
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArgument(\F3\FLOW3\Object\Configuration\ConfigurationArgument $argument) {
		$this->arguments[$argument->getIndex()] = $argument;
	}

	/**
	 * Returns a sorted array of constructor arguments indexed by position (starting with "1")
	 *
	 * @return array A sorted array of \F3\FLOW3\Object\Configuration\ConfigurationArgument objects with the argument position as index
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @param string $hint The hint - e.g. the file name of the configuration file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setConfigurationSourceHint($hint) {
		$this->configurationSourceHint = $hint;
	}

	/**
	 * Returns some information (if any) about where this configuration has been created.
	 *
	 * @return string The hint - e.g. the file name of the configuration file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getConfigurationSourceHint() {
		return $this->configurationSourceHint;
	}
}

?>