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

use TYPO3\FLOW3\Object\Configuration\Configuration as ObjectConfiguration;
use TYPO3\FLOW3\Object\Configuration\ConfigurationArgument as ObjectConfigurationArgument;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Object Manager
 *
 * @FLOW3\Scope("singleton")
 * @FLOW3\Proxy(false)
 */
class ObjectManager implements ObjectManagerInterface {

	/**
	 * @var string The configuration context for this FLOW3 run
	 */
	protected $context;

	/**
	 * @var \TYPO3\FLOW3\Session\SessionInterface
	 */
	protected $session;

	/**
	 * @var \TYPO3\FLOW3\Object\ObjectSerializer
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
	protected $objects = array();

	/**
	 * @var array
	 */
	protected $classesBeingInstantiated = array();

	/**
	 * @var array
	 */
	protected $cachedLowerCasedObjectNames = array();

	/**
	 * A SplObjectStorage containing those objects which need to be shutdown when the container
	 * shuts down. Each value of each entry is the respective shutdown method name.
	 *
	 * @var array
	 */
	protected $shutdownObjects;

	/**
	 * Constructor for this Object Container
	 *
	 * @param string $context The configuration context for this FLOW3 run
	 */
	public function __construct($context) {
		$this->context = $context;
		$this->shutdownObjects = new \SplObjectStorage;
	}

	/**
	 * Sets the objects array
	 *
	 * @param array $objects An array of object names and some information about each registered object (scope, lower cased name etc.)
	 * @return void
	 */
	public function setObjects(array $objects) {
		$this->objects = $objects;
		$this->objects['TYPO3\FLOW3\Object\ObjectManagerInterface']['i'] = $this;
		$this->objects[get_class($this)]['i'] = $this;
	}

	/**
	 * Injects the global settings array, indexed by package key.
	 *
	 * @param array $settings The global settings
	 * @return void
	 * @FLOW3\Autowiring(false)
	 */
	public function injectAllSettings(array $settings) {
		$this->allSettings = $settings;
	}

	/**
	 * Returns the context FLOW3 is running in.
	 *
	 * @return string The context, for example "Development" or "Production"
	 */
	public function getContext() {
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
	public function isRegistered($objectName) {
		if (isset($this->objects[$objectName])) {
			return TRUE;
		}

		if ($objectName[0] === '\\') {
			throw new \InvalidArgumentException('Object names must not start with a backslash ("' . $objectName . '")', 1270827335);
		}
		return FALSE;
	}

	/**
	 * Registers the passed shutdown lifecycle method for the given object
	 *
	 * @param object $object The object to register the shutdown method for
	 * @param string $shutdownLifecycleMethodName The method name of the shutdown method to be called
	 * @return void
	 * @api
	 */
	public function registerShutdownObject($object, $shutdownLifecycleMethodName) {
		$this->shutdownObjects[$object] = $shutdownLifecycleMethodName;
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @throws \TYPO3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function get($objectName) {
		if (func_num_args() > 1 && isset($this->objects[$objectName]) && $this->objects[$objectName]['s'] !== ObjectConfiguration::SCOPE_PROTOTYPE) {
			throw new \InvalidArgumentException('You cannot provide constructor arguments for singleton objects via get(). If you need to pass arguments to the constructor, define them in the Objects.yaml configuration.', 1298049934);
		}

		if (isset($this->objects[$objectName]['i'])) {
			return $this->objects[$objectName]['i'];
		}

		if (isset($this->objects[$objectName]['f'])) {
			$this->objects[$objectName]['i'] = $this->buildObjectByFactory($objectName);
			return $this->objects[$objectName]['i'];
		}

		$className = $this->getClassNameByObjectName($objectName);
		if ($className === FALSE) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \TYPO3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1264589155);
		}

		if (!isset($this->objects[$objectName]) || $this->objects[$objectName]['s'] === ObjectConfiguration::SCOPE_PROTOTYPE) {
			return $this->instantiateClass($className, array_slice(func_get_args(), 1));
		}

		$this->objects[$objectName]['i'] = $this->instantiateClass($className, array());
		return $this->objects[$objectName]['i'];
	}

