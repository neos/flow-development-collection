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

use Neos\Flow\ObjectManagement\Configuration\Configuration as ObjectConfiguration;
use Neos\Flow\ObjectManagement\Configuration\ConfigurationArgument as ObjectConfigurationArgument;
use Neos\Flow\Core\ApplicationContext;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Flow\Security\Context;
use Neos\Utility\Arrays;

/**
 * Object Manager
 *
 * @Flow\Scope("singleton")
 * @Flow\Proxy(false)
 */
class ObjectManager implements ObjectManagerInterface
{
    /**
     * The configuration context for this Flow run
     *
     * @var ApplicationContext
     */
    protected $context;

    /**
     * @var ObjectSerializer
     */
    protected $objectSerializer;

    /**
     * An array of settings of all packages, indexed by package key
     *
     * @var array
     */
    protected $allSettings;

    /**
     * @var array
     */
    protected $objects = [];

    /**
     * @var array<DependencyInjection\DependencyProxy>
     */
    protected $dependencyProxies = [];

    /**
     * @var array
     */
    protected $classesBeingInstantiated = [];

    /**
     * @var array
     */
    protected $cachedLowerCasedObjectNames = [];

    /**
     * A SplObjectStorage containing those objects which need to be shutdown when the container
     * shuts down. Each value of each entry is the respective shutdown method name.
     *
     * @var \SplObjectStorage
     */
    protected $shutdownObjects;

    /**
     * A SplObjectStorage containing only those shutdown objects which have been registered for Flow.
     * These shutdown method will be called after all other shutdown methods have been called.
     *
     * @var \SplObjectStorage
     */
    protected $internalShutdownObjects;

    /**
     * Constructor for this Object Container
     *
     * @param ApplicationContext $context The configuration context for this Flow run
     */
    public function __construct(ApplicationContext $context)
    {
        $this->context = $context;
        $this->shutdownObjects = new \SplObjectStorage;
        $this->internalShutdownObjects = new \SplObjectStorage;
    }

    /**
     * Sets the objects array
     *
     * @param array $objects An array of object names and some information about each registered object (scope, lower cased name etc.)
     * @return void
     */
    public function setObjects(array $objects)
    {
        $this->objects = $objects;
        $this->objects[ObjectManagerInterface::class]['i'] = $this;
        $this->objects[get_class($this)]['i'] = $this;
    }

    /**
     * Injects the global settings array, indexed by package key.
     *
     * @param array $settings The global settings
     * @return void
     * @Flow\Autowiring(false)
     */
    public function injectAllSettings(array $settings)
    {
        $this->allSettings = $settings;
    }

    /**
     * Returns the context Flow is running in.
     *
     * @return ApplicationContext The context, for example "Development" or "Production"
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Returns TRUE if an object with the given name is registered
     *
     * @param  string $objectName Name of the object
     * @return boolean TRUE if the object has been registered, otherwise FALSE
     * @throws \InvalidArgumentException
     * @api
     */
    public function isRegistered($objectName)
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
     * Registers the passed shutdown lifecycle method for the given object
     *
     * @param object $object The object to register the shutdown method for
     * @param string $shutdownLifecycleMethodName The method name of the shutdown method to be called
     * @return void
     * @api
     */
    public function registerShutdownObject($object, $shutdownLifecycleMethodName)
    {
        if (strpos(get_class($object), 'Neos\Flow\\') === 0) {
            $this->internalShutdownObjects[$object] = $shutdownLifecycleMethodName;
        } else {
            $this->shutdownObjects[$object] = $shutdownLifecycleMethodName;
        }
    }

    /**
     * Returns a fresh or existing instance of the object specified by $objectName.
     *
     * @param string $objectName The name of the object to return an instance of
     * @return object The object instance
     * @throws Exception\UnknownObjectException if an object with the given name does not exist
     * @throws \InvalidArgumentException
     * @api
     */
    public function get($objectName)
    {
        if (func_num_args() > 1 && isset($this->objects[$objectName]) && $this->objects[$objectName]['s'] !== ObjectConfiguration::SCOPE_PROTOTYPE) {
            throw new \InvalidArgumentException('You cannot provide constructor arguments for singleton objects via get(). If you need to pass arguments to the constructor, define them in the Objects.yaml configuration.', 1298049934);
        }

        if (isset($this->objects[$objectName]['i'])) {
            return $this->objects[$objectName]['i'];
        }

        if (isset($this->objects[$objectName]['f'])) {
            if ($this->objects[$objectName]['s'] === ObjectConfiguration::SCOPE_PROTOTYPE) {
                return $this->buildObjectByFactory($objectName);
            }

            $this->objects[$objectName]['i'] = $this->buildObjectByFactory($objectName);
            return $this->objects[$objectName]['i'];
        }

        $className = $this->getClassNameByObjectName($objectName);
        if ($className === false) {
            $hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
            throw new Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1264589155);
        }

