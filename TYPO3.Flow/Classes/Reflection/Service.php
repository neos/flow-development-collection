<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Reflection;

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
 * A service for aquiring reflection based information in a performant way.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Service {

	/**
	 * If this service has been initialized
	 *
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $cache;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * If class alterations should be detected on each initialization
	 *
	 * @var boolean
	 */
	protected $detectClassChanges = FALSE;

	/**
	 * All available class names to consider. Class name = key, value is always TRUE
	 *
	 * @var array
	 */
	protected $reflectedClassNames = array();

	/**
	 * Class names of all reflections which have been loaded from the cache.
	 *
	 * @var array
	 */
	protected $cachedClassNames = array();

	/**
	 * Names of interfaces and an array of class names implementing these
	 *
	 * @var array
	 */
	protected $interfaceImplementations = array();

	/**
	 * Names of classes which are abstract
	 *
	 * @var array
	 */
	protected $abstractClasses = array();

	/**
	 * Names of classes which are final
	 *
	 * @var array
	 */
	protected $finalClasses = array();

	/**
	 * Names of methods (in the form classname::methodname) which are final
	 *
	 * @var array
	 */
	protected $finalMethods = array();

	/**
	 * Names of methods (in the form classname::methodname) which are static
	 *
	 * @var array
	 */
	protected $staticMethods = array();

	/**
	 * Array of tags and the names of classes which are tagged with them
	 *
	 * @var array
	 */
	protected $taggedClasses = array();

	/**
	 * Array of class names and their tags and values
	 *
	 * @var array
	 */
	protected $classTagsValues = array();

	/**
	 * Array of class names and names of their constructors if they have one
	 *
	 * @var array
	 */
	protected $classConstructorMethodNames = array();

	/**
	 * Array of class names, method names and their tags and values
	 *
	 * @var array
	 */
	protected $methodTagsValues = array();

	/**
	 * Array of class names, method names, their parameters and additional information about the parameters
	 *
	 * @var array
	 */
	protected $methodParameters = array();

	/**
	 * Array of class names, method names and their visibility (' ' = public, '*' = protected, '-' = private)
	 *
	 * @var array
	 */
	protected $methodVisibilities = array();

	/**
	 * Array of class names and names of their properties
	 *
	 * @var array
	 */
	protected $classPropertyNames = array();

	/**
	 * Array of class names, property names and their tags and values
	 *
	 * @var array
	 */
	protected $propertyTagsValues = array();

	/**
	 * List of tags which are ignored while reflecting class and method annotations
	 *
	 * @var array
	 */
	protected $ignoredTags = array('package', 'subpackage', 'license', 'copyright', 'author', 'version', 'const');

	/**
	 * Sets the cache
	 *
	 * The cache must be set before initializing the Reflection Service
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $cache Cache for the reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->cache = $cache;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		if (isset($settings['reflection']['detectClassChanges'])) {
			$this->detectClassChanges = $settings['reflection']['detectClassChanges'] ? TRUE : FALSE;
		}
	}

	/**
	 * Injects the System Logger
	 *
	 * @param \F3\FLOW3\Log\SystemLoggerInterface
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Returns TRUE if the reflection service has been initialized or data has been
	 * imported.
	 *
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isInitialized() {
		return $this->initialized;
	}

	/**
	 * Initializes this service
	 *
	 * @param array $classNamesToReflect Names of available classes to consider in this reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize(array $classNamesToReflect) {
		if ($this->initialized) throw new \F3\FLOW3\Reflection\Exception('The Reflection Service can only be initialized once.', 1232044696);

		$this->loadFromCache();
		if ($this->detectClassChanges === TRUE) {
			$this->forgetChangedClasses();
		}
		$this->reflectEmergedClasses($classNamesToReflect);

		$this->initialized = TRUE;
	}

	/**
	 * Shuts the Reflection Service down.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function shutdown() {
		if ($this->cachedClassNames !== $this->reflectedClassNames) {
			$this->saveToCache();
		}
	}

	/**
	 * Tells if the specified class is known to this reflection service and
	 * reflection information is available.
	 *
	 * @param string $className Name of the class
	 * @return boolean If the class is reflected by this service
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassReflected($className) {
		return isset($this->reflectedClassNames[$className]);
	}

	/**
	 * Returns the names of all classes known to this reflection service.
	 *
	 * @return array Class names
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getAllClassNames() {
		return array_keys($this->reflectedClassNames);
	}

	/**
	 * Searches for and returns the class name of the default implementation of the given
	 * interface name. If no class implementing the interface was found or more than one
	 * implementation was found in the package defining the interface, FALSE is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return mixed Either the class name of the default implementation for the object type or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getDefaultImplementationClassNameForInterface($interfaceName) {
		if ($this->initialized !== TRUE) throw new \F3\FLOW3\Reflection\Exception('Reflection has not yet been initialized.', 1238667823);
		if (interface_exists($interfaceName) === FALSE) throw new \InvalidArgumentException('"' . $interface . '" does not exist or is not the name of an interface.', 1238769559);

		$classNamesFound = isset($this->interfaceImplementations[$interfaceName]) ? $this->interfaceImplementations[$interfaceName] : array();
		if (count($classNamesFound) === 1) return current($classNamesFound);
		if (count($classNamesFound) === 2 && isset($this->interfaceImplementations['F3\FLOW3\Object\ProxyInterface'])) {
			if (array_search(current($classNamesFound), $this->interfaceImplementations['F3\FLOW3\Object\ProxyInterface']) !== FALSE) return current($classNamesFound);
			next($classNamesFound);
			if (array_search(current($classNamesFound), $this->interfaceImplementations['F3\FLOW3\Object\ProxyInterface']) !== FALSE) return current($classNamesFound);
		}
		return FALSE;
	}

	/**
	 * Searches for and returns all class names of implementations of the given object type
	 * (interface name). If no class implementing the interface was found, an empty array is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return array An array of class names of the default implementation for the object type
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownInterface if the given interface does not exist
	 * @api
	 */
	public function getAllImplementationClassNamesForInterface($interfaceName) {
		if ($this->initialized !== TRUE) throw new \F3\FLOW3\Reflection\Exception('Reflection has not yet been initialized.', 1238667824);
		if (interface_exists($interfaceName) === FALSE) throw new \InvalidArgumentException('"' . $interface . '" does not exist or is not the name of an interface.', 1238769560);
		return (isset($this->interfaceImplementations[$interfaceName])) ? $this->interfaceImplementations[$interfaceName] : array();
	}

	/**
	 * Returns the names of all interfaces implemented by the specified class
	 *
	 * @param string $className Name of the class
	 * @return array An array of interface names
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getInterfaceNamesImplementedByClass($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		$interfaceNamesFound = array();
		foreach ($this->interfaceImplementations as $interfaceName => $classNames) {
			if (array_search($className, $classNames) !== FALSE) $interfaceNamesFound[] = $interfaceName;
		}
		return $interfaceNamesFound;
	}

	/**
	 * Searches for and returns all names of classes which are tagged by the specified tag.
	 * If no classes were found, an empty array is returned.
	 *
	 * @param string $tag Tag to search for
	 * @return array An array of class names tagged by the tag
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassNamesByTag($tag) {
		if ($this->initialized !== TRUE) throw new \F3\FLOW3\Reflection\Exception('Reflection has not yet been initialized.', 1238667825);
		return (isset($this->taggedClasses[$tag])) ? $this->taggedClasses[$tag] : array();
	}

	/**
	 * Returns all tags and their values the specified class is tagged with
	 *
	 * @param string $className The class name to reflect
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassTagsValues($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->classTagsValues[$className])) ? $this->classTagsValues[$className] : array();
	}

	/**
	 * Returns values of the specified class tag.
	 *
	 * @param string $className The class name to reflect
	 * @param string $tag The tag to return values of
	 * @return array An array of values or an empty array of the class is not tagged with the tag or the tag has no values
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassTagValues($className, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classTagsValues[$className])) return array();
		return (isset($this->classTagsValues[$className][$tag])) ? $this->classTagsValues[$className][$tag] : array();
	}

	/**
	 * Tells if the specified class is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassTaggedWith($className, $tag) {
		if ($this->initialized === FALSE) return FALSE;
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classTagsValues[$className])) return FALSE;
		return isset($this->classTagsValues[$className][$tag]);
	}

	/**
	 * Tells if the specified class implements the given interface
	 *
	 * @param string $className Name of the class
	 * @param string $interfaceName interface to check for
	 * @return boolean TRUE if the class implements $interfaceName, otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isClassImplementationOf($className, $interfaceName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->interfaceImplementations[$interfaceName])) return FALSE;
		return (array_search($className, $this->interfaceImplementations[$interfaceName]) !== FALSE);
	}

	/**
	 * Tells if the specified class is abstract or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is abstract, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassAbstract($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->abstractClasses[$className]);
	}

	/**
	 * Tells if the specified class is final or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is final, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isClassFinal($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->finalClasses[$className]);
	}

	/**
	 * Tells if the specified method is final or not
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is final, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isMethodFinal($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->finalMethods[$className . '::' . $methodName]);
	}

	/**
	 * Tells if the specified method is declared as static or not
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is static, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isMethodStatic($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->staticMethods[$className . '::' . $methodName]);
	}

	/**
	 * Tells if the specified method is public
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is public, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isMethodPublic($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === ' ');
	}

	/**
	 * Tells if the specified method is protected
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is protected, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isMethodProtected($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === '*');
	}

	/**
	 * Tells if the specified method is private
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is private, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isMethodPrivate($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === '-');
	}

	/**
	 * Returns the names of all methods of the specified class
	 *
	 * @param string $className Name of the class to return the method names of
	 * @return array An array of method names or an empty array if none exist
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassMethodNames($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->methodVisibilities[$className])) return array();
		return array_keys($this->methodVisibilities[$className]);
	}

	/**
	 * Returns the name of the classe's constructor method if it has one
	 *
	 * @param string $className Name of the class to return the constructor name of
	 * @return mixed Name of the constructor method or NULL if it has no constructor
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassConstructorName($className) {
		if ($this->initialized === TRUE) {
			if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
			return (isset($this->classConstructorMethodNames[$className])) ? $this->classConstructorMethodNames[$className] : NULL;
		} else {
			$class = new \ReflectionClass($className);
			$constructor = $class->getConstructor();
			return ($constructor !== NULL) ? $constructor->getName() : NULL;
		}
	}

	/**
	 * Returns the names of all properties of the specified class
	 *
	 * @param string $className Name of the class to return the property names of
	 * @return array An array of property names or an empty array if none exist
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getClassPropertyNames($className) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->classPropertyNames[$className])) ? $this->classPropertyNames[$className] : array();
	}

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getMethodTagsValues($className, $methodName) {
		if ($this->initialized === TRUE && !isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->methodTagsValues[$className])) return array();
		return (isset($this->methodTagsValues[$className][$methodName])) ? $this->methodTagsValues[$className][$methodName] : array();
	}

	/**
	 * Returns an array of parameters of the given method. Each entry contains
	 * additional information about the parameter position, type hint etc.
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return parameter information of
	 * @return array An array of parameter names and additional information or an empty array of no parameters were found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getMethodParameters($className, $methodName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if ($this->initialized && isset($this->reflectedClassNames[$className])) {
			if (!isset($this->methodParameters[$className])) return array();
			$parametersInformation = (isset($this->methodParameters[$className][$methodName])) ? $this->methodParameters[$className][$methodName] : array();
		} else {
			$method = new \ReflectionMethod($className, $methodName);
			$parametersInformation = array();
			foreach($method->getParameters() as $parameter) {
				$parametersInformation[$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
			}
		}
		return $parametersInformation;
	}

	/**
	 * Searches for and returns all names of class properties which are tagged by the specified tag.
	 * If no properties were found, an empty array is returned.
	 *
	 * @param string $className Name of the class containing the properties
	 * @param string $tag Tag to search for
	 * @return array An array of property names tagged by the tag
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPropertyNamesByTag($className, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return array();
		$propertyNames = array();
		foreach ($this->propertyTagsValues[$className] as $propertyName => $tagsValues) {
			if (isset($tagsValues[$tag])) $propertyNames[$propertyName] = TRUE;
		}
		return array_keys($propertyNames);
	}

	/**
	 * Returns all tags and their values the specified class property is tagged with
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the property to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPropertyTagsValues($className, $propertyName) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return array();
		return (isset($this->propertyTagsValues[$className][$propertyName])) ? $this->propertyTagsValues[$className][$propertyName] : array();
	}

	/**
	 * Returns the values of the specified class property tag
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the tagged property
	 * @param string $tag Tag to return the values of
	 * @return array An array of values or an empty array if the tag was not found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getPropertyTagValues($className, $propertyName, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return array();
		if (!isset($this->propertyTagsValues[$className][$propertyName])) return array();
		return (isset($this->propertyTagsValues[$className][$propertyName][$tag])) ? $this->propertyTagsValues[$className][$propertyName][$tag] : array();
	}

	/**
	 * Tells if the specified class property is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class property is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isPropertyTaggedWith($className, $propertyName, $tag) {
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return FALSE;
		if (!isset($this->propertyTagsValues[$className][$propertyName])) return FALSE;
		return isset($this->propertyTagsValues[$className][$propertyName][$tag]);
	}

	/**
	 * Checks if the given class names match those which already have been
	 * reflected. If the given array contains class names not yet known to
	 * this service, these classes will be reflected.
	 *
	 * @param array $classNamesToReflect Names of all classes which should be known by this service. Class names = values of the array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function reflectEmergedClasses(array $classNamesToReflect) {
		$classNamesToReflect = array_unique($classNamesToReflect);
		$reflectedClassNames = array_keys($this->reflectedClassNames);
		sort($classNamesToReflect);
		sort($reflectedClassNames);
		if ($this->reflectedClassNames !== $classNamesToReflect) {
			foreach (array_diff($classNamesToReflect, $reflectedClassNames) as $className) {
				$this->reflectClass($className);
			}
		}
	}

	/**
	 * Reflects the given class and stores the results in this service's properties.
	 *
	 * @param string $className Full qualified name of the class to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function reflectClass($className) {
		$this->log('Reflecting class "' . $className . '" (' . ($this->initialized ? '' : 'not ') . 'initialized)', LOG_DEBUG);

		$class = new \F3\FLOW3\Reflection\ClassReflection($className);
		$this->reflectedClassNames[$className] = time();

		if ($class->isAbstract()) $this->abstractClasses[$className] = TRUE;
		if ($class->isFinal()) $this->finalClasses[$className] = TRUE;

		$constructor = $class->getConstructor();
		if ($constructor instanceof \ReflectionMethod) {
			$this->classConstructorMethodNames[$className] = $constructor->getName();
		}
		foreach ($class->getInterfaces() as $interface) {
			if (!isset($this->abstractClasses[$className])) {
				$this->interfaceImplementations[$interface->getName()][] = $className;
			}
		}
		foreach ($class->getTagsValues() as $tag => $values) {
			if (array_search($tag, $this->ignoredTags) === FALSE) {
				$this->taggedClasses[$tag][] = $className;
				$this->classTagsValues[$className][$tag] = $values;
			}
		}

		foreach ($class->getProperties() as $property) {
			$propertyName = $property->getName();
			$this->classPropertyNames[$className][] = $propertyName;

			foreach ($property->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->propertyTagsValues[$className][$propertyName][$tag] = $values;
				}
			}
		}

		foreach ($class->getMethods() as $method) {
			$methodName = $method->getName();
			if ($method->isFinal()) $this->finalMethods[$className . '::' . $methodName] = TRUE;
			if ($method->isStatic()) $this->staticMethods[$className . '::' . $methodName] = TRUE;
			if ($method->isPublic()) $this->methodVisibilities[$className][$methodName] = ' ';
			if ($method->isProtected()) $this->methodVisibilities[$className][$methodName] = '*';
			if ($method->isPrivate()) $this->methodVisibilities[$className][$methodName] = '-';

			foreach ($method->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->methodTagsValues[$className][$methodName][$tag] = $values;
				}
			}

			foreach ($method->getParameters() as $parameter) {
				$this->methodParameters[$className][$methodName][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
			}
		}
		ksort($this->reflectedClassNames);
	}

	/**
	 * Converts the given parameter reflection into an information array
	 *
	 * @param ReflectionParameter $parameter The parameter to reflect
	 * @return array Parameter information array
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function convertParameterReflectionToArray(\ReflectionParameter $parameter, \ReflectionMethod $method = NULL) {
		$parameterInformation = array(
			'position' => $parameter->getPosition(),
			'byReference' => $parameter->isPassedByReference() ? TRUE : FALSE,
			'array' => $parameter->isArray() ? TRUE : FALSE,
			'optional' => $parameter->isOptional() ? TRUE : FALSE,
			'allowsNull' => $parameter->allowsNull() ? TRUE : FALSE
		);

		$parameterClass = $parameter->getClass();
		$parameterInformation['class'] = ($parameterClass !== NULL) ? $parameterClass->getName() : NULL;
		if ($parameter->isDefaultValueAvailable()) {
			$parameterInformation['defaultValue'] = $parameter->getDefaultValue();
		}
		if ($parameterClass !== NULL) {
			$parameterInformation['type'] = $parameterClass->getName();
		} elseif ($method !== NULL) {
			$methodTagsAndValues = $this->getMethodTagsValues($method->getDeclaringClass()->getName(), $method->getName());
			if (isset($methodTagsAndValues['param']) && isset($methodTagsAndValues['param'][$parameter->getPosition()])) {
				$explodedParameters = explode(' ', $methodTagsAndValues['param'][$parameter->getPosition()]);
				if (count($explodedParameters) >= 2) {
					$parameterInformation['type'] = $explodedParameters[0];
				}
			}
		}
		if (isset($parameterInformation['type']) && $parameterInformation['type']{0} === '\\') {
			$parameterInformation['type'] = substr($parameterInformation['type'], 1);
		}
		return $parameterInformation;
	}

	/**
	 * Checks which classes lack a cache entry and removes their reflection data
	 * accordingly.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function forgetChangedClasses() {
		foreach (array_keys($this->reflectedClassNames) as $className) {
			if (!$this->cache->has(str_replace('\\', '_', $className))) {
				$this->forgetClass($className);
			}
		}
	}

	/**
	 * Forgets all reflection data related to the specified class
	 *
	 * @param string $className Name of the class to forget
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function forgetClass($className) {
		foreach ($this->interfaceImplementations as $interfaceName => $interfaceImplementations) {
			$index = array_search($className, $interfaceImplementations);
			if ($index !== FALSE) unset($this->interfaceImplementations[$interfaceName][$index]);
		}

		foreach ($this->taggedClasses as $tag => $classNames) {
			$index = array_search($className, $classNames);
			if ($index !== FALSE) unset($this->taggedClasses[$tag][$index]);
		}

		$propertyNames = array(
			'abstractClasses',
			'classConstructorMethodNames',
			'classPropertyNames',
			'classTagsValues',
			'finalClasses',
			'finalMethods',
			'staticMethods',
			'methodTagsValues',
			'methodParameters',
			'methodVisibilities',
			'propertyTagsValues',
		);
		foreach ($propertyNames as $propertyName) {
			if (isset($this->{$propertyName}[$className])) {
				unset($this->{$propertyName}[$className]);
			}
		}
		unset($this->reflectedClassNames[$className]);
	}

	/**
	 * Tries to load the reflection data from this service's cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function loadFromCache() {
		if ($this->cache->has('ReflectionData')) {
			$data = $this->cache->get('ReflectionData');
			foreach ($data as $propertyName => $propertyValue) {
				$this->$propertyName = $propertyValue;
			}
			$this->cachedClassNames = $this->reflectedClassNames;
		}
	}

	/**
	 * Exports the internal reflection data into the ReflectionData cache
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function saveToCache() {
		if (!is_object($this->cache)) throw new \F3\FLOW3\Reflection\Exception('A cache must be injected before initializing the Reflection Service.', 1232044697);

		$nonCachedClassNames = array_diff_assoc($this->reflectedClassNames, $this->cachedClassNames);
		$this->log('Found ' . count($nonCachedClassNames) . ' classes whose reflection data was not cached previously.', LOG_DEBUG);
		foreach ($nonCachedClassNames as $className => $reflectionTimestamp) {
			$this->cache->set(str_replace('\\', '_', $className), '', array($this->cache->getClassTag($className)));
		}

		$data = array();
		$propertyNames = array(
			'reflectedClassNames',
			'abstractClasses',
			'classConstructorMethodNames',
			'classPropertyNames',
			'classTagsValues',
			'finalClasses',
			'finalMethods',
			'staticMethods',
			'interfaceImplementations',
			'methodTagsValues',
			'methodParameters',
			'methodVisibilities',
			'propertyTagsValues',
			'taggedClasses'
		);
		foreach ($propertyNames as $propertyName) {
			$data[$propertyName] = $this->$propertyName;
		}
		$this->cache->set('ReflectionData', $data);
		$this->cachedClassNames = $this->reflectedClassNames;
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the SEVERITY_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function log($message, $severity = 6, $additionalData = NULL) {
		if (is_object($this->systemLogger)) $this->systemLogger->log($message, $severity, $additionalData);
	}
}
?>