<?php
namespace TYPO3\FLOW3\Reflection;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A service for acquiring reflection based information in a performant way.
 *
 * @api
 * @FLOW3\Scope("singleton")
 * @FLOW3\Proxy(false)
 */
class ReflectionService {

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	protected $annotationReader;

	/**
	 * @var \TYPO3\FLOW3\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var array
	 */
	protected $availableClassNames = array();

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\StringFrontend
	 */
	protected $statusCache;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $dataCache;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * Settings of the FLOW3 package
	 *
	 * @var array
	 */
	protected $settings;

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
	 * Names of classes and an array of interfaces implemented by them
	 *
	 * @var array
	 */
	protected $interfacesImplementedByClass = array();

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
	 * Array of class names and names of their sub classes as $className => TRUE
	 *
	 * @var array
	 */
	protected $subClasses = array();

	/**
	 * Array of tags and the names of classes which are tagged with them
	 *
	 * @var array
	 */
	protected $taggedClasses = array();

	/**
	 * Array of annotations classnames and the names of classes which are annotated with them
	 *
	 * @var array
	 */
	protected $annotatedClasses = array();

	/**
	 * Array of class names and their tags and values
	 *
	 * @var array
	 */
	protected $classTagsValues = array();

	/**
	 * Array of class names and their annotations
	 *
	 * @var array
	 */
	protected $classAnnotations = array();

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
	 * Array of class names, property names and their annotations
	 *
	 * @var array
	 */
	protected $propertyAnnotations = array();

	/**
	 * List of tags which are ignored while reflecting class and method annotations
	 *
	 * @var array
	 */
	protected $ignoredTags = array('api', 'package', 'subpackage', 'license', 'copyright', 'author', 'const', 'see', 'todo');

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array<\TYPO3\FLOW3\Reflection\ClassSchema>
	 */
	protected $classSchemata = array();

	/**
	 * An array of class names which are currently being forgotten by forgetClass(). Acts as a safeguard against infinite loops.
	 *
	 * @var array
	 */
	protected $classesCurrentlyBeingForgotten = array();

	/**
	 * Sets the status cache
	 *
	 * The cache must be set before initializing the Reflection Service
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\StringFrontend $cache Cache for the reflection service
	 * @return void
	 */
	public function setStatusCache(\TYPO3\FLOW3\Cache\Frontend\StringFrontend $cache) {
		$this->statusCache = $cache;
	}

