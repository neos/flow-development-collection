<?php
namespace TYPO3\FLOW3\Object;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use \TYPO3\FLOW3\Object\Configuration\Configuration;
use \TYPO3\FLOW3\Object\Configuration\ConfigurationProperty as Property;
use \TYPO3\FLOW3\Reflection\ObjectAccess;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A specialized Object Manager which is able to do some basic dependency injection for
 * singleton scoped objets. This Object Manager is used during compile time when the proxy
 * class based DI mechanism is not yet available.
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Proxy(false)
 */
class CompileTimeObjectManager extends ObjectManager {

	/**
	 * @var \TYPO3\FLOW3\Cache\Frontend\VariableFrontend
	 */
	protected $configurationCache;

	/**
	 * @var \TYPO3\FLOW3\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\FLOW3\Log\SystemLoggerInterface
	 */
	protected $systemLogger;

	/**
	 * @var array
	 */
	protected $objectConfigurations;

	/**
	 * A list of all class names known to the Object Manager
	 *
	 * @var array
	 */
	protected $registeredClassNames = array();

	/**
	 * @var array
	 */
	protected $objectNameBuildStack = array();

	/**
	 * @var array
	 */
	protected $cachedClassNamesByScope = array();

	/**
	 * @param \TYPO3\FLOW3\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\FLOW3\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\FLOW3\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Injects the configuration cache of the Object Framework
	 *
	 * @param \TYPO3\FLOW3\Cache\Frontend\VariableFrontend $configurationCache
	 * @return void
	 */
	public function injectConfigurationCache(\TYPO3\FLOW3\Cache\Frontend\VariableFrontend $configurationCache) {
		$this->configurationCache = $configurationCache;
	}

	/**
	 * @param \TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger
	 * @return void
	 */
	public function injectSystemLogger(\TYPO3\FLOW3\Log\SystemLoggerInterface $systemLogger) {
		$this->systemLogger = $systemLogger;
	}

	/**
	 * Initializes the the object configurations and some other parts of this Object Manager.
	 *
	 * @param array $packages An array of active packages to consider
	 * @return void
	 */
	public function initialize(array $packages) {
		$this->registeredClassNames = $this->registerClassFiles($packages);
		$this->reflectionService->buildReflectionData($this->registeredClassNames);

		$rawCustomObjectConfigurations = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);

		$configurationBuilder = new \TYPO3\FLOW3\Object\Configuration\ConfigurationBuilder();
		$configurationBuilder->injectReflectionService($this->reflectionService);
		$configurationBuilder->injectSystemLogger($this->systemLogger);

		$this->objectConfigurations = $configurationBuilder->buildObjectConfigurations($this->registeredClassNames, $rawCustomObjectConfigurations);

