<?php
namespace TYPO3\Flow\Reflection;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Files;

/**
 * A service for acquiring reflection based information in a performant way. This
 * service also builds up class schema information which is used by the Flow's
 * persistence layer.
 *
 * Reflection of classes of all active packages is triggered through the bootstrap's
 * initializeReflectionService() method. In a development context, single classes
 * may be re-reflected once files are modified whereas in a production context
 * reflection is done once and successive requests read from the frozen caches for
 * performance reasons.
 *
 * The list of available classes is determined by the CompiletimeObjectManager which
 * also triggers the initial build of reflection data in this service.
 *
 * The invalidation of reflection cache entries is done by the CacheManager which
 * in turn is triggered by signals sent by the file monitor.
 *
 * The internal representation of cache data is optimized for memory consumption and
 * speed by using constants which have an integer value.
 *
 * @api
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ReflectionService {

	const
		VISIBILITY_PRIVATE = 1,
		VISIBILITY_PROTECTED = 2,
		VISIBILITY_PUBLIC = 3,
			// Implementations of an interface
		DATA_INTERFACE_IMPLEMENTATIONS = 1,
			// Implemented interfaces of a class
		DATA_CLASS_INTERFACES = 2,
			// Subclasses of a class
		DATA_CLASS_SUBCLASSES = 3,
			// Class tag values
		DATA_CLASS_TAGS_VALUES = 4,
			// Class annotations
		DATA_CLASS_ANNOTATIONS = 5,
		DATA_CLASS_ABSTRACT = 6,
		DATA_CLASS_FINAL = 7,
		DATA_CLASS_METHODS = 8,
		DATA_CLASS_PROPERTIES = 9,
		DATA_METHOD_FINAL = 10,
		DATA_METHOD_STATIC = 11,
		DATA_METHOD_VISIBILITY = 12,
		DATA_METHOD_PARAMETERS = 13,
		DATA_PROPERTY_TAGS_VALUES = 14,
		DATA_PROPERTY_ANNOTATIONS = 15,
		DATA_PROPERTY_VISIBILITY = 24,
		DATA_PARAMETER_POSITION = 16,
		DATA_PARAMETER_OPTIONAL = 17,
		DATA_PARAMETER_TYPE = 18,
		DATA_PARAMETER_ARRAY = 19,
		DATA_PARAMETER_CLASS = 20,
		DATA_PARAMETER_ALLOWS_NULL = 21,
		DATA_PARAMETER_DEFAULT_VALUE = 22,
		DATA_PARAMETER_BY_REFERENCE = 23;

	/**
	 * @var \Doctrine\Common\Annotations\Reader
	 */
	protected $annotationReader;

	/**
	 * @var \TYPO3\Flow\Core\ClassLoader
	 */
	protected $classLoader;

	/**
	 * @var array
	 */
	protected $availableClassNames = array();

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\StringFrontend
	 */
	protected $statusCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $reflectionDataCompiletimeCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $reflectionDataRuntimeCache;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\VariableFrontend
	 */
	protected $classSchemataRuntimeCache;

	/**
	 * @var \TYPO3\Flow\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var \TYPO3\Flow\Package\PackageManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\Flow\Utility\Environment
	 */
	protected $environment;

	/**
	 * @var \TYPO3\Flow\Core\ApplicationContext
	 */
	protected $context;

	/**
	 * In Production context, with frozen caches, this flag will be TRUE
	 * @var boolean
	 */
	protected $loadFromClassSchemaRuntimeCache = FALSE;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Array of annotation classnames and the names of classes which are annotated with them
	 * @var array
	 */
	protected $annotatedClasses = array();

	/**
	 * Array of method annotations and the classes and methods which are annotated with them
	 * @var array
	 */
	protected $classesByMethodAnnotations = array();

	/**
	 * Schemata of all classes which can be persisted
	 * @var array<\TYPO3\Flow\Reflection\ClassSchema>
	 */
	protected $classSchemata = array();

	/**
	 * An array of class names which are currently being forgotten by forgetClass(). Acts as a safeguard against infinite loops.
	 * @var array
	 */
	protected $classesCurrentlyBeingForgotten = array();

	/**
	 * Array with reflection information indexed by class name
	 * @var array
	 */
	protected $classReflectionData = array();

	/**
	 * Array with updated reflection information (e.g. in Development context after classes have changed)
	 * @var array
	 */
	protected $updatedReflectionData = array();

	/**
	 * Sets the status cache
	 *
	 * The cache must be set before initializing the Reflection Service
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\StringFrontend $cache Cache for the reflection service
	 * @return void
	 */
	public function setStatusCache(\TYPO3\Flow\Cache\Frontend\StringFrontend $cache) {
		$this->statusCache = $cache;
		$this->statusCache->getBackend()->initializeObject();
	}

	/**
	 * Sets the compiletime data cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache Cache for the reflection service
	 * @return void
	 */
	public function setReflectionDataCompiletimeCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->reflectionDataCompiletimeCache = $cache;
	}

	/**
	 * Sets the runtime data cache
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache Cache for the reflection service
	 * @return void
	 */
	public function setReflectionDataRuntimeCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->reflectionDataRuntimeCache = $cache;
	}

	/**
	 * Sets the dedicated class schema cache for runtime purposes
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\VariableFrontend $cache
	 * @return void
	 */
	public function setClassSchemataRuntimeCache(\TYPO3\Flow\Cache\Frontend\VariableFrontend $cache) {
		$this->classSchemataRuntimeCache = $cache;
	}

	/**
	 * @param array $settings Settings of the Flow package
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Flow\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\Flow\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * @param \TYPO3\Flow\Core\ClassLoader $classLoader
	 * @return void
	 */
	public function injectClassLoader(\TYPO3\Flow\Core\ClassLoader $classLoader) {
		$this->classLoader = $classLoader;
	}

	/**
	 * @param \TYPO3\Flow\Package\PackageManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\TYPO3\Flow\Package\PackageManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * @param \TYPO3\Flow\Utility\Environment $environment
	 * @return void
	 */
	public function injectEnvironment(\TYPO3\Flow\Utility\Environment $environment) {
		$this->environment = $environment;
	}

	/**
	 * Initializes this service.
	 *
	 * This method must be run only after all dependencies have been injected.
	 *
	 * @param \TYPO3\Flow\Core\Bootstrap $bootstrap
	 * @return void
	 */
	public function initialize(\TYPO3\Flow\Core\Bootstrap $bootstrap) {
		$this->context = $bootstrap->getContext();

		if ($this->context->isProduction() && $this->reflectionDataRuntimeCache->getBackend()->isFrozen()) {
			$this->classReflectionData = $this->reflectionDataRuntimeCache->get('__classNames');
			$this->annotatedClasses = $this->reflectionDataRuntimeCache->get('__annotatedClasses');
			$this->loadFromClassSchemaRuntimeCache = TRUE;
		} else {
			$this->loadClassReflectionCompiletimeCache();
		}

		$this->annotationReader = new \Doctrine\Common\Annotations\AnnotationReader();
		foreach ($this->settings['reflection']['ignoredTags'] as $tag) {
			\Doctrine\Common\Annotations\AnnotationReader::addGlobalIgnoredName($tag);
		}
		\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($this->classLoader, 'loadClass'));
	}

	/**
	 * Builds the reflection data cache during compile time.
	 *
	 * This method is called by the CompiletimeObjectManager which also determines
	 * the list of classes to consider for reflection.
	 *
	 * @param array $availableClassNames List of all class names to consider for reflection
	 * @return void
	 */
	public function buildReflectionData(array $availableClassNames) {
		$this->availableClassNames = $availableClassNames;
		$this->forgetChangedClasses();
		$this->reflectEmergedClasses();
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		return isset($this->classReflectionData[$className]);
	}

	/**
	 * Returns the names of all classes known to this reflection service.
	 *
	 * @return array Class names
	 * @api
	 */
	public function getAllClassNames() {
		return array_keys($this->classReflectionData);
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
		if ($interfaceName[0] === '\\') {
			$interfaceName = substr($interfaceName, 1);
		}
		if (interface_exists($interfaceName) === FALSE) {
			throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769559);
		}
		$this->loadOrReflectClassIfNecessary($interfaceName);

		$classNamesFound = isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) ? array_keys($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) : array();
		if (count($classNamesFound) === 1) {
			return $classNamesFound[0];
		}
		if (count($classNamesFound) === 2 && isset($this->classReflectionData['TYPO3\Flow\Object\Proxy\ProxyInterface'][self::DATA_INTERFACE_IMPLEMENTATIONS])) {
			if (isset($this->classReflectionData['TYPO3\Flow\Object\Proxy\ProxyInterface'][self::DATA_INTERFACE_IMPLEMENTATIONS][$classNamesFound[0]])) {
				return $classNamesFound[0];
			}
			if (isset($this->classReflectionData['TYPO3\Flow\Object\Proxy\ProxyInterface'][self::DATA_INTERFACE_IMPLEMENTATIONS][$classNamesFound[1]])) {
				return $classNamesFound[1];
			}
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
		if ($interfaceName[0] === '\\') {
			$interfaceName = substr($interfaceName, 1);
		}
		$this->loadOrReflectClassIfNecessary($interfaceName);

		if (interface_exists($interfaceName) === FALSE) {
			throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769560);
		}

		return (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS])) ? array_keys($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) : array();
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		if (class_exists($className) === FALSE) {
			throw new \InvalidArgumentException('"' . $className . '" does not exist or is not the name of a class.', 1257168042);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES])) ? array_keys($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES]) : array();
	}

	/**
	 * Returns the class name of the given object. This is a convenience
	 * method that returns the expected class names even for proxy classes.
	 *
	 * @param object $object
	 * @return string The class name of the given object
	 */
	public function getClassNameByObject($object) {
		if ($object instanceof \Doctrine\ORM\Proxy\Proxy) {
			$className = get_parent_class($object);
		} else {
			$className = get_class($object);
		}
		return $className;
	}

	/**
	 * Searches for and returns all names of classes which are tagged by the specified
	 * annotation. If no classes were found, an empty array is returned.
	 *
	 * @param string $annotationClassName Name of the annotation class, for example "TYPO3\Flow\Annotations\Aspect"
	 * @return array
	 */
	public function getClassNamesByAnnotation($annotationClassName) {
		if ($annotationClassName[0] === '\\') {
			$annotationClassName = substr($annotationClassName, 1);
		}
		return (isset($this->annotatedClasses[$annotationClassName]) ? array_keys($this->annotatedClasses[$annotationClassName]) : array());
	}

	/**
	 * Tells if the specified class has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $annotationClassName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isClassAnnotatedWith($className, $annotationClassName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		if ($annotationClassName[0] === '\\') {
			$annotationClassName = substr($annotationClassName, 1);
		}
		return (isset($this->annotatedClasses[$annotationClassName][$className]));
	}

	/**
	 * Returns the specified class annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $annotationClassName Annotation to filter for
	 * @return array<object>
	 */
	public function getClassAnnotations($className, $annotationClassName = NULL) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		if ($annotationClassName !== NULL && $annotationClassName[0] === '\\') {
			$annotationClassName = substr($annotationClassName, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS])) {
			return array();
		}
		if ($annotationClassName === NULL) {
			return $this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS];
		} else {
			$annotations = array();
			foreach ($this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS] as $annotation) {
				if ($annotation instanceof $annotationClassName) {
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
	 * @param string $annotationClassName Annotation to filter for
	 * @return object
	 */
	public function getClassAnnotation($className, $annotationClassName) {
		$annotations = $this->getClassAnnotations($className, $annotationClassName);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		if ($interfaceName[0] === '\\') {
			$interfaceName = substr($interfaceName, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		$this->loadOrReflectClassIfNecessary($interfaceName);
		if (!isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS])) {
			return FALSE;
		}
		return (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className]));
	}

	/**
	 * Tells if the specified class is abstract or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is abstract, otherwise FALSE
	 * @api
	 */
	public function isClassAbstract($className) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT]);
	}

	/**
	 * Tells if the specified class is final or not
	 *
	 * @param string $className Name of the class to analyze
	 * @return boolean TRUE if the class is final, otherwise FALSE
	 * @api
	 */
	public function isClassFinal($className) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_FINAL]);
	}

	/**
	 * Returns all class names of classes containing at least one method annotated
	 * with the given annotation class
	 *
	 * @param string $annotationClassName The annotation class name for a method annotation
	 * @return array An array of class names
	 */
	public function getClassesContainingMethodsAnnotatedWith($annotationClassName) {
		return isset($this->classesByMethodAnnotations[$annotationClassName]) ? array_keys($this->classesByMethodAnnotations[$annotationClassName]) : array();
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_FINAL]);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_STATIC]);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PUBLIC);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PROTECTED);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PRIVATE);
	}

	/**
	 * Tells if the specified method is tagged with the given tag
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to analyze
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the method is tagged with $tag, otherwise FALSE
	 * @api
	 */
	public function isMethodTaggedWith($className, $methodName, $tag) {
		$method = new \TYPO3\Flow\Reflection\MethodReflection(trim($className, '\\'), $methodName);
		$tagsValues = $method->getTagsValues();
		return isset($tagsValues[$tag]);
	}

	/**
	 * Tells if the specified method has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $methodName Name of the method
	 * @param string $annotationClassName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isMethodAnnotatedWith($className, $methodName, $annotationClassName) {
		return $this->getMethodAnnotations($className, $methodName, $annotationClassName) !== array();
	}

	/**
	 * Returns the specified method annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $methodName Name of the method
	 * @param string $annotationClassName Annotation to filter for
	 * @return array<object>
	 * @api
	 */
	public function getMethodAnnotations($className, $methodName, $annotationClassName = NULL) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		if ($annotationClassName[0] === '\\') {
			$annotationClassName = substr($annotationClassName, 1);
		}
		$annotations = array();
		$methodAnnotations = $this->annotationReader->getMethodAnnotations(new MethodReflection($className, $methodName));
		if ($annotationClassName === NULL) {
			return $methodAnnotations;
		} else {
			foreach ($methodAnnotations as $annotation) {
				if ($annotation instanceof $annotationClassName) {
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
	 * @param string $annotationClassName Annotation to filter for
	 * @return object
	 */
	public function getMethodAnnotation($className, $methodName, $annotationClassName) {
		$annotations = $this->getMethodAnnotations($className, $methodName, $annotationClassName);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$className = trim($className, '\\');
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) ? array_keys($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) : array();
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName]);
	}

	/**
	 * Returns all tags and their values the specified method is tagged with
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $methodName Name of the method to return the tags and values of
	 * @return array An array of tags and their values or an empty array of no tags were found
	 * @api
	 */
	public function getMethodTagsValues($className, $methodName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$method = new \TYPO3\Flow\Reflection\MethodReflection($className, $methodName);
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
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);

		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS])) {
			return array();
		}
		return $this->convertParameterDataToArray($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS]);
	}

	/**
	 * Searches for and returns all names of class properties which are tagged by the specified tag.
	 * If no properties were found, an empty array is returned.
	 *
	 * @param string $className Name of the class containing the properties
	 * @param string $tag Tag to search for
	 * @return array An array of property names tagged by the tag
	 * @api
	 */
	public function getPropertyNamesByTag($className, $tag) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES])) {
			return array();
		}

		$propertyNames = array();
		foreach ($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES] as $propertyName => $propertyData) {
			if (isset($propertyData[self::DATA_PROPERTY_TAGS_VALUES][$tag])) {
				$propertyNames[$propertyName] = TRUE;
			}
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
	 */
	public function getPropertyTagsValues($className, $propertyName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);

		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
			return array();
		}
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES])) ? $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES] : array();
	}

	/**
	 * Returns the values of the specified class property tag
	 *
	 * @param string $className Name of the class containing the property
	 * @param string $propertyName Name of the tagged property
	 * @param string $tag Tag to return the values of
	 * @return array An array of values or an empty array if the tag was not found
	 * @api
	 */
	public function getPropertyTagValues($className, $propertyName, $tag) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);

		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
			return array();
		}
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag])) ? $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag] : array();
	}

	/**
	 * Tells if the specified property is private
	 *
	 * @param string $className Name of the class containing the method
	 * @param string $propertyName Name of the property to analyze
	 * @return boolean TRUE if the property is private, otherwise FALSE
	 * @api
	 */
	public function isPropertyPrivate($className, $propertyName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY])
				&& $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY] === self::VISIBILITY_PRIVATE);
	}

	/**
	 * Tells if the specified class property is tagged with the given tag
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $tag Tag to check for
	 * @return boolean TRUE if the class property is tagged with $tag, otherwise FALSE
	 * @api
	 */
	public function isPropertyTaggedWith($className, $propertyName, $tag) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag]);
	}

	/**
	 * Tells if the specified property has the given annotation
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the method
	 * @param string $annotationClassName Annotation to check for
	 * @return boolean
	 * @api
	 */
	public function isPropertyAnnotatedWith($className, $propertyName, $annotationClassName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);
		return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName]);
	}

	/**
	 * Searches for and returns all names of class properties which are marked by the
	 * specified annotation. If no properties were found, an empty array is returned.
	 *
	 * @param string $className Name of the class containing the properties
	 * @param string $annotationClassName Class name of the annotation to search for
	 * @return array An array of property names carrying the annotation
	 * @api
	 */
	public function getPropertyNamesByAnnotation($className, $annotationClassName) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);

		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES])) {
			return array();
		}

		$propertyNames = array();
		foreach ($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES] as $propertyName => $propertyData) {
			if (isset($propertyData[self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName])) {
				$propertyNames[$propertyName] = TRUE;
			}
		}
		return array_keys($propertyNames);
	}

	/**
	 * Returns the specified property annotations or an empty array
	 *
	 * @param string $className Name of the class
	 * @param string $propertyName Name of the property
	 * @param string $annotationClassName Annotation to filter for
	 * @return array<object>
	 * @api
	 */
	public function getPropertyAnnotations($className, $propertyName, $annotationClassName = NULL) {
		if ($className[0] === '\\') {
			$className = substr($className, 1);
		}
		$this->loadOrReflectClassIfNecessary($className);

		if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS])) {
			return array();
		}

		if ($annotationClassName === NULL) {
			return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS];
		} elseif (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName])) {
			return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName];
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
	 * @param string $annotationClassName Annotation to filter for
	 * @return object
	 */
	public function getPropertyAnnotation($className, $propertyName, $annotationClassName) {
		$annotations = $this->getPropertyAnnotations($className, $propertyName, $annotationClassName);
		return $annotations === array() ? NULL : current($annotations);
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param mixed $classNameOrObject The class name or an object
	 * @return \TYPO3\Flow\Reflection\ClassSchema
	 */
	public function getClassSchema($classNameOrObject) {
		if (is_object($classNameOrObject)) {
			$className = get_class($classNameOrObject);
		} else {
			$className = ($classNameOrObject[0] === '\\' ? substr($classNameOrObject, 1) : $classNameOrObject);
		}
		if (!isset($this->classSchemata[$className])) {
			$this->classSchemata[$className] = $this->classSchemataRuntimeCache->get(str_replace('\\', '_', $className));
		}
		return is_object($this->classSchemata[$className]) ? $this->classSchemata[$className] : NULL;
	}

	/**
	 * Checks if the given class names match those which already have been
	 * reflected. If the given array contains class names not yet known to
	 * this service, these classes will be reflected.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Reflection\Exception
	 */
	protected function reflectEmergedClasses() {
		$classNamesToReflect = array();
		foreach ($this->availableClassNames as $classNamesInOnePackage) {
			$classNamesToReflect = array_merge($classNamesToReflect, $classNamesInOnePackage);
		}
		$reflectedClassNames = array_keys($this->classReflectionData);
		sort($classNamesToReflect);
		sort($reflectedClassNames);
		$newClassNames = array_diff($classNamesToReflect, $reflectedClassNames);
		if ($newClassNames !== array()) {
			$this->systemLogger->log('Reflected class names did not match class names to reflect', LOG_DEBUG);
			$classNamesToBuildSchemaFor = array();
			$count = 0;
			foreach ($newClassNames as $className) {
				$count++;
				try {
					$this->reflectClass($className);
				} catch (Exception\ClassLoadingForReflectionFailedException $exception) {
					$this->systemLogger->log('Could not reflect "' . $className . '" because the class could not be loaded.', LOG_DEBUG);
					continue;
				}
				if ($this->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Entity') || $this->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\Entity') || $this->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\ValueObject')) {
					$scopeAnnotation = $this->getClassAnnotation($className, 'TYPO3\Flow\Annotations\Scope');
					if ($scopeAnnotation !== NULL && $scopeAnnotation->value !== 'prototype') {
						throw new \TYPO3\Flow\Reflection\Exception(sprintf('Classes tagged as entity or value object must be of scope prototype, however, %s is declared as %s.', $className, $scopeAnnotation->value), 1264103349);
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
	 * @throws \TYPO3\Flow\Reflection\Exception\InvalidClassException
	 */
	protected function reflectClass($className) {
		$this->log(sprintf('Reflecting class %s', $className), LOG_DEBUG);

		$className = trim($className, '\\');
		if (strpos($className, 'TYPO3\Flow\Persistence\Doctrine\Proxies') === 0 && array_search('Doctrine\ORM\Proxy\Proxy', class_implements($className))) {
				// Somebody tried to reflect a doctrine proxy, which will have severe side effects.
				// see bug http://forge.typo3.org/issues/29449 for details.
				throw new Exception\InvalidClassException('The class with name "' . $className . '" is a Doctrine proxy. It is not supported to reflect doctrine proxy classes.', 1314944681);
		}

		$class = new ClassReflection($className);

		if (!isset($this->classReflectionData[$className])) {
			$this->classReflectionData[$className] = array();
		}

		if ($class->isAbstract()) {
			$this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT] = TRUE;
		}
		if ($class->isFinal()) {
			$this->classReflectionData[$className][self::DATA_CLASS_FINAL] = TRUE;
		}

		foreach ($this->getParentClasses($class) as $parentClass) {
			$parentClassName = $parentClass->getName();
			if (!isset($this->classReflectionData[$parentClassName])) {
				$this->reflectClass($parentClassName);
			}
			$this->classReflectionData[$parentClassName][self::DATA_CLASS_SUBCLASSES][$className] = TRUE;
		}

		foreach ($class->getInterfaces() as $interface) {
			if (!isset($this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT])) {
				$interfaceName = $interface->getName();
				if (!isset($this->classReflectionData[$interfaceName])) {
					$this->reflectClass($interfaceName);
				}
				$this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className] = TRUE;
			}
		}

		foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
			$annotationClassName = get_class($annotation);
			$this->annotatedClasses[$annotationClassName][$className] = TRUE;
			$this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS][] = $annotation;
		}

		foreach ($class->getProperties() as $property) {
			$propertyName = $property->getName();
			$this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName] = array();

			$visibility = $property->isPublic() ? self::VISIBILITY_PUBLIC : ($property->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
			$this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY] = $visibility;

			foreach ($property->getTagsValues() as $tag => $values) {
				if (array_search($tag, $this->settings['reflection']['ignoredTags']) === FALSE) {
					$this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag] = $values;
				}
			}
			foreach ($this->annotationReader->getPropertyAnnotations($property, $propertyName) as $annotation) {
				$this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][get_class($annotation)][] = $annotation;
			}
		}

		foreach ($class->getMethods() as $method) {
			$methodName = $method->getName();
			if ($method->isFinal()) {
				$this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_FINAL] = TRUE;
			}
			if ($method->isStatic()) {
				$this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_STATIC] = TRUE;
			}
			$visibility = $method->isPublic() ? self::VISIBILITY_PUBLIC : ($method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
			$this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] = $visibility;

			foreach ($this->getMethodAnnotations($className, $methodName) as $methodAnnotation) {
				$this->classesByMethodAnnotations[get_class($methodAnnotation)][$className] = $methodName;
			}

			$paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : array();
			foreach ($method->getParameters() as $parameter) {
				$this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
				if ($this->settings['reflection']['logIncorrectDocCommentHints'] === TRUE) {
					if (isset($paramAnnotations[$parameter->getPosition()])) {
						$parameterAnnotation = explode(' ', $paramAnnotations[$parameter->getPosition()], 3);
						if (count($parameterAnnotation) < 2) {
							$this->log('  Wrong @param use for "' . $method->getName() . '::' . $parameter->getName() . '": "' . implode(' ', $parameterAnnotation) . '"', LOG_DEBUG);
						} else {
							if (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE]) && ($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE] !== ltrim($parameterAnnotation[0], '\\'))) {
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
			// Sort reflection data so that the cache data is deterministic. This is
			// important for comparisons when checking if classes have changed in a
			// Development context.
		ksort($this->classReflectionData);

		$this->updatedReflectionData[$className] = TRUE;
	}

	/**
	 * Finds all parent classes of the given class
	 *
	 * @param \TYPO3\Flow\Reflection\ClassReflection $class The class to reflect
	 * @param array $parentClasses Array of parent classes
	 * @return array<\TYPO3\Flow\Reflection\ClassReflection>
	 */
	protected function getParentClasses(\TYPO3\Flow\Reflection\ClassReflection $class, array $parentClasses = array()) {
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
			$classSchema = new \TYPO3\Flow\Reflection\ClassSchema($className);
			if ($this->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Entity') || $this->isClassAnnotatedWith($className, 'Doctrine\ORM\Mapping\Entity')) {
				$classSchema->setModelType(\TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_ENTITY);
				$classSchema->setLazyLoadableObject($this->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\Lazy'));

				$possibleRepositoryClassName = str_replace('\\Model\\', '\\Repository\\', $className) . 'Repository';
				if ($this->isClassReflected($possibleRepositoryClassName) === TRUE) {
					$classSchema->setRepositoryClassName($possibleRepositoryClassName);
				}
			} elseif ($this->isClassAnnotatedWith($className, 'TYPO3\Flow\Annotations\ValueObject')) {
				$this->checkValueObjectRequirements($className);
				$classSchema->setModelType(\TYPO3\Flow\Reflection\ClassSchema::MODELTYPE_VALUEOBJECT);
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
	 * @param \TYPO3\Flow\Reflection\ClassSchema $classSchema
	 * @return void
	 * @throws \InvalidArgumentException
	 * @throws \TYPO3\Flow\Reflection\Exception\InvalidPropertyTypeException
	 */
	protected function addPropertiesToClassSchema(\TYPO3\Flow\Reflection\ClassSchema $classSchema) {

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
			if ($this->isPropertyTaggedWith($className, $propertyName, 'var') && !$this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\Flow\Annotations\Transient')) {
				$declaredType = trim(implode(' ', $this->getPropertyTagValues($className, $propertyName, 'var')), ' \\');
				if (preg_match('/\s/', $declaredType) === 1) {
					throw new \TYPO3\Flow\Reflection\Exception\InvalidPropertyTypeException('The @var annotation for "' . $className . '::$' . $propertyName . '" seems to be invalid.', 1284132314);
				}
				if ($this->isPropertyAnnotatedWith($className, $propertyName, 'Doctrine\ORM\Mapping\Id')) {
					$needsArtificialIdentity = FALSE;
				}

				try {
					$parsedType = \TYPO3\Flow\Utility\TypeHandling::parseType($declaredType);
				} catch (\TYPO3\Flow\Utility\Exception\InvalidTypeException $exception) {
					throw new \InvalidArgumentException(sprintf($exception->getMessage(), 'class "' . $className . '" for property "' . $propertyName . '"'), 1315564475);
				}

				if (!in_array($parsedType['type'], $propertyTypeWhiteList)
						&& (class_exists($parsedType['type']) || interface_exists($parsedType['type']))
						&& !($this->isClassAnnotatedWith($parsedType['type'], 'TYPO3\Flow\Annotations\Entity') || $this->isClassAnnotatedWith($parsedType['type'], 'Doctrine\ORM\Mapping\Entity') || $this->isClassAnnotatedWith($parsedType['type'], 'TYPO3\Flow\Annotations\ValueObject'))) {
					continue;
				}

				$classSchema->addProperty($propertyName, $declaredType, $this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\Flow\Annotations\Lazy'));
				if ($this->isPropertyAnnotatedWith($className, $propertyName, 'TYPO3\Flow\Annotations\Identity')) {
					$classSchema->markAsIdentityProperty($propertyName);
				}
			}
		}
		if ($needsArtificialIdentity === TRUE) {
			$classSchema->addProperty('Persistence_Object_Identifier', 'string');
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
	 * @throws Exception\ClassSchemaConstraintViolationException
	 */
	protected function completeRepositoryAssignments() {
		foreach ($this->getAllImplementationClassNamesForInterface('TYPO3\Flow\Persistence\RepositoryInterface') as $repositoryClassName) {
				// need to be extra careful because this code could be called
				// during a cache:flush run with corrupted reflection cache
			if (class_exists($repositoryClassName) && !$this->isClassAbstract($repositoryClassName)) {
				if (!$this->isClassAnnotatedWith($repositoryClassName, 'TYPO3\Flow\Annotations\Scope') || $this->getClassAnnotation($repositoryClassName, 'TYPO3\Flow\Annotations\Scope')->value !== 'singleton') {
					throw new \TYPO3\Flow\Reflection\Exception\ClassSchemaConstraintViolationException('The repository "' . $repositoryClassName . '" must be of scope singleton, but it is not.', 1335790707);
				}
				$claimedObjectType = $repositoryClassName::ENTITY_CLASSNAME;
				if ($claimedObjectType !== NULL && isset($this->classSchemata[$claimedObjectType])) {
					$this->classSchemata[$claimedObjectType]->setRepositoryClassName($repositoryClassName);
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
	 * @param \TYPO3\Flow\Reflection\ClassSchema $classSchema
	 * @return void
	 */
	protected function makeChildClassesAggregateRoot(\TYPO3\Flow\Reflection\ClassSchema $classSchema) {
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
	 * @throws \TYPO3\Flow\Reflection\Exception
	 */
	protected function ensureAggregateRootInheritanceChainConsistency() {
		foreach ($this->classSchemata as $className => $classSchema) {
			if (!class_exists($className) || $classSchema->isAggregateRoot() === FALSE) {
				continue;
			}

			foreach (class_parents($className) as $parentClassName) {
				if ($this->isClassAbstract($parentClassName) === FALSE && $this->classSchemata[$parentClassName]->isAggregateRoot() === FALSE) {
					throw new \TYPO3\Flow\Reflection\Exception('In a class hierarchy either all or no classes must be an aggregate root, "' . $className . '" is one but the parent class "' . $parentClassName . '" is not. You probably want to add a repository for "' . $parentClassName . '"', 1316009511);
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
	 * @throws \TYPO3\Flow\Reflection\Exception\InvalidValueObjectException
	 */
	protected function checkValueObjectRequirements($className) {
		$methods = get_class_methods($className);
		if (array_search('__construct', $methods) === FALSE) {
			throw new \TYPO3\Flow\Reflection\Exception\InvalidValueObjectException('A value object must have a constructor, "' . $className . '" does not have one.', 1268740874);
		}
		foreach ($methods as $method) {
			if (substr($method, 0, 3) === 'set') {
				throw new \TYPO3\Flow\Reflection\Exception\InvalidValueObjectException('A value object must not have setters, "' . $className . '" does.', 1268740878);
			}
		}
	}

	/**
	 * Converts the internal, optimized data structure of parameter information into
	 * a human-friendly array with speaking indexes.
	 *
	 * @param array $parametersInformation Raw, internal parameter information
	 * @return array Developer friendly version
	 */
	protected function convertParameterDataToArray(array $parametersInformation) {
		$parameters = array();
		foreach ($parametersInformation as $parameterName => $parameterData) {
			$parameters[$parameterName] = array(
				'position' => $parameterData[self::DATA_PARAMETER_POSITION],
				'optional' => isset($parameterData[self::DATA_PARAMETER_OPTIONAL]),
				'type' => $parameterData[self::DATA_PARAMETER_TYPE],
				'class' => isset($parameterData[self::DATA_PARAMETER_CLASS]) ? $parameterData[self::DATA_PARAMETER_CLASS] : NULL,
				'array' => isset($parameterData[self::DATA_PARAMETER_ARRAY]),
				'byReference' => isset($parameterData[self::DATA_PARAMETER_BY_REFERENCE]),
				'allowsNull' => isset($parameterData[self::DATA_PARAMETER_ALLOWS_NULL]),
				'defaultValue' => isset($parameterData[self::DATA_PARAMETER_DEFAULT_VALUE]) ? $parameterData[self::DATA_PARAMETER_DEFAULT_VALUE] : NULL
			);
		}
		return $parameters;
	}

	/**
	 * Converts the given parameter reflection into an information array
	 *
	 * @param \TYPO3\Flow\Reflection\ParameterReflection $parameter The parameter to reflect
	 * @param \TYPO3\Flow\Reflection\MethodReflection $method The parameter's method
	 * @return array Parameter information array
	 */
	protected function convertParameterReflectionToArray(\TYPO3\Flow\Reflection\ParameterReflection $parameter, \TYPO3\Flow\Reflection\MethodReflection $method = NULL) {
		$parameterInformation = array(
			self::DATA_PARAMETER_POSITION => $parameter->getPosition()
		);
		if ($parameter->isPassedByReference()) {
			$parameterInformation[self::DATA_PARAMETER_BY_REFERENCE] = TRUE;
		}
		if ($parameter->isArray()) {
			$parameterInformation[self::DATA_PARAMETER_ARRAY] = TRUE;
		}
		if ($parameter->isOptional()) {
			$parameterInformation[self::DATA_PARAMETER_OPTIONAL] = TRUE;
		}
		if ($parameter->allowsNull()) {
			$parameterInformation[self::DATA_PARAMETER_ALLOWS_NULL] = TRUE;
		}

		$parameterClass = $parameter->getClass();
		if ($parameterClass !== NULL) {
			$parameterInformation[self::DATA_PARAMETER_CLASS] = $parameterClass->getName();
		}
		if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
			$parameterInformation[self::DATA_PARAMETER_DEFAULT_VALUE] = $parameter->getDefaultValue();
		}
		if ($method !== NULL) {
			$paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : array();
			if (isset($paramAnnotations[$parameter->getPosition()])) {
				$explodedParameters = explode(' ', $paramAnnotations[$parameter->getPosition()]);
				if (count($explodedParameters) >= 2) {
					$parameterInformation[self::DATA_PARAMETER_TYPE] = ltrim($explodedParameters[0], '\\');
				}
			}
		}
		if (!isset($parameterInformation[self::DATA_PARAMETER_TYPE]) && $parameterClass !== NULL) {
			$parameterInformation[self::DATA_PARAMETER_TYPE] = ltrim($parameterClass->getName(), '\\');
		} elseif (!isset($parameterInformation[self::DATA_PARAMETER_TYPE])) {
			$parameterInformation[self::DATA_PARAMETER_TYPE] = 'mixed';
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
		$frozenNamespaces = array();
		foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
			if ($this->packageManager->isPackageFrozen($packageKey)) {
				$frozenNamespaces[] = $package->getNamespace();
			}
		}

		$classNames = array_keys($this->classReflectionData);
		foreach ($frozenNamespaces as $namespace) {
			$namespace .= '\\';
			$namespaceLength = strlen($namespace);
			foreach ($classNames as $index => $className) {
				if (substr($className, 0, $namespaceLength) === $namespace) {
					unset($classNames[$index]);
				}
			}
		}

		foreach ($classNames as $className) {
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
		$this->systemLogger->log('Forget class ' . $className, LOG_DEBUG);
		if (isset($this->classesCurrentlyBeingForgotten[$className])) {
			$this->systemLogger->log('Detected recursion while forgetting class ' . $className, LOG_WARNING);
			return;
		}
		$this->classesCurrentlyBeingForgotten[$className] = TRUE;

		if (class_exists($className)) {
			$interfaceNames = class_implements($className);
			foreach ($interfaceNames as $interfaceName) {
				if (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className])) {
					unset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className]);
				}
			}
		} else {
			foreach ($this->availableClassNames as $interfaceNames) {
				foreach ($interfaceNames as $interfaceName) {
					if (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className])) {
						unset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className]);
					}
				}
			}
		}

		if (isset($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES])) {
			foreach (array_keys($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES]) as $subClassName) {
				$this->forgetClass($subClassName);
			}
		}

		foreach (array_keys($this->annotatedClasses) as $annotationClassName) {
			if (isset($this->annotatedClasses[$annotationClassName][$className])) {
				unset($this->annotatedClasses[$annotationClassName][$className]);
			}
		}

		if (isset($this->classSchemata[$className])) {
			unset($this->classSchemata[$className]);
		}

		foreach (array_keys($this->classesByMethodAnnotations) as $annotationClassName) {
			unset($this->classesByMethodAnnotations[$annotationClassName][$className]);
		}

		unset($this->classReflectionData[$className]);
		unset($this->classesCurrentlyBeingForgotten[$className]);
	}

	/**
	 * Tries to load the reflection data from the compile time cache.
	 *
	 * The compile time cache is only supported for Development context and thus
	 * this function will return in any other context.
	 *
	 * If no reflection data was found, this method will at least load the precompiled
	 * reflection data of any possible frozen package. Even if precompiled reflection
	 * data could be loaded, FALSE will be returned in order to signal that other
	 * packages still need to be reflected.
	 *
	 * @return boolean TRUE if reflection data could be loaded, otherwise FALSE
	 */
	protected function loadClassReflectionCompiletimeCache() {
		$data = $this->reflectionDataCompiletimeCache->get('ReflectionData');

		if ($data === FALSE) {
			if ($this->context->isDevelopment()) {
				$useIgBinary = extension_loaded('igbinary');
				foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
					if ($this->packageManager->isPackageFrozen($packageKey)) {
						$pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
						if (file_exists($pathAndFilename)) {
							$data = ($useIgBinary ? igbinary_unserialize(file_get_contents($pathAndFilename)) : unserialize(file_get_contents($pathAndFilename)));
							foreach ($data as $propertyName => $propertyValue) {
								$this->$propertyName = \TYPO3\Flow\Utility\Arrays::arrayMergeRecursiveOverrule($this->$propertyName, $propertyValue);
							}
						}
					}
				}
			}
			return FALSE;
		}

		foreach ($data as $propertyName => $propertyValue) {
			$this->$propertyName = $propertyValue;
		}

		return TRUE;
	}

	/**
	 * Loads reflection data from the cache or reflects the class if needed.
	 *
	 * If the class is completely unknown, this method won't try to load or reflect
	 * it. If it is known and reflection data has been loaded already, it won't be
	 * loaded again.
	 *
	 * In Production context, with frozen caches, this method will load reflection
	 * data for the specified class from the runtime cache.
	 *
	 * @param string $className Name of the class to load data for
	 * @return void
	 */
	protected function loadOrReflectClassIfNecessary($className) {
		if (!isset($this->classReflectionData[$className]) || is_array($this->classReflectionData[$className])) {
			return;
		}

		if ($this->loadFromClassSchemaRuntimeCache === TRUE) {
			$this->classReflectionData[$className] = $this->reflectionDataRuntimeCache->get(str_replace('\\', '_', $className));
		} else {
			$this->reflectClass($className);
		}
	}

	/**
	 * Stores the current reflection data related to classes of the specified package
	 * in the PrecompiledReflectionData directory for the current context.
	 *
	 * This method is used by the package manager.
	 *
	 * @param string $packageKey
	 * @return void
	 */
	public function freezePackageReflection($packageKey) {
		$package = $this->packageManager->getPackage($packageKey);

		$packageNamespace = $package->getNamespace() . '\\';
		$packageNamespaceLength = strlen($packageNamespace);

		$reflectionData = array(
			'classReflectionData' => $this->classReflectionData,
			'classSchemata' => $this->classSchemata,
			'annotatedClasses' => $this->annotatedClasses,
			'classesByMethodAnnotations' => $this->classesByMethodAnnotations
		);

		foreach (array_keys($reflectionData['classReflectionData']) as $className) {
			if (substr($className, 0, $packageNamespaceLength) !== $packageNamespace) {
				unset($reflectionData['classReflectionData'][$className]);
			}
		}

		foreach (array_keys($reflectionData['classSchemata']) as $className) {
			if (substr($className, 0, $packageNamespaceLength) !== $packageNamespace) {
				unset($reflectionData['classSchemata'][$className]);
			}
		}

		foreach (array_keys($reflectionData['annotatedClasses']) as $className) {
			if (substr($className, 0, $packageNamespaceLength) !== $packageNamespace) {
				unset($reflectionData['annotatedClasses'][$className]);
			}
		}

		if (isset($reflectionData['classesByMethodAnnotations'])) {
			foreach ($reflectionData['classesByMethodAnnotations'] as $annotationClassName => $classNames) {
				foreach ($classNames as $index => $className) {
					if (substr($className, 0, $packageNamespaceLength) !== $packageNamespace) {
						unset($reflectionData['classesByMethodAnnotations'][$annotationClassName][$index]);
					}
				}
			}
		}

		$precompiledReflectionStoragePath = $this->getPrecompiledReflectionStoragePath();
		if (!is_dir($precompiledReflectionStoragePath)) {
			Files::createDirectoryRecursively($precompiledReflectionStoragePath);
		}
		$pathAndFilename = $precompiledReflectionStoragePath . $packageKey . '.dat';
		file_put_contents($pathAndFilename, extension_loaded('igbinary') ? igbinary_serialize($reflectionData) : serialize($reflectionData));
	}

	/**
	 * Removes the precompiled reflection data of a frozen package
	 *
	 * This method is used by the package manager.
	 *
	 * @param string $packageKey The package to remove the data from
	 * @return void
	 */
	public function unfreezePackageReflection($packageKey) {
		$pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
		if (file_exists($pathAndFilename)) {
			unlink($pathAndFilename);
		}
	}

	/**
	 * Exports the internal reflection data into the ReflectionData cache
	 *
	 * This method is triggered by a signal which is connected to the bootstrap's
	 * shutdown sequence.
	 *
	 * If the reflection data has previously been loaded from the runtime cache,
	 * saving it is omitted as changes are not expected.
	 *
	 * In Production context the whole cache is written at once and then frozen in
	 * order to be consistent. Frozen cache data in Development is only produced for
	 * classes contained in frozen packages.
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Reflection\Exception if no cache has been injected
	 */
	public function saveToCache() {
		if ($this->loadFromClassSchemaRuntimeCache === TRUE) {
			return;
		}

		if (!($this->reflectionDataCompiletimeCache instanceof \TYPO3\Flow\Cache\Frontend\FrontendInterface)) {
			throw new \TYPO3\Flow\Reflection\Exception('A cache must be injected before initializing the Reflection Service.', 1232044697);
		}

		if (count($this->updatedReflectionData) > 0) {
			$this->log(sprintf('Found %s classes whose reflection data was not cached previously.', count($this->updatedReflectionData)), LOG_DEBUG);

			foreach (array_keys($this->updatedReflectionData) as $className) {
				$this->statusCache->set(str_replace('\\', '_', $className), '');
			}

			$data = array();
			$propertyNames = array(
				'classReflectionData',
				'classSchemata',
				'annotatedClasses',
				'classesByMethodAnnotations'
			);
			foreach ($propertyNames as $propertyName) {
				$data[$propertyName] = $this->$propertyName;
			}
			$this->reflectionDataCompiletimeCache->set('ReflectionData', $data);
		}

		if ($this->context->isProduction()) {
			$this->reflectionDataRuntimeCache->flush();

			$classNames = array();
			foreach ($this->classReflectionData as $className => $reflectionData) {
				$classNames[$className] = TRUE;
				$this->reflectionDataRuntimeCache->set(str_replace('\\', '_', $className), $reflectionData);
				if (isset($this->classSchemata[$className])) {
					$this->classSchemataRuntimeCache->set(str_replace('\\', '_', $className), $this->classSchemata[$className]);
				}
			}
			$this->reflectionDataRuntimeCache->set('__classNames', $classNames);
			$this->reflectionDataRuntimeCache->set('__annotatedClasses', $this->annotatedClasses);

			$this->reflectionDataRuntimeCache->getBackend()->freeze();
			$this->classSchemataRuntimeCache->getBackend()->freeze();

			$this->log(sprintf('Built and froze reflection runtime caches (%s classes).', count($this->classReflectionData)), LOG_INFO);
		} elseif ($this->context->isDevelopment()) {
			foreach (array_keys($this->packageManager->getFrozenPackages()) as $packageKey) {
				$pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
				if (!file_exists($pathAndFilename)) {
					$this->log(sprintf('Rebuilding precompiled reflection data for frozen package %s.', $packageKey), LOG_INFO);
					$this->freezePackageReflection($packageKey);
				}
			}
		}
	}

	/**
	 * Writes the given message along with the additional information into the log.
	 *
	 * @param string $message The message to log
	 * @param integer $severity An integer value, one of the LOG_* constants
	 * @param mixed $additionalData A variable containing more information about the event to be logged
	 * @return void
	 */
	protected function log($message, $severity = LOG_INFO, $additionalData = NULL) {
		if (is_object($this->systemLogger)) {
			$this->systemLogger->log($message, $severity, $additionalData);
		}
	}

	/**
	 * Determines the path to the precompiled reflection data.
	 *
	 * @return string
	 */
	protected function getPrecompiledReflectionStoragePath() {
		return Files::concatenatePaths(array($this->environment->getPathToTemporaryDirectory(), 'PrecompiledReflectionData/')) . '/';
	}

}
?>