	/**
	 * Returns the scope of the specified object.
	 *
	 * @param string $objectName The object name
	 * @return integer One of the ObjectConfiguration::SCOPE_ constants
	 * @throws \TYPO3\FLOW3\Object\Exception\UnknownObjectException
	 * @api
	 */
	public function getScope($objectName) {
		if (!isset($this->objects[$objectName])) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \TYPO3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1265367590);
		}
		return $this->objects[$objectName]['s'];
	}

	/**
	 * Returns the case sensitive object name of an object specified by a
	 * case insensitive object name. If no object of that name exists,
	 * FALSE is returned.
	 *
	 * In general, the case sensitive variant is used everywhere in FLOW3,
	 * however there might be special situations in which the
	 * case sensitive name is not available. This method helps you in these
	 * rare cases.
	 *
	 * @param  string $caseInsensitiveObjectName The object name in lower-, upper- or mixed case
	 * @return mixed Either the mixed case object name or FALSE if no object of that name was found.
	 * @api
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName) {
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

		return FALSE;
	}

	/**
	 * Returns the object name corresponding to a given class name.
	 *
	 * @param string $className The class name
	 * @return string The object name corresponding to the given class name or FALSE if no object is configured to use that class
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function getObjectNameByClassName($className) {
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

		return FALSE;
	}

	/**
	 * Returns the implementation class name for the specified object
	 *
	 * @param string $objectName The object name
	 * @return string The class name corresponding to the given object name or FALSE if no such object is registered
	 * @api
	 */
	public function getClassNameByObjectName($objectName) {
		if (!isset($this->objects[$objectName])) {
			return (class_exists($objectName)) ? $objectName : FALSE;
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
	public function getPackageKeyByObjectName($objectName) {
		return (isset($this->objects[$objectName]) ? $this->objects[$objectName]['p'] : FALSE);
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
	 * @throws \TYPO3\FLOW3\Object\Exception\WrongScopeException
	 * @throws \TYPO3\FLOW3\Object\Exception\UnknownObjectException
	 */
	public function setInstance($objectName, $instance) {
		if (!isset($this->objects[$objectName])) {
			if (!class_exists($objectName, FALSE)) {
				throw new \TYPO3\FLOW3\Object\Exception\UnknownObjectException('Cannot set instance of object "' . $objectName . '" because the object or class name is unknown to the Object Manager.', 1265370539);
			} else {
				throw new \TYPO3\FLOW3\Object\Exception\WrongScopeException('Cannot set instance of class "' . $objectName . '" because no matching object configuration was found. Classes which exist but are not registered are considered to be of scope prototype. However, setInstance() only accepts "session" and "singleton" instances. Check your object configuration and class name spellings.', 12653705341);
			}
		}
		if ($this->objects[$objectName]['s'] === ObjectConfiguration::SCOPE_PROTOTYPE) {
			throw new \TYPO3\FLOW3\Object\Exception\WrongScopeException('Cannot set instance of object "' . $objectName . '" because it is of scope prototype. Only session and singleton instances can be set.', 1265370540);
		}
		$this->objects[$objectName]['i'] = $instance;
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
	public function forgetInstance($objectName) {
		unset($this->objects[$objectName]['i']);
	}

	/**
	 * Returns all instances of objects with scope session
	 *
	 * @return array
	 */
	public function getSessionInstances() {
		$sessionObjects = array();
		foreach($this->objects as $information) {
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
	public function shutdown() {
		foreach ($this->shutdownObjects as $object) {
			$methodName = $this->shutdownObjects[$object];
			$object->$methodName();
		}
	}

	/**
	 * Returns the an array of package settings or a single setting value by the given path.
	 *
	 * @param array $settingsPath Path to the setting(s) as an array, for example array('TYPO3', 'FLOW3', 'persistence', 'backendOptions')
	 * @return mixed Either an array of settings or the value of a single setting
	 */
	public function getSettingsByPath(array $settingsPath) {
		return \TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->allSettings, $settingsPath);
	}

	/**
	 * Invokes the Factory defined in the object configuration of the specified object in order
	 * to build an instance. Arguments which were defined in the object configuration are
	 * passed to the factory method.
	 *
	 * @param string $objectName Name of the object to build
	 * @return object The built object
	 */
	protected function buildObjectByFactory($objectName) {
		$factory = $this->get($this->objects[$objectName]['f'][0]);
		$factoryMethodName = $this->objects[$objectName]['f'][1];

		$factoryMethodArguments = array();
		foreach ($this->objects[$objectName]['fa'] as $index => $argumentInformation) {
			switch ($argumentInformation['t']) {
				case ObjectConfigurationArgument::ARGUMENT_TYPES_SETTING :
					$settingPath = explode('.', $argumentInformation['v']);
					$factoryMethodArguments[$index] = \TYPO3\FLOW3\Utility\Arrays::getValueByPath($this->allSettings, $settingPath);
				break;
				case ObjectConfigurationArgument::ARGUMENT_TYPES_STRAIGHTVALUE :
					$factoryMethodArguments[$index] = $argumentInformation['v'];
				break;
				case ObjectConfigurationArgument::ARGUMENT_TYPES_OBJECT :
					$factoryMethodArguments[$index] = $this->get($argumentInformation['v']);
				break;
			}
		}

		if (count($factoryMethodArguments) === 0) {
			return $factory->$factoryMethodName();
		} else {
			return call_user_func_array(array($factory, $factoryMethodName), $factoryMethodArguments);
		}
	}

	/**
	 * Speed optimized alternative to ReflectionClass::newInstanceArgs()
	 *
	 * @param string $className Name of the class to instantiate
	 * @param array $arguments Arguments to pass to the constructor
	 * @return object The object
	 * @throws \TYPO3\FLOW3\Object\Exception\CannotBuildObjectException
	 * @throws \Exception
	 */
	protected function instantiateClass($className, array $arguments) {
		if (isset ($this->classesBeingInstantiated[$className])) {
			throw new \TYPO3\FLOW3\Object\Exception\CannotBuildObjectException('Circular dependency detected while trying to instantiate class "' . $className . '".', 1168505928);
		}

		try {
			switch (count($arguments)) {
				case 0: return new $className();
				case 1: return new $className($arguments[0]);
				case 2: return new $className($arguments[0], $arguments[1]);
				case 3: return new $className($arguments[0], $arguments[1], $arguments[2]);
				case 4: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
				case 5: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4]);
				case 6: return new $className($arguments[0], $arguments[1], $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
			}
			$class = new \ReflectionClass($className);
			$object =  $class->newInstanceArgs($arguments);
		} catch (\Exception $exception) {
			unset ($this->classesBeingInstantiated[$className]);
			throw $exception;
		}

		unset ($this->classesBeingInstantiated[$className]);
		return $object;
	}

}
?>