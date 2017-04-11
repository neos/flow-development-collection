<?php
namespace Neos\Flow\Reflection;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\FrontendInterface;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Core\ClassLoader;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\Flow\Persistence\RepositoryInterface;
use Neos\Flow\Reflection\Exception\ClassSchemaConstraintViolationException;
use Neos\Flow\Reflection\Exception\InvalidPropertyTypeException;
use Neos\Flow\Reflection\Exception\InvalidValueObjectException;
use Neos\Utility\Arrays;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Exception\InvalidTypeException;
use Neos\Utility\Files;
use Neos\Utility\TypeHandling;

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
class ReflectionService
{
    const VISIBILITY_PRIVATE = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PUBLIC = 3;

    // Implementations of an interface
    const DATA_INTERFACE_IMPLEMENTATIONS = 1;

    // Implemented interfaces of a class
    const DATA_CLASS_INTERFACES = 2;

    // Subclasses of a class
    const DATA_CLASS_SUBCLASSES = 3;

    // Class tag values
    const DATA_CLASS_TAGS_VALUES = 4;

    // Class annotations
    const DATA_CLASS_ANNOTATIONS = 5;
    const DATA_CLASS_ABSTRACT = 6;
    const DATA_CLASS_FINAL = 7;
    const DATA_CLASS_METHODS = 8;
    const DATA_CLASS_PROPERTIES = 9;
    const DATA_METHOD_FINAL = 10;
    const DATA_METHOD_STATIC = 11;
    const DATA_METHOD_VISIBILITY = 12;
    const DATA_METHOD_PARAMETERS = 13;
    const DATA_METHOD_DECLARED_RETURN_TYPE = 25;
    const DATA_PROPERTY_TAGS_VALUES = 14;
    const DATA_PROPERTY_ANNOTATIONS = 15;
    const DATA_PROPERTY_VISIBILITY = 24;
    const DATA_PARAMETER_POSITION = 16;
    const DATA_PARAMETER_OPTIONAL = 17;
    const DATA_PARAMETER_TYPE = 18;
    const DATA_PARAMETER_ARRAY = 19;
    const DATA_PARAMETER_CLASS = 20;
    const DATA_PARAMETER_ALLOWS_NULL = 21;
    const DATA_PARAMETER_DEFAULT_VALUE = 22;
    const DATA_PARAMETER_BY_REFERENCE = 23;
    const DATA_PARAMETER_SCALAR_DECLARATION = 24;

    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $annotationReader;

    /**
     * @var array
     */
    protected $availableClassNames = [];

    /**
     * @var StringFrontend
     */
    protected $statusCache;

    /**
     * @var VariableFrontend
     */
    protected $reflectionDataCompiletimeCache;

    /**
     * @var VariableFrontend
     */
    protected $reflectionDataRuntimeCache;

    /**
     * @var VariableFrontend
     */
    protected $classSchemataRuntimeCache;

    /**
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @var PackageManagerInterface
     */
    protected $packageManager;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var ApplicationContext
     */
    protected $context;

    /**
     * The doctrine PHP parser which can parse "use" statements. Is initialized
     * lazily when it is first needed.
     * Note: Don't refer to this member directly but use getDoctrinePhpParser() to obtain an instance
     *
     * @var \Doctrine\Common\Annotations\PhpParser
     */
    protected $doctrinePhpParser;

    /**
     * a cache which stores the use statements reflected for a particular class
     * (only relevant for un-expanded "var" and "param" annotations)
     *
     * @var array
     */
    protected $useStatementsForClassCache;

    /**
     * In Production context, with frozen caches, this flag will be TRUE
     *
     * @var boolean
     */
    protected $loadFromClassSchemaRuntimeCache = false;

    /**
     * @var array
     */
    protected $settings;

    /**
     * Array of annotation classnames and the names of classes which are annotated with them
     *
     * @var array
     */
    protected $annotatedClasses = [];

    /**
     * Array of method annotations and the classes and methods which are annotated with them
     *
     * @var array
     */
    protected $classesByMethodAnnotations = [];

    /**
     * Schemata of all classes which can be persisted
     *
     * @var array<\Neos\Flow\Reflection\ClassSchema>
     */
    protected $classSchemata = [];

    /**
     * An array of class names which are currently being forgotten by forgetClass(). Acts as a safeguard against infinite loops.
     *
     * @var array
     */
    protected $classesCurrentlyBeingForgotten = [];

    /**
     * Array with reflection information indexed by class name
     *
     * @var array
     */
    protected $classReflectionData = [];

    /**
     * Array with updated reflection information (e.g. in Development context after classes have changed)
     *
     * @var array
     */
    protected $updatedReflectionData = [];

    /**
     * @var boolean
     */
    protected $initialized = false;

    /**
     * Sets the status cache
     *
     * The cache must be set before initializing the Reflection Service
     *
     * @param StringFrontend $cache Cache for the reflection service
     * @return void
     */
    public function setStatusCache(StringFrontend $cache)
    {
        $this->statusCache = $cache;
        $backend = $this->statusCache->getBackend();
        if (is_callable(['initializeObject', $backend])) {
            $backend->initializeObject();
        }
    }

    /**
     * Sets the compile-time data cache
     *
     * @param VariableFrontend $cache Cache for the reflection service
     * @return void
     */
    public function setReflectionDataCompiletimeCache(VariableFrontend $cache)
    {
        $this->reflectionDataCompiletimeCache = $cache;
    }

    /**
     * Sets the runtime data cache
     *
     * @param VariableFrontend $cache Cache for the reflection service
     * @return void
     */
    public function setReflectionDataRuntimeCache(VariableFrontend $cache)
    {
        $this->reflectionDataRuntimeCache = $cache;
    }

    /**
     * Sets the dedicated class schema cache for runtime purposes
     *
     * @param VariableFrontend $cache
     * @return void
     */
    public function setClassSchemataRuntimeCache(VariableFrontend $cache)
    {
        $this->classSchemataRuntimeCache = $cache;
    }

    /**
     * @param array $settings Settings of the Flow package
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param SystemLoggerInterface $systemLogger
     * @return void
     */
    public function injectSystemLogger(SystemLoggerInterface $systemLogger)
    {
        $this->systemLogger = $systemLogger;
    }

    /**
     * @param PackageManagerInterface $packageManager
     * @return void
     */
    public function injectPackageManager(PackageManagerInterface $packageManager)
    {
        $this->packageManager = $packageManager;
    }

    /**
     * @param Environment $environment
     * @return void
     */
    public function injectEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * Retrieves a singleton instance of the Doctrine PhpParser
     *
     * @return \Doctrine\Common\Annotations\PhpParser
     */
    protected function getDoctrinePhpParser()
    {
        if ($this->doctrinePhpParser === null) {
            $this->doctrinePhpParser = new PhpParser();
        }

        return $this->doctrinePhpParser;
    }

