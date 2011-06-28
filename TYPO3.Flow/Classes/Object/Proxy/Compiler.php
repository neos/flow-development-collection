<?php
namespace TYPO3\FLOW3\Object\Proxy;

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

use \TYPO3\FLOW3\Cache\CacheManager;

/**
 * Builder for proxy classes which are used to implement Dependency Injection and
 * Aspect-Oriented Programming
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @proxy disable
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
	 * @var \TYPO3\FLOW3\Object\CompileTimeObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var array
	 */
	protected $proxyClasses = array();

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The settings
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * @param \TYPO3\FLOW3\Object\CompileTimeObjectManager $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\TYPO3\FLOW3\Object\CompileTimeObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the cache for storing the renamed original classes and proxy classes
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @autowiring off
	 */
	public function injectClassesCache(\TYPO3\FLOW3\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Returns a proxy class object for the specified original class.
	 *
	 * If no such proxy class has been created yet by this renderer,
	 * this function will create one and register it for later use.
	 *
	 * @param string $fullClassName Name of the original class
	 * @return \TYPO3\FLOW3\Object\Proxy\ProxyClass
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProxyClass($fullClassName) {
		if (interface_exists($fullClassName) || in_array('TYPO3\FLOW3\Tests\BaseTestCase', class_parents($fullClassName))) {
			return FALSE;
		}

		if (class_exists($fullClassName) === FALSE) {
			return FALSE;
		}

		$classReflection = new \ReflectionClass($fullClassName);
		if ($classReflection->isInternal() === TRUE) {
			return FALSE;
		}

		$proxyAnnotationValues = $this->reflectionService->getClassTagValues($fullClassName, 'proxy');
		if ($proxyAnnotationValues !== array() && $proxyAnnotationValues[0] === 'disable') {
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function compile() {
		$classCount = 0;
		foreach ($this->objectManager->getRegisteredClassNames() as $fullOriginalClassNames) {
			foreach ($fullOriginalClassNames as $fullOriginalClassName) {
				if (isset($this->proxyClasses[$fullOriginalClassName])) {
					$proxyClassCode = $this->proxyClasses[$fullOriginalClassName]->render();
					if ($proxyClassCode !== '') {
						$this->classesCache->set(str_replace('\\', '_', $fullOriginalClassName), $proxyClassCode, $this->proxyClasses[$fullOriginalClassName]->getCacheTags());

						$class = new \ReflectionClass($fullOriginalClassName);
						$classPathAndFilename = $class->getFileName();
						$this->cacheOriginalClassFile($fullOriginalClassName, $classPathAndFilename);
						$classCount ++;
					}
				}
			}
		}
		return $classCount;
	}

	/**
	 * Reads the specified class file, appends ORIGINAL_CLASSNAME_SUFFIX to its
	 * class name and stores the result in the proxy classes cache.
	 *
	 * @param string $className Short class name of the class to copy
	 * @param string $pathAndFilename Full path and file name of the original class file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function cacheOriginalClassFile($className, $pathAndFilename) {
		$classCode = file_get_contents($pathAndFilename);
		$classCode = preg_replace('/^<\\?php.*\n/', '', $classCode);
		$classCode = preg_replace('/^([a-z ]*)(interface|class)\s+([a-zA-Z0-9_]+)/m', '$1$2 $3' . self::ORIGINAL_CLASSNAME_SUFFIX, $classCode);

		$classCode = preg_replace('/\\?>[\n\s\r]*$/', '', $classCode);

		$this->classesCache->set(str_replace('\\', '_', $className . self::ORIGINAL_CLASSNAME_SUFFIX), $classCode, array(CacheManager::getClassTag($className)));
	}
}

?>