<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Proxy;

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
 * Builder for proxy classes which are used to implement Dependency Injection and
 * Aspect-Oriented Programming
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @proxy disable
 */
class Compiler {

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * @var \F3\FLOW3\Cache\Frontend\PhpFrontend
	 */
	protected $classesCache;

	/**
	 * @var \F3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $configurationCache;

	/**
	 * @var \F3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \F3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $allAvailableClassNames = array();

	/**
	 * @var array
	 */
	protected $proxyableClassNames = array();

	/**
	 * @var array
	 */
	protected $objectConfigurations;

	/**
	 * @var string
	 */
	static $originalClassNameSuffix = '_Original';

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
	 * Injects the cache for storing the renamed original classes and proxy classes
	 *
	 * @param \F3\FLOW3\Cache\Frontend\PhpFrontend $classesCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectClassesCache(\F3\FLOW3\Cache\Frontend\PhpFrontend $classesCache) {
		$this->classesCache = $classesCache;
	}

	/**
	 * Injects the configuration cache of the Object Framework
	 *
	 * @param \F3\FLOW3\Cache\Frontend\VariableFrontend $configurationCache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationCache(\F3\FLOW3\Cache\Frontend\VariableFrontend $configurationCache) {
		$this->configurationCache = $configurationCache;
	}

	/**
	 * @param \F3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \F3\FLOW3\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectConfigurationManager(\F3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * @param \F3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSystemLogger(\F3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the Proxy Class Builder
	 *
	 * @param array $packages
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize(array $packages) {
		$this->classesCache->flush(); # FIXME

		$this->registerClassFiles($packages);
		$this->reflectionService->buildReflectionData($this->allAvailableClassNames);

		$rawCustomObjectConfigurations = $this->configurationManager->getConfiguration(\F3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);

		$configurationBuilder = new \F3\FLOW3\Object\Configuration\ConfigurationBuilder();
		$configurationBuilder->injectReflectionService($this->reflectionService);
		$configurationBuilder->injectSystemLogger($this->systemLogger);

		$this->objectConfigurations = $configurationBuilder->buildObjectConfigurations($this->allAvailableClassNames, $rawCustomObjectConfigurations);
	}

	/**
	 * Compiles the configured proxy classes and methods as static PHP code and stores it in the proxy class code cache.
	 * Also builds the static object container which acts as a registry for non-prototype objects during runtime.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function compile() {
		foreach ($this->allAvailableClassNames as $fullOriginalClassName) {
			if (isset($this->proxyClasses[$fullOriginalClassName])) {
				$proxyClassCode = $this->proxyClasses[$fullOriginalClassName]->render();
				if ($proxyClassCode !== '') {
					$this->classesCache->set(str_replace('\\', '_', $fullOriginalClassName), $proxyClassCode);
				}
			} else {
				$this->classesCache->remove(str_replace('\\', '_', $fullOriginalClassName . self::$originalClassNameSuffix));
			}
		}
		$this->configurationCache->set('objects', $this->buildObjectsArray());
	}

	/**
	 * Provides the array of object configuration objects which have been generated by the Configuration Builder to
	 * the proxy class builders configuring this Compiler.
	 *
	 * @return array The object configurations
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectConfigurations() {
		return $this->objectConfigurations;
	}

	/**
	 * Returns a proxy class object for the specified original class.
	 *
	 * If no such proxy class has been created yet by this renderer,
	 * this function will create one and register it for later use.
	 *
	 * @param string $fullClassName Name of the original class
	 * @return \F3\FLOW3\Object\Proxy\ProxyClass
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProxyClass($fullClassName) {
		if (!in_array($fullClassName, $this->proxyableClassNames)) {
			return FALSE;
		}
		if (!isset($this->proxyClasses[$fullClassName])) {
			$this->proxyClasses[$fullClassName] = new ProxyClass($fullClassName);
			$this->proxyClasses[$fullClassName]->injectReflectionService($this->reflectionService);
		}
		return $this->proxyClasses[$fullClassName];
	}

	/**
	 * Traverses through all class files of the active packages and registers collects the class names as
	 * "all available class names". Except of Interfaces and Exceptions, a copy of each class file is created
	 * via cacheOriginalClassFile().
	 *
	 * If the settings say so, also function test classes are registered.
	 *
	 * @param array $packages A list of packages to consider
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function registerClassFiles(array $packages) {
		$this->proxyableClassNames = array();
		foreach ($packages as $package) {
			$packagePath = $package->getPackagePath();
			$classFiles = $package->getClassFiles();
			if ($this->settings['object']['registerFunctionalTestClasses'] === TRUE) {
				$classFiles = array_merge($classFiles, $package->getFunctionalTestsClassFiles());
			}
			foreach ($classFiles as $fullClassName => $relativePathAndFilename) {
				if (substr($fullClassName, -9, 9) === 'Exception') {
					continue;
				}

				$this->allAvailableClassNames[] = $fullClassName;

				if (interface_exists($fullClassName)) {
					continue;
				}

				$proxyAnnotationValues = $this->reflectionService->getClassTagValues($fullClassName, 'proxy');
				if ($proxyAnnotationValues !== array() && $proxyAnnotationValues[0] === 'disable') {
					$this->systemLogger->log(sprintf('Skipping class %s because it contains a @proxy disable annotation.', $fullClassName), LOG_DEBUG);
					continue;
				}
				if (in_array('F3\FLOW3\Tests\BaseTestCase', class_parents($fullClassName))) {
					continue;
				}

				$this->proxyableClassNames[] = $fullClassName;
				$this->cacheOriginalClassFile($fullClassName, $packagePath . $relativePathAndFilename);
			}
		}

		$this->allAvailableClassNames = array_unique($this->allAvailableClassNames);
		$this->proxyableClassNames = array_unique($this->proxyableClassNames);
	}

	/**
	 * Reads the specified class file, appends "_Original" to its class name and stores the result in the
	 * proxy classes cache.
	 *
	 * @param string $className Short class name of the class to copy
	 * @param string $pathAndFilename Full path and file name of the original class file
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function cacheOriginalClassFile($className, $pathAndFilename) {
		$classCode = file_get_contents($pathAndFilename);
		$classCode = preg_replace('/^<\\?php.*\n/', '', $classCode);
		$classCode = preg_replace('/^([a-z ]*)(interface|class)\s+([a-zA-Z0-9_]+)/m', '$1$2 $3_Original', $classCode);

		$classCode = preg_replace('/\\?>[\n\s\r]*$/', '', $classCode);
		$this->classesCache->set(str_replace('\\', '_', $className . self::$originalClassNameSuffix), $classCode);
	}

	/**
	 * Builds the PHP code of the object manager's objects array which contains information
	 * about the registered objects, their scope, class, built method etc.
	 *
	 * @return string PHP code of the objects array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function buildObjectsArray() {
		$objects = array();
		foreach ($this->objectConfigurations as $objectConfiguration) {
			$objectName = $objectConfiguration->getObjectName();
			$objects[$objectName] = array(
				'l' => strtolower($objectName),
				's' => $objectConfiguration->getScope()
			);
			if ($objectConfiguration->getClassName() !== $objectName) {
				$objects[$objectName]['c'] = $objectConfiguration->getClassName();
			}
			if ($objectConfiguration->getFactoryObjectName() !== '') {
				$objects[$objectName]['f'] = array(
					$objectConfiguration->getFactoryObjectName(),
					$objectConfiguration->getFactoryMethodName()
				);

				$objects[$objectName]['fa'] = array();
				$factoryMethodArguments = $objectConfiguration->getArguments();
				if (count($factoryMethodArguments) > 0) {
					foreach ($factoryMethodArguments as $index => $argument) {
						$objects[$objectName]['fa'][$index] = array(
							't' => $argument->getType(),
							'v' => $argument->getValue()
						);
					}
				}
			}
		}
		return $objects;
	}
}

?>