		$this->setObjects($this->buildObjectsArray());
	}

	/**
	 * Sets the instance of the given object
	 *
	 * In the Compile Time Object Manager it is even allowed to set instances of not-yet-known objects as long as the Object
	 * Manager is not initialized, because some few parts need an object registry even before the Object Manager is fully
	 * functional.
	 *
	 * @param string $objectName The object name
	 * @param object $instance A prebuilt instance
	 */
	public function setInstance($objectName, $instance) {
		if ($this->registeredClassNames === array()) {
			$this->objects[$objectName]['i'] = $instance;
		} else {
			parent::setInstance($objectName, $instance);
		}
	}


	/**
	 * Returns a list of all class names, grouped by package key,  which were registered by registerClassFiles()
	 *
	 * @return array
	 */
	public function getRegisteredClassNames() {
		return $this->registeredClassNames;
	}

	/**
	 * Returns a list of class names, which are configured with the given scope
	 *
	 * @param integer $scope One of the ObjectConfiguration::SCOPE_ constants
	 * @return array An array of class names configured with the given scope
	 */
	public function getClassNamesByScope($scope) {
		if (!isset($this->cachedClassNamesByScope[$scope])) {
			foreach ($this->objects as $objectName => $information) {
				if ($information['s'] === $scope) {
					if (isset($information['c'])) {
						$this->cachedClassNamesByScope[$scope][] = $information['c'];
					} else {
						$this->cachedClassNamesByScope[$scope][] = $objectName;
					}
				}
			}
		}
		return $this->cachedClassNamesByScope[$scope];
	}

	/**
	 * Traverses through all class files of the active packages and registers collects the class names as
	 * "all available class names". If the respective FLOW3 settings say so, also function test classes
	 * are registered.
	 *
	 * For performance reasons this function ignores classes whose name ends with "Exception".
	 *
	 * @param array $packages A list of packages to consider
	 * @return array A list of class names which were discovered in the given packages
	 */
	protected function registerClassFiles(array $packages) {
		$availableClassNames = array('' => array('DateTime'));

		foreach ($packages as $packageKey => $package) {
			if ($package->isObjectManagementEnabled()) {
				$classFiles = $package->getClassFiles();
				if ($this->allSettings['TYPO3']['FLOW3']['object']['registerFunctionalTestClasses'] === TRUE) {
					$classFiles = array_merge($classFiles, $package->getFunctionalTestsClassFiles());
				}
				foreach (array_keys($classFiles) as $fullClassName) {
					if (substr($fullClassName, -9, 9) !== 'Exception') {
						$availableClassNames[$packageKey][] = $fullClassName;
					}
				}
				$availableClassNames[$packageKey] = array_unique($availableClassNames[$packageKey]);
			}
		}
		return $availableClassNames;
	}

	/**
	 * Builds the  objects array which contains information about the registered objects,
	 * their scope, class, built method etc.
	 *
	 * @return array
	 */
	protected function buildObjectsArray() {
		$objects = array();
		foreach ($this->objectConfigurations as $objectConfiguration) {
			$objectName = $objectConfiguration->getObjectName();
			$objects[$objectName] = array(
				'l' => strtolower($objectName),
				's' => $objectConfiguration->getScope(),
				'p' => $objectConfiguration->getPackageKey()
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
		$this->configurationCache->set('objects', $objects);
		return $objects;
	}

	/**
	 * Returns object configurations which were previously built by the ConfigurationBuilder.
	 *
	 * @return array
	 */
	public function getObjectConfigurations() {
		return $this->objectConfigurations;
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * This specialized get() method is able to do setter injection for properties defined in the object configuration
	 * of the specified object.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 */
	public function get($objectName) {
		if (isset($this->objects[$objectName]['i'])) {
			return $this->objects[$objectName]['i'];
		}

		if (isset($this->objectConfigurations[$objectName]) && count($this->objectConfigurations[$objectName]->getArguments()) > 0) {
			throw new Exception\CannotBuildObjectException('Cannot build object "' . $objectName . '" because constructor injection is not available in the compile time Object Manager. Refactor your code to use setter injection instead. Configuration source: ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . '. Build stack: ' . implode(', ', $this->objectNameBuildStack), 1297090026);
		}
		if (!isset($this->objects[$objectName])) {
			throw new Exception\UnknownObjectException('Cannot build object "' . $objectName . '" because it is unknown to the compile time Object Manager.', 1301477694);
		}

		if ($this->objects[$objectName]['s'] !== Configuration::SCOPE_SINGLETON) {
			throw new Exception\CannotBuildObjectException('Cannot build object "' . $objectName . '" because the get() method in the compile time Object Manager only supports singletons.', 1297090027);
		}

		$this->objectNameBuildStack[] = $objectName;

		$object = parent::get($objectName);
		foreach ($this->objectConfigurations[$objectName]->getProperties() as $propertyName => $property) {
			if ($property->getAutowiring() !== Configuration::AUTOWIRING_MODE_ON) {
				continue;
			}
			switch ($property->getType()) {
				case Property::PROPERTY_TYPES_STRAIGHTVALUE:
					$value = $property->getValue();
				break;
				case Property::PROPERTY_TYPES_SETTING:
					$value = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->allSettings, explode('.', $property->getValue()));
				break;
				case Property::PROPERTY_TYPES_OBJECT:
					$propertyObjectName = $property->getValue();
					if (!is_string($propertyObjectName)) {
						throw new Exception\CannotBuildObjectException('The object definition of "' . $objectName . '::' . $propertyName . '" is too complex for the compile time Object Manager. You can only use plain object names, not factories and the like. Check configuration in ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . ' and objects which depend on ' . $objectName. '.', 1297099659);
					}
					$value = $this->get($propertyObjectName);
				break;
				default:
					throw new Exception\CannotBuildObjectException('Invalid property type.', 1297090029);
				break;
			}

			if (method_exists($object, $setterMethodName = 'inject' . ucfirst($propertyName))) {
				$object->$setterMethodName($value);
			} elseif (method_exists($object, $setterMethodName = 'set' . ucfirst($propertyName))) {
				$object->$setterMethodName($value);
			} else {
				throw new Exception\UnresolvedDependenciesException('Could not inject configured property "' . $propertyName . '" into "' . $objectName . '" because no injection method exists, but for compile time use this is required. Configuration source: ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . '.', 1297110953);
			}
		}

		$initializationLifecycleMethodName = $this->objectConfigurations[$objectName]->getLifecycleInitializationMethodName();
		if (method_exists($object, $initializationLifecycleMethodName)) {
			$object->$initializationLifecycleMethodName();
		}

		$shutdownLifecycleMethodName = $this->objectConfigurations[$objectName]->getLifecycleShutdownMethodName();
		if (method_exists($object, $shutdownLifecycleMethodName)) {
			$this->shutdownObjects[$object] = $shutdownLifecycleMethodName;
		}

		array_pop($this->objectNameBuildStack);
		return $object;
	}
}
?>