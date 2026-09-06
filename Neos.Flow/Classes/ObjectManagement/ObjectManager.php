<?php

declare(strict_types=1);

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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Configuration\ConfigurationManager;
use Neos\Flow\Configuration\Exception\InvalidConfigurationTypeException;
use Neos\Flow\Core\ApplicationContext;
use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument as ObjectConfigurationArgument;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Security\Context;

/**
 * Object Manager
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ObjectManager implements ObjectManagerInterface
{
    protected const KEY_INSTANCE = 'i';
    protected const KEY_SCOPE = 's';
    protected const KEY_FACTORY = 'f';
    protected const KEY_FACTORY_ARGUMENTS = 'fa';
    protected const KEY_CONSTRUCTOR_ARGUMENTS = 'ca';
    protected const KEY_AUTOWIRING_MODE = 'wm';
    protected const KEY_ARGUMENT_TYPE = 't';
    protected const KEY_ARGUMENT_VALUE = 'v';
    protected const KEY_CLASS_NAME = 'c';
    protected const KEY_PACKAGE = 'p';
    protected const KEY_LOWERCASE_NAME = 'l';

    /**
     * The configuration context for this Flow run
     *
     * @var ApplicationContext
     */
    protected ApplicationContext $context;

    /**
     * @var array<string, array<string,mixed>>
     */
    protected array $objects = [];

    /**
     * @var array<DependencyInjection\DependencyProxy>
     */
    protected array $dependencyProxies = [];

    /**
     * @var array<string, bool>
     */
    protected array $classesBeingInstantiated = [];

    /**
     * @var array<string, string>
     */
    protected array $cachedLowerCasedObjectNames = [];

    /**
     * A SplObjectStorage containing those objects which need to be shutdown when the container
     * shuts down. Each value of each entry is the respective shutdown method name.
     *
     * @var \SplObjectStorage
     */
    protected \SplObjectStorage $shutdownObjects;

    /**
     * A SplObjectStorage containing only those shutdown objects which have been registered for Flow.
     * These shutdown method will be called after all other shutdown methods have been called.
     *
     * @var \SplObjectStorage
     */
    protected \SplObjectStorage $internalShutdownObjects;

    /**
     * Constructor for this Object Container
     *
     * @param ApplicationContext $context The configuration context for this Flow run
     */
    public function __construct(ApplicationContext $context)
    {
        $this->context = $context;
        $this->shutdownObjects = new \SplObjectStorage();
        $this->internalShutdownObjects = new \SplObjectStorage();
    }

    /**
     * Sets the objects array
     *
     * @param array<string, array> $objects An array of object names and some information about each registered object (scope, lower cased name etc.)
     * @return void
     */
    public function setObjects(array $objects): void
    {
        $this->objects = $objects;
        $this->objects[ObjectManagerInterface::class][self::KEY_INSTANCE] = $this;
        $this->objects[get_class($this)][self::KEY_INSTANCE] = $this;
    }

    /**
     * Returns the context Flow is running in.
     *
     * @return ApplicationContext The context, for example "Development" or "Production"
     */
    public function getContext(): ApplicationContext
    {
        return $this->context;
    }

    /**
     * Returns true if an object with the given name is registered
     *
     * @param  string $objectName Name of the object
     * @return boolean true if the object has been registered, otherwise false
     * @throws \InvalidArgumentException
     * @api
     */
    public function isRegistered($objectName): bool
    {
        if (isset($this->objects[$objectName])) {
            return true;
        }

        if ($objectName[0] === '\\') {
            throw new \InvalidArgumentException('Object names must not start with a backslash ("' . $objectName . '")', 1270827335);
        }
        return false;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $objectName
     * @return bool
     */
    public function has($objectName): bool
    {
        return $this->isRegistered($objectName);
    }

    /**
     * Registers the passed shutdown lifecycle method for the given object
     *
     * @param object $object The object to register the shutdown method for
     * @param string $shutdownLifecycleMethodName The method name of the shutdown method to be called
     * @return void
     * @api
     */
    public function registerShutdownObject($object, $shutdownLifecycleMethodName): void
    {
        if (str_starts_with(get_class($object), 'Neos\Flow\\')) {
            $this->internalShutdownObjects[$object] = $shutdownLifecycleMethodName;
        } else {
            $this->shutdownObjects[$object] = $shutdownLifecycleMethodName;
        }
    }

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * @template T of object
     * @param class-string<T>|string $objectName The name of the object to return an instance of
     * @param mixed ...$constructorArguments Any number of arguments that should be passed to the constructor of the object
     * @phpstan-return ($objectName is class-string<T> ? T : object) The object instance
     * @return T The object instance
     * @throws Exception\CannotBuildObjectException
     * @throws Exception\UnknownObjectException if an object with the given name does not exist
     * @throws \InvalidArgumentException
     * @throws InvalidConfigurationTypeException
     * @api
     */
    public function get($objectName, ...$constructorArguments): object
    {
        if (!empty($constructorArguments) && isset($this->objects[$objectName]) && $this->objects[$objectName][self::KEY_SCOPE] !== ObjectConfiguration::SCOPE_PROTOTYPE) {
            throw new \InvalidArgumentException('You cannot provide constructor arguments for singleton objects via get(). If you need to pass arguments to the constructor, define them in the Objects.yaml configuration.', 1298049934);
        }

        if (isset($this->objects[$objectName][self::KEY_INSTANCE])) {
            return $this->objects[$objectName][self::KEY_INSTANCE];
        }

        if (isset($this->objects[$objectName][self::KEY_FACTORY])) {
            if ($this->objects[$objectName][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_PROTOTYPE) {
                return $this->buildObjectByFactory($objectName);
            }

            $this->objects[$objectName][self::KEY_INSTANCE] = $this->buildObjectByFactory($objectName);
            return $this->objects[$objectName][self::KEY_INSTANCE];
        }

        $className = $this->getClassNameByObjectName($objectName);
        if ($className === false) {
            $hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
            throw new Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1264589155);
        }

        // Someone might have requested the implementation class directly, in that case we want to reuse that instance when requesting the object.
        if (
            $objectName !== $className
            && isset($this->objects[$className])
            // this condition is important as otherwise you could run into trouble with virtual objects where the class is not declared singleton
            && $this->objects[$className][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_SINGLETON
            && isset($this->objects[$className][self::KEY_INSTANCE])
        ) {
            $this->objects[$objectName][self::KEY_INSTANCE] = $this->objects[$className][self::KEY_INSTANCE];
            return $this->objects[$objectName][self::KEY_INSTANCE];
        }

        if (!isset($this->objects[$objectName]) || $this->objects[$objectName][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_PROTOTYPE) {
            return $this->instantiateClass($className, $this->autowireConstructorArguments($objectName, $className, $constructorArguments));
        }

        return $this->instantiateRegisteredObject($objectName, $className);
    }

    /**
     * Creates the instance of a singleton or session scoped object and registers it *before* its
     * constructor arguments are resolved and its constructor is called.
     *
     * Objects of these scopes may depend on each other, directly or through a chain of other
     * objects. Registering the instance first makes sure that a re-entrant call to get() for the
     * same object – triggered while resolving its dependencies – returns the instance which is
     * currently being built, instead of trying to build a second one and running into the
     * circular dependency guard of instantiateClass().
     *
     * @param string $objectName Name of the object to instantiate
     * @param class-string $className Name of the class implementing the object
     * @return object The object
     * @throws Exception\CannotBuildObjectException
     * @throws \Throwable
     */
    protected function instantiateRegisteredObject(string $objectName, string $className): object
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->isInternal()) {
            $instance = $this->instantiateClass($className, $this->autowireConstructorArguments($objectName, $className, []));
            $this->registerInstance($objectName, $className, $instance);
            return $instance;
        }

        $instance = $classReflection->newInstanceWithoutConstructor();
        $this->registerInstance($objectName, $className, $instance);
        try {
            $constructorArguments = $this->autowireConstructorArguments($objectName, $className, []);
            if ($classReflection->hasMethod('__construct')) {
                $instance->__construct(...$constructorArguments);
            }
        } catch (\Throwable $throwable) {
            $this->unregisterInstance($objectName, $className);
            throw $throwable;
        }
        return $instance;
    }

    /**
     * Registers the given instance for the object name and – if the implementation class is
     * registered as a singleton of its own – for the class name as well, so that requesting
     * the class directly returns the same instance.
     *
     * @param class-string $className
     */
    protected function registerInstance(string $objectName, string $className, object $instance): void
    {
        $this->objects[$objectName][self::KEY_INSTANCE] = $instance;
        if ($this->classIsRegisteredAsSingleton($objectName, $className)) {
            $this->objects[$className][self::KEY_INSTANCE] = $instance;
        }
    }

    /**
     * @param class-string $className
     */
    protected function unregisterInstance(string $objectName, string $className): void
    {
        unset($this->objects[$objectName][self::KEY_INSTANCE]);
        if ($this->classIsRegisteredAsSingleton($objectName, $className)) {
            unset($this->objects[$className][self::KEY_INSTANCE]);
        }
    }

    /**
     * @param class-string $className
     */
    protected function classIsRegisteredAsSingleton(string $objectName, string $className): bool
    {
        return $objectName !== $className
            && isset($this->objects[$className])
            && $this->objects[$className][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_SINGLETON;
    }

    /**
     * Resolves the constructor arguments configured for the given object – by object name and,
     * if different, by class name – which were not passed explicitly.
     *
     * @param class-string $className
     * @param array<mixed> $constructorArguments Arguments which were passed explicitly
     * @return array<mixed> The complete list of constructor arguments
     */
    protected function autowireConstructorArguments(string $objectName, string $className, array $constructorArguments): array
    {
        if (isset($this->objects[$objectName][self::KEY_CONSTRUCTOR_ARGUMENTS])) {
            $constructorArguments = $this->autowireArguments($this->objects[$objectName][self::KEY_CONSTRUCTOR_ARGUMENTS], $constructorArguments);
        }
        if ($objectName !== $className && isset($this->objects[$className][self::KEY_CONSTRUCTOR_ARGUMENTS])) {
            $constructorArguments = $this->autowireArguments($this->objects[$className][self::KEY_CONSTRUCTOR_ARGUMENTS], $constructorArguments);
        }
        return $constructorArguments;
    }

    /**
     * Returns the scope of the specified object.
     *
     * @param string $objectName The object name
     * @return integer One of the Configuration::SCOPE_ constants
     * @throws Exception\UnknownObjectException
     * @api
     */
    public function getScope($objectName): int
    {
        if (!isset($this->objects[$objectName])) {
            $hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
            throw new Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1265367590);
        }
        return $this->objects[$objectName][self::KEY_SCOPE];
    }

    /**
     * Returns the case sensitive object name of an object specified by a
     * case insensitive object name. If no object of that name exists,
     * false is returned.
     *
     * In general, the case sensitive variant is used everywhere in Flow,
     * however there might be special situations in which the
     * case sensitive name is not available. This method helps you in these
     * rare cases.
     *
     * @param  string $caseInsensitiveObjectName The object name in lower-, upper- or mixed case
     * @return string|null Either the mixed case object name or false if no object of that name was found.
     * @internal
     */
    public function getCaseSensitiveObjectName($caseInsensitiveObjectName): ?string
    {
        $lowerCasedObjectName = strtolower(ltrim($caseInsensitiveObjectName, '\\'));
        if (isset($this->cachedLowerCasedObjectNames[$lowerCasedObjectName])) {
            return $this->cachedLowerCasedObjectNames[$lowerCasedObjectName];
        }

        foreach ($this->objects as $objectName => $information) {
            if (isset($information[self::KEY_LOWERCASE_NAME]) && $information[self::KEY_LOWERCASE_NAME] === $lowerCasedObjectName) {
                $this->cachedLowerCasedObjectNames[$lowerCasedObjectName] = $objectName;
                return $objectName;
            }
        }

        return null;
    }

    /**
     * Returns the object name corresponding to a given class name.
     *
     * @param string $className The class name
     *
     * @return string|false The object name corresponding to the given class name or false if no object is configured to use that class
     *
     * @throws \InvalidArgumentException
     *
     * @api
     */
    public function getObjectNameByClassName($className): string|false
    {
        if (isset($this->objects[$className]) && (!isset($this->objects[$className][self::KEY_CLASS_NAME]) || $this->objects[$className][self::KEY_CLASS_NAME] === $className)) {
            return $className;
        }

        foreach ($this->objects as $objectName => $information) {
            if (isset($information[self::KEY_CLASS_NAME]) && $information[self::KEY_CLASS_NAME] === $className) {
                return $objectName;
            }
        }
        if ($className[0] === '\\') {
            throw new \InvalidArgumentException('Class names must not start with a backslash ("' . $className . '")', 1270826088);
        }

        return false;
    }

    /**
     * Returns the implementation class name for the specified object
     *
     * @param string $objectName The object name
     * @return class-string|false The class name corresponding to the given object name or false if no such object is registered
     * @api
     */
    public function getClassNameByObjectName($objectName): string|false
    {
        if (!isset($this->objects[$objectName])) {
            return class_exists($objectName) ? $objectName : false;
        }
        return $this->objects[$objectName][self::KEY_CLASS_NAME] ?? $objectName;
    }

    /**
     * Returns the key of the package the specified object is contained in.
     *
     * @param string $objectName The object name
     * @return string|false The package key or false if no such object exists
     * @internal
     */
    public function getPackageKeyByObjectName($objectName): string|false
    {
        return (isset($this->objects[$objectName]) ? $this->objects[$objectName][self::KEY_PACKAGE] : false);
    }

    /**
     * Sets the instance of the given object
     *
     * Objects of scope sessions are assumed to be the real session object, not the
     * lazy loading proxy.
     *
     * @param string $objectName The object name
     * @param object $instance A prebuilt instance
     * @return void
     * @throws Exception\WrongScopeException
     * @throws Exception\UnknownObjectException
     */
    public function setInstance($objectName, $instance): void
    {
        if (!isset($this->objects[$objectName])) {
            if (!class_exists($objectName, false)) {
                throw new Exception\UnknownObjectException('Cannot set instance of object "' . $objectName . '" because the object or class name is unknown to the Object Manager.', 1265370539);
            }

            throw new Exception\WrongScopeException('Cannot set instance of class "' . $objectName . '" because no matching object configuration was found. Classes which exist but are not registered are considered to be of scope prototype. However, setInstance() only accepts "session" and "singleton" instances. Check your object configuration and class name spellings.', 12653705341);
        }
        if ($this->objects[$objectName][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_PROTOTYPE) {
            throw new Exception\WrongScopeException('Cannot set instance of object "' . $objectName . '" because it is of scope prototype. Only session and singleton instances can be set.', 1265370540);
        }
        $this->objects[$objectName][self::KEY_INSTANCE] = $instance;
    }

    /**
     * Returns true if this object manager already has an instance for the specified
     * object.
     *
     * @param string $objectName The object name
     * @return boolean true if an instance already exists
     */
    public function hasInstance(string $objectName): bool
    {
        return isset($this->objects[$objectName][self::KEY_INSTANCE]);
    }

    /**
     * Returns the instance of the specified object or NULL if no instance has been
     * registered yet.
     *
     * @template T of object
     * @param class-string<T>|string $objectName The object name
     * @phpstan-return ($objectName is class-string<T> ? T|null : object|null) The object instance or null
     * @return T|null The object instance or null
     */
    public function getInstance(string $objectName): ?object
    {
        return $this->objects[$objectName][self::KEY_INSTANCE] ?? null;
    }

    /**
     * This method is used internally to retrieve either an actual (singleton) instance
     * of the specified dependency or, if no instance exists yet, a Dependency Proxy
     * object which automatically triggers the creation of an instance as soon as
     * it is used the first time.
     *
     * Internally used by the injectProperties method of generated proxy classes.
     *
     * @param string $hash
     * @param mixed &$propertyReferenceVariable Reference of the variable to inject into once the proxy is activated
     * @return object|null
     */
    public function getLazyDependencyByHash(string $hash, mixed &$propertyReferenceVariable): ?object
    {
        if (!isset($this->dependencyProxies[$hash])) {
            return null;
        }
        $this->dependencyProxies[$hash]->_addPropertyVariable($propertyReferenceVariable);
        return $this->dependencyProxies[$hash];
    }

    /**
     * Creates a new DependencyProxy class for a dependency built through code
     * identified through "hash" for a dependency of class $className. The
     * closure in $builder contains code for actually creating the dependency
     * instance once it needs to be materialized.
     *
     * Internally used by the injectProperties method of generated proxy classes.
     *
     * @param string $hash An md5 hash over the code needed to actually build the dependency instance
     * @param mixed &$propertyReferenceVariable A first variable where the dependency needs to be injected into
     * @param string $className Name of the class of the dependency which eventually will be instantiated
     * @param \Closure $builder An anonymous function which creates the instance to be injected
     * @return DependencyProxy
     */
    public function createLazyDependency(string $hash, mixed &$propertyReferenceVariable, string $className, \Closure $builder): DependencyProxy
    {
        $this->dependencyProxies[$hash] = new DependencyProxy($className, $builder);
        $this->dependencyProxies[$hash]->_addPropertyVariable($propertyReferenceVariable);
        return $this->dependencyProxies[$hash];
    }


    /**
     * Unsets the instance of the given object
     *
     * If run during standard runtime, the whole application might become unstable
     * because certain parts might already use an instance of this object. Therefore
     * this method should only be used in a setUp() method of a functional test case.
     *
     * @param string $objectName The object name
     * @return void
     */
    public function forgetInstance($objectName): void
    {
        unset($this->objects[$objectName][self::KEY_INSTANCE]);
        $className = $this->objects[$objectName][self::KEY_CLASS_NAME] ?? null;
        if ($className !== null && $className !== $objectName) {
            unset($this->objects[$objectName][self::KEY_INSTANCE]);
        }
    }

    /**
     * Returns all instances of objects with scope session
     *
     * @return array
     */
    public function getSessionInstances(): array
    {
        $sessionObjects = [];
        foreach ($this->objects as $information) {
            if (isset($information[self::KEY_INSTANCE]) && $information[self::KEY_SCOPE] === ObjectConfiguration::SCOPE_SESSION) {
                $sessionObjects[] = $information[self::KEY_INSTANCE];
            }
        }
        return $sessionObjects;
    }

    /**
     * Shuts down this Object Container by calling the shutdown methods of all
     * object instances which were configured to be shut down.
     *
     * @return void
     * @throws Exception\CannotBuildObjectException
     * @throws Exception\UnknownObjectException
     * @throws InvalidConfigurationTypeException
     * @throws \Exception
     */
    public function shutdown(): void
    {
        $this->callShutdownMethods($this->shutdownObjects);

        $securityContext = $this->get(Context::class);
        /** @var Context $securityContext */
        if ($securityContext->isInitialized()) {
            $this->get(Context::class)->withoutAuthorizationChecks(function () {
                $this->callShutdownMethods($this->internalShutdownObjects);
            });
        } else {
            $this->callShutdownMethods($this->internalShutdownObjects);
        }
    }

    /**
     * Returns all current object configurations.
     * For internal use in bootstrap only. Can change anytime.
     *
     * @return array
     */
    public function getAllObjectConfigurations(): array
    {
        return $this->objects;
    }

    /**
     * Invokes the Factory defined in the object configuration of the specified object in order
     * to build an instance. Arguments which were defined in the object configuration are
     * passed to the factory method.
     *
     * @param string $objectName Name of the object to build
     * @return object The built object
     * @throws Exception\UnknownObjectException
     * @throws InvalidConfigurationTypeException
     * @throws Exception\CannotBuildObjectException
     */
    protected function buildObjectByFactory(string $objectName): object
    {
        $factory = $this->objects[$objectName][self::KEY_FACTORY][0] ? $this->get($this->objects[$objectName][self::KEY_FACTORY][0]) : null;
        $factoryMethodName = $this->objects[$objectName][self::KEY_FACTORY][1];

        $factoryMethodArguments = [];
        foreach ($this->objects[$objectName][self::KEY_FACTORY_ARGUMENTS] as $index => $argumentInformation) {
            switch ($argumentInformation[self::KEY_ARGUMENT_TYPE]) {
                case ObjectConfigurationArgument::ARGUMENT_TYPES_SETTING:
                    $factoryMethodArguments[$index] = $this->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $argumentInformation[self::KEY_ARGUMENT_VALUE]);
                    break;
                case ObjectConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
                    $factoryMethodArguments[$index] = $argumentInformation[self::KEY_ARGUMENT_VALUE];
                    break;
                case ObjectConfigurationArgument::ARGUMENT_TYPES_OBJECT:
                    $factoryMethodArguments[$index] = $this->get($argumentInformation[self::KEY_ARGUMENT_VALUE]);
                    break;
            }
        }

        if ($factory !== null) {
            return $factory->$factoryMethodName(...$factoryMethodArguments);
        }

        return $factoryMethodName(...$factoryMethodArguments);
    }

    /**
     * Speed optimized alternative to ReflectionClass::newInstanceArgs()
     *
     * @param string $className Name of the class to instantiate
     * @param array<mixed> $arguments Arguments to pass to the constructor
     * @return object The object
     * @throws Exception\CannotBuildObjectException
     * @throws \Exception
     */
    protected function instantiateClass(string $className, array $arguments): object
    {
        if (isset($this->classesBeingInstantiated[$className])) {
            throw new Exception\CannotBuildObjectException('Circular dependency detected while trying to instantiate class "' . $className . '".', 1168505928);
        }

        $this->classesBeingInstantiated[$className] = true;
        try {
            return new $className(...$arguments);
        } catch (\Exception $exception) {
            throw $exception;
        } finally {
            unset($this->classesBeingInstantiated[$className]);
        }
    }

    protected function autowireArguments($configuration, $existingArguments): array
    {
        foreach ($configuration as $index => $argument) {
            if (isset($existingArguments[$index - 1])) {
                continue;
            }
            $existingArguments[$index - 1] = $this->getConfiguredArgument($argument[self::KEY_ARGUMENT_TYPE], $argument[self::KEY_ARGUMENT_VALUE]);
        }

        return $existingArguments;
    }

    protected function getConfiguredArgument(int $argumentType, mixed $argumentValue): mixed
    {
        if ($argumentType === ObjectConfigurationArgument::ARGUMENT_TYPES_SETTING) {
            return $this->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $argumentValue);
        }

        if ($argumentType === ObjectConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE) {
            return $argumentValue;
        }

        if ($argumentType !== ObjectConfigurationArgument::ARGUMENT_TYPES_OBJECT) {
            return null;
        }

        if (!$argumentValue instanceof ObjectConfiguration) {
            if (str_contains($argumentValue, '.')) {
                $argumentValue = $this->get(ConfigurationManager::class)->getConfiguration(ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, $argumentValue);
            }
            $argumentObjectConfiguration = $this->objects[$argumentValue] ?? null;
            if ($argumentObjectConfiguration === null) {
                throw new \RuntimeException('broken');
            }
            return $this->get($argumentValue);
        }

        if ($argumentValue->getFactoryObjectName()) {
            $methodName = $argumentValue->getFactoryMethodName();
            return $this->get($argumentValue->getFactoryObjectName())->$methodName(...$this->buildMethodArguments($argumentValue->getFactoryArguments()));
        }

        $argumentValueObjectName = $argumentValue->getObjectName();
        if ($this->objects[$argumentValueObjectName][self::KEY_SCOPE] === ObjectConfiguration::SCOPE_PROTOTYPE) {
            return new $argumentValueObjectName(...$this->buildMethodArguments($argumentValue->getArguments()));
        }

        return $this->get($argumentValueObjectName);
    }

    /**
     * @param array<ConfigurationArgument|null> $argumentConfigurations
     * @return array<mixed> the (auto)wired arguments
     */
    protected function buildMethodArguments(array $argumentConfigurations): array
    {
        $result = [];
        foreach ($argumentConfigurations as $argument) {
            if ($argument === null || $argument->getAutowiring() === 0) {
                continue;
            }
            $result[] = $this->getConfiguredArgument($argument->getType(), $argument->getValue());
        }
        return $result;
    }

    /**
     * Executes the methods of the provided objects.
     *
     * @param \SplObjectStorage $shutdownObjects
     * @return void
     */
    protected function callShutdownMethods(\SplObjectStorage $shutdownObjects): void
    {
        foreach ($shutdownObjects as $object) {
            $methodName = $shutdownObjects[$object];
            $object->$methodName();
        }
    }
}
