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

use Neos\Cache\Exception;
use Neos\Cache\Exception\InvalidDataException;
use Neos\Cache\Frontend\PhpFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\CompileTimeObjectManager;
use Neos\Flow\ObjectManagement\Exception\ProxyCompilerException;
use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Tests\BaseTestCase;
use ReflectionAttribute;
use ReflectionClass;

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
    public const ORIGINAL_CLASSNAME_SUFFIX = '_Original';

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
    protected $excludedSubPackages = ['Neos\Flow\Aop', 'Neos\Flow\Cor', 'Neos\Flow\Obj', 'Neos\Flow\Pac', 'Neos\Flow\Ref', 'Neos\Flow\Uti'];

    /**
     * Length of the prefix that will be checked for exclusion of proxy building.
     * See above.
     *
     * @var int
     */
    protected $excludedSubPackagesLength;

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
        $this->excludedSubPackagesLength = strlen('Neos\Flow') + 4;
    }

    /**
     * @param CompileTimeObjectManager $objectManager
     * @return void
     */
    public function injectObjectManager(CompileTimeObjectManager $objectManager): void
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
    public function injectClassesCache(PhpFrontend $classesCache): void
    {
        $this->classesCache = $classesCache;
    }

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService): void
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * Returns a proxy class object for the specified original class.
     *
     * If no such proxy class has been created yet by this renderer,
     * this function will create one and register it for later use.
     *
     * If the class is not proxyable, or is not a real class at all,
     * false will be returned
     *
     * @param string $fullClassName Name of the original class
     * @return ProxyClass|bool
     */
    public function getProxyClass(string $fullClassName): bool|ProxyClass
    {
        if (interface_exists($fullClassName) || in_array(BaseTestCase::class, class_parents($fullClassName), true)) {
            return false;
        }

        if (class_exists($fullClassName) === false) {
            return false;
        }

        $classReflection = new ReflectionClass($fullClassName);
        if ($classReflection->isInternal() === true) {
            return false;
        }

        if (method_exists($classReflection, 'isEnum') && $classReflection->isEnum()) {
            return false;
        }

        if ($this->reflectionService->getClassAnnotation($fullClassName, Flow\Proxy::class)?->enabled === false) {
            return false;
        }

        if (in_array(substr($fullClassName, 0, $this->excludedSubPackagesLength), $this->excludedSubPackages, true)) {
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
     * @return bool true if a cache entry exists
     */
    public function hasCacheEntryForClass(string $fullClassName): bool
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
     * @return int Number of classes which have been compiled
     */
    public function compile(): int
    {
        $compiledClasses = [];
        foreach ($this->objectManager->getRegisteredClassNames() as $fullOriginalClassNames) {
            foreach ($fullOriginalClassNames as $fullOriginalClassName) {
                if (isset($this->proxyClasses[$fullOriginalClassName])) {
                    $proxyClassCode = $this->proxyClasses[$fullOriginalClassName]->render();
                    if ($proxyClassCode !== '') {
                        $class = new ReflectionClass($fullOriginalClassName);
                        $classPathAndFilename = $class->getFileName();
                        $this->cacheOriginalClassFileAndProxyCode($fullOriginalClassName, $classPathAndFilename, $proxyClassCode);
                        $this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = true;
                        $compiledClasses[] = $fullOriginalClassName;
                    }
                } elseif ($this->classesCache->has(str_replace('\\', '_', $fullOriginalClassName))) {
                    $this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = true;
                }
            }
        }
        $this->emitCompiledClasses($compiledClasses);
        return count($compiledClasses);
    }

    /**
     * @param array<string> $classNames
     *
     * @Flow\Signal
     */
    public function emitCompiledClasses(array $classNames): void
    {
    }

    /**
     * @return string
     */
    public function getStoredProxyClassMap(): string
    {
        return '<?php
/**
 * This is a cached list of all proxy classes. Only classes in this array will
 * actually be loaded from the proxy class cache in the ClassLoader.
 */
return ' . var_export($this->storedProxyClasses, true) . ';';
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
     * @throws InvalidDataException
     * @throws Exception
     * @throws ProxyCompilerException
     */
    protected function cacheOriginalClassFileAndProxyCode(string $className, string $pathAndFilename, string $proxyClassCode): void
    {
        $classCode = file_get_contents($pathAndFilename);
        $classCode = $this->replaceClassName($classCode, $pathAndFilename);
        $classCode = $this->replaceSelfWithStatic($classCode);
        $classCode = $this->makePrivateConstructorPublic($classCode, $pathAndFilename);
        $classCode = $this->stripOpeningPhpTag($classCode);
        $classCode = $this->commentOutFinalKeywordForMethods($classCode, $proxyClassCode);

        $classCode = preg_replace('/\\?>[\n\s\r]*$/', '', $classCode);

        $proxyClassCode .= PHP_EOL . '# PathAndFilename: ' . $pathAndFilename;

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
    protected function stripOpeningPhpTag(string $classCode): string
    {
        return preg_replace('/^\s*\\<\\?php(.*\n|.*)/', '$1', $classCode, 1);
    }


    /**
     * Render the source (string) form of a PHP Attribute.
     * @param ReflectionAttribute $attribute
     * @return string
     */
    public static function renderAttribute(ReflectionAttribute $attribute): string
    {
        $attributeAsString = '\\' . $attribute->getName();
        if (count($attribute->getArguments()) > 0) {
            $argumentsAsString = [];
            foreach ($attribute->getArguments() as $argumentName => $argumentValue) {
                $renderedArgumentValue = var_export($argumentValue, true);
                if (is_numeric($argumentName)) {
                    $argumentsAsString[] = $renderedArgumentValue;
                } else {
                    $argumentsAsString[] = "$argumentName: $renderedArgumentValue";
                }
            }
            $attributeAsString .= '(' . implode(', ', $argumentsAsString) . ')';
        }
        return "#[$attributeAsString]";
    }

    /**
     * Render the source (string) form of an Annotation instance.
     *
     * @param object $annotation
     * @return string
     */
    public static function renderAnnotation(object $annotation): string
    {
        $annotationAsString = '@\\' . get_class($annotation);

        $optionDefaults = get_class_vars(get_class($annotation));
        $optionValues = get_object_vars($annotation);
        $optionsAsStrings = [];
        foreach ($optionValues as $optionName => $optionValue) {
            // FIXME: This is a workaround for https://github.com/neos/flow-development-collection/issues/2387
            if ($optionName[0] === '_') {
                continue;
            }
            $optionValueAsString = '';
            if (is_object($optionValue)) {
                $optionValueAsString = self::renderAnnotation($optionValue);
            } elseif (is_string($optionValue)) {
                $optionValueAsString = '"' . $optionValue . '"';
            } elseif (is_bool($optionValue)) {
                $optionValueAsString = $optionValue ? 'true' : 'false';
            } elseif (is_scalar($optionValue)) {
                $optionValueAsString = $optionValue;
            } elseif (is_array($optionValue)) {
                $optionValueAsString = self::renderOptionArrayValueAsString($optionValue);
            }
            if ($optionName === 'value') {
                $optionsAsStrings[] = $optionValueAsString;
            } elseif ($optionValue !== $optionDefaults[$optionName]) {
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
    protected static function renderOptionArrayValueAsString(array $optionValue): string
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
            } elseif (is_string($v)) {
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

    /**
     * Appends ORIGINAL_CLASSNAME_SUFFIX to the original class name
     *
     * @param string $classCode
     * @param string $pathAndFilename
     * @return string
     * @throws Exception
     */
    protected function replaceClassName(string $classCode, string $pathAndFilename): string
    {
        $tokens = token_get_all($classCode);
        $classNameTokenIndex = $this->getClassNameTokenIndex($tokens);
        if ($classNameTokenIndex === null) {
            throw new Exception('No class token found in class file "' . basename($pathAndFilename) . '". Path: ' . $pathAndFilename, 1636575752);
        }

        $classCodeUntilClassName = '';
        $classCodeUntilClassNameReplacement = '';
        for ($i = 0; $i <= $classNameTokenIndex; $i++) {
            $classCodeUntilClassName .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            if ($tokens[$i][0] === T_FINAL || ($i > 0 && $tokens[$i - 1][0] === T_FINAL)) {
                continue;
            }
            $classCodeUntilClassNameReplacement .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        }
        $classCodeUntilClassNameReplacement .= self::ORIGINAL_CLASSNAME_SUFFIX;

        return str_replace($classCodeUntilClassName, $classCodeUntilClassNameReplacement, $classCode);
    }

    private function getClassNameTokenIndex(array $tokens): ?int
    {
        foreach ($tokens as $i => $token) {
            # $token is an array: [0] => token id, [1] => token text, [2] => line number
            if (isset($classToken) && is_array($token) && $token[0] === T_STRING) {
                return $i;
            }
            # search first T_CLASS token that is not a `Foo::class` class name resolution
            if (is_array($token) && $token[0] === T_CLASS && isset($previousToken) && $previousToken[0] !== T_DOUBLE_COLON) {
                $classToken = $token;
            }
            $previousToken = $token;
        }
        return null;
    }

    /**
     * If a constructor exists, and it is private, this method will change its visibility to public
     * in the given class code. This is only necessary in order to allow the proxy class to call its
     * parent constructor.
     *
     * @throws ProxyCompilerException
     */
    protected function makePrivateConstructorPublic(string $classCode, string $pathAndFilename): string
    {
        $result = preg_replace('/private\s+function\s+__construct/', 'public function __construct', $classCode, 1);
        if ($result === null) {
            throw new ProxyCompilerException(sprintf('Could not make private constructor public in class file "%s".', $pathAndFilename), 1686149268);
        }
        return $result;
    }

    protected function commentOutFinalKeywordForMethods(string $classCode, string $proxyClassCode): string
    {
        // comment out "final" keyword, if the method is final and if it is advised (= part of the $proxyClassCode)
        // Note: Method name regex according to http://php.net/manual/en/language.oop5.basic.php
        $classCode = preg_replace_callback('/^(\s*)((public|protected)\s+)?final(\s+(public|protected))?(\s+function\s+)([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]+\s*\()/m', static function ($matches) use ($proxyClassCode) {
            // the method is not advised => don't remove the final keyword
            if (!str_contains($proxyClassCode, $matches[0])) {
                return $matches[0];
            }
            return $matches[1] . $matches[2] . '/*final*/' . $matches[4] . $matches[6] . $matches[7];
        }, $classCode);
        assert(is_string($classCode), 'preg_replace_callback() failed');
        return $classCode;
    }

    /**
     * Replace occurrences of "self" with "static" for instantiation with new self()
     * and function return declarations like "public function foo(): self".
     *
     * References to constants like "self::FOO" are not replaced.
     */
    protected function replaceSelfWithStatic(string $classCode): string
    {
        if (!str_contains($classCode, 'self')) {
            return $classCode;
        }

        $tokens = token_get_all($classCode);
        $tokensCount = count($tokens);
        $newClassCode = '';
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $tokensCount; $i++) {
            if (is_array($tokens[$i])) {
                if ($tokens[$i][0] === T_NEW && $tokens[$i + 2][0] === T_STRING && $tokens[$i + 2][1] === 'self') {
                    $newClassCode .= $tokens[$i][1] . $tokens[$i + 1][1] . 'static';
                    $i += 2;
                } elseif ($tokens[$i][0] === T_STRING && $tokens[$i][1] === 'self' && $tokens[$i + 1][0] !== T_DOUBLE_COLON) {
                    $newClassCode .= 'static';
                } else {
                    $newClassCode .= $tokens[$i][1];
                }
            } else {
                $newClassCode .= $tokens[$i];
            }
        }

        return $newClassCode;
    }
}
