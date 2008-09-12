<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Reflection;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 */

/**
 * A service for aquiring reflection based information in a performant way.
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Service {

	/**
	 * If this service has been initialized
	 *
	 * @var boolean
	 */
	protected $initialized = FALSE;

	/**
	 * All available class names to consider
	 *
	 * @var array
	 */
	protected $availableClassNames = array();

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
	 * Array of class names and names of their methods
	 *
	 * @var array
	 */
	protected $classMethodNames = array();

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
	 * Imports the given reflection data, usually from a cache
	 *
	 * @param array $reflectionData The reflection data in the same format as export() delivers
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function import(array $reflectionData) {
		foreach ($reflectionData as $propertyName => $propertyValue) {
			$this->$propertyName = $propertyValue;
		}
		$this->initialized = TRUE;
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
	 * @param array $availableClassNames Names of available classes to consider in this reflection service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize(array $availableClassNames) {
		$this->availableClassNames = array_unique($availableClassNames);

		foreach ($this->availableClassNames as $className) {
			$class = new F3::FLOW3::Reflection::ReflectionClass($className);
			if ($class->isAbstract()) $this->abstractClasses[$className] = TRUE;
			if ($class->isFinal()) $this->finalClasses[$className] = TRUE;
		}

		foreach ($this->availableClassNames as $className) {
			$class = new F3::FLOW3::Reflection::ReflectionClass($className);
			$constructor = $class->getConstructor();
			if ($constructor instanceof ::ReflectionMethod) {
				$this->classConstructorMethodNames[$className] = $constructor->getName();
			}
			foreach ($class->getInterfaces() as $interface) {
				if (!key_exists($className, $this->abstractClasses)) {
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
				$this->classMethodNames[$className][] = $methodName;

				foreach ($method->getTagsValues() as $tag => $values) {
					if (array_search($tag, $this->ignoredTags) === FALSE) {
						$this->methodTagsValues[$className][$methodName][$tag] = $values;
					}
				}
			}
		}

		foreach ($this->taggedClasses as $tag => $classes) {
			$this->taggedClasses[$tag] = array_unique($classes);
		}
		foreach ($this->classPropertyNames as $className => $propertyNames) {
			$this->classPropertyNames[$className] = array_unique($propertyNames);
		}
		foreach ($this->classMethodNames as $className => $methodNames) {
			$this->classMethodNames[$className] = array_unique($methodNames);
		}
		foreach ($this->interfaceImplementations as $interfaceName => $classNames) {
			$this->interfaceImplementations[$interfaceName] = array_unique($classNames);
		}

		$this->initialized = TRUE;
	}

	/**
	 * Exports the internal reflection data so it can be cached elsewhere
	 *
	 * @return array The reflection data which can be imported again with import()
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function export() {
		$data = array();
		$propertyNames = array(
			'abstractClasses',
			'availableClassNames',
			'classConstructorMethodNames',
			'classMethodNames',
			'classPropertyNames',
			'classTagsValues',
			'finalClasses',
			'interfaceImplementations',
			'methodTagsValues',
			'propertyTagsValues',
			'taggedClasses'
		);
		foreach ($propertyNames as $propertyName) {
			$data[$propertyName] = $this->$propertyName;
		}
		return $data;
	}

	/**
	 * Returns the names of all classes known to this reflection service.
	 *
	 * @return array Class names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAvailableClassNames() {
		return $this->availableClassNames;
	}

	/**
	 * Allows to tweak the information about which classes implement a certain interface.
	 * This is used by the FLOW3 bootstrap to register some built in implementations as
	 * long as the reflection service is not analyzed.
	 *
	 * Note that this information will be overriden by intialize().
	 *
	 * @param string $interfaceName Name of the interface
	 * @param array $classNames Names of classes which implement this interface
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInterfaceImplementations($interfaceName, array $classNames) {
		$this->interfaceImplementations[$interfaceName] = $classNames;
	}

	/**
	 * Searches for and returns the class name of the default implementation of the given
	 * interface name. If no class implementing the interface was found or more than one
	 * implementation was found in the package defining the interface, FALSE is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return mixed Either the class name of the default implementation for the component type or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Component::Exception::UnknownInterface if the specified interface does not exist.
	 */
	public function getDefaultImplementationClassNameForInterface($interfaceName) {
		$classNamesFound = key_exists($interfaceName, $this->interfaceImplementations) ? $this->interfaceImplementations[$interfaceName] : array();
		return (count($classNamesFound) == 1 ? $classNamesFound[0] : FALSE);
	}

	/**
	 * Searches for and returns all class names of implementations of the given component type
	 * (interface name). If no class implementing the interface was found, FALSE is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return array An array of class names of the default implementation for the component type
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws F3::FLOW3::Component::Exception::UnknownInterface if the given interface does not exist
	 */
	public function getAllImplementationClassNamesForInterface($interfaceName) {
		return key_exists($interfaceName, $this->interfaceImplementations) ? $this->interfaceImplementations[$interfaceName] : array();
	}

	/**
	 * Searches for and returns all names of classes which are tagged by the specified tag.
	 * If no classes were found, an empty array is returned.
	 *
	 * @param string $tag Tag to search for
	 * @return array An array of class names tagged by the tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassNamesByTag($tag) {
		return key_exists($tag, $this->taggedClasses) ? $this->taggedClasses[$tag] : array();
	}

	/**
	 * Returns all tags and their values the specified class is tagged with
	 *
	 * @param string $className The class name to reflect
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagsValues($className) {
		return (key_exists($className, $this->classTagsValues)) ? $this->classTagsValues[$className] : array();
	}

	/**
	 * Returns values of the specified class tag.
	 *
	 * @param string $className The class name to reflect
	 * @param string $tag The tag to return values of
	 * @return array An array of values or an empty array of the class is not tagged with the tag or the tag has no values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassTagValues($className, $tag) {
		if (!key_exists($className, $this->classTagsValues)) return array();
		return (key_exists($tag, $this->classTagsValues[$className])) ? $this->classTagsValues[$className][$tag] : array();
	}

	/**
	 * Tells if the specified class is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassTaggedWith($className, $tag) {
		if (!key_exists($className, $this->classTagsValues)) return FALSE;
		return key_exists($tag, $this->classTagsValues[$className]);
	}

	/**
	 * Tells if the specified class is abstract or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is abstract, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassAbstract($className) {
		return (key_exists($className, $this->abstractClasses));
	}

	/**
	 * Tells if the specified class is final or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is final, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isClassFinal($className) {
		return (key_exists($className, $this->finalClasses));
	}

	/**
	 * Returns the names of all methods of the specified class
	 *
	 * @param string $className Name of the class to return the method names of
	 * @return array An array of method names or an empty array if none exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassMethodNames($className) {
		return (key_exists($className, $this->classMethodNames)) ? $this->classMethodNames[$className] : array();
	}

	/**
	 * Returns the name of the classe's constructor method if it has one
	 *
	 * @param string $className Name of the class to return the constructor name of
	 * @return mixed Name of the constructor method or NULL if it has no constructor
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassConstructorName($className) {
		return (key_exists($className, $this->classConstructorMethodNames)) ? $this->classConstructorMethodNames[$className] : NULL;
	}

	/**
	 * Returns the names of all properties of the specified class
	 *
	 * @param string $className Name of the class to return the property names of
	 * @return array An array of property names or an empty array if none exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassPropertyNames($className) {
		return (key_exists($className, $this->classPropertyNames)) ? $this->classPropertyNames[$className] : array();
	}

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getMethodTagsValues($className, $methodName) {
		if (!key_exists($className, $this->methodTagsValues)) return array();
		return (key_exists($methodName, $this->methodTagsValues[$className])) ? $this->methodTagsValues[$className][$methodName] : array();
	}

	/**
	 * Searches for and returns all names of class properties which are tagged by the specified tag.
	 * If no properties were found, an empty array is returned.
	 *
	 * @param string $className Name of the class containing the properties
	 * @param string $tag Tag to search for
	 * @return array An array of property names tagged by the tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyNamesByTag($className, $tag) {
		if (!key_exists($className, $this->propertyTagsValues)) return array();
		$propertyNames = array();
		foreach ($this->propertyTagsValues[$className] as $propertyName => $tagsValues) {
			if (key_exists($tag, $tagsValues)) $propertyNames[$propertyName] = TRUE;
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
	 */
	public function getPropertyTagsValues($className, $propertyName) {
		if (!key_exists($className, $this->propertyTagsValues)) return array();
		return (key_exists($propertyName, $this->propertyTagsValues[$className])) ? $this->propertyTagsValues[$className][$propertyName] : array();
	}

	/**
	 * Returns the values of the specified class property tag
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the tagged property
	 * @param string $tag Tag to return the values of
	 * @return array An array of values or an empty array if the tag was not found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPropertyTagValues($className, $propertyName, $tag) {
		if (!key_exists($className, $this->propertyTagsValues)) return array();
		if (!key_exists($propertyName, $this->propertyTagsValues[$className])) return array();
		return (key_exists($tag, $this->propertyTagsValues[$className][$propertyName])) ? $this->propertyTagsValues[$className][$propertyName][$tag] : array();
	}

	/**
	 * Tells if the specified class property is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class property is tagged with $tag, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isPropertyTaggedWith($className, $propertyName, $tag) {
		if (!key_exists($className, $this->propertyTagsValues)) return FALSE;
		if (!key_exists($propertyName, $this->propertyTagsValues[$className])) return FALSE;
		return key_exists($tag, $this->propertyTagsValues[$className][$propertyName]);
	}

}
?>