	/**
	 * Sets the data cache
	 *
	 * The cache must be set before initializing the Reflection Service
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache Cache for the reflection service
	 * @return void
	 */
	public function setDataCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $cache) {
		$this->dataCache = $cache;
	}

	/**
	 * @param array $settings Settings of the FLOW3 package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * @param \TYPO3\FLOW3\Core\ClassLoader $classLoader
	 * @return void
	 */
	public function injectClassLoader(\TYPO3\FLOW3\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * Initializes this service after dependencies have been injected.
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->loadFromCache();
		$this->annotationReader = new \Doctrine\Common\Annotations\AnnotationReader();
		\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName('fixme');
		\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName('test');
		\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName('expectedException');
		\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName('dataProvider');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($this->classLoader, 'loadClass'));
	}

	/**
	 * Builds the reflection data cache during compile time.
	 *
	 * @param array $availableClassNames
	 * @return void
	 */
	public function buildReflectionData(array $availableClassNames) {
		$this->availableClassNames = $availableClassNames;

		$this->forgetChangedClasses();
		$this->reflectEmergedClasses();
	}

	/**
	 * Returns an array with annotations that are ignored while reflecting class
	 * and method annotations.
	 *
	 * @return array
	 * @deprecated since 1.0.0
	 */
	public function getIgnoredTags() {
		return $this->ignoredTags;
	}

	/**
	 * Returns an array with annotations that are ignored while reflecting class
	 * and method annotations.
	 *
	 * @return array
	 */
	public function getIgnoredAnnotations() {
		return $this->ignoredTags;
	}

	/**
	 * Tells if the specified class is known to this reflection service and
	 * reflection information is available.
	 *
	 * @param string $className Name of the class
	 * @return boolean If the class is reflected by this service
	 * @api
	 */
	public function isClassReflected($className) {
		return isset($this->reflectedClassNames[trim($className, '\\')]);
	}

	/**
	 * Returns the names of all classes known to this reflection service.
	 *
	 * @return array Class names
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
	 * @throws \InvalidArgumentException if the given interface does not exist
	 * @api
	 */
	public function getDefaultImplementationClassNameForInterface($interfaceName) {
		$interfaceName = trim($interfaceName, '\\');
		if (interface_exists($interfaceName) === FALSE) throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769559);

		$classNamesFound = isset($this->interfaceImplementations[$interfaceName]) ? $this->interfaceImplementations[$interfaceName] : array();
		if (count($classNamesFound) === 1) return current($classNamesFound);
		if (count($classNamesFound) === 2 && isset($this->interfaceImplementations['TYPO3\FLOW3\Object\Proxy\ProxyInterface'])) {
			if (array_search(current($classNamesFound), $this->interfaceImplementations['TYPO3\FLOW3\Object\Proxy\ProxyInterface']) !== FALSE) return current($classNamesFound);
			next($classNamesFound);
			if (array_search(current($classNamesFound), $this->interfaceImplementations['TYPO3\FLOW3\Object\Proxy\ProxyInterface']) !== FALSE) return current($classNamesFound);
		}
		return FALSE;
	}

	/**
	 * Searches for and returns all class names of implementations of the given object type
	 * (interface name). If no class implementing the interface was found, an empty array is returned.
	 *
	 * @param string $interfaceName Name of the interface
	 * @return array An array of class names of the default implementation for the object type
	 * @throws \InvalidArgumentException if the given interface does not exist
	 * @api
	 */
	public function getAllImplementationClassNamesForInterface($interfaceName) {
		$interfaceName = trim($interfaceName, '\\');
		if (interface_exists($interfaceName) === FALSE) throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769560);
		return (isset($this->interfaceImplementations[$interfaceName])) ? $this->interfaceImplementations[$interfaceName] : array();
	}

	/**
	 * Returns the names of all interfaces implemented by the specified class
	 *
	 * @param string $className Name of the class
	 * @return array An array of interface names
	 * @deprecated since 1.0.0beta1 – use the PHP function class_implements() instead
	 */
	public function getInterfaceNamesImplementedByClass($className) {
		$className = trim($className, '\\');
		if (!isset($this->interfacesImplementedByClass[$className])) {
			$class = new \TYPO3\FLOW3\Reflection\ClassReflection($className);
			$this->interfacesImplementedByClass[$className] = $class->getInterfaceNames();
		}
		return $this->interfacesImplementedByClass[$className];
	}

	/**
	 * Searches for and returns all names of classes inheriting the specified class.
	 * If no class inheriting the given class was found, an empty array is returned.
	 *
	 * @param string $className Name of the parent class
	 * @return array An array of names of those classes being a direct or indirect subclass of the specified class
	 * @throws \InvalidArgumentException if the given class does not exist
	 * @api
	 */
	public function getAllSubClassNamesForClass($className) {
		$className = trim($className, '\\');
		if (class_exists($className) === FALSE) throw new \InvalidArgumentException('"' . $className . '" does not exist or is not the name of a class.', 1257168042);
		return (isset($this->subClasses[$className])) ? array_keys($this->subClasses[$className]) : array();
	}

	/**
	 * Searches for and returns all names of classes which are tagged by the specified tag.
	 * If no classes were found, an empty array is returned.
	 *
	 * @param string $tag Tag to search for
	 * @return array An array of class names tagged by the tag
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getClassNamesByTag($tag) {
		return (isset($this->taggedClasses[$tag])) ? $this->taggedClasses[$tag] : array();
	}

	/**
	 * Returns all tags and their values the specified class is tagged with
	 *
	 * @param string $className The class name to reflect
	 * @return array An array of tags and their values or an empty array if no tags were found
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getClassTagsValues($className) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->classTagsValues[$className])) ? $this->classTagsValues[$className] : array();
	}

	/**
	 * Returns values of the specified class tag.
	 *
	 * @param string $className The class name to reflect
	 * @param string $tag The tag to return values of
	 * @return array An array of values or an empty array of the class is not tagged with the tag or the tag has no values
	 * @api
	 * @deprecated since 1.0.0
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
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function isClassTaggedWith($className, $tag) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classTagsValues[$className])) return FALSE;
		return isset($this->classTagsValues[$className][$tag]);
	}

	/**
	 * Tells if the specified class has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $annotationName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isClassAnnotatedWith($className, $annotationName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classAnnotations[$className])) return FALSE;
		foreach ($this->classAnnotations[$className] as $annotation) {
			if ($annotation instanceof $annotationName) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Returns the specified class annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $annotationName Annotation to filter for
	 * @return array<object>
	 */
	public function getClassAnnotations($className, $annotationName = NULL) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->classAnnotations[$className])) return array();

		if ($annotationName === NULL) {
			return $this->classAnnotations[$className];
		} else {
			$annotations = array();
			foreach ($this->classAnnotations[$className] as $annotation) {
				if ($annotation instanceof $annotationName) {
					$annotations[] = $annotation;
				}
			}
			return $annotations;
		}
	}

	/**
	 * Returns the specified class annotation or NULL.
	 *
	 * If multiple annotations are set on the target you will
	 * get one (random) instance of them.
	 *
	 * @param string $className Name of the class
	 * @param string $annotationName Annotation to filter for
	 * @return object
	 */
	public function getClassAnnotation($className, $annotationName) {
		$annotations = $this->getClassAnnotations($className, $annotationName);
		return $annotations === array() ? NULL : current($annotations);
	}

	/**
	 * Tells if the specified class implements the given interface
	 *
	 * @param string $className Name of the class
	 * @param string $interfaceName interface to check for
	 * @return boolean TRUE if the class implements $interfaceName, otherwise FALSE
	 * @api
	 */
	public function isClassImplementationOf($className, $interfaceName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->interfaceImplementations[$interfaceName])) return FALSE;
		return (array_search($className, $this->interfaceImplementations[$interfaceName]) !== FALSE);
	}

	/**
	 * Tells if the specified class is abstract or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is abstract, otherwise FALSE
	 * @api
	 */
	public function isClassAbstract($className) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->abstractClasses[$className]);
	}

	/**
	 * Tells if the specified class is final or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is final, otherwise FALSE
	 * @api
	 */
	public function isClassFinal($className) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->finalClasses[$className]);
	}

	/**
	 * Tells if the specified method is final or not
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is final, otherwise FALSE
	 * @api
	 */
	public function isMethodFinal($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->finalMethods[$className . '::' . $methodName]);
	}

	/**
	 * Tells if the specified method is declared as static or not
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is static, otherwise FALSE
	 * @api
	 */
	public function isMethodStatic($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return isset($this->staticMethods[$className . '::' . $methodName]);
	}

	/**
	 * Tells if the specified method is public
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is public, otherwise FALSE
	 * @api
	 */
	public function isMethodPublic($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === ' ');
	}

	/**
	 * Tells if the specified method is protected
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is protected, otherwise FALSE
	 * @api
	 */
	public function isMethodProtected($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === '*');
	}

	/**
	 * Tells if the specified method is private
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @return boolean TRUE if the method is private, otherwise FALSE
	 * @api
	 */
	public function isMethodPrivate($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->methodVisibilities[$className][$methodName]) && $this->methodVisibilities[$className][$methodName] === '-');
	}

	/**
	 * Tells if the specified method is tagged with the given tag
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the method is tagged with $tag, otherwise FALSE
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function isMethodTaggedWith($className, $methodName, $tag) {
		$method = new \TYPO3\FLOW3\Reflection\MethodReflection(trim($className, '\\'), $methodName);
		$tagsValues = $method->getTagsValues();
		return isset($tagsValues[$tag]);
	}

	/**
	 * Tells if the specified method has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $methodName Name of the method
	 * @param string $annotationName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isMethodAnnotatedWith($className, $methodName, $annotationName) {
		return $this->getMethodAnnotations($className, $methodName, $annotationName) !== array();
	}

	/**
	 * Returns the specified method annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $methodName Name of the method
	 * @param string $annotationName Annotation to filter for
	 * @return array<object>
	 * @api
	 */
	public function getMethodAnnotations($className, $methodName, $annotationName = NULL) {
		$className = trim($className, '\\');
		$annotations = array();
		$methodAnnotations = $this->annotationReader->getMethodAnnotations(new MethodReflection($className, $methodName));
		if ($annotationName === NULL) {
			return $methodAnnotations;
		} else {
			foreach ($methodAnnotations as $annotation) {
				if ($annotation instanceof $annotationName) {
					$annotations[] = $annotation;
				}
			}
			return $annotations;
		}
	}

	/**
	 * Returns the specified method annotation or NULL.
	 *
	 * If multiple annotations are set on the target you will
	 * get one (random) instance of them.
	 *
	 * @param string $className Name of the class
	 * @param string $methodName Name of the method
	 * @param string $annotationName Annotation to filter for
	 * @return object
	 */
	public function getMethodAnnotation($className, $methodName, $annotationName) {
		$annotations = $this->getMethodAnnotations($className, $methodName, $annotationName);
		return $annotations === array() ? NULL : current($annotations);
	}

	/**
	 * Returns the names of all properties of the specified class
	 *
	 * @param string $className Name of the class to return the property names of
	 * @return array An array of property names or an empty array if none exist
	 * @api
	 */
	public function getClassPropertyNames($className) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		return (isset($this->classPropertyNames[$className])) ? $this->classPropertyNames[$className] : array();
	}

	/**
	 * Wrapper for method_exists() which tells if the given method exists.
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method
	 * @return boolean
	 * @api
	 */
	public function hasMethod($className, $methodName) {
		return isset($this->methodVisibilities[$className][$methodName]);
	}

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getMethodTagsValues($className, $methodName) {
		$method = new \TYPO3\FLOW3\Reflection\MethodReflection(trim($className, '\\'), $methodName);
		return $method->getTagsValues();
	}

	/**
	 * Returns an array of parameters of the given method. Each entry contains
	 * additional information about the parameter position, type hint etc.
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return parameter information of
	 * @return array An array of parameter names and additional information or an empty array of no parameters were found
	 * @api
	 */
	public function getMethodParameters($className, $methodName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) {
			$this->reflectClass($className);
		}
		if (isset($this->reflectedClassNames[$className])) {
			if (!isset($this->methodParameters[$className])) {
				return array();
			}
			$parametersInformation = (isset($this->methodParameters[$className][$methodName])) ? $this->methodParameters[$className][$methodName] : array();
		} else {
			$method = new \TYPO3\FLOW3\Reflection\MethodReflection($className, $methodName);
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
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getPropertyNamesByTag($className, $tag) {
		$className = trim($className, '\\');
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
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getPropertyTagsValues($className, $propertyName) {
		$className = trim($className, '\\');
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
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function getPropertyTagValues($className, $propertyName, $tag) {
		$className = trim($className, '\\');
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
	 * @api
	 * @deprecated since 1.0.0
	 */
	public function isPropertyTaggedWith($className, $propertyName, $tag) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyTagsValues[$className])) return FALSE;
		if (!isset($this->propertyTagsValues[$className][$propertyName])) return FALSE;
		return isset($this->propertyTagsValues[$className][$propertyName][$tag]);
	}

	/**
	 * Tells if the specified property has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the method
	 * @param string $annotationName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isPropertyAnnotatedWith($className, $propertyName, $annotationName) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);

		if (!isset($this->propertyAnnotations[$className])) return FALSE;
		if (!isset($this->propertyAnnotations[$className][$propertyName])) return FALSE;
		return isset($this->propertyAnnotations[$className][$propertyName][$annotationName]);
	}

	/**
	 * Returns the specified property annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $annotationName Annotation to filter for
	 * @return array<object>
	 * @api
	 */
	public function getPropertyAnnotations($className, $propertyName, $annotationName = NULL) {
		$className = trim($className, '\\');
		if (!isset($this->reflectedClassNames[$className])) $this->reflectClass($className);
		if (!isset($this->propertyAnnotations[$className])) return array();
		if (!isset($this->propertyAnnotations[$className][$propertyName])) return array();

		if ($annotationName === NULL) {
			return $this->propertyAnnotations[$className][$propertyName];
		} elseif (isset($this->propertyAnnotations[$className][$propertyName][$annotationName])) {
			return $this->propertyAnnotations[$className][$propertyName][$annotationName];
		} else {
			return array();
		}
	}

	/**
	 * Returns the specified property annotation or NULL.
	 *
	 * If multiple annotations are set on the target you will
	 * get one (random) instance of them.
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $annotationName Annotation to filter for
	 * @return object
	 */
	public function getPropertyAnnotation($className, $propertyName, $annotationName) {
		$annotations = $this->getPropertyAnnotations($className, $propertyName, $annotationName);
		return $annotations === array() ? NULL : current($annotations);
	}

	/**
	 * Returns the available class schemata.
	 *
	 * @return array<\TYPO3\FLOW3\Reflection\ClassSchema>
	 */
	public function getClassSchemata() {
		return $this->classSchemata;
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param mixed $classNameOrObject The class name or an object
	 * @return \TYPO3\FLOW3\Reflection\ClassSchema
	 */
	public function getClassSchema($classNameOrObject) {
		$className = trim(is_object($classNameOrObject) ? get_class($classNameOrObject) : $classNameOrObject, '\\');
		return isset($this->classSchemata[$className]) ? $this->classSchemata[$className] : NULL;
	}

	/**
	 * Checks if the given class names match those which already have been
	 * reflected. If the given array contains class names not yet known to
	 * this service, these classes will be reflected.
	 *
	 * @param array $classNamesToReflect Names of all classes which should be known by this service. Class names = values of the array
	 * @return void
	 */
	protected function reflectEmergedClasses() {
		$classNamesToReflect = array();
		foreach ($this->availableClassNames as $classNamesInOnePackage) {
			$classNamesToReflect = array_merge($classNamesToReflect, $classNamesInOnePackage);
		}
		$reflectedClassNames = array_keys($this->reflectedClassNames);
		sort($classNamesToReflect);
		sort($reflectedClassNames);
		if ($this->reflectedClassNames != $classNamesToReflect) {
			$classNamesToBuildSchemaFor = array();
			$count = 0;
			foreach (array_diff($classNamesToReflect, $reflectedClassNames) as $className) {
				$count ++;
				$this->reflectClass($className);
				if ($this->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\Entity') || $this->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\Entity') || $this->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\ValueObject')) {
					$scopeAnnotation = $this->getClassAnnotation($className, 'TYPO3\FLOW3\Annotations\Scope');
					if ($scopeAnnotation !== NULL && $scopeAnnotation->value !== 'prototype') {
						throw new \TYPO3\FLOW3\Reflection\Exception(sprintf('Classes tagged as entity or value object must be of scope prototype, however, %s is declared as %s.', $className, $scopeAnnotation->value), 1264103349);
					}
					$classNamesToBuildSchemaFor[] = $className;
				}
			}

			$this->buildClassSchemata($classNamesToBuildSchemaFor);
			if ($count > 0) {
				$this->log(sprintf('Reflected %s emerged classes.', $count), LOG_INFO);
			}
		}
	}

	/**
	 * Reflects the given class and stores the results in this service's properties.
	 *
	 * @param string $className Full qualified name of the class to reflect
	 * @return void
	 */
	protected function reflectClass($className) {
		$this->log(sprintf('Reflecting class %s', $className), LOG_DEBUG);

		$className = trim($className, '\\');
		if (strpos($className, 'TYPO3\FLOW3\Persistence\Doctrine\Proxies') === 0 && array_search('Doctrine\ORM\Proxy\Proxy', class_implements($className))) {
				// Somebody tried to reflect a doctrine proxy, which will have severe side effects.
				// see bug http://forge.typo3.org/issues/29449 for details.
				throw new Exception\InvalidClassException('The class with name "' . $className . '" is a Doctrine proxy. It is not supported to reflect doctrine proxy classes.', 1314944681);
		}

		$class = new ClassReflection($className);

		$this->reflectedClassNames[$className] = $_SERVER['REQUEST_TIME'];

		if ($class->isAbstract()) $this->abstractClasses[$className] = TRUE;
		if ($class->isFinal()) $this->finalClasses[$className] = TRUE;

		foreach($this->getParentClasses($class) as $parentClass) {
			$this->subClasses[$parentClass->getName()][$className] = TRUE;
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

		foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
			$this->annotatedClasses[get_class($annotation)][] = $className;
			$this->classAnnotations[$className][] = $annotation;
		}

		foreach ($class->getProperties() as $property) {
			$propertyName = $property->getName();
			$this->classPropertyNames[$className][] = $propertyName;

			foreach ($property->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->ignoredTags) === FALSE) {
					$this->propertyTagsValues[$className][$propertyName][$tag] = $values;
				}
			}
			foreach ($this->annotationReader->getPropertyAnnotations($property, $propertyName) as $annotation) {
				$this->propertyAnnotations[$className][$propertyName][get_class($annotation)][] = $annotation;
			}
		}

		foreach ($class->getMethods() as $method) {
			$methodName = $method->getName();
			if ($method->isFinal()) $this->finalMethods[$className . '::' . $methodName] = TRUE;
			if ($method->isStatic()) $this->staticMethods[$className . '::' . $methodName] = TRUE;
			if ($method->isPublic()) $this->methodVisibilities[$className][$methodName] = ' ';
			if ($method->isProtected()) $this->methodVisibilities[$className][$methodName] = '*';
			if ($method->isPrivate()) $this->methodVisibilities[$className][$methodName] = '-';

			$paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : array();
			foreach ($method->getParameters() as $parameter) {
				$this->methodParameters[$className][$methodName][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
				if ($this->settings['reflection']['logIncorrectDocCommentHints'] === TRUE) {
					if (isset($paramAnnotations[$parameter->getPosition()])) {
						$parameterAnnotation = explode(' ', $paramAnnotations[$parameter->getPosition()], 3);
						if (count($parameterAnnotation) < 2) {
							$this->log('  Wrong @param use for "' . $method->getName() . '::' . $parameter->getName() . '": "' . implode(' ', $parameterAnnotation) . '"', LOG_DEBUG);
						} else {
							if (isset($this->methodParameters[$className][$methodName][$parameter->getName()]['type']) && ($this->methodParameters[$className][$methodName][$parameter->getName()]['type'] !== ltrim($parameterAnnotation[0], '\\'))) {
								$this->log('  Wrong type in @param for "' . $method->getName() . '::' . $parameter->getName() . '": "' . $parameterAnnotation[0] . '"', LOG_DEBUG);
							}
							if ($parameter->getName() !== ltrim($parameterAnnotation[1], '$&')) {
								$this->log('  Wrong name in @param for "' . $method->getName() . '::$' . $parameter->getName() . '": "' . $parameterAnnotation[1] . '"', LOG_DEBUG);
							}
						}
					} else {
						$this->log('  Missing @param for "' . $method->getName() . '::$' . $parameter->getName(), LOG_DEBUG);
					}
				}
			}
		}
		ksort($this->reflectedClassNames);
	}

	/**
	 * Finds all parent classes of the given class
	 *
	 * @param \ReflectionClass $class The class to reflect
	 * @param array $parentClasses Array of parent classes
	 * @return array<\TYPO3\FLOW3\Reflection\ClassReflection>
	 */
	protected function getParentClasses(\TYPO3\FLOW3\Reflection\ClassReflection $class, array $parentClasses = array()) {
		$parentClass = $class->getParentClass();
		if ($parentClass !== FALSE) {
			$parentClasses[] = $parentClass;
			$parentClasses = $this->getParentClasses($parentClass, $parentClasses);
		}
		return $parentClasses;
	}

	/**
	 * Builds class schemata from classes annotated as entities or value objects
	 *
	 * @param array $classNames
	 * @return void
	 */
	protected function buildClassSchemata(array $classNames) {
		foreach ($classNames as $className) {
			$classSchema = new \TYPO3\FLOW3\Reflection\ClassSchema($className);
			if ($this->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\Entity') || $this->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\Entity')) {
				$classSchema->setModelType(\TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_ENTITY);
				$classSchema->setLazyLoadableObject($this->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\Lazy'));

				$possibleRepositoryClassName = str_replace('\\Model\\', '\\Repository\\', $className) . 'Repository';
				if ($this->isClassReflected($possibleRepositoryClassName) === TRUE) {
					$classSchema->setRepositoryClassName($possibleRepositoryClassName);
				}
			} elseif ($this->isClassAnnotatedWith($className, 'TYPO3\FLOW3\Annotations\ValueObject')) {
				$this->checkValueObjectRequirements($className);
				$classSchema->setModelType(\TYPO3\FLOW3\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
			}

			$this->addPropertiesToClassSchema($classSchema);

			$this->classSchemata[$className] = $classSchema;
		}

		$this->completeRepositoryAssignments();
		$this->ensureAggregateRootInheritanceChainConsistency();
	}

	/**
	 * Adds properties of the class at hand to the class schema.
	 *
	 * Only non-transient properties annotated with a var annotation will be added.
	 * Invalid annotations will cause an exception to be thrown. Properties pointing
	 * to existing classes will only be added if the target type is annotated as
	 * entity or valueobject.
	 *
	 * @param \TYPO3\FLOW3\Reflection\ClassSchema $classSchema
	 * @return void
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\FLOW3\Reflection\Exception\InvalidPropertyTypeException
	 */
	protected function addPropertiesToClassSchema(\TYPO3\FLOW3\Reflection\ClassSchema $classSchema) {

			// those are added as property even if not tagged with entity/valueobject
		$propertyTypeWhiteList = array(
			'DateTime',
			'SplObjectStorage',
			'Doctrine\Common\Collections\Collection',
			'Doctrine\Common\Collections\ArrayCollection'
		);

		$className = $classSchema->getClassName();
		$needsArtificialIdentity = TRUE;
		foreach ($this->getClassPropertyNames($className) as $propertyName) {
			if ($this->isPropertyTaggedWith($className, $propertyName, 'var') && !$this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\FLOW3\Annotations\Transient')) {
				$declaredType = trim(implode(' ', $this->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
				if (preg_match('/\s/', $declaredType) === 1) {
					throw new \TYPO3\FLOW3\Reflection\Exception\InvalidPropertyTypeException('The @var annotation for "' . $className . '::$' . $propertyName . '" seems to be invalid.', 1284132314);
				}
				if ($this->isPropertyAnnotatedWith($className, $propertyName, 'Doctrine\ORM\Mapping\Id')) {
					$needsArtificialIdentity = FALSE;
				}

				try {
					$parsedType = \TYPO3\FLOW3\Utility\TypeHandling::parseType($declaredType);
				} catch (\TYPO3\FLOW3\Utility\Exception\InvalidTypeException $exception) {
					throw new \InvalidArgumentException(sprintf($exception->getMessage(), 'class "' . $className . '" for property "' . $propertyName . '"'), 1315564475);
				}

				if (!in_array($parsedType['type'], $propertyTypeWhiteList)
						&& (class_exists($parsedType['type']) || interface_exists($parsedType['type']))
						&& !($this->isClassAnnotatedWith($parsedType['type'], 'TYPO3\FLOW3\Annotations\Entity') || $this->isClassAnnotatedWith($parsedType['type'], 'Doctrine\ORM\Mapping\Entity') || $this->isClassAnnotatedWith($parsedType['type'], 'TYPO3\FLOW3\Annotations\ValueObject'))) {
					continue;
				}

				$classSchema->addProperty($propertyName, $declaredType, $this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\FLOW3\Annotations\Lazy'));
				if ($this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\FLOW3\Annotations\Identity')) {
					$classSchema->markAsIdentityProperty($propertyName);
				}
			}
		}
		if ($needsArtificialIdentity === TRUE) {
			$classSchema->addProperty('FLOW3_Persistence_Identifier', 'string');
		}
	}

	/**
	 * Complete repository-to-entity assignments.
	 *
	 * This method looks for repositories that declare themselves responsible
	 * for a specific model and sets a repository classname on the corresponding
	 * models.
	 *
	 * It then walks the inheritance chain for all aggregate roots and checks
	 * the subclasses for their aggregate root status - if no repository is
	 * assigned yet, that will be done.
	 *
	 * @return void
	 */
	protected function completeRepositoryAssignments() {
		foreach ($this->getAllImplementationClassNamesForInterface('TYPO3\FLOW3\Persistence\RepositoryInterface') as $repositoryClassname) {
				// need to be extra careful because this code could be called
				// during a cache:flush run with corrupted reflection cache
			if (class_exists($repositoryClassname)) {
				$claimedObjectType = $repositoryClassname::ENTITY_CLASSNAME;
				if ($claimedObjectType !== NULL && isset($this->classSchemata[$claimedObjectType])) {
					$this->classSchemata[$claimedObjectType]->setRepositoryClassName($repositoryClassname);
				}
			}
		}

		foreach (array_values($this->classSchemata) as $classSchema) {
			if (class_exists($classSchema->getClassName()) && $classSchema->isAggregateRoot()) {
				$this->makeChildClassesAggregateRoot($classSchema);
			}
		}
	}

	/**
	 * Assigns the repository of any aggregate root to all it's
	 * subclasses, unless they are aggregate root already.
	 *
	 * @param \TYPO3\FLOW3\Reflection\ClassSchema $classSchema
	 * @return void
	 */
	protected function makeChildClassesAggregateRoot(\TYPO3\FLOW3\Reflection\ClassSchema $classSchema) {
		foreach ($this->getAllSubClassNamesForClass($classSchema->getClassName()) as $childClassName) {
			if ($this->classSchemata[$childClassName]->isAggregateRoot()) {
				continue;
			} else {
				$this->classSchemata[$childClassName]->setRepositoryClassName($classSchema->getRepositoryClassName());
				$this->makeChildClassesAggregateRoot($this->classSchemata[$childClassName]);
			}
		}
	}

	/**
	 * Checks whether all aggregate roots having superclasses
	 * have a repository assigned up to the tip of their hierarchy.
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Reflection\Exception
	 */
	protected function ensureAggregateRootInheritanceChainConsistency() {
		foreach ($this->classSchemata as $className => $classSchema) {
			if (!class_exists($className) || $classSchema->isAggregateRoot() === FALSE) {
				continue;
			}

			foreach (class_parents($className) as $parentClassName) {
				if ($this->classSchemata[$parentClassName]->isAggregateRoot() === FALSE) {
					throw new \TYPO3\FLOW3\Reflection\Exception('In a class hierarchy either all or no classes must be an aggregate root, "' . $className . '" is one but the parent class "' . $parentClassName . '" is not.', 1316009511);
				}
			}
		}
	}

	/**
	 * Checks if the given class meets the requirements for a value object, i.e.
	 * does have a constructor and does not have any setter methods.
	 *
	 * @param string $className
	 * @return void
	 * @throws \TYPO3\FLOW3\Reflection\Exception\InvalidValueObjectException
	 */
	protected function checkValueObjectRequirements($className) {
		$methods = get_class_methods($className);
		if (array_search('__construct', $methods) === FALSE) {
			throw new \TYPO3\FLOW3\Reflection\Exception\InvalidValueObjectException('A value object must have a constructor, "' . $className . '" does not have one.', 1268740874);
		}
		foreach ($methods as $method) {
			if (substr($method, 0, 3) === 'set') {
				throw new \TYPO3\FLOW3\Reflection\Exception\InvalidValueObjectException('A value object must not have setters, "' . $className . '" does.', 1268740878);
			}
		}
	}

	/**
	 * Converts the given parameter reflection into an information array
	 *
	 * @param \TYPO3\FLOW3\Reflection\ParameterReflection $parameter The parameter to reflect
	 * @param \TYPO3\FLOW3\Reflection\MethodReflection $method The parameter's method
	 * @return array Parameter information array
	 */
	protected function convertParameterReflectionToArray(\TYPO3\FLOW3\Reflection\ParameterReflection $parameter, \TYPO3\FLOW3\Reflection\MethodReflection $method = NULL) {
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
		if ($method !== NULL) {
			$paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : array();
			if (isset($paramAnnotations[$parameter->getPosition()])) {
				$explodedParameters = explode(' ', $paramAnnotations[$parameter->getPosition()]);
				if (count($explodedParameters) >= 2) {
					$parameterInformation['type'] = ltrim($explodedParameters[0], '\\');
				}
			}
		}
		if (!isset($parameterInformation['type']) && $parameterClass !== NULL) {
			$parameterInformation['type'] = ltrim($parameterClass->getName(), '\\');
		} elseif (!isset($parameterInformation['type'])) {
			$parameterInformation['type'] = 'mixed';
		}
		return $parameterInformation;
	}

	/**
	 * Checks which classes lack a cache entry and removes their reflection data
	 * accordingly.
	 *
	 * @return void
	 */
	protected function forgetChangedClasses() {
		foreach (array_keys($this->reflectedClassNames) as $className) {
			if (!$this->statusCache->has(str_replace('\\', '_', $className))) {
				$this->forgetClass($className);
			}
		}
	}

	/**
	 * Forgets all reflection data related to the specified class
	 *
	 * @param string $className Name of the class to forget
	 * @return void
	 */
	protected function forgetClass($className) {
		if (isset($this->classesCurrentlyBeingForgotten[$className])) {
			$this->systemLogger->log('Detected recursion while forgetting class ' . $className, LOG_WARNING);
			return;
		}
		$this->classesCurrentlyBeingForgotten[$className] = TRUE;

		foreach ($this->interfaceImplementations as $interfaceName => $interfaceImplementations) {
			$index = array_search($className, $interfaceImplementations);
			if ($index !== FALSE) unset($this->interfaceImplementations[$interfaceName][$index]);
		}
		if (isset($this->subClasses[$className])) {
			foreach (array_keys($this->subClasses[$className]) as $subClassName) {
				$this->forgetClass($subClassName);
			}
		}

		foreach ($this->taggedClasses as $tag => $classNames) {
			$index = array_search($className, $classNames);
			if ($index !== FALSE) unset($this->taggedClasses[$tag][$index]);
		}
		foreach ($this->annotatedClasses as $annotationClassName => $classNames) {
			$index = array_search($className, $classNames);
			if ($index !== FALSE) unset($this->taggedClasses[$annotationClassName][$index]);
		}

		$propertyNames = array(
			'abstractClasses',
			'classPropertyNames',
			'classTagsValues',
			'classAnnotations',
			'subClasses',
			'finalClasses',
			'finalMethods',
			'staticMethods',
			'methodParameters',
			'methodVisibilities',
			'propertyTagsValues',
			'propertyAnnotations'
		);
		foreach ($propertyNames as $propertyName) {
			if (isset($this->{$propertyName}[$className])) {
				unset($this->{$propertyName}[$className]);
			}
		}

		if (isset($this->classSchemata[$className])) {
			unset($this->classSchemata[$className]);
		}

		unset($this->reflectedClassNames[$className]);
		unset($this->classesCurrentlyBeingForgotten[$className]);
	}

	/**
	 * Tries to load the reflection data from this service's cache.
	 *
	 * @return boolean TRUE if reflection data could be loaded, otherwise FALSE
	 */
	protected function loadFromCache() {
		$data = $this->dataCache->get('ReflectionData');
		if ($data === FALSE) {
			return FALSE;
		}

		foreach ($data as $propertyName => $propertyValue) {
			$this->$propertyName = $propertyValue;
		}
		$this->cachedClassNames = $this->reflectedClassNames;
		return TRUE;
	}

	/**
	 * Exports the internal reflection data into the ReflectionData cache
	 *
	 * @return void
	 * @throws \TYPO3\FLOW3\Reflection\Exception if no cache has been injected
	 */
	public function saveToCache() {
		if (!is_object($this->dataCache)) throw new \TYPO3\FLOW3\Reflection\Exception('A cache must be injected before initializing the Reflection Service.', 1232044697);

		$nonCachedClassNames = array_diff_assoc($this->reflectedClassNames, $this->cachedClassNames);
		$emergedClassesCount = count($nonCachedClassNames);
		if ($emergedClassesCount > 0) {
			$this->log(sprintf('Found %s classes whose reflection data was not cached previously.', $emergedClassesCount), LOG_DEBUG);

			foreach (array_keys($nonCachedClassNames) as $className) {
				$this->statusCache->set(str_replace('\\', '_', $className), '', array(\TYPO3\FLOW3\Cache\CacheManager::getClassTag($className)));
			}

			$data = array();
			$propertyNames = array(
				'reflectedClassNames',
				'abstractClasses',
				'classPropertyNames',
				'classSchemata',
				'classTagsValues',
				'classAnnotations',
				'subClasses',
				'finalClasses',
				'finalMethods',
				'staticMethods',
				'interfaceImplementations',
				'methodParameters',
				'methodVisibilities',
				'propertyTagsValues',
				'propertyAnnotations',
				'taggedClasses',
				'annotatedClasses'
			);
			foreach ($propertyNames as $propertyName) {
				$data[$propertyName] = $this->$propertyName;
			}
			$this->dataCache->set('ReflectionData', $data);
			$this->cachedClassNames = $this->reflectedClassNames;
		}
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the SEVERITY_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @return void
	 */
	protected function log($message, $severity = 6, $additionalData = NULL) {
		if (is_object($this->systemLogger)) {
			$this->systemLogger->log($message, $severity, $additionalData);
		}
	}
}
?>
