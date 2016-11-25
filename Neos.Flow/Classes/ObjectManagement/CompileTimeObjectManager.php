<?php
namespace Neos\Flow\ObjectManagement;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Composer\ComposerUtility as ComposerUtility;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\ObjectManagement\Configuration\Configuration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationBuilder;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationProperty as Property;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Reflection\ReflectionService;

/**
 * A specialized Object Manager which is able to do some basic dependency injection for
 * singleton scoped objects. This Object Manager is used during compile time when the proxy
 * class based DI mechanism is not yet available.
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class CompileTimeObjectManager extends ObjectManager
{
    /**
     * @var VariableFrontend
     */
    protected $configurationCache;

    /**
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var SystemLoggerInterface
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
    protected $registeredClassNames = [];

    /**
     * @var array
     */
    protected $objectNameBuildStack = [];

    /**
     * @var array
     */
    protected $cachedClassNamesByScope = [];

    /**
     * @param ReflectionService $reflectionService
     * @return void
     */
    public function injectReflectionService(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param ConfigurationManager $configurationManager
     * @return void
     */
    public function injectConfigurationManager(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Injects the configuration cache of the Object Framework
     *
     * @param VariableFrontend $configurationCache
     * @return void
     */
    public function injectConfigurationCache(VariableFrontend $configurationCache)
    {
        $this->configurationCache = $configurationCache;
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
     * Initializes the the object configurations and some other parts of this Object Manager.
     *
     * @param array $packages An array of active packages to consider
     * @return void
     */
    public function initialize(array $packages)
    {
        $this->registeredClassNames = $this->registerClassFiles($packages);
        $this->reflectionService->buildReflectionData($this->registeredClassNames);

        $rawCustomObjectConfigurations = $this->configurationManager->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_OBJECTS);

        $configurationBuilder = new ConfigurationBuilder();
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
     * @return void
     */
    public function setInstance($objectName, $instance)
    {
        if ($this->registeredClassNames === []) {
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
    public function getRegisteredClassNames()
    {
        return $this->registeredClassNames;
    }

    /**
     * Returns a list of class names, which are configured with the given scope
     *
     * @param integer $scope One of the ObjectConfiguration::SCOPE_ constants
     * @return array An array of class names configured with the given scope
     */
    public function getClassNamesByScope($scope)
    {
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
     * "all available class names". If the respective Flow settings say so, also function test classes
     * are registered.
     *
     * For performance reasons this function ignores classes whose name ends with "Exception".
     *
     * @param array $packages A list of packages to consider
     * @return array A list of class names which were discovered in the given packages
     *
     * @throws InvalidConfigurationTypeException
     */
    protected function registerClassFiles(array $packages)
    {
        $includeClassesConfiguration = [];
        if (isset($this->allSettings['Neos']['Flow']['object']['includeClasses'])) {
            if (!is_array($this->allSettings['Neos']['Flow']['object']['includeClasses'])) {
                throw new InvalidConfigurationTypeException('The setting "Neos.Flow.object.includeClasses" is invalid, it must be an array if set. Check the syntax in the YAML file.', 1422357285);
            }

            $includeClassesConfiguration = $this->allSettings['Neos']['Flow']['object']['includeClasses'];
        }

        $availableClassNames = ['' => ['DateTime']];
        /** @var \Neos\Flow\Package\Package $package */
        foreach ($packages as $packageKey => $package) {
            if ($package->isObjectManagementEnabled() && (ComposerUtility::isFlowPackageType($package->getComposerManifest('type')) || isset($includeClassesConfiguration[$packageKey]))) {
                foreach ($package->getClassFiles() as $fullClassName => $path) {
                    if (substr($fullClassName, -9, 9) !== 'Exception') {
                        $availableClassNames[$packageKey][] = $fullClassName;
                    }
                }
                if (isset($this->allSettings['Neos']['Flow']['object']['registerFunctionalTestClasses']) && $this->allSettings['Neos']['Flow']['object']['registerFunctionalTestClasses'] === true) {
                    foreach ($package->getFunctionalTestsClassFiles() as $fullClassName => $path) {
                        if (substr($fullClassName, -9, 9) !== 'Exception') {
                            $availableClassNames[$packageKey][] = $fullClassName;
                        }
                    }
                }
                if (isset($availableClassNames[$packageKey]) && is_array($availableClassNames[$packageKey])) {
                    $availableClassNames[$packageKey] = array_unique($availableClassNames[$packageKey]);
                }
            }
        }
        return $this->filterClassNamesFromConfiguration($availableClassNames, $includeClassesConfiguration);
    }

    /**
     * Given an array of class names by package key this filters out classes that
     * have been configured to be excluded or included by object management.
     *
     * @param array $classNames 2-level array - key of first level is package key, value of second level is classname (FQN)
     * @param array $includeClassesConfiguration array of includeClasses configurations
     * @return array The input array with all configured to be excluded from object management filtered out
     * @throws InvalidConfigurationTypeException
     * @throws \Neos\Flow\Configuration\Exception\NoSuchOptionException
     */
    protected function filterClassNamesFromConfiguration(array $classNames, $includeClassesConfiguration)
    {
        if (isset($this->allSettings['Neos']['Flow']['object']['excludeClasses'])) {
            $this->systemLogger->log('Using "Neos.Flow.object.excludeClasses" is deprecated. Non flow packages are no longer enabled for object management by default, you can use "Neos.Flow.object.includeClasses" to add them. You can also use it to remove classes of flow packages from object management as any classes that do not match the given expression(s) are excluded if it is configured for a package.');
            if (!is_array($this->allSettings['Neos']['Flow']['object']['excludeClasses'])) {
                throw new InvalidConfigurationTypeException('The setting "Neos.Flow.object.excludeClasses" is invalid, it must be an array if set. Check the syntax in the YAML file.', 1422357311);
            }

            $excludeClasses = $this->collectExcludedPackages(array_keys($classNames));
            $classNames = $this->applyClassFilterConfiguration($classNames, $excludeClasses, 'exclude');
        }

        $classNames = $this->applyClassFilterConfiguration($classNames, $includeClassesConfiguration);

        return $classNames;
    }

    /**
     * Explodes the regular expressions for package name in excludeClasses configuration to full package names.
     *
     * @param array $registeredPackageKeys
     * @return array
     */
    protected function collectExcludedPackages($registeredPackageKeys)
    {
        $excludeClasses = [];
        foreach ($this->allSettings['Neos']['Flow']['object']['excludeClasses'] as $packageKey => $filterExpressions) {
            if (strpos($packageKey, '*') === false) {
                $excludeClasses[$packageKey] = $filterExpressions;
                continue;
            }
            $packageKey = rtrim($packageKey, '*');
            foreach ($registeredPackageKeys as $registeredPackageKey) {
                if (strpos($registeredPackageKey, $packageKey) === 0) {
                    $excludeClasses[$registeredPackageKey] = $filterExpressions;
                }
            }
        }

        return $excludeClasses;
    }

    /**
     * Filters the classnames available for object management by filter expressions that either include or exclude classes.
     *
     * @param array $classNames All classnames per package
     * @param array $filterConfiguration The filter configuration to apply
     * @param string $includeOrExclude if this is an "include" or "exclude" filter
     * @return array the remaining class
     * @throws InvalidConfigurationTypeException
     */
    protected function applyClassFilterConfiguration($classNames, $filterConfiguration, $includeOrExclude = 'include')
    {
        if (!in_array($includeOrExclude, ['include', 'exclude'])) {
            throw new \InvalidArgumentException('The argument $includeOrExclude must be one of "include" or "exclude", the given value was not allowed.', 1423726253);
        }
        foreach ($filterConfiguration as $packageKey => $filterExpressions) {
            if (!array_key_exists($packageKey, $classNames)) {
                $this->systemLogger->log('The package "' . $packageKey . '" specified in the setting "Neos.Flow.object.' . $includeOrExclude . 'Classes" was either excluded or is not loaded.', LOG_DEBUG);
                continue;
            }
            if (!is_array($filterExpressions)) {
                throw new InvalidConfigurationTypeException('The value given for setting "Neos.Flow.object.' . $includeOrExclude . 'Classes.\'' . $packageKey . '\'" is  invalid. It should be an array of expressions. Check the syntax in the YAML file.', 1422357272);
            }

            $classesForPackageUnderInspection = $classNames[$packageKey];
            $classNames[$packageKey] = [];

            foreach ($filterExpressions as $filterExpression) {
                $classesForPackageUnderInspection = array_filter(
                    $classesForPackageUnderInspection,
                    function ($className) use ($filterExpression, $includeOrExclude) {
                        $match = preg_match('/' . $filterExpression . '/', $className);
                        return ($includeOrExclude === 'include' ? $match === 1 : $match !== 1);
                    }
                );
                if ($includeOrExclude === 'include') {
                    $classNames[$packageKey] = array_merge($classNames[$packageKey], $classesForPackageUnderInspection);
                    $classesForPackageUnderInspection = $classNames[$packageKey];
                } else {
                    $classNames[$packageKey] = $classesForPackageUnderInspection;
                }
            }

            if ($classNames[$packageKey] === []) {
                unset($classNames[$packageKey]);
            }
        }

        return $classNames;
    }

    /**
     * Builds the  objects array which contains information about the registered objects,
     * their scope, class, built method etc.
     *
     * @return array
     */
    protected function buildObjectsArray()
    {
        $objects = [];
        foreach ($this->objectConfigurations as $objectConfiguration) {
            $objectName = $objectConfiguration->getObjectName();
            $objects[$objectName] = [
                'l' => strtolower($objectName),
                's' => $objectConfiguration->getScope(),
                'p' => $objectConfiguration->getPackageKey()
            ];
            if ($objectConfiguration->getClassName() !== $objectName) {
                $objects[$objectName]['c'] = $objectConfiguration->getClassName();
            }
            if ($objectConfiguration->getFactoryObjectName() !== '') {
                $objects[$objectName]['f'] = [
                    $objectConfiguration->getFactoryObjectName(),
                    $objectConfiguration->getFactoryMethodName()
                ];

                $objects[$objectName]['fa'] = [];
                $factoryMethodArguments = $objectConfiguration->getArguments();
                if (count($factoryMethodArguments) > 0) {
                    foreach ($factoryMethodArguments as $index => $argument) {
                        $objects[$objectName]['fa'][$index] = [
                            't' => $argument->getType(),
                            'v' => $argument->getValue()
                        ];
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
    public function getObjectConfigurations()
    {
        return $this->objectConfigurations;
    }

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * This specialized get() method is able to do setter injection for properties
     * defined in the object configuration of the specified object.
     *
     * @param string $objectName The name of the object to return an instance of
     * @return object The object instance
     * @throws \Neos\Flow\ObjectManagement\Exception\CannotBuildObjectException
     * @throws \Neos\Flow\ObjectManagement\Exception\UnresolvedDependenciesException
     * @throws \Neos\Flow\ObjectManagement\Exception\UnknownObjectException
     */
    public function get($objectName)
    {
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
        /** @var Configuration $objectConfiguration */
        $objectConfiguration = $this->objectConfigurations[$objectName];
        /** @var Property $property */
        foreach ($objectConfiguration->getProperties() as $propertyName => $property) {
            if ($property->getAutowiring() !== Configuration::AUTOWIRING_MODE_ON) {
                continue;
            }
            switch ($property->getType()) {
                case Property::PROPERTY_TYPES_STRAIGHTVALUE:
                    $value = $property->getValue();
                    break;
                case Property::PROPERTY_TYPES_CONFIGURATION:
                    $propertyValue = $property->getValue();
                    $value = $this->configurationManager->getConfiguration($propertyValue['type'], $propertyValue['path']);
                    break;
                case Property::PROPERTY_TYPES_OBJECT:
                    $propertyObjectName = $property->getValue();
                    if (!is_string($propertyObjectName)) {
                        throw new Exception\CannotBuildObjectException('The object definition of "' . $objectName . '::' . $propertyName . '" is too complex for the compile time Object Manager. You can only use plain object names, not factories and the like. Check configuration in ' . $this->objectConfigurations[$objectName]->getConfigurationSourceHint() . ' and objects which depend on ' . $objectName . '.', 1297099659);
                    }
                    $value = $this->get($propertyObjectName);
                    break;
                default:
                    throw new Exception\CannotBuildObjectException('Invalid property type.', 1297090029);
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

    /**
     * Shuts down this Object Container by calling the shutdown methods of all
     * object instances which were configured to be shut down.
     *
     * @return void
     */
    public function shutdown()
    {
        $this->callShutdownMethods($this->shutdownObjects);
        $this->callShutdownMethods($this->internalShutdownObjects);
    }
}