        if (!isset($this->objects[$objectName]) || $this->objects[$objectName]['s'] === ObjectConfiguration::SCOPE_PROTOTYPE) {
            return $this->instantiateClass($className, array_slice(func_get_args(), 1));
        }

        $this->objects[$objectName]['i'] = $this->instantiateClass($className, []);
        return $this->objects[$objectName]['i'];
    }

    /**
     * Returns the scope of the specified object.
     *
     * @param string $objectName The object name
     * @return integer One of the Configuration::SCOPE_ constants
     * @throws Exception\UnknownObjectException
     * @api
     */
    public function getScope($objectName)
    {
        if (!isset($this->objects[$objectName])) {
            $hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
            throw new Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1265367590);
        }
        return $this->objects[$objectName]['s'];
    }

    /**
     * Returns the case sensitive object name of an object specified by a
     * case insensitive object name. If no object of that name exists,
     * FALSE is returned.
     *
     * In general, the case sensitive variant is used everywhere in Flow,
     * however there might be special situations in which the
     * case sensitive name is not available. This method helps you in these
     * rare cases.
     *
     * @param  string $caseInsensitiveObjectName The object name in lower-, upper- or mixed case
     * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
     * @api
     */
    public function getCaseSensitiveObjectName($caseInsensitiveObjectName)
    {
        $lowerCasedObjectName = ltrim(strtolower($caseInsensitiveObjectName), '\\');
        if (isset($this->cachedLowerCasedObjectNames[$lowerCasedObjectName])) {
            return $this->cachedLowerCasedObjectNames[$lowerCasedObjectName];
        }

        foreach ($this->objects as $objectName => $information) {
            if (isset($information['l']) && $information['l'] === $lowerCasedObjectName) {
                $this->cachedLowerCasedObjectNames[$lowerCasedObjectName] = $objectName;
                return $objectName;
            }
        }

        return false;
    }

    /**
     * Returns the object name corresponding to a given class name.
     *
     * @param string $className The class name
     * @return string The object name corresponding to the given class name or FALSE if no object is configured to use that class
     * @throws \InvalidArgumentException
     * @api
     */
    public function getObjectNameByClassName($className)
    {
        if (isset($this->objects[$className]) && (!isset($this->objects[$className]['c']) || $this->objects[$className]['c'] === $className)) {
            return $className;
        }

        foreach ($this->objects as $objectName => $information) {
            if (isset($information['c']) && $information['c'] === $className) {
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
     * @return string The class name corresponding to the given object name or FALSE if no such object is registered
     * @api
     */
    public function getClassNameByObjectName($objectName)
    {
        if (!isset($this->objects[$objectName])) {
            return (class_exists($objectName)) ? $objectName : false;
        }
        return (isset($this->objects[$objectName]['c']) ? $this->objects[$objectName]['c'] : $objectName);
    }

    /**
     * Returns the key of the package the specified object is contained in.
     *
     * @param string $objectName The object name
     * @return string The package key or FALSE if no such object exists
     * @api
     */
    public function getPackageKeyByObjectName($objectName)
    {
        return (isset($this->objects[$objectName]) ? $this->objects[$objectName]['p'] : false);
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
    public function setInstance($objectName, $instance)
    {
        if (!isset($this->objects[$objectName])) {
            if (!class_exists($objectName, false)) {
                throw new Exception\UnknownObjectException('Cannot set instance of object "' . $objectName . '" because the object or class name is unknown to the Object Manager.', 1265370539);
            } else {
                throw new Exception\WrongScopeException('Cannot set instance of class "' . $objectName . '" because no matching object configuration was found. Classes which exist but are not registered are considered to be of scope prototype. However, setInstance() only accepts "session" and "singleton" instances. Check your object configuration and class name spellings.', 12653705341);
            }
        }
        if ($this->objects[$objectName]['s'] === ObjectConfiguration::SCOPE_PROTOTYPE) {
            throw new Exception\WrongScopeException('Cannot set instance of object "' . $objectName . '" because it is of scope prototype. Only session and singleton instances can be set.', 1265370540);
        }
        $this->objects[$objectName]['i'] = $instance;
    }

    /**
     * Returns TRUE if this object manager already has an instance for the specified
     * object.
     *
     * @param string $objectName The object name
     * @return boolean TRUE if an instance already exists
     */
    public function hasInstance($objectName)
    {
        return isset($this->objects[$objectName]['i']);
    }

    /**
     * Returns the instance of the specified object or NULL if no instance has been
     * registered yet.
     *
     * @param string $objectName The object name
     * @return object The object or NULL
     */
    public function getInstance($objectName)
    {
        return isset($this->objects[$objectName]['i']) ? $this->objects[$objectName]['i'] : null;
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
     * @return mixed
     */
    public function getLazyDependencyByHash($hash, &$propertyReferenceVariable)
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
     * @param string &$propertyReferenceVariable A first variable where the dependency needs to be injected into
     * @param string $className Name of the class of the dependency which eventually will be instantiated
     * @param \Closure $builder An anonymous function which creates the instance to be injected
     * @return DependencyProxy
     */
    public function createLazyDependency($hash, &$propertyReferenceVariable, $className, \Closure $builder)
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
    public function forgetInstance($objectName)
    {
        unset($this->objects[$objectName]['i']);
    }

    /**
     * Returns all instances of objects with scope session
     *
     * @return array
     */
    public function getSessionInstances()
    {
        $sessionObjects = [];
        foreach ($this->objects as $information) {
            if (isset($information['i']) && $information['s'] === ObjectConfiguration::SCOPE_SESSION) {
                $sessionObjects[] = $information['i'];
            }
        }
        return $sessionObjects;
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
     * Returns the an array of package settings or a single setting value by the given path.
     *
     * @param array $settingsPath Path to the setting(s) as an array, for example array('Neos', 'Flow', 'persistence', 'backendOptions')
     * @return mixed Either an array of settings or the value of a single setting
     */
    public function getSettingsByPath(array $settingsPath)
    {
        return Arrays::getValueByPath($this->allSettings, $settingsPath);
    }

    /**
     * Returns all current object configurations.
     * For internal use in bootstrap only. Can change anytime.
     *
     * @return array
     */
    public function getAllObjectConfigurations()
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
     */
    protected function buildObjectByFactory($objectName)
    {
        $factory = $this->get($this->objects[$objectName]['f'][0]);
        $factoryMethodName = $this->objects[$objectName]['f'][1];

        $factoryMethodArguments = [];
        foreach ($this->objects[$objectName]['fa'] as $index => $argumentInformation) {
            switch ($argumentInformation['t']) {
                case ObjectConfigurationArgument::ARGUMENT_TYPES_SETTING:
                    $settingPath = explode('.', $argumentInformation['v']);
                    $factoryMethodArguments[$index] = Arrays::getValueByPath($this->allSettings, $settingPath);
                break;
                case ObjectConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE:
                    $factoryMethodArguments[$index] = $argumentInformation['v'];
                break;
                case ObjectConfigurationArgument::ARGUMENT_TYPES_OBJECT:
                    $factoryMethodArguments[$index] = $this->get($argumentInformation['v']);
                break;
            }
        }

        if (count($factoryMethodArguments) === 0) {
            return $factory->$factoryMethodName();
        } else {
            return call_user_func_array([$factory, $factoryMethodName], $factoryMethodArguments);
        }
    }

    /**
     * Speed optimized alternative to ReflectionClass::newInstanceArgs()
     *
     * @param string $className Name of the class to instantiate
     * @param array $arguments Arguments to pass to the constructor
     * @return object The object
     * @throws Exception\CannotBuildObjectException
     * @throws \Exception
     */
    protected function instantiateClass($className, array $arguments)
    {
        if (isset($this->classesBeingInstantiated[$className])) {
            throw new Exception\CannotBuildObjectException('Circular dependency detected while trying to instantiate class "' . $className . '".', 1168505928);
        }

        try {
            switch (count($arguments)) {
                case 0: $object = new $className(); break;
                case 1: $object = new $className($arguments[0]); break;
                case 2: $object = new $className($arguments[0], $arguments[1]); break;
                case 3: $object = new $className($arguments[0], $arguments[1], $arguments[2]); break;
                case 4: $object = new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3]); break;
                case 5: $object = new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]); break;
                case 6: $object = new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]); break;
                default:
                    $class = new \ReflectionClass($className);
                    $object =  $class->newInstanceArgs($arguments);
            }
            unset($this->classesBeingInstantiated[$className]);
            return $object;
        } catch (\Exception $exception) {
            unset($this->classesBeingInstantiated[$className]);
            throw $exception;
        }
    }

    /**
     * Executes the methods of the provided objects.
     *
     * @param \SplObjectStorage $shutdownObjects
     * @return void
     */
    protected function callShutdownMethods(\SplObjectStorage $shutdownObjects)
    {
        foreach ($shutdownObjects as $object) {
            $methodName = $shutdownObjects[$object];
            $object->$methodName();
        }
    }
}
