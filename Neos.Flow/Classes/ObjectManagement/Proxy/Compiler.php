<?php
namespace Neos\Flow\ObjectManagement\Proxy;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\BaseTestCase;

/**
 * Builder for proxy classes which are used to implement Dependency Injection and
 * Aspect-Oriented Programming
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class Compiler
{
    /**
     * @var string
     */
    const ORIGINAL_CLASSNAME_SUFFIX = '_Original';

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * @var CompileTimeObjectManager
     */
    protected $objectManager;

    /**
     * @var PhpFrontend
     */
    protected $classesCache;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var array
     */
    protected $proxyClasses = [];

    /**
     * Hardcoded list of Flow sub packages which must be immune proxying for security, technical or conceptual reasons.
     * @var array
     */
    protected $blacklistedSubPackages = ['Neos\Flow\Aop', 'Neos\Flow\Cor', 'Neos\Flow\Obj', 'Neos\Flow\Pac', 'Neos\Flow\Ref', 'Neos\Flow\Uti'];

    /**
     * Length of the prefix that will be checked for exclusion of proxy building.
     * See above.
     *
     * @var integer
     */
    protected $blacklistedSubPackagesLength;

    /**
     * The final map of proxy classes that end up in the cache.
     *
     * @var array
     */
    protected $storedProxyClasses = [];

    /**
     * Compiler constructor.
     */
    public function __construct()
    {
        $this->blacklistedSubPackagesLength = strlen('Neos\Flow') + 4;
    }

    /**
     * Injects the Flow settings
     *
     * @param array $settings The settings
     * @return void
     */
    public function injectSettings(array $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(CompileTimeObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Injects the cache for storing the renamed original classes and proxy classes
     *
     * @param PhpFrontend $classesCache
     * @return void
     * @Flow\Autowiring(false)
     */
    public function injectClassesCache(PhpFrontend $classesCache)
    {
        $this->classesCache = $classesCache;
    }

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Returns a proxy class object for the specified original class.
     *
     * If no such proxy class has been created yet by this renderer,
     * this function will create one and register it for later use.
     *
     * If the class is not proxable, FALSE will be returned
     *
     * @param string $fullClassName Name of the original class
     * @return ProxyClass|boolean
     */
    public function getProxyClass($fullClassName)
    {
        if (interface_exists($fullClassName) || in_array(BaseTestCase::class, class_parents($fullClassName))) {
            return false;
        }

        if (class_exists($fullClassName) === false) {
            return false;
        }

        $classReflection = new \ReflectionClass($fullClassName);
        if ($classReflection->isInternal() === true) {
            return false;
        }

        $proxyAnnotation = $this->reflectionService->getClassAnnotation($fullClassName, Flow\Proxy::class);
        if ($proxyAnnotation !== null && $proxyAnnotation->enabled === false) {
            return false;
        }

        if (in_array(substr($fullClassName, 0, $this->blacklistedSubPackagesLength), $this->blacklistedSubPackages)) {
            return false;
        }
        // Annotation classes (like \Neos\Flow\Annotations\Entity) must never be proxied because that would break the Doctrine AnnotationParser
        if ($classReflection->isFinal() && preg_match('/^\s?\*\s?\@Annotation\s/m', $classReflection->getDocComment()) === 1) {
            return false;
        }

        if (!isset($this->proxyClasses[$fullClassName])) {
            $this->proxyClasses[$fullClassName] = new ProxyClass($fullClassName);
            $this->proxyClasses[$fullClassName]->injectReflectionService($this->reflectionService);
        }
        return $this->proxyClasses[$fullClassName];
    }

    /**
     * Checks if the specified class still exists in the code cache. If that is the case, it means that obviously
     * the proxy class doesn't have to be rebuilt because otherwise the cache would have been flushed by the file
     * monitor or some other mechanism.
     *
     * @param string $fullClassName Name of the original class
     * @return boolean TRUE if a cache entry exists
     */
    public function hasCacheEntryForClass($fullClassName)
    {
        if (isset($this->proxyClasses[$fullClassName])) {
            return false;
        }
        return $this->classesCache->has(str_replace('\\', '_', $fullClassName));
    }

    /**
     * Compiles the configured proxy classes and methods as static PHP code and stores it in the proxy class code cache.
     * Also builds the static object container which acts as a registry for non-prototype objects during runtime.
     *
     * @return integer Number of classes which have been compiled
     */
    public function compile()
    {
        $classCount = 0;
        foreach ($this->objectManager->getRegisteredClassNames() as $fullOriginalClassNames) {
            foreach ($fullOriginalClassNames as $fullOriginalClassName) {
                if (isset($this->proxyClasses[$fullOriginalClassName])) {
                    $proxyClassCode = $this->proxyClasses[$fullOriginalClassName]->render();
                    if ($proxyClassCode !== '') {
                        $class = new \ReflectionClass($fullOriginalClassName);
                        $classPathAndFilename = $class->getFileName();
                        $this->cacheOriginalClassFileAndProxyCode($fullOriginalClassName, $classPathAndFilename, $proxyClassCode);
                        $this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = true;
                        $classCount++;
                    }
                } else {
                    if ($this->classesCache->has(str_replace('\\', '_', $fullOriginalClassName))) {
                        $this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = true;
                    }
                }
            }
        }
        return $classCount;
    }

    /**
     * @return string
     */
    public function getStoredProxyClassMap()
    {
        $return = '<?php
/**
 * This is a cached list of all proxy classes. Only classes in this array will
 * actually be loaded from the proxy class cache in the ClassLoader.
 */
return ' . var_export($this->storedProxyClasses, true) . ';';

        return $return;
    }

    /**
     * Reads the specified class file, appends ORIGINAL_CLASSNAME_SUFFIX to its
     * class name and stores the result in the proxy classes cache.
     *
     * @param string $className Short class name of the class to copy
     * @param string $pathAndFilename Full path and filename of the original class file
     * @param string $proxyClassCode The code that makes up the proxy class
     * @return void
     *
     * @throws Exception If the original class filename doesn't match the actual class name inside the file.
     */
    protected function cacheOriginalClassFileAndProxyCode($className, $pathAndFilename, $proxyClassCode)
    {
        $classCode = file_get_contents($pathAndFilename);
        $classCode = $this->stripOpeningPhpTag($classCode);

        $classNameSuffix = self::ORIGINAL_CLASSNAME_SUFFIX;
        $classCode = preg_replace_callback('/^([a-z\s]*?)(final\s+)?(interface|class)\s+([a-zA-Z0-9_]+)/m', function ($matches) use ($pathAndFilename, $classNameSuffix, $proxyClassCode) {
            $classNameAccordingToFileName = basename($pathAndFilename, '.php');
            if ($matches[4] !== $classNameAccordingToFileName) {
                throw new Exception('The name of the class "' . $matches[4] . '" is not the same as the filename which is "' . basename($pathAndFilename) . '". Path: ' . $pathAndFilename, 1398356897);
            }
            return $matches[1] . $matches[3] . ' ' . $matches[4] . $classNameSuffix;
        }, $classCode);

        $classCode = preg_replace('/\\?>[\n\s\r]*$/', '', $classCode);

        $proxyClassCode .= "\n" . '# PathAndFilename: ' . $pathAndFilename;

        $separator =
            PHP_EOL . '#' .
            PHP_EOL . '# Start of Flow generated Proxy code' .
            PHP_EOL . '#' . PHP_EOL;

        $this->classesCache->set(str_replace('\\', '_', $className), $classCode . $separator . $proxyClassCode);
    }

    /**
     * Removes the first opening php tag ("<?php") from the given $classCode if there is any
     *
     * @param string $classCode
     * @return string the original class code without opening php tag
     */
    protected function stripOpeningPhpTag($classCode)
    {
        return preg_replace('/^\s*\\<\\?php(.*\n|.*)/', '$1', $classCode, 1);
    }


    /**
     * Render the source (string) form of an Annotation instance.
     *
     * @param \Doctrine\Common\Annotations\Annotation $annotation
     * @return string
     */
    public static function renderAnnotation($annotation)
    {
        $annotationAsString = '@\\' . get_class($annotation);

        $optionNames = get_class_vars(get_class($annotation));
        $optionsAsStrings = [];
        foreach ($optionNames as $optionName => $optionDefault) {
            $optionValue = $annotation->$optionName;
            $optionValueAsString = '';
            if (is_object($optionValue)) {
                $optionValueAsString = self::renderAnnotation($optionValue);
            } elseif (is_scalar($optionValue) && is_string($optionValue)) {
                $optionValueAsString = '"' . $optionValue . '"';
            } elseif (is_bool($optionValue)) {
                $optionValueAsString = $optionValue ? 'true' : 'false';
            } elseif (is_scalar($optionValue)) {
                $optionValueAsString = $optionValue;
            } elseif (is_array($optionValue)) {
                $optionValueAsString = self::renderOptionArrayValueAsString($optionValue);
            }
            switch ($optionName) {
                case 'value':
                    $optionsAsStrings[] = $optionValueAsString;
                    break;
                default:
                    if ($optionValue === $optionDefault) {
                        continue;
                    }
                    $optionsAsStrings[] = $optionName . '=' . $optionValueAsString;
            }
        }
        return $annotationAsString . ($optionsAsStrings !== [] ? '(' . implode(', ', $optionsAsStrings) . ')' : '');
    }

    /**
     * Render an array value as string for an annotation.
     *
     * @param array $optionValue
     * @return string
     */
    protected static function renderOptionArrayValueAsString(array $optionValue)
    {
        $values = [];
        foreach ($optionValue as $k => $v) {
            $value = '';
            if (is_string($k)) {
                $value .= '"' . $k . '"=';
            }
            if (is_object($v)) {
                $value .= self::renderAnnotation($v);
            } elseif (is_array($v)) {
                $value .= self::renderOptionArrayValueAsString($v);
            } elseif (is_scalar($v) && is_string($v)) {
                $value .= '"' . $v . '"';
            } elseif (is_bool($v)) {
                $value .= $v ? 'true' : 'false';
            } elseif (is_scalar($v)) {
                $value .= $v;
            }
            $values[] = $value;
        }
        return '{ ' . implode(', ', $values) . ' }';
    }
}