    /**
     * Initialize the reflection service lazily
     *
     * This method must be run only after all dependencies have been injected.
     *
     * @return void
     */
    protected function initialize()
    {
        $this->context = $this->environment->getContext();

        if ($this->hasFrozenCacheInProduction()) {
            $this->classReflectionData = $this->reflectionDataRuntimeCache->get('__classNames');
            $this->annotatedClasses = $this->reflectionDataRuntimeCache->get('__annotatedClasses');
            $this->loadFromClassSchemaRuntimeCache = true;
        } else {
            $this->loadClassReflectionCompiletimeCache();
        }

        $this->annotationReader = new AnnotationReader();
        foreach ($this->settings['reflection']['ignoredTags'] as $tagName => $ignoreFlag) {
            if ($ignoreFlag === true) {
                AnnotationReader::addGlobalIgnoredName($tagName);
            }
        }

        $this->initialized = true;
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
    public function buildReflectionData(array $availableClassNames)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
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
    public function isClassReflected($className)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);

        return isset($this->classReflectionData[$className]);
    }

    /**
     * Returns the names of all classes known to this reflection service.
     *
     * @return array Class names
     * @api
     */
    public function getAllClassNames()
    {
        if (!$this->initialized) {
            $this->initialize();
        }

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
    public function getDefaultImplementationClassNameForInterface($interfaceName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $interfaceName = $this->cleanClassName($interfaceName);

        if (interface_exists($interfaceName) === false) {
            throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769559);
        }
        $this->loadOrReflectClassIfNecessary($interfaceName);

        $classNamesFound = isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) ? array_keys($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) : [];
        if (count($classNamesFound) === 1) {
            return $classNamesFound[0];
        }
        if (count($classNamesFound) !== 2 || !isset($this->classReflectionData[ProxyInterface::class][self::DATA_INTERFACE_IMPLEMENTATIONS])) {
            return false;
        }

        if (isset($this->classReflectionData[ProxyInterface::class][self::DATA_INTERFACE_IMPLEMENTATIONS][$classNamesFound[0]])) {
            return $classNamesFound[0];
        }
        if (isset($this->classReflectionData[ProxyInterface::class][self::DATA_INTERFACE_IMPLEMENTATIONS][$classNamesFound[1]])) {
            return $classNamesFound[1];
        }

        return false;
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
    public function getAllImplementationClassNamesForInterface($interfaceName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $interfaceName = $this->cleanClassName($interfaceName);

        if (interface_exists($interfaceName) === false) {
            throw new \InvalidArgumentException('"' . $interfaceName . '" does not exist or is not the name of an interface.', 1238769560);
        }
        $this->loadOrReflectClassIfNecessary($interfaceName);

        return (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS])) ? array_keys($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS]) : [];
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
    public function getAllSubClassNamesForClass($className)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);

        if (class_exists($className) === false) {
            throw new \InvalidArgumentException('"' . $className . '" does not exist or is not the name of a class.', 1257168042);
        }
        $this->loadOrReflectClassIfNecessary($className);

        return (isset($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES])) ? array_keys($this->classReflectionData[$className][self::DATA_CLASS_SUBCLASSES]) : [];
    }

    /**
     * Returns the class name of the given object. This is a convenience
     * method that returns the expected class names even for proxy classes.
     *
     * @param object $object
     * @return string The class name of the given object
     * @deprecated since 3.0 use \Neos\Utility\TypeHandling::getTypeForValue() instead
     */
    public function getClassNameByObject($object)
    {
        return TypeHandling::getTypeForValue($object);
    }

    /**
     * Searches for and returns all names of classes which are tagged by the specified
     * annotation. If no classes were found, an empty array is returned.
     *
     * @param string $annotationClassName Name of the annotation class, for example "Neos\Flow\Annotations\Aspect"
     * @return array
     */
    public function getClassNamesByAnnotation($annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotationClassName = $this->cleanClassName($annotationClassName);

        return (isset($this->annotatedClasses[$annotationClassName]) ? array_keys($this->annotatedClasses[$annotationClassName]) : []);
    }

    /**
     * Tells if the specified class has the given annotation
     *
     * @param string $className Name of the class
     * @param string $annotationClassName Annotation to check for
     * @return boolean
     * @api
     */
    public function isClassAnnotatedWith($className, $annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);

        $annotationClassName = $this->cleanClassName($annotationClassName);

        return (isset($this->annotatedClasses[$annotationClassName][$className]));
    }

    /**
     * Returns the specified class annotations or an empty array
     *
     * @param string $className Name of the class
     * @param string $annotationClassName Annotation to filter for
     * @return array<object>
     */
    public function getClassAnnotations($className, $annotationClassName = null)
    {
        $className = $this->prepareClassReflectionForUsage($className);

        $annotationClassName = $annotationClassName === null ? null : $this->cleanClassName($annotationClassName);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS])) {
            return [];
        }
        if ($annotationClassName === null) {
            return $this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS];
        }

        $annotations = [];
        foreach ($this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS] as $annotation) {
            if ($annotation instanceof $annotationClassName) {
                $annotations[] = $annotation;
            }
        }

        return $annotations;
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
    public function getClassAnnotation($className, $annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getClassAnnotations($className, $annotationClassName);

        return $annotations === [] ? null : current($annotations);
    }

    /**
     * Tells if the specified class implements the given interface
     *
     * @param string $className Name of the class
     * @param string $interfaceName interface to check for
     * @return boolean TRUE if the class implements $interfaceName, otherwise FALSE
     * @api
     */
    public function isClassImplementationOf($className, $interfaceName)
    {
        $className = $this->prepareClassReflectionForUsage($className);

        $interfaceName = $this->cleanClassName($interfaceName);
        $this->loadOrReflectClassIfNecessary($interfaceName);

        return (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className]));
    }

    /**
     * Tells if the specified class is abstract or not
     *
     * @param string $className Name of the class to analyze
     * @return boolean TRUE if the class is abstract, otherwise FALSE
     * @api
     */
    public function isClassAbstract($className)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT]);
    }

    /**
     * Tells if the specified class is final or not
     *
     * @param string $className Name of the class to analyze
     * @return boolean TRUE if the class is final, otherwise FALSE
     * @api
     */
    public function isClassFinal($className)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_FINAL]);
    }

    /**
     * Tells if the class is unconfigurable or not
     *
     * @param string $className Name of the class to analyze
     * @return boolean return TRUE if class not could not be automatically configured, otherwise FALSE
     * @api
     */
    public function isClassUnconfigurable($className)
    {
        $className = $this->cleanClassName($className);

        return $this->classReflectionData[$className] === [];
    }

    /**
     * Returns all class names of classes containing at least one method annotated
     * with the given annotation class
     *
     * @param string $annotationClassName The annotation class name for a method annotation
     * @return array An array of class names
     * @api
     */
    public function getClassesContainingMethodsAnnotatedWith($annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->classesByMethodAnnotations[$annotationClassName]) ? array_keys($this->classesByMethodAnnotations[$annotationClassName]) : [];
    }

    /**
     * Returns all names of methods of the given class that are annotated with the given annotation class
     *
     * @param string $className Name of the class containing the method(s)
     * @param string $annotationClassName The annotation class name for a method annotation
     * @return array An array of method names
     * @api
     */
    public function getMethodsAnnotatedWith($className, $annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return isset($this->classesByMethodAnnotations[$annotationClassName][$className]) ? $this->classesByMethodAnnotations[$annotationClassName][$className] : [];
    }

    /**
     * Tells if the specified method is final or not
     *
     * @param string $className Name of the class containing the method
     * @param string $methodName Name of the method to analyze
     * @return boolean TRUE if the method is final, otherwise FALSE
     * @api
     */
    public function isMethodFinal($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isMethodStatic($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isMethodPublic($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isMethodProtected($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isMethodPrivate($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isMethodTaggedWith($className, $methodName, $tag)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $method = new MethodReflection($this->cleanClassName($className), $methodName);
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
    public function isMethodAnnotatedWith($className, $methodName, $annotationClassName)
    {
        return $this->getMethodAnnotations($className, $methodName, $annotationClassName) !== [];
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
    public function getMethodAnnotations($className, $methodName, $annotationClassName = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);
        $annotationClassName = $annotationClassName === null ? null : $this->cleanClassName($annotationClassName);

        $annotations = [];
        $methodAnnotations = $this->annotationReader->getMethodAnnotations(new MethodReflection($className, $methodName));
        if ($annotationClassName === null) {
            return $methodAnnotations;
        }

        foreach ($methodAnnotations as $annotation) {
            if ($annotation instanceof $annotationClassName) {
                $annotations[] = $annotation;
            }
        }

        return $annotations;
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
    public function getMethodAnnotation($className, $methodName, $annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getMethodAnnotations($className, $methodName, $annotationClassName);

        return $annotations === [] ? null : current($annotations);
    }

    /**
     * Returns the names of all properties of the specified class
     *
     * @param string $className Name of the class to return the property names of
     * @return array An array of property names or an empty array if none exist
     * @api
     */
    public function getClassPropertyNames($className)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) ? array_keys($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) : [];
    }

    /**
     * Wrapper for method_exists() which tells if the given method exists.
     *
     * @param string $className Name of the class containing the method
     * @param string $methodName Name of the method
     * @return boolean
     * @api
     */
    public function hasMethod($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function getMethodTagsValues($className, $methodName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);

        $method = new MethodReflection($className, $methodName);

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
    public function getMethodParameters($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS])) {
            return [];
        }

        return $this->convertParameterDataToArray($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS]);
    }

    /**
     * Returns the declared return type of a method (for PHP < 7.0 this will always return null)
     *
     * @param string $className
     * @param string $methodName
     * @return string The declared return type of the method or null if none was declared
     */
    public function getMethodDeclaredReturnType($className, $methodName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_DECLARED_RETURN_TYPE])) {
            return null;
        }

        return $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_DECLARED_RETURN_TYPE];
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
    public function getPropertyNamesByTag($className, $tag)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES])) {
            return [];
        }

        $propertyNames = [];
        foreach ($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES] as $propertyName => $propertyData) {
            if (isset($propertyData[self::DATA_PROPERTY_TAGS_VALUES][$tag])) {
                $propertyNames[$propertyName] = true;
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
    public function getPropertyTagsValues($className, $propertyName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
            return [];
        }

        return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES])) ? $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES] : [];
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
    public function getPropertyTagValues($className, $propertyName, $tag)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
            return [];
        }

        return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag])) ? $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag] : [];
    }

    /**
     * Tells if the specified property is private
     *
     * @param string $className Name of the class containing the method
     * @param string $propertyName Name of the property to analyze
     * @return boolean TRUE if the property is private, otherwise FALSE
     * @api
     */
    public function isPropertyPrivate($className, $propertyName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isPropertyTaggedWith($className, $propertyName, $tag)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function isPropertyAnnotatedWith($className, $propertyName, $annotationClassName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
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
    public function getPropertyNamesByAnnotation($className, $annotationClassName)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES])) {
            return [];
        }

        $propertyNames = [];
        foreach ($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES] as $propertyName => $propertyData) {
            if (isset($propertyData[self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName])) {
                $propertyNames[$propertyName] = true;
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
    public function getPropertyAnnotations($className, $propertyName, $annotationClassName = null)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS])) {
            return [];
        }

        if ($annotationClassName === null) {
            return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS];
        }

        if (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName])) {
            return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName];
        }

        return [];
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
    public function getPropertyAnnotation($className, $propertyName, $annotationClassName)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getPropertyAnnotations($className, $propertyName, $annotationClassName);

        return $annotations === [] ? null : current($annotations);
    }

    /**
     * Returns the class schema for the given class
     *
     * @param mixed $classNameOrObject The class name or an object
     * @return ClassSchema
     */
    public function getClassSchema($classNameOrObject)
    {
        $className = $classNameOrObject;
        if (is_object($classNameOrObject)) {
            $className = TypeHandling::getTypeForValue($classNameOrObject);
        }
        $className = $this->cleanClassName($className);

        if (!isset($this->classSchemata[$className])) {
            $this->classSchemata[$className] = $this->classSchemataRuntimeCache->get($this->produceCacheIdentifierFromClassName($className));
        }

        return is_object($this->classSchemata[$className]) ? $this->classSchemata[$className] : null;
    }

    /**
     * Initializes the ReflectionService, cleans the given class name and finally reflects the class if necessary.
     *
     * @param string $className
     * @return string The cleaned class name
     */
    protected function prepareClassReflectionForUsage($className)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);
        $this->loadOrReflectClassIfNecessary($className);

        return $className;
    }

    /**
     * Checks if the given class names match those which already have been
     * reflected. If the given array contains class names not yet known to
     * this service, these classes will be reflected.
     *
     * @return void
     * @throws Exception
     */
    protected function reflectEmergedClasses()
    {
        $classNamesToReflect = [];
        foreach ($this->availableClassNames as $classNamesInOnePackage) {
            $classNamesToReflect = array_merge($classNamesToReflect, $classNamesInOnePackage);
        }
        $reflectedClassNames = array_keys($this->classReflectionData);
        sort($classNamesToReflect);
        sort($reflectedClassNames);
        $newClassNames = array_diff($classNamesToReflect, $reflectedClassNames);
        if ($newClassNames === []) {
            return;
        }

        $this->systemLogger->log('Reflected class names did not match class names to reflect', LOG_DEBUG);
        $count = 0;

        $classNameFilterFunction = function ($className) use (&$count) {
            $this->reflectClass($className);
            if (
                !$this->isClassAnnotatedWith($className, Flow\Entity::class) &&
                !$this->isClassAnnotatedWith($className, ORM\Entity::class) &&
                !$this->isClassAnnotatedWith($className, ORM\Embeddable::class) &&
                !$this->isClassAnnotatedWith($className, Flow\ValueObject::class)
            ) {
                return false;
            }

            $scopeAnnotation = $this->getClassAnnotation($className, Flow\Scope::class);
            if ($scopeAnnotation !== null && $scopeAnnotation->value !== 'prototype') {
                throw new Exception(sprintf('Classes tagged as entity or value object must be of scope prototype, however, %s is declared as %s.', $className, $scopeAnnotation->value), 1264103349);
            }

            $count++;
            return true;
        };

        $classNamesToBuildSchemaFor = array_filter($newClassNames, $classNameFilterFunction);
        $this->buildClassSchemata($classNamesToBuildSchemaFor);

        if ($count > 0) {
            $this->log(sprintf('Reflected %s emerged classes.', $count), LOG_INFO);
        }
    }

    /**
     * Check if a specific annotation tag is configured to be ignored.
     *
     * @param string $tagName The annotation tag to check
     * @return boolean TRUE if the tag is configured to be ignored, FALSE otherwise
     */
    protected function isTagIgnored($tagName)
    {
        if (isset($this->settings['reflection']['ignoredTags'][$tagName]) && $this->settings['reflection']['ignoredTags'][$tagName] === true) {
            return true;
        }
        // Make this setting backwards compatible with old array schema (deprecated since 3.0)
        if (in_array($tagName, $this->settings['reflection']['ignoredTags'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Reflects the given class and stores the results in this service's properties.
     *
     * @param string $className Full qualified name of the class to reflect
     * @return void
     * @throws Exception\InvalidClassException
     */
    protected function reflectClass($className)
    {
        $this->log(sprintf('Reflecting class %s', $className), LOG_DEBUG);

        $className = $this->cleanClassName($className);
        if (strpos($className, 'Neos\Flow\Persistence\Doctrine\Proxies') === 0 && in_array(\Doctrine\ORM\Proxy\Proxy::class, class_implements($className))) {
            // Somebody tried to reflect a doctrine proxy, which will have severe side effects.
            // see bug http://forge.typo3.org/issues/29449 for details.
            throw new Exception\InvalidClassException('The class with name "' . $className . '" is a Doctrine proxy. It is not supported to reflect doctrine proxy classes.', 1314944681);
        }

        $class = new ClassReflection($className);
        if (!isset($this->classReflectionData[$className])) {
            $this->classReflectionData[$className] = [];
        }

        if ($class->isAbstract() || $class->isInterface()) {
            $this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT] = true;
        }
        if ($class->isFinal()) {
            $this->classReflectionData[$className][self::DATA_CLASS_FINAL] = true;
        }

        /** @var $parentClass ClassReflection */
        foreach ($this->getParentClasses($class) as $parentClass) {
            $this->addParentClass($className, $parentClass);
        }

        /** @var $interface ClassReflection */
        foreach ($class->getInterfaces() as $interface) {
            $this->addImplementedInterface($className, $interface);
        }

        foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
            $annotationClassName = get_class($annotation);
            $this->annotatedClasses[$annotationClassName][$className] = true;
            $this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS][] = $annotation;
        }

        /** @var $property PropertyReflection */
        foreach ($class->getProperties() as $property) {
            $this->reflectClassProperty($className, $property);
        }

        foreach ($class->getMethods() as $method) {
            $this->reflectClassMethod($className, $method);
        }
        // Sort reflection data so that the cache data is deterministic. This is
        // important for comparisons when checking if classes have changed in a
        // Development context.
        ksort($this->classReflectionData);

        $this->updatedReflectionData[$className] = true;
    }

    /**
     * @param string $className
     * @param PropertyReflection $property
     * @return integer visibility
     */
    public function reflectClassProperty($className, PropertyReflection $property)
    {
        $propertyName = $property->getName();
        $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName] = [];

        $visibility = $property->isPublic() ? self::VISIBILITY_PUBLIC : ($property->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
        $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY] = $visibility;

        foreach ($property->getTagsValues() as $tagName => $tagValues) {
            $tagValues = $this->reflectPropertyTag($className, $property, $tagName, $tagValues);
            if ($tagValues === null) {
                continue;
            }
            $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tagName] = $tagValues;
        }

        foreach ($this->annotationReader->getPropertyAnnotations($property, $propertyName) as $annotation) {
            $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][get_class($annotation)][] = $annotation;
        }

        return $visibility;
    }

    /**
     * @param string $className
     * @param PropertyReflection $property
     * @param string $tagName
     * @param array $tagValues
     * @return array
     */
    protected function reflectPropertyTag($className, PropertyReflection $property, $tagName, $tagValues)
    {
        if ($this->isTagIgnored($tagName)) {
            return null;
        }

        if ($tagName !== 'var' || !isset($tagValues[0])) {
            return $tagValues;
        }

        $propertyName = $property->getName();
        $propertyDeclaringClass = $property->getDeclaringClass();
        if ($propertyDeclaringClass->getName() !== $className && isset($this->classReflectionData[$propertyDeclaringClass->getName()][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tagName])) {
            $tagValues = $this->classReflectionData[$propertyDeclaringClass->getName()][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tagName];
        } else {
            $tagValues[0] = $this->expandType($propertyDeclaringClass, $tagValues[0]);
        }
        return $tagValues;
    }

    /**
     * @param string $className
     * @param ClassReflection $parentClass
     * @return void
     */
    protected function addParentClass($className, ClassReflection $parentClass)
    {
        $parentClassName = $parentClass->getName();
        if (!isset($this->classReflectionData[$parentClassName])) {
            $this->reflectClass($parentClassName);
        }
        $this->classReflectionData[$parentClassName][self::DATA_CLASS_SUBCLASSES][$className] = true;
    }

    /**
     * @param string $className
     * @param ClassReflection $interface
     * @throws Exception\InvalidClassException
     */
    protected function addImplementedInterface($className, ClassReflection $interface)
    {
        if (isset($this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT])) {
            return;
        }

        $interfaceName = $interface->getName();
        if (!isset($this->classReflectionData[$interfaceName])) {
            $this->reflectClass($interfaceName);
        }
        $this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className] = true;
    }

    /**
     * @param string $className
     * @param \Neos\Flow\Reflection\MethodReflection $method
     * @return void
     */
    protected function reflectClassMethod($className, MethodReflection $method)
    {
        $methodName = $method->getName();
        if ($method->isFinal()) {
            $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_FINAL] = true;
        }
        if ($method->isStatic()) {
            $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_STATIC] = true;
        }
        $visibility = $method->isPublic() ? self::VISIBILITY_PUBLIC : ($method->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
        $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] = $visibility;

        foreach ($this->getMethodAnnotations($className, $methodName) as $methodAnnotation) {
            $annotationClassName = get_class($methodAnnotation);
            if (!isset($this->classesByMethodAnnotations[$annotationClassName][$className])) {
                $this->classesByMethodAnnotations[$annotationClassName][$className] = [];
            }
            $this->classesByMethodAnnotations[$annotationClassName][$className][] = $methodName;
        }

        $returnType = $method->getDeclaredReturnType();
        if ($returnType !== null) {
            $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_DECLARED_RETURN_TYPE] = $returnType;
        }

        foreach ($method->getParameters() as $parameter) {
            $this->reflectClassMethodParameter($className, $method, $parameter);
        }
    }

    /**
     * @param string $className
     * @param \Neos\Flow\Reflection\MethodReflection $method
     * @param \Neos\Flow\Reflection\ParameterReflection $parameter
     * @return void
     */
    protected function reflectClassMethodParameter($className, MethodReflection $method, ParameterReflection $parameter)
    {
        $methodName = $method->getName();
        $paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : [];

        $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
        if ($this->settings['reflection']['logIncorrectDocCommentHints'] !== true) {
            return;
        }

        if (!isset($paramAnnotations[$parameter->getPosition()])) {
            $this->log('  Missing @param for "' . $method->getName() . '::$' . $parameter->getName(), LOG_DEBUG);

            return;
        }

        $parameterAnnotation = explode(' ', $paramAnnotations[$parameter->getPosition()], 3);
        if (count($parameterAnnotation) < 2) {
            $this->log('  Wrong @param use for "' . $method->getName() . '::' . $parameter->getName() . '": "' . implode(' ', $parameterAnnotation) . '"', LOG_DEBUG);
        }

        if (
            isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE]) &&
            $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE] !== $this->cleanClassName($parameterAnnotation[0])
        ) {
            $this->log('  Wrong type in @param for "' . $method->getName() . '::' . $parameter->getName() . '": "' . $parameterAnnotation[0] . '"', LOG_DEBUG);
        }

        if ($parameter->getName() !== ltrim($parameterAnnotation[1], '$&')) {
            $this->log('  Wrong name in @param for "' . $method->getName() . '::$' . $parameter->getName() . '": "' . $parameterAnnotation[1] . '"', LOG_DEBUG);
        }
    }

    /**
     * Expand shortened class names in "var" and "param" annotations, taking use statements into account.
     *
     * @param ClassReflection $class
     * @param string $type the type inside var/param annotation
     * @return string the possibly expanded type
     */
    protected function expandType(ClassReflection $class, $type)
    {
        // expand "SomeType<SomeElementType>" to "\SomeTypeNamespace\SomeType<\ElementTypeNamespace\ElementType>"
        if (strpos($type, '<') !== false) {
            $typeParts = explode('<', $type);
            $type = $typeParts[0];
            $elementType = rtrim($typeParts[1], '>');

            return $this->expandType($class, $type) . '<' . $this->expandType($class, $elementType) . '>';
        }

        // skip simple types and types with fully qualified namespaces
        if ($type === 'mixed' || $type[0] === '\\' || TypeHandling::isSimpleType($type)) {
            return TypeHandling::normalizeType($type);
        }

        // we try to find the class relative to the current namespace...
        $possibleFullyQualifiedClassName = sprintf('%s\\%s', $class->getNamespaceName(), $type);
        if (class_exists($possibleFullyQualifiedClassName) || interface_exists($possibleFullyQualifiedClassName)) {
            return $possibleFullyQualifiedClassName;
        }

        // and then we try to find "use" statements for the class.
        $className = $class->getName();
        if (!isset($this->useStatementsForClassCache[$className])) {
            $this->useStatementsForClassCache[$className] = $this->getDoctrinePhpParser()->parseClass($class);
        }
        $useStatementsForClass = $this->useStatementsForClassCache[$className];

        // ... and try to expand them
        $typeParts = explode('\\', $type, 2);
        $lowercasedFirstTypePart = strtolower($typeParts[0]);
        if (isset($useStatementsForClass[$lowercasedFirstTypePart])) {
            $typeParts[0] = $useStatementsForClass[$lowercasedFirstTypePart];

            return implode('\\', $typeParts);
        }

        return $type;
    }

    /**
     * Finds all parent classes of the given class
     *
     * @param ClassReflection $class The class to reflect
     * @param array $parentClasses Array of parent classes
     * @return array<ClassReflection>
     */
    protected function getParentClasses(ClassReflection $class, array $parentClasses = [])
    {
        $parentClass = $class->getParentClass();
        if ($parentClass !== false) {
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
    protected function buildClassSchemata(array $classNames)
    {
        foreach ($classNames as $className) {
            $this->classSchemata[$className] = $this->buildClassSchema($className);
        }

        $this->completeRepositoryAssignments();
        $this->ensureAggregateRootInheritanceChainConsistency();
    }

    /**
     * Builds a class schema for the given class name.
     *
     * @param string $className
     * @return ClassSchema
     */
    protected function buildClassSchema($className)
    {
        $classSchema = new ClassSchema($className);
        $this->addPropertiesToClassSchema($classSchema);

        if ($this->isClassAnnotatedWith($className, ORM\Embeddable::class)) {
            return $classSchema;
        }

        if ($this->isClassAnnotatedWith($className, Flow\ValueObject::class)) {
            $this->checkValueObjectRequirements($className);
            $classSchema->setModelType(ClassSchema::MODELTYPE_VALUEOBJECT);

            return $classSchema;
        }

        if ($this->isClassAnnotatedWith($className, Flow\Entity::class) || $this->isClassAnnotatedWith($className, ORM\Entity::class)) {
            $classSchema->setModelType(ClassSchema::MODELTYPE_ENTITY);
            $classSchema->setLazyLoadableObject($this->isClassAnnotatedWith($className, Flow\Lazy::class));
        }

        $possibleRepositoryClassName = str_replace('\\Model\\', '\\Repository\\', $className) . 'Repository';
        if ($this->isClassReflected($possibleRepositoryClassName) === true) {
            $classSchema->setRepositoryClassName($possibleRepositoryClassName);
        }

        return $classSchema;
    }

    /**
     * Adds properties of the class at hand to the class schema.
     *
     * Properties will be added if they have a var annotation && (!transient-annotation && !inject-annotation)
     *
     * Invalid annotations will cause an exception to be thrown.
     *
     * @param ClassSchema $classSchema
     * @return void
     * @throws Exception\InvalidPropertyTypeException
     */
    protected function addPropertiesToClassSchema(ClassSchema $classSchema)
    {
        $className = $classSchema->getClassName();
        $skipArtificialIdentity = false;

        /* @var $valueObjectAnnotation Flow\ValueObject */
        $valueObjectAnnotation = $this->getClassAnnotation($className, Flow\ValueObject::class);
        if ($valueObjectAnnotation !== null && $valueObjectAnnotation->embedded === true) {
            $skipArtificialIdentity = true;
        } elseif ($this->isClassAnnotatedWith($className, ORM\Embeddable::class)) {
            $skipArtificialIdentity = true;
        }

        foreach ($this->getClassPropertyNames($className) as $propertyName) {
            $skipArtificialIdentity = $this->evaluateClassPropertyAnnotationsForSchema($classSchema, $propertyName) ? true : $skipArtificialIdentity;
        }

        if ($skipArtificialIdentity !== true) {
            $classSchema->addProperty('Persistence_Object_Identifier', 'string');
        }
    }

    /**
     * @param ClassSchema $classSchema
     * @param string $propertyName
     * @return boolean
     * @throws InvalidPropertyTypeException
     * @throws \InvalidArgumentException
     */
    protected function evaluateClassPropertyAnnotationsForSchema(ClassSchema $classSchema, $propertyName)
    {
        $skipArtificialIdentity = false;

        $className = $classSchema->getClassName();
        if ($this->isPropertyAnnotatedWith($className, $propertyName, Flow\Transient::class)) {
            return false;
        }

        if ($this->isPropertyAnnotatedWith($className, $propertyName, Flow\Inject::class)) {
            return false;
        }

        if ($this->isPropertyAnnotatedWith($className, $propertyName, Flow\InjectConfiguration::class)) {
            return false;
        }

        if (!$this->isPropertyTaggedWith($className, $propertyName, 'var')) {
            return false;
        }

        $varTagValues = $this->getPropertyTagValues($className, $propertyName, 'var');
        if (count($varTagValues) > 1) {
            throw new InvalidPropertyTypeException('More than one @var annotation given for "' . $className . '::$' . $propertyName . '"', 1367334366);
        }

        $declaredType = strtok(trim(current($varTagValues), " \n\t"), " \n\t");
        try {
            TypeHandling::parseType($declaredType);
        } catch (InvalidTypeException $exception) {
            throw new \InvalidArgumentException(sprintf($exception->getMessage(), 'class "' . $className . '" for property "' . $propertyName . '"'), 1315564475);
        }

        if ($this->isPropertyAnnotatedWith($className, $propertyName, ORM\Id::class)) {
            $skipArtificialIdentity = true;
        }

        $classSchema->addProperty($propertyName, $declaredType, $this->isPropertyAnnotatedWith($className, $propertyName, Flow\Lazy::class), $this->isPropertyAnnotatedWith($className, $propertyName, Flow\Transient::class));

        if ($this->isPropertyAnnotatedWith($className, $propertyName, Flow\Identity::class)) {
            $classSchema->markAsIdentityProperty($propertyName);
        }

        return $skipArtificialIdentity;
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
    protected function completeRepositoryAssignments()
    {
        foreach ($this->getAllImplementationClassNamesForInterface(RepositoryInterface::class) as $repositoryClassName) {
            // need to be extra careful because this code could be called
            // during a cache:flush run with corrupted reflection cache
            if (!class_exists($repositoryClassName) || $this->isClassAbstract($repositoryClassName)) {
                continue;
            }

            if (!$this->isClassAnnotatedWith($repositoryClassName, Flow\Scope::class) || $this->getClassAnnotation($repositoryClassName, Flow\Scope::class)->value !== 'singleton') {
                throw new ClassSchemaConstraintViolationException('The repository "' . $repositoryClassName . '" must be of scope singleton, but it is not.', 1335790707);
            }
            if (defined($repositoryClassName . '::ENTITY_CLASSNAME') && isset($this->classSchemata[$repositoryClassName::ENTITY_CLASSNAME])) {
                $claimedObjectType = $repositoryClassName::ENTITY_CLASSNAME;
                $this->classSchemata[$claimedObjectType]->setRepositoryClassName($repositoryClassName);
            }
        }

        foreach ($this->classSchemata as $classSchema) {
            if ($classSchema instanceof ClassSchema && class_exists($classSchema->getClassName()) && $classSchema->isAggregateRoot()) {
                $this->makeChildClassesAggregateRoot($classSchema);
            }
        }
    }

    /**
     * Assigns the repository of any aggregate root to all it's
     * subclasses, unless they are aggregate root already.
     *
     * @param ClassSchema $classSchema
     * @return void
     */
    protected function makeChildClassesAggregateRoot(ClassSchema $classSchema)
    {
        foreach ($this->getAllSubClassNamesForClass($classSchema->getClassName()) as $childClassName) {
            if (!isset($this->classSchemata[$childClassName]) || $this->classSchemata[$childClassName]->isAggregateRoot()) {
                continue;
            }

            $this->classSchemata[$childClassName]->setRepositoryClassName($classSchema->getRepositoryClassName());
            $this->makeChildClassesAggregateRoot($this->classSchemata[$childClassName]);
        }
    }

    /**
     * Checks whether all aggregate roots having superclasses
     * have a repository assigned up to the tip of their hierarchy.
     *
     * @return void
     * @throws Exception
     */
    protected function ensureAggregateRootInheritanceChainConsistency()
    {
        foreach ($this->classSchemata as $className => $classSchema) {
            if (!class_exists($className) || ($classSchema instanceof ClassSchema && $classSchema->isAggregateRoot() === false)) {
                continue;
            }

            foreach (class_parents($className) as $parentClassName) {
                if (!isset($this->classSchemata[$parentClassName])) {
                    continue;
                }
                if ($this->isClassAbstract($parentClassName) === false && $this->classSchemata[$parentClassName]->isAggregateRoot() === false) {
                    throw new Exception(sprintf('In a class hierarchy of entities either all or no classes must be an aggregate root, "%1$s" is one but the parent class "%2$s" is not. You probably want to add a repository for "%2$s" or remove the Entity annotation.', $className, $parentClassName), 1316009511);
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
     * @throws InvalidValueObjectException
     */
    protected function checkValueObjectRequirements($className)
    {
        $methods = get_class_methods($className);
        if (in_array('__construct', $methods, true) === false) {
            throw new InvalidValueObjectException('A value object must have a constructor, "' . $className . '" does not have one.', 1268740874);
        }

        $setterMethods = array_filter($methods, function ($method) {
            return strpos($method, 'set') === 0;
        });

        if ($setterMethods !== []) {
            throw new InvalidValueObjectException('A value object must not have setters, "' . $className . '" does.', 1268740878);
        }
    }

    /**
     * Converts the internal, optimized data structure of parameter information into
     * a human-friendly array with speaking indexes.
     *
     * @param array $parametersInformation Raw, internal parameter information
     * @return array Developer friendly version
     */
    protected function convertParameterDataToArray(array $parametersInformation)
    {
        $parameters = [];
        foreach ($parametersInformation as $parameterName => $parameterData) {
            $parameters[$parameterName] = [
                'position' => $parameterData[self::DATA_PARAMETER_POSITION],
                'optional' => isset($parameterData[self::DATA_PARAMETER_OPTIONAL]),
                'type' => $parameterData[self::DATA_PARAMETER_TYPE],
                'class' => isset($parameterData[self::DATA_PARAMETER_CLASS]) ? $parameterData[self::DATA_PARAMETER_CLASS] : null,
                'array' => isset($parameterData[self::DATA_PARAMETER_ARRAY]),
                'byReference' => isset($parameterData[self::DATA_PARAMETER_BY_REFERENCE]),
                'allowsNull' => isset($parameterData[self::DATA_PARAMETER_ALLOWS_NULL]),
                'defaultValue' => isset($parameterData[self::DATA_PARAMETER_DEFAULT_VALUE]) ? $parameterData[self::DATA_PARAMETER_DEFAULT_VALUE] : null,
                'scalarDeclaration' => isset($parameterData[self::DATA_PARAMETER_SCALAR_DECLARATION])
            ];
        }

        return $parameters;
    }

    /**
     * Converts the given parameter reflection into an information array
     *
     * @param ParameterReflection $parameter The parameter to reflect
     * @param MethodReflection $method The parameter's method
     * @return array Parameter information array
     */
    protected function convertParameterReflectionToArray(ParameterReflection $parameter, MethodReflection $method = null)
    {
        $parameterInformation = [
            self::DATA_PARAMETER_POSITION => $parameter->getPosition()
        ];
        if ($parameter->isPassedByReference()) {
            $parameterInformation[self::DATA_PARAMETER_BY_REFERENCE] = true;
        }
        if ($parameter->isArray()) {
            $parameterInformation[self::DATA_PARAMETER_ARRAY] = true;
        }
        if ($parameter->isOptional()) {
            $parameterInformation[self::DATA_PARAMETER_OPTIONAL] = true;
        }
        if ($parameter->allowsNull()) {
            $parameterInformation[self::DATA_PARAMETER_ALLOWS_NULL] = true;
        }

        $parameterClass = $parameter->getClass();
        if ($parameterClass !== null) {
            $parameterInformation[self::DATA_PARAMETER_CLASS] = $parameterClass->getName();
        }
        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            $parameterInformation[self::DATA_PARAMETER_DEFAULT_VALUE] = $parameter->getDefaultValue();
        }
        if ($method !== null) {
            $paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : [];
            if (isset($paramAnnotations[$parameter->getPosition()])) {
                $explodedParameters = explode(' ', $paramAnnotations[$parameter->getPosition()]);
                if (count($explodedParameters) >= 2) {
                    $parameterType = $this->expandType($method->getDeclaringClass(), $explodedParameters[0]);
                    $parameterInformation[self::DATA_PARAMETER_TYPE] = $this->cleanClassName($parameterType);
                }
            }
            if (!$parameter->isArray()) {
                $builtinType = $parameter->getBuiltinType();
                if ($builtinType !== null) {
                    $parameterInformation[self::DATA_PARAMETER_TYPE] = $builtinType;
                    $parameterInformation[self::DATA_PARAMETER_SCALAR_DECLARATION] = true;
                }
            }
        }
        if (!isset($parameterInformation[self::DATA_PARAMETER_TYPE]) && $parameterClass !== null) {
            $parameterInformation[self::DATA_PARAMETER_TYPE] = $this->cleanClassName($parameterClass->getName());
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
    protected function forgetChangedClasses()
    {
        $frozenNamespaces = [];
        /** @var $package Package */
        foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
            if ($this->packageManager->isPackageFrozen($packageKey)) {
                $frozenNamespaces = array_merge($frozenNamespaces, $package->getNamespaces());
            }
        }
        $frozenNamespaces = array_unique($frozenNamespaces);

        $classNames = array_keys($this->classReflectionData);
        foreach ($frozenNamespaces as $namespace) {
            $namespace .= '\\';
            foreach ($classNames as $index => $className) {
                if (strpos($className, $namespace) === 0) {
                    unset($classNames[$index]);
                }
            }
        }

        foreach ($classNames as $className) {
            if (!$this->statusCache->has($this->produceCacheIdentifierFromClassName($className))) {
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
    protected function forgetClass($className)
    {
        $this->systemLogger->log('Forget class ' . $className, LOG_DEBUG);
        if (isset($this->classesCurrentlyBeingForgotten[$className])) {
            $this->systemLogger->log('Detected recursion while forgetting class ' . $className, LOG_WARNING);

            return;
        }
        $this->classesCurrentlyBeingForgotten[$className] = true;

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
    protected function loadClassReflectionCompiletimeCache()
    {
        $data = $this->reflectionDataCompiletimeCache->get('ReflectionData');

        if ($data !== false) {
            foreach ($data as $propertyName => $propertyValue) {
                $this->$propertyName = $propertyValue;
            }

            return true;
        }

        if (!$this->context->isDevelopment()) {
            return false;
        }

        $useIgBinary = extension_loaded('igbinary');
        foreach ($this->packageManager->getActivePackages() as $packageKey => $package) {
            if (!$this->packageManager->isPackageFrozen($packageKey)) {
                continue;
            }

            $pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
            if (!file_exists($pathAndFilename)) {
                continue;
            }

            $data = ($useIgBinary ? igbinary_unserialize(file_get_contents($pathAndFilename)) : unserialize(file_get_contents($pathAndFilename)));
            foreach ($data as $propertyName => $propertyValue) {
                $this->$propertyName = Arrays::arrayMergeRecursiveOverrule($this->$propertyName, $propertyValue);
            }
        }

        return false;
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
    protected function loadOrReflectClassIfNecessary($className)
    {
        if (!isset($this->classReflectionData[$className]) || is_array($this->classReflectionData[$className])) {
            return;
        }

        if ($this->loadFromClassSchemaRuntimeCache === true) {
            $this->classReflectionData[$className] = $this->reflectionDataRuntimeCache->get($this->produceCacheIdentifierFromClassName($className));

            return;
        }

        $this->reflectClass($className);
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
    public function freezePackageReflection($packageKey)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        if (empty($this->availableClassNames)) {
            $this->availableClassNames = $this->reflectionDataRuntimeCache->get('__availableClassNames');
        }

        $reflectionData = [
            'classReflectionData' => $this->classReflectionData,
            'classSchemata' => $this->classSchemata,
            'annotatedClasses' => $this->annotatedClasses,
            'classesByMethodAnnotations' => $this->classesByMethodAnnotations
        ];

        $reflectionData['classReflectionData'] = $this->filterArrayByClassesInPackageNamespace($reflectionData['classReflectionData'], $packageKey);
        $reflectionData['classSchemata'] = $this->filterArrayByClassesInPackageNamespace($reflectionData['classSchemata'], $packageKey);
        $reflectionData['annotatedClasses'] = $this->filterArrayByClassesInPackageNamespace($reflectionData['annotatedClasses'], $packageKey);

        $reflectionData['classesByMethodAnnotations'] = isset($reflectionData['classesByMethodAnnotations']) ? $reflectionData['classesByMethodAnnotations'] : [];
        $methodAnnotationsFilters = function ($className) use ($packageKey) {
            return (isset($this->availableClassNames[$packageKey]) && in_array($className, $this->availableClassNames[$packageKey]));
        };

        foreach ($reflectionData['classesByMethodAnnotations'] as $annotationClassName => $classNames) {
            $reflectionData['classesByMethodAnnotations'][$annotationClassName] = array_filter($classNames, $methodAnnotationsFilters);
        }

        $precompiledReflectionStoragePath = $this->getPrecompiledReflectionStoragePath();
        if (!is_dir($precompiledReflectionStoragePath)) {
            Files::createDirectoryRecursively($precompiledReflectionStoragePath);
        }
        $pathAndFilename = $precompiledReflectionStoragePath . $packageKey . '.dat';
        file_put_contents($pathAndFilename, extension_loaded('igbinary') ? igbinary_serialize($reflectionData) : serialize($reflectionData));
    }

    /**
     * Filter an array of entries were keys are class names by being in the given package namespace.
     *
     * @param array $array
     * @param string $packageKey
     * @return array
     */
    protected function filterArrayByClassesInPackageNamespace(array $array, $packageKey)
    {
        return array_filter($array, function ($className) use ($packageKey) {
            return (isset($this->availableClassNames[$packageKey]) && in_array($className, $this->availableClassNames[$packageKey]));
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Removes the precompiled reflection data of a frozen package
     *
     * This method is used by the package manager.
     *
     * @param string $packageKey The package to remove the data from
     * @return void
     */
    public function unfreezePackageReflection($packageKey)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
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
     * @throws Exception if no cache has been injected
     */
    public function saveToCache()
    {
        if ($this->hasFrozenCacheInProduction()) {
            return;
        }
        if (!$this->initialized) {
            $this->initialize();
        }
        if ($this->loadFromClassSchemaRuntimeCache === true) {
            return;
        }

        if (!($this->reflectionDataCompiletimeCache instanceof FrontendInterface)) {
            throw new Exception('A cache must be injected before initializing the Reflection Service.', 1232044697);
        }

        if (!empty($this->availableClassNames)) {
            $this->reflectionDataRuntimeCache->set('__availableClassNames', $this->availableClassNames);
        }

        if ($this->updatedReflectionData !== []) {
            $this->updateReflectionData();
        }

        if ($this->context->isProduction()) {
            $this->saveProductionData();
            return;
        }

        $this->saveDevelopmentData();
    }

    /**
     * Save reflection data to cache in Development context.
     */
    protected function saveDevelopmentData()
    {
        foreach (array_keys($this->packageManager->getFrozenPackages()) as $packageKey) {
            $pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
            if (!file_exists($pathAndFilename)) {
                $this->log(sprintf('Rebuilding precompiled reflection data for frozen package %s.', $packageKey), LOG_INFO);
                $this->freezePackageReflection($packageKey);
            }
        }
    }

    /**
     * Save reflection data to cache in Production context.
     */
    protected function saveProductionData()
    {
        $this->reflectionDataRuntimeCache->flush();

        $classNames = [];
        foreach ($this->classReflectionData as $className => $reflectionData) {
            $classNames[$className] = true;
            $cacheIdentifier = $this->produceCacheIdentifierFromClassName($className);
            $this->reflectionDataRuntimeCache->set($cacheIdentifier, $reflectionData);
            if (isset($this->classSchemata[$className])) {
                $this->classSchemataRuntimeCache->set($cacheIdentifier, $this->classSchemata[$className]);
            }
        }
        $this->reflectionDataRuntimeCache->set('__classNames', $classNames);
        $this->reflectionDataRuntimeCache->set('__annotatedClasses', $this->annotatedClasses);

        $this->reflectionDataRuntimeCache->getBackend()->freeze();
        $this->classSchemataRuntimeCache->getBackend()->freeze();

        $this->log(sprintf('Built and froze reflection runtime caches (%s classes).', count($this->classReflectionData)), LOG_INFO);
    }

    /**
     * Set updated reflection data to caches.
     */
    protected function updateReflectionData()
    {
        $this->log(sprintf('Found %s classes whose reflection data was not cached previously.', count($this->updatedReflectionData)), LOG_DEBUG);

        foreach (array_keys($this->updatedReflectionData) as $className) {
            $this->statusCache->set($this->produceCacheIdentifierFromClassName($className), '');
        }

        $data = [];
        $propertyNames = [
            'classReflectionData',
            'classSchemata',
            'annotatedClasses',
            'classesByMethodAnnotations'
        ];

        foreach ($propertyNames as $propertyName) {
            $data[$propertyName] = $this->$propertyName;
        }

        $this->reflectionDataCompiletimeCache->set('ReflectionData', $data);
    }

    /**
     * Clean a given class name from possibly prefixed backslash
     *
     * @param string $className
     * @return string
     */
    protected function cleanClassName($className)
    {
        return ltrim($className, '\\');
    }

    /**
     * Transform backslashes to underscores to provide an valid cache identifier.
     *
     * @param string $className
     * @return string
     */
    protected function produceCacheIdentifierFromClassName($className)
    {
        return str_replace('\\', '_', $className);
    }

    /**
     * Writes the given message along with the additional information into the log.
     *
     * @param string $message The message to log
     * @param integer $severity An integer value, one of the LOG_* constants
     * @param mixed $additionalData A variable containing more information about the event to be logged
     * @return void
     */
    protected function log($message, $severity = LOG_INFO, $additionalData = null)
    {
        if (is_object($this->systemLogger)) {
            $this->systemLogger->log($message, $severity, $additionalData);
        }
    }

    /**
     * Determines the path to the precompiled reflection data.
     *
     * @return string
     */
    protected function getPrecompiledReflectionStoragePath()
    {
        return Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'PrecompiledReflectionData/']) . '/';
    }

    /**
     * @return boolean
     */
    protected function hasFrozenCacheInProduction()
    {
        return $this->environment->getContext()->isProduction() && $this->reflectionDataRuntimeCache->getBackend()->isFrozen();
    }
}
