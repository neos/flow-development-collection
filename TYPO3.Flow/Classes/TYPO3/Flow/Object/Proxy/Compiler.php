<?php
namespace TYPO3\Flow\Object\Proxy;

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
 * Builder for proxy classes which are used to implement Dependency Injection and
 * Aspect-Oriented Programming
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class Compiler {

	/**
	 * @var string
	 */
	const ORIGINAL_CLASSNAME_SUFFIX = '_Original';

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var \TYPO3\Flow\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $proxyClasses = array();

	/**
	 * Hardcoded list of Flow sub packages (first 14 characters) which must be immune proxying for security, technical or conceptual reasons.
	 * @var array
	 */
	protected $blacklistedSubPackages = array('TYPO3\Flow\Aop', 'TYPO3\Flow\Cor', 'TYPO3\Flow\Obj', 'TYPO3\Flow\Pac', 'TYPO3\Flow\Ref', 'TYPO3\Flow\Uti');

	/**
	 * The final map of proxy classes that end up in the cache.
	 *
	 * @var array
	 */
	protected $storedProxyClasses = array();

	/**
	 * Injects the Flow settings
	 *
	 * @param array $settings The settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\Flow\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\Flow\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the cache for storing the renamed original classes and proxy classes
	 *
	 * @param \TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 * @Flow\Autowiring(false)
	 */
	public function injectClassesCache(\TYPO3\Flow\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * @param \TYPO3\Flow\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\Flow\Reflection\ReflectionService $reflectionService) {
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
	 * @return \TYPO3\Flow\Object\Proxy\ProxyClass|boolean
	 */
	public function getProxyClass($fullClassName) {
		if (interface_exists($fullClassName) || in_array('TYPO3\Flow\Tests\BaseTestCase', class_parents($fullClassName))) {
			return FALSE;
		}

		if (class_exists($fullClassName) === FALSE) {
			return FALSE;
		}

		$classReflection = new \ReflectionClass($fullClassName);
		if ($classReflection->isInternal() === TRUE) {
			return FALSE;
		}

		$proxyAnnotation = $this->reflectionService->getClassAnnotation($fullClassName, 'TYPO3\Flow\Annotations\Proxy');
		if ($proxyAnnotation !== NULL && $proxyAnnotation->enabled === FALSE) {
			return FALSE;
		}

		if (in_array(substr($fullClassName, 0, 14), $this->blacklistedSubPackages)) {
			return FALSE;
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
	public function hasCacheEntryForClass($fullClassName) {
		if (isset($this->proxyClasses[$fullClassName])) {
			return FALSE;
		}
		return $this->classesCache->has(str_replace('\\', '_', $fullClassName));
	}

	/**
	 * Compiles the configured proxy classes and methods as static PHP code and stores it in the proxy class code cache.
	 * Also builds the static object container which acts as a registry for non-prototype objects during runtime.
	 *
	 * @return integer Number of classes which have been compiled
	 */
	public function compile() {
		$classCount = 0;
		foreach ($this->objectManager->getRegisteredClassNames() as $fullOriginalClassNames) {
			foreach ($fullOriginalClassNames as $fullOriginalClassName) {
				if (isset($this->proxyClasses[$fullOriginalClassName])) {
					$proxyClassCode = $this->proxyClasses[$fullOriginalClassName]->render();
					if ($proxyClassCode !== '') {
						$class = new \ReflectionClass($fullOriginalClassName);
						$classPathAndFilename = $class->getFileName();
						$this->cacheOriginalClassFileAndProxyCode($fullOriginalClassName, $classPathAndFilename, $proxyClassCode);
						$this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = TRUE;
						$classCount ++;
					}
				} else {
					if ($this->classesCache->has(str_replace('\\', '_', $fullOriginalClassName))) {
						$this->storedProxyClasses[str_replace('\\', '_', $fullOriginalClassName)] = TRUE;
					}
				}
			}
		}
		return $classCount;
	}

	/**
	 * @return string
	 */
	public function getStoredProxyClassMap() {
		$return = "<?php
/**
 * This is a cached list of all proxy classes. Only classes in this array will
 * actually be loaded from the proxy class cache in the ClassLoader.
 */
return " . var_export($this->storedProxyClasses, TRUE) . ";";

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
	 */
	protected function cacheOriginalClassFileAndProxyCode($className, $pathAndFilename, $proxyClassCode) {
		$classCode = file_get_contents($pathAndFilename);
		$classCode = preg_replace('/^<\\?php.*\n/', '', $classCode);
		$classCode = preg_replace('/^([a-z ]*)(interface|class)\s+([a-zA-Z0-9_]+)/m', '$1$2 $3' . self::ORIGINAL_CLASSNAME_SUFFIX, $classCode);

		$classCode = preg_replace('/\\?>[\n\s\r]*$/', '', $classCode);

		$this->classesCache->set(str_replace('\\', '_', $className), $classCode . $proxyClassCode);
	}


	/**
	 * Render the source (string) form of an Annotation instance.
	 *
	 * @param \Doctrine\Common\Annotations\Annotation $annotation
	 * @return string
	 */
	static public function renderAnnotation($annotation) {
		$annotationAsString = '@\\' . get_class($annotation);

		$optionNames = get_class_vars(get_class($annotation));
		$optionsAsStrings = array();
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
		return $annotationAsString . ($optionsAsStrings !== array() ? '(' . implode(', ', $optionsAsStrings) . ')' : '');
	}

	/**
	 * Render an array value as string for an annotation.
	 *
	 * @param array $optionValue
	 * @return string
	 */
	static protected function renderOptionArrayValueAsString(array $optionValue) {
		$values = array();
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
