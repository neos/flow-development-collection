<?php
declare(strict_types=1);
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
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy as DoctrineProxy;
use Neos\Cache\Exception;
use Neos\Cache\Frontend\StringFrontend;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\ObjectManagement\Proxy\ProxyInterface;
use Neos\Flow\Package;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Persistence\RepositoryInterface;
use Neos\Flow\Reflection\Exception\ClassLoadingForReflectionFailedException;
use Neos\Flow\Reflection\Exception\ClassSchemaConstraintViolationException;
use Neos\Flow\Reflection\Exception\InvalidClassException;
use Neos\Flow\Reflection\Exception\InvalidPropertyTypeException;
use Neos\Flow\Reflection\Exception\InvalidValueObjectException;
use Neos\Flow\Utility\Environment;
use Neos\Utility\Arrays;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Files;
use Neos\Utility\TypeHandling;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionException;

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
    protected const VISIBILITY_PRIVATE = 1;
    protected const VISIBILITY_PROTECTED = 2;
    protected const VISIBILITY_PUBLIC = 3;

    // Implementations of an interface
    protected const DATA_INTERFACE_IMPLEMENTATIONS = 1;

    // Implemented interfaces of a class
    protected const DATA_CLASS_INTERFACES = 2;

    // Subclasses of a class
    protected const DATA_CLASS_SUBCLASSES = 3;

    // Class tag values
    protected const DATA_CLASS_TAGS_VALUES = 4;

    // Class annotations
    protected const DATA_CLASS_ANNOTATIONS = 5;
    protected const DATA_CLASS_ABSTRACT = 6;
    protected const DATA_CLASS_FINAL = 7;
    protected const DATA_CLASS_METHODS = 8;
    protected const DATA_CLASS_PROPERTIES = 9;
    protected const DATA_METHOD_FINAL = 10;
    protected const DATA_METHOD_STATIC = 11;
    protected const DATA_METHOD_VISIBILITY = 12;
    protected const DATA_METHOD_PARAMETERS = 13;
    protected const DATA_METHOD_DECLARED_RETURN_TYPE = 25;
    protected const DATA_PROPERTY_TAGS_VALUES = 14;
    protected const DATA_PROPERTY_ANNOTATIONS = 15;
    protected const DATA_PROPERTY_VISIBILITY = 24;
    protected const DATA_PROPERTY_TYPE = 26;
    protected const DATA_PARAMETER_POSITION = 16;
    protected const DATA_PARAMETER_OPTIONAL = 17;
    protected const DATA_PARAMETER_TYPE = 18;
    protected const DATA_PARAMETER_ARRAY = 19;
    protected const DATA_PARAMETER_CLASS = 20;
    protected const DATA_PARAMETER_ALLOWS_NULL = 21;
    protected const DATA_PARAMETER_DEFAULT_VALUE = 22;
    protected const DATA_PARAMETER_BY_REFERENCE = 23;
    protected const DATA_PARAMETER_SCALAR_DECLARATION = 24;

    protected Reader $annotationReader;
    protected array $availableClassNames = [];
    protected StringFrontend $statusCache;
    protected VariableFrontend $reflectionDataCompiletimeCache;
    protected VariableFrontend $reflectionDataRuntimeCache;
    protected VariableFrontend $classSchemataRuntimeCache;
    protected ?LoggerInterface $logger = null;
    protected PackageManager $packageManager;
    protected Environment $environment;
    protected ApplicationContext $context;

    /**
     * The doctrine PHP parser which can parse "use" statements. Is initialized
     * lazily when it is first needed.
     * Note: Don't refer to this member directly but use getDoctrinePhpParser() to obtain an instance
     */
    protected ?PhpParser $doctrinePhpParser = null;

    /**
     * a cache which stores the use statements reflected for a particular class
     * (only relevant for un-expanded "var" and "param" annotations)
     */
    protected array $useStatementsForClassCache;

    /**
     * In Production context, with frozen caches, this flag will be true
     */
    protected bool $loadFromClassSchemaRuntimeCache = false;

    protected array $settings = [];

    /**
     * Array of annotation classnames and the names of classes which are annotated with them
     */
    protected array $annotatedClasses = [];

    /**
     * Array of method annotations and the classes and methods which are annotated with them
     */
    protected array $classesByMethodAnnotations = [];

    /**
     * Schemata of all classes which can be persisted
     *
     * @var array<ClassSchema>
     */
    protected array $classSchemata = [];

    /**
     * An array of class names which are currently being forgotten by forgetClass(). Acts as a safeguard against infinite loops.
     */
    protected array $classesCurrentlyBeingForgotten = [];

    /**
     * Array with reflection information indexed by class name
     */
    protected array $classReflectionData = [];

    /**
     * Array with updated reflection information (e.g. in Development context after classes have changed)
     */
    protected array $updatedReflectionData = [];

    protected bool $initialized = false;

    /**
     * A runtime cache for reflected method annotations to speed up repeating checks.
     */
    protected array $methodAnnotationsRuntimeCache = [];

    /**
     * Sets the status cache
     *
     * The cache must be set before initializing the Reflection Service
     */
    public function setStatusCache(StringFrontend $cache): void
    {
        $this->statusCache = $cache;
        $backend = $this->statusCache->getBackend();
        if (is_callable([$backend, 'initializeObject'])) {
            $backend->initializeObject();
        }
    }

    public function setReflectionDataCompiletimeCache(VariableFrontend $cache): void
    {
        $this->reflectionDataCompiletimeCache = $cache;
    }

    public function setReflectionDataRuntimeCache(VariableFrontend $cache): void
    {
        $this->reflectionDataRuntimeCache = $cache;
    }

    public function setClassSchemataRuntimeCache(VariableFrontend $cache): void
    {
        $this->classSchemataRuntimeCache = $cache;
    }

    public function injectSettings(array $settings): void
    {
        $this->settings = $settings['reflection'];
    }

    public function injectLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function injectPackageManager(PackageManager $packageManager): void
    {
        $this->packageManager = $packageManager;
    }

    public function injectEnvironment(Environment $environment): void
    {
        $this->environment = $environment;
    }

    protected function getDoctrinePhpParser(): ?PhpParser
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
     * @throws FilesException
     * @throws \Neos\Flow\Utility\Exception
     */
    protected function initialize(): void
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
        foreach ($this->settings['ignoredTags'] as $tagName => $ignoreFlag) {
            if ($ignoreFlag === true) {
                AnnotationReader::addGlobalIgnoredName($tagName);
            }
        }

        $this->initialized = true;
    }

    /**
     * Builds the reflection data cache during compile time.
     *
     * This method is called by the Compile Time Object Manager which also determines
     * the list of classes to consider for reflection.
     *
     * @throws ClassLoadingForReflectionFailedException
     * @throws ClassSchemaConstraintViolationException
     * @throws Exception
     * @throws FilesException
     * @throws InvalidClassException
     * @throws ReflectionException
     * @throws \Neos\Flow\Utility\Exception
     */
    public function buildReflectionData(array $availableClassNames): void
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
     * @api
     */
    public function isClassReflected(string $className): bool
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
     * @api
     */
    public function getAllClassNames(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return array_keys($this->classReflectionData);
    }

    /**
     * Searches for and returns the class name of the default implementation of the given
     * interface name. If no class implementing the interface was found or more than one
     * implementation was found in the package defining the interface, false is returned.
     *
     * @api
     */
    public function getDefaultImplementationClassNameForInterface(string $interfaceName): string|bool
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
        if (!isset($this->classReflectionData[ProxyInterface::class][self::DATA_INTERFACE_IMPLEMENTATIONS]) || count($classNamesFound) !== 2) {
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
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @api
     */
    public function getAllImplementationClassNamesForInterface(string $interfaceName): array
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
     * @psalm-param class-string $className
     * @psalm-return array<class-string>
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @api
     */
    public function getAllSubClassNamesForClass(string $className): array
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
     * Searches for and returns all names of classes which are tagged by the specified
     * annotation. If no classes were found, an empty array is returned.
     */
    public function getClassNamesByAnnotation(string $annotationClassName): array
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
     * @api
     */
    public function isClassAnnotatedWith(string $className, string $annotationClassName): bool
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
     * @return array<object>
     *
     * @param null|string $annotationClassName
     */
    public function getClassAnnotations(string $className, string|null $annotationClassName = null): array
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
     * get the first instance of them.
     */
    public function getClassAnnotation(string $className, string $annotationClassName): object|bool|null
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getClassAnnotations($className, $annotationClassName);

        return $annotations === [] ? null : reset($annotations);
    }

    /**
     * Tells if the specified class implements the given interface
     *
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @api
     */
    public function isClassImplementationOf(string $className, string $interfaceName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);

        $interfaceName = $this->cleanClassName($interfaceName);
        $this->loadOrReflectClassIfNecessary($interfaceName);

        return (isset($this->classReflectionData[$interfaceName][self::DATA_INTERFACE_IMPLEMENTATIONS][$className]));
    }

    /**
     * Tells if the specified class is abstract or not
     *
     * @api
     */
    public function isClassAbstract(string $className): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_ABSTRACT]);
    }

    /**
     * Tells if the specified class is final or not
     *
     * @param string $className Name of the class to analyze
     * @return bool true if the class is final, otherwise false
     * @api
     */
    public function isClassFinal(string $className): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_FINAL]);
    }

    /**
     * Tells if the class is unconfigurable or not
     *
     * @api
     */
    public function isClassUnconfigurable(string $className): bool
    {
        $className = $this->cleanClassName($className);

        return $this->classReflectionData[$className] === [];
    }

    /**
     * Returns all class names of classes containing at least one method annotated
     * with the given annotation class
     *
     * @api
     */
    public function getClassesContainingMethodsAnnotatedWith(string $annotationClassName): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        return isset($this->classesByMethodAnnotations[$annotationClassName]) ? array_keys($this->classesByMethodAnnotations[$annotationClassName]) : [];
    }

    /**
     * Returns all names of methods of the given class that are annotated with the given annotation class
     *
     * @api
     */
    public function getMethodsAnnotatedWith(string $className, string $annotationClassName): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->classesByMethodAnnotations[$annotationClassName][$className] ?? [];
    }

    /**
     * Tells if the specified method is final or not
     *
     * @api
     */
    public function isMethodFinal(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_FINAL]);
    }

    /**
     * Tells if the specified method is declared as static or not
     *
     * @api
     */
    public function isMethodStatic(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_STATIC]);
    }

    /**
     * Tells if the specified method is public
     *
     * @api
     */
    public function isMethodPublic(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PUBLIC);
    }

    /**
     * Tells if the specified method is protected
     *
     * @api
     */
    public function isMethodProtected(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PROTECTED);
    }

    /**
     * Tells if the specified method is private
     *
     * @api
     */
    public function isMethodPrivate(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return (isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY]) && $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_VISIBILITY] === self::VISIBILITY_PRIVATE);
    }

    /**
     * Tells if the specified method is tagged with the given tag
     *
     * @throws ReflectionException
     * @api
     */
    public function isMethodTaggedWith(string $className, string $methodName, string $tag): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $method = new MethodReflection($this->cleanClassName($className), $methodName);
        $tagsValues = $method->getTagsValues();

        return isset($tagsValues[$tag]);
    }

    /**
     * Tells if a specific PHP attribute is to be ignored for reflection
     */
    public function isAttributeIgnored(string $attributeName): bool
    {
        return $attributeName === 'ReturnTypeWillChange' && !class_exists($attributeName);
    }

    /**
     * Tells if the specified method has the given annotation
     *
     * @throws ReflectionException
     * @api
     */
    public function isMethodAnnotatedWith(string $className, string $methodName, string $annotationClassName): bool
    {
        return $this->getMethodAnnotations($className, $methodName, $annotationClassName) !== [];
    }

    /**
     * Returns the specified method annotations or an empty array
     *
     * @param string $className
     * @param string $methodName
     * @param null|string $annotationClassName
     * @return array<object>
     *
     * @throws FilesException
     * @throws ReflectionException
     * @throws \Neos\Flow\Utility\Exception
     * @api
     *
     */
    public function getMethodAnnotations(string $className, string $methodName, string|null $annotationClassName = null): array
    {
        $className = $this->cleanClassName($className);
        $annotationClassName = $annotationClassName === null ? null : $this->cleanClassName($annotationClassName);

        $methodAnnotations = $this->methodAnnotationsRuntimeCache[$className][$methodName] ?? null;
        $annotations = [];
        if ($methodAnnotations === null) {
            if (!$this->initialized) {
                $this->initialize();
            }

            $method = new MethodReflection($className, $methodName);
            $methodAnnotations = $this->annotationReader->getMethodAnnotations($method);
            if (PHP_MAJOR_VERSION >= 8) {
                foreach ($method->getAttributes() as $attribute) {
                    if ($this->isAttributeIgnored($attribute->getName())) {
                        continue;
                    }
                    $methodAnnotations[] = $attribute->newInstance();
                }
            }
            $this->methodAnnotationsRuntimeCache[$className][$methodName] = $methodAnnotations;
        }
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
     * get the first instance of them.
     *
     * @throws ReflectionException
     */
    public function getMethodAnnotation(string $className, string $methodName, string $annotationClassName): ?object
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getMethodAnnotations($className, $methodName, $annotationClassName);

        return $annotations === [] ? null : reset($annotations);
    }

    /**
     * Returns the names of all properties of the specified class
     *
     * @api
     */
    public function getClassPropertyNames(string $className): array
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) ? array_keys($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES]) : [];
    }

    /**
     * Wrapper for method_exists() which tells if the given method exists.
     *
     * @api
     */
    public function hasMethod(string $className, string $methodName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName]);
    }

    /**
     * Returns all tags and their values the specified method is tagged with
     *
     * @throws ReflectionException
     * @api
     */
    public function getMethodTagsValues(string $className, string $methodName): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $className = $this->cleanClassName($className);

        return (new MethodReflection($className, $methodName))->getTagsValues();
    }

    /**
     * Returns an array of parameters of the given method. Each entry contains
     * additional information about the parameter position, type hint etc.
     *
     * @return array An array of parameter names and additional information or an empty array of no parameters were found
     * @api
     */
    public function getMethodParameters(string $className, string $methodName): array
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
     * @return ?string The declared return type of the method or null if none was declared
     *
     * @param class-string $className
     */
    public function getMethodDeclaredReturnType(string $className, string $methodName): ?string
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_DECLARED_RETURN_TYPE] ?? null;
    }

    /**
     * Searches for and returns all names of class properties which are tagged by the specified tag.
     * If no properties were found, an empty array is returned.
     *
     * @api
     *
     * @psalm-param 'id'|'var' $tag
     */
    public function getPropertyNamesByTag(string $className, string $tag): array
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
     * @api
     */
    public function getPropertyTagsValues(string $className, string $propertyName): mixed
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
            return [];
        }

        return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES] ?? [];
    }

    /**
     * Returns the values of the specified class property tag
     *
     * @api
     *
     * @psalm-param 'var' $tag
     */
    public function getPropertyTagValues(string $className, string $propertyName, string $tag)
    {
        $className = $this->prepareClassReflectionForUsage($className);
        if (!isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName])) {
            return [];
        }

        return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag] ?? [];
    }

    /**
     * Returns the property type
     */
    public function getPropertyType(string $className, string $propertyName): ?string
    {
        return $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TYPE] ?? null;
    }

    /**
     * Tells if the specified property is private
     *
     * @api
     */
    public function isPropertyPrivate(string $className, string $propertyName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return (isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY])
            && $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY] === self::VISIBILITY_PRIVATE);
    }

    /**
     * Tells if the specified class property is tagged with the given tag
     *
     * @api
     */
    public function isPropertyTaggedWith(string $className, string $propertyName, string $tag): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tag]);
    }

    /**
     * Tells if the specified property has the given annotation
     *
     * @api
     */
    public function isPropertyAnnotatedWith(string $className, string $propertyName, string $annotationClassName): bool
    {
        $className = $this->prepareClassReflectionForUsage($className);
        return isset($this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$annotationClassName]);
    }

    /**
     * Searches for and returns all names of class properties which are marked by the
     * specified annotation. If no properties were found, an empty array is returned.
     *
     * @api
     */
    public function getPropertyNamesByAnnotation(string $className, string $annotationClassName): array
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
     * @return array<object>
     * @api
     */
    public function getPropertyAnnotations(string $className, string $propertyName, string $annotationClassName = null): array
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
     * get the first instance of them.
     */
    public function getPropertyAnnotation(string $className, string $propertyName, string $annotationClassName): ?object
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        $annotations = $this->getPropertyAnnotations($className, $propertyName, $annotationClassName);
        return $annotations === [] ? null : reset($annotations);
    }

    /**
     * Returns the class schema for the given class
     */
    public function getClassSchema(string|object $classNameOrObject): ?ClassSchema
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
     */
    protected function prepareClassReflectionForUsage(string $className): string
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
     * @throws ClassLoadingForReflectionFailedException
     * @throws ClassSchemaConstraintViolationException
     * @throws Exception
     * @throws InvalidClassException
     * @throws ReflectionException
     */
    protected function reflectEmergedClasses(): void
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

        $this->log('Reflected class names did not match class names to reflect', LogLevel::DEBUG);
        $count = 0;

        $classNameFilterFunction = function ($className) use (&$count): bool {
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
            $this->log(sprintf('Reflected %s emerged classes.', $count), LogLevel::INFO, LogEnvironment::fromMethodName(__METHOD__));
        }
    }

    /**
     * Check if a specific annotation tag is configured to be ignored.
     */
    protected function isTagIgnored(string $tagName): bool
    {
        if (isset($this->settings['ignoredTags'][$tagName]) && $this->settings['ignoredTags'][$tagName] === true) {
            return true;
        }
        // Make this setting backwards compatible with old array schema (deprecated since 3.0)
        if (in_array($tagName, $this->settings['ignoredTags'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Reflects the given class and stores the results in this service's properties.
     *
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws ReflectionException
     */
    protected function reflectClass(string $className): void
    {
        $this->log(sprintf('Reflecting class %s', $className), LogLevel::DEBUG);

        $className = $this->cleanClassName($className);
        if (str_starts_with($className, 'Neos\Flow\Persistence\Doctrine\Proxies') && in_array(DoctrineProxy::class, class_implements($className), true)) {
            throw new InvalidClassException('The class with name "' . $className . '" is a Doctrine proxy. It is not supported to reflect doctrine proxy classes.', 1314944681);
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

        foreach ($this->getParentClasses($class) as $parentClass) {
            $this->addParentClass($className, $parentClass);
        }

        foreach ($class->getInterfaces() as $interface) {
            $this->addImplementedInterface($className, $interface);
        }

        foreach ($this->annotationReader->getClassAnnotations($class) as $annotation) {
            $annotationClassName = get_class($annotation);
            $this->annotatedClasses[$annotationClassName][$className] = true;
            $this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS][] = $annotation;
        }
        if (PHP_MAJOR_VERSION >= 8) {
            foreach ($class->getAttributes() as $attribute) {
                $annotationClassName = $attribute->getName();
                if ($this->isAttributeIgnored($annotationClassName)) {
                    continue;
                }
                $this->annotatedClasses[$annotationClassName][$className] = true;
                $this->classReflectionData[$className][self::DATA_CLASS_ANNOTATIONS][] = $attribute->newInstance();
            }
        }

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
     * @return int visibility
     */
    public function reflectClassProperty(string $className, PropertyReflection $property): int
    {
        $propertyName = $property->getName();
        $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName] = [];
        if ($property->hasType()) {
            $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TYPE] = trim((string)$property->getType(), '?');
        }

        $visibility = $property->isPublic() ? self::VISIBILITY_PUBLIC : ($property->isProtected() ? self::VISIBILITY_PROTECTED : self::VISIBILITY_PRIVATE);
        $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_VISIBILITY] = $visibility;

        foreach ($property->getTagsValues() as $tagName => $tagValues) {
            $tagValues = $this->reflectPropertyTag($className, $property, $tagName, $tagValues);
            if ($tagValues === null) {
                continue;
            }
            $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_TAGS_VALUES][$tagName] = $tagValues;
        }

        foreach ($this->annotationReader->getPropertyAnnotations($property) as $annotation) {
            $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][get_class($annotation)][] = $annotation;
        }
        if (PHP_MAJOR_VERSION >= 8) {
            foreach ($property->getAttributes() as $attribute) {
                if ($this->isAttributeIgnored($attribute->getName())) {
                    continue;
                }
                $this->classReflectionData[$className][self::DATA_CLASS_PROPERTIES][$propertyName][self::DATA_PROPERTY_ANNOTATIONS][$attribute->getName()][] = $attribute->newInstance();
            }
        }

        return $visibility;
    }

    protected function reflectPropertyTag(string $className, PropertyReflection $property, string $tagName, array $tagValues): ?array
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
     * @throws InvalidClassException
     * @throws ClassLoadingForReflectionFailedException
     * @throws ReflectionException
     */
    protected function addParentClass(string $className, ClassReflection $parentClass): void
    {
        $parentClassName = $parentClass->getName();
        if (!isset($this->classReflectionData[$parentClassName])) {
            $this->reflectClass($parentClassName);
        }
        $this->classReflectionData[$parentClassName][self::DATA_CLASS_SUBCLASSES][$className] = true;
    }

    /**
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws ReflectionException
     */
    protected function addImplementedInterface(string $className, ClassReflection $interface): void
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
     * @throws ReflectionException
     */
    protected function reflectClassMethod(string $className, MethodReflection $method): void
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

        $returnType= $method->getDeclaredReturnType();
        $applyLeadingSlashIfNeeded = function (string $type): string {
            if (!in_array($type, ['self', 'parent', 'static', 'null', 'callable', 'void', 'never', 'iterable', 'object', 'resource', 'mixed'])
                && !TypeHandling::isSimpleType($type)
            ) {
                return '\\' . $type;
            }
            return $type;
        };
        if ($returnType !== null) {
            if (TypeHandling::isUnionType($returnType)) {
                $returnType = implode('|', array_map($applyLeadingSlashIfNeeded, explode('|', $returnType)));
            } elseif (TypeHandling::isIntersectionType($returnType)) {
                $returnType = implode('&', array_map($applyLeadingSlashIfNeeded, explode('&', $returnType)));
            } else {
                $returnType = $applyLeadingSlashIfNeeded($returnType);
                if ($method->isDeclaredReturnTypeNullable()) {
                    $returnType = '?' . $returnType;
                }
            }
        }
        $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_DECLARED_RETURN_TYPE] = $returnType;

        foreach ($method->getParameters() as $parameter) {
            $this->reflectClassMethodParameter($className, $method, $parameter);
        }
    }

    /**
     * @param string $className
     * @param MethodReflection $method
     * @param ParameterReflection $parameter
     * @return void
     */
    protected function reflectClassMethodParameter($className, MethodReflection $method, ParameterReflection $parameter): void
    {
        $methodName = $method->getName();
        $paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : [];

        $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()] = $this->convertParameterReflectionToArray($parameter, $method);
        if (!isset($this->settings['logIncorrectDocCommentHints']) || $this->settings['logIncorrectDocCommentHints'] !== true) {
            return;
        }

        if (!isset($paramAnnotations[$parameter->getPosition()])) {
            $this->log('  Missing @param for "' . $method->getName() . '::$' . $parameter->getName(), LogLevel::DEBUG);

            return;
        }

        $parameterAnnotation = explode(' ', $paramAnnotations[$parameter->getPosition()], 3);
        if (count($parameterAnnotation) < 2) {
            $this->log('  Wrong @param use for "' . $method->getName() . '::' . $parameter->getName() . '": "' . implode(' ', $parameterAnnotation) . '"', LogLevel::DEBUG);
        }

        if (
            isset($this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE]) &&
            $this->classReflectionData[$className][self::DATA_CLASS_METHODS][$methodName][self::DATA_METHOD_PARAMETERS][$parameter->getName()][self::DATA_PARAMETER_TYPE] !== $this->cleanClassName($parameterAnnotation[0])
        ) {
            $this->log('  Wrong type in @param for "' . $method->getName() . '::' . $parameter->getName() . '": "' . $parameterAnnotation[0] . '"', LogLevel::DEBUG);
        }

        if ($parameter->getName() !== ltrim($parameterAnnotation[1], '$&')) {
            $this->log('  Wrong name in @param for "' . $method->getName() . '::$' . $parameter->getName() . '": "' . $parameterAnnotation[1] . '"', LogLevel::DEBUG);
        }
    }

    /**
     * Expand shortened class names in "var" and "param" annotations, taking use statements into account.
     */
    protected function expandType(ClassReflection $class, string $type): string
    {
        $typeWithoutNull = TypeHandling::stripNullableType($type);
        $isNullable = $typeWithoutNull !== $type;
        // expand "SomeType<SomeElementType>" to "\SomeTypeNamespace\SomeType<\ElementTypeNamespace\ElementType>"
        if (str_contains($type, '<')) {
            $typeParts = explode('<', $typeWithoutNull);
            $type = $typeParts[0];
            $elementType = rtrim($typeParts[1], '>');

            return $this->expandType($class, $type) . '<' . $this->expandType($class, $elementType) . '>' . ($isNullable ? '|null' : '');
        }

        // expand SomeElementType[]" to "array<\ElementTypeNamespace\SomeElementType>"
        if (substr_compare($typeWithoutNull, '[]', -2, 2) === 0) {
            $elementType = substr($typeWithoutNull, 0, -2);
            return 'array<' . $this->expandType($class, $elementType) . '>' . ($isNullable ? '|null' : '');
        }

        // skip simple types and types with fully qualified namespaces
        if ($type === 'mixed' || $type[0] === '\\' || TypeHandling::isSimpleType($type)) {
            return TypeHandling::normalizeType($typeWithoutNull) . ($isNullable ? '|null' : '');
        }

        // we try to find the class relative to the current namespace...
        $possibleFullyQualifiedClassName = sprintf('%s\\%s', $class->getNamespaceName(), $typeWithoutNull);
        if (class_exists($possibleFullyQualifiedClassName) || interface_exists($possibleFullyQualifiedClassName)) {
            return $possibleFullyQualifiedClassName . ($isNullable ? '|null' : '');
        }

        // and then we try to find "use" statements for the class.
        $className = $class->getName();
        if (!isset($this->useStatementsForClassCache[$className])) {
            $this->useStatementsForClassCache[$className] = $this->getDoctrinePhpParser()->parseClass($class);
        }
        $useStatementsForClass = $this->useStatementsForClassCache[$className];

        // ... and try to expand them
        $typeParts = explode('\\', $typeWithoutNull, 2);
        $lowercasedFirstTypePart = strtolower($typeParts[0]);
        if (isset($useStatementsForClass[$lowercasedFirstTypePart])) {
            $typeParts[0] = $useStatementsForClass[$lowercasedFirstTypePart];

            return implode('\\', $typeParts) . ($isNullable ? '|null' : '');
        }

        return $type;
    }

    /**
     * Finds all parent classes of the given class
     *
     * @return array<ClassReflection>
     */
    protected function getParentClasses(ClassReflection $class, array $parentClasses = []): array
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
     * @throws ClassSchemaConstraintViolationException
     * @throws Exception
     * @throws InvalidPropertyTypeException
     * @throws InvalidValueObjectException
     */
    protected function buildClassSchemata(array $classNames): void
    {
        foreach ($classNames as $className) {
            $this->classSchemata[$className] = $this->buildClassSchema($className);
        }

        $this->completeRepositoryAssignments();
        $this->ensureAggregateRootInheritanceChainConsistency();
    }

    /**
     * @throws InvalidValueObjectException
     * @throws ClassSchemaConstraintViolationException
     */
    protected function buildClassSchema(string $className): ClassSchema
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
     * @throws InvalidPropertyTypeException
     * @throws ClassSchemaConstraintViolationException
     */
    protected function addPropertiesToClassSchema(ClassSchema $classSchema): void
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
     * @throws InvalidPropertyTypeException
     * @throws \InvalidArgumentException
     * @throws ClassSchemaConstraintViolationException
     */
    protected function evaluateClassPropertyAnnotationsForSchema(ClassSchema $classSchema, string $propertyName): bool
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
     * @throws ClassLoadingForReflectionFailedException
     * @throws ClassSchemaConstraintViolationException
     * @throws InvalidClassException
     */
    protected function completeRepositoryAssignments(): void
    {
        foreach ($this->getAllImplementationClassNamesForInterface(RepositoryInterface::class) as $repositoryClassName) {
            // need to be extra careful because this code could be called
            // during a cache:flush run with corrupted reflection cache
            if (!class_exists($repositoryClassName) || $this->isClassAbstract($repositoryClassName)) {
                continue;
            }

            $scopeAnnotation = $this->getClassAnnotation($repositoryClassName, Flow\Scope::class);
            if ($scopeAnnotation === null || $scopeAnnotation->value !== 'singleton') {
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
     * @throws ClassLoadingForReflectionFailedException
     * @throws ClassSchemaConstraintViolationException
     * @throws InvalidClassException
     */
    protected function makeChildClassesAggregateRoot(ClassSchema $classSchema): void
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
     * @throws Exception
     */
    protected function ensureAggregateRootInheritanceChainConsistency(): void
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
     * @throws InvalidValueObjectException
     */
    protected function checkValueObjectRequirements(string $className): void
    {
        $methods = get_class_methods($className);
        if (in_array('__construct', $methods, true) === false) {
            throw new InvalidValueObjectException('A value object must have a constructor, "' . $className . '" does not have one.', 1268740874);
        }

        $setterMethods = array_filter($methods, function ($method) {
            return str_starts_with($method, 'set');
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
    protected function convertParameterDataToArray(array $parametersInformation): array
    {
        $parameters = [];
        foreach ($parametersInformation as $parameterName => $parameterData) {
            $parameters[$parameterName] = [
                'position' => $parameterData[self::DATA_PARAMETER_POSITION],
                'optional' => isset($parameterData[self::DATA_PARAMETER_OPTIONAL]),
                'type' => $parameterData[self::DATA_PARAMETER_TYPE],
                'class' => $parameterData[self::DATA_PARAMETER_CLASS] ?? null,
                'array' => isset($parameterData[self::DATA_PARAMETER_ARRAY]),
                'byReference' => isset($parameterData[self::DATA_PARAMETER_BY_REFERENCE]),
                'allowsNull' => isset($parameterData[self::DATA_PARAMETER_ALLOWS_NULL]),
                'defaultValue' => $parameterData[self::DATA_PARAMETER_DEFAULT_VALUE] ?? null,
                'scalarDeclaration' => isset($parameterData[self::DATA_PARAMETER_SCALAR_DECLARATION])
            ];
        }

        return $parameters;
    }

    /**
     * Converts the given parameter reflection into an information array
     *
     * @return array Parameter information array
     */
    protected function convertParameterReflectionToArray(ParameterReflection $parameter, MethodReflection $method): array
    {
        $parameterInformation = [
            self::DATA_PARAMETER_POSITION => $parameter->getPosition()
        ];
        if ($parameter->isPassedByReference()) {
            $parameterInformation[self::DATA_PARAMETER_BY_REFERENCE] = true;
        }
        if ($parameter->isOptional()) {
            $parameterInformation[self::DATA_PARAMETER_OPTIONAL] = true;
        }
        if ($parameter->allowsNull()) {
            $parameterInformation[self::DATA_PARAMETER_ALLOWS_NULL] = true;
        }

        $parameterType = $parameter->getType();
        if ($parameterType !== null) {
            if ($parameterType instanceof \ReflectionUnionType) {
                // ReflectionUnionType as of PHP 8
                $parameterType = implode('|', array_map(
                    static function (\ReflectionNamedType $type) {
                        return $type->getName();
                    },
                    $parameterType->getTypes()
                ));
            } elseif ($parameterType instanceof \ReflectionIntersectionType) {
                // ReflectionIntersectionType as of PHP 8.1
                $parameterType = implode('&', array_map(
                    static function (\ReflectionNamedType $type) {
                        return $type->getName();
                    },
                    $parameterType->getTypes()
                ));
            } else {
                // ReflectionNamedType as of PHP 7.1
                $parameterType = $parameterType->getName();
            }
        }
        if ($parameterType !== null && !TypeHandling::isSimpleType($parameterType)) {
            // We use parameter type here to make class_alias usage work and return the hinted class name instead of the alias
            $parameterInformation[self::DATA_PARAMETER_CLASS] = $parameterType;
        } elseif ($parameterType === 'array') {
            $parameterInformation[self::DATA_PARAMETER_ARRAY] = true;
        } else {
            $builtinType = $parameter->getBuiltinType();
            if ($builtinType !== null) {
                $parameterInformation[self::DATA_PARAMETER_TYPE] = $builtinType;
                $parameterInformation[self::DATA_PARAMETER_SCALAR_DECLARATION] = true;
            }
        }
        if ($parameter->isOptional() && $parameter->isDefaultValueAvailable()) {
            $parameterInformation[self::DATA_PARAMETER_DEFAULT_VALUE] = $parameter->getDefaultValue();
        }
        $paramAnnotations = $method->isTaggedWith('param') ? $method->getTagValues('param') : [];
        if (isset($paramAnnotations[$parameter->getPosition()])) {
            $explodedParameters = explode(' ', $paramAnnotations[$parameter->getPosition()]);
            if (count($explodedParameters) >= 2) {
                $parameterType = $this->expandType($method->getDeclaringClass(), $explodedParameters[0]);
            }
        }
        if (!isset($parameterInformation[self::DATA_PARAMETER_TYPE]) && $parameterType !== null) {
            $parameterInformation[self::DATA_PARAMETER_TYPE] = $this->cleanClassName($parameterType);
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
    protected function forgetChangedClasses(): void
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
                if (str_starts_with($className, $namespace)) {
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
     * @param (int|string) $className
     *
     * @psalm-param array-key $className
     */
    protected function forgetClass($className): void
    {
        $this->log('Forget class ' . $className, LogLevel::DEBUG);
        if (isset($this->classesCurrentlyBeingForgotten[$className])) {
            $this->log('Detected recursion while forgetting class ' . $className, LogLevel::WARNING);
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
     * data could be loaded, false will be returned in order to signal that other
     * packages still need to be reflected.
     *
     * @return bool true if reflection data could be loaded, otherwise false
     * @throws FilesException
     * @throws \Neos\Flow\Utility\Exception
     */
    protected function loadClassReflectionCompiletimeCache(): bool
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
        foreach ($this->packageManager->getAvailablePackages() as $packageKey => $package) {
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
     * @throws ClassLoadingForReflectionFailedException
     * @throws InvalidClassException
     * @throws ReflectionException
     */
    protected function loadOrReflectClassIfNecessary(string $className): void
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
     * @param (int|string) $packageKey
     *
     * @psalm-param array-key $packageKey
     */
    public function freezePackageReflection($packageKey): void
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

        $methodAnnotationsFilters = function ($className) use ($packageKey): bool {
            return (isset($this->availableClassNames[$packageKey]) && in_array($className, $this->availableClassNames[$packageKey], true));
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
     * Filter an array of entries where keys are class names by being in the given package namespace.
     *
     * @param (int|string) $packageKey
     *
     * @psalm-param array-key $packageKey
     */
    protected function filterArrayByClassesInPackageNamespace(array $array, $packageKey): array
    {
        return array_filter($array, function ($className) use ($packageKey) {
            return (isset($this->availableClassNames[$packageKey]) && in_array($className, $this->availableClassNames[$packageKey], true));
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Removes the precompiled reflection data of a frozen package
     *
     * This method is used by the package manager.
     *
     * @throws FilesException
     * @throws \Neos\Flow\Utility\Exception
     */
    public function unfreezePackageReflection(string $packageKey): void
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
     * @throws Exception
     */
    public function saveToCache(): void
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
     *
     * @throws FilesException
     * @throws \Neos\Flow\Utility\Exception
     */
    protected function saveDevelopmentData(): void
    {
        foreach (array_keys($this->packageManager->getFrozenPackages()) as $packageKey) {
            $pathAndFilename = $this->getPrecompiledReflectionStoragePath() . $packageKey . '.dat';
            if (!file_exists($pathAndFilename)) {
                $this->log(sprintf('Rebuilding precompiled reflection data for frozen package %s.', $packageKey), LogLevel::DEBUG);
                $this->freezePackageReflection($packageKey);
            }
        }
    }

    /**
     * Save reflection data to cache in Production context.
     *
     * @throws Exception
     */
    protected function saveProductionData(): void
    {
        $this->reflectionDataRuntimeCache->flush();
        $this->classSchemataRuntimeCache->flush();

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

        $this->log(sprintf('Built and froze reflection runtime caches (%s classes).', count($this->classReflectionData)), LogLevel::INFO);
    }

    /**
     * Set updated reflection data to caches.
     *
     * @throws Exception
     */
    protected function updateReflectionData(): void
    {
        $this->log(sprintf('Found %s classes whose reflection data was not cached previously.', count($this->updatedReflectionData)), LogLevel::DEBUG);

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
     */
    protected function cleanClassName(string $className): string
    {
        return ltrim($className, '\\');
    }

    /**
     * Transform backslashes to underscores to provide a valid cache identifier.
     */
    protected function produceCacheIdentifierFromClassName(string $className): array|string
    {
        return str_replace('\\', '_', $className);
    }

    /**
     * Writes the given message along with the additional information into the log.
     */
    protected function log(string $message, string $severity = LogLevel::INFO, array $additionalData = []): void
    {
        $this->logger?->log($severity, $message, $additionalData);
    }

    /**
     * @throws \Neos\Flow\Utility\Exception
     * @throws FilesException
     */
    protected function getPrecompiledReflectionStoragePath(): string
    {
        return Files::concatenatePaths([$this->environment->getPathToTemporaryDirectory(), 'PrecompiledReflectionData/']) . '/';
    }

    protected function hasFrozenCacheInProduction(): bool
    {
        return $this->environment->getContext()->isProduction() && $this->reflectionDataRuntimeCache->getBackend()->isFrozen();
    }
}
