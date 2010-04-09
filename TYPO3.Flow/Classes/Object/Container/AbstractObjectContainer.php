<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object\Container;

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
 * Abstract Object Container, supporting Dependency Injection.
 * Provides functions for the dynamic and the static object container.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class AbstractObjectContainer implements \F3\FLOW3\Object\Container\ObjectContainerInterface {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * An array of all registered objects and some additional information.
	 *
	 * For performance reasons, this array contains a few cryptic key values which
	 * have the following meaning:
	 *
	 * $objects['F3\MyPackage\MyObject'] => array(
	 *    'l' => 'f3\mypackage\myobject',            // the lowercased object name
	 *    's' => self::SCOPE_PROTOTYPE,              // the scope
	 *    'm' => '0045',                             // number of the internal create / inject / recreate method
	 *    'i' => object                              // the instance (singleton & session only)
	 * );
	 *
	 * @var array
	 */
	protected $objects = array();

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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function  __construct() {
		$this->shutdownObjects = new \SplObjectStorage;
	}

	/**
	 * Injects the global settings array, indexed by package key.
	 *
	 * @param array $settings The global settings
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws \InvalidArgumentException if the object name starts with a backslash or is otherwise invalid
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScopeException if the specified object is not configured as Prototype
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function create($objectName) {
		if (isset($this->objects[$objectName]) === FALSE) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1264584588);
		}
		if ($this->objects[$objectName]['s'] !== self::SCOPE_PROTOTYPE) {
			throw new \F3\FLOW3\Object\Exception\WrongScopeException('Object "' . $objectName . '" is of not of scope prototype, but only prototype is supported by create()', 1264584592);
		}
		if (func_num_args() > 1) {
			return call_user_func(array($this, 'c' . $this->objects[$objectName]['m']), array_slice(func_get_args(), 1));
		} else {
			return call_user_func(array($this, 'c' . $this->objects[$objectName]['m']));
		}
	}

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 */
	public function get($objectName) {
		if (isset($this->objects[$objectName]) === FALSE) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1264589155);
		}
		if (isset($this->objects[$objectName]['i'])) {
			return $this->objects[$objectName]['i'];
		}
		if ($this->objects[$objectName]['s'] === self::SCOPE_PROTOTYPE) {
			if (func_num_args() > 1) {
				return call_user_func(array($this, 'c' . $this->objects[$objectName]['m']), array_slice(func_get_args(), 1));
			} else {
				return call_user_func(array($this, 'c' . $this->objects[$objectName]['m']));
			}
		} else {
			$this->objects[$objectName]['i'] = call_user_func(array($this, 'c' . $this->objects[$objectName]['m']));
			return $this->objects[$objectName]['i'];
		}
	}

	/**
	 * Creates an instance of the specified object without calling its constructor.
	 * Subsequently reinjects the object's dependencies.
	 *
	 * This method is mainly used by the persistence and the session sub package.
	 *
	 * Note: The object must be of scope prototype or session which means that
	 *       the object container won't store an instance of the recreated object.
	 *
	 * @param string $objectName Name of the object to create a skeleton for
	 * @return object The recreated, uninitialized (ie. w/ uncalled constructor) object
	 * @throws \F3\FLOW3\Object\Exception\CannotReconstituteObjectException
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function recreate($objectName) {
		if (!isset($this->objects[$objectName])) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Cannot recreate unknown object "' . $objectName . '".' . $hint, 1265297672);
		}
		return call_user_func(array($this, 'r' . $this->objects[$objectName]['m']));
	}

	/**
	 * Sets the instance of the given object
	 *
	 * @param string $objectName The object name
	 * @param object $instance A prebuilt instance
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setInstance($objectName, $instance) {
		if (!isset($this->objects[$objectName])) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Cannot set instance of object "' . $objectName . '" because it is unknown to this Object Container.' . $hint, 1265197803);
		}
		if ($this->objects[$objectName]['s'] === self::SCOPE_PROTOTYPE) {
			throw new \F3\FLOW3\Object\Exception\WrongScopeException('Cannot set instance of object "' . $objectName . '", because it is of scope prototype.', 1265370539);
		}
		$this->objects[$objectName]['i'] = $instance;
	}

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName) {
		$lowerCasedObjectName = strtolower($caseInsensitiveObjectName);
		foreach ($this->objects as $objectName => $information) {
			if ($information['l'] === $lowerCasedObjectName) {
				return $objectName;
			}
		}
		if ($caseInsensitiveObjectName[0] === '\\') {
			throw new \InvalidArgumentException('Object names must not start with a backslash ("' . $caseInsensitiveObjectName . '")', 1270827377);
		}
		return FALSE;
	}

	/**
	 * Returns the object name corresponding to a given class name.
	 *
	 * @param string $className The class name
	 * @return string The object name corresponding to the given class name or FALSE if no object is configured to use that class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjectNameByClassName($className) {
		if (isset($this->objects[$className]) && !isset($this->objects[$className]['c'])) {
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
	 * Returns the scope of the specified object.
	 *
	 * @param string $objectName The object name
	 * @return integer One of the Configuration::SCOPE_ constants
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getScope($objectName) {
		if (isset($this->objects[$objectName]) === FALSE) {
			$hint = ($objectName[0] === '\\') ? ' Hint: You specified an object name with a leading backslash!' : '';
			throw new \F3\FLOW3\Object\Exception\UnknownObjectException('Object "' . $objectName . '" is not registered.' . $hint, 1265367590);
		}
		return $this->objects[$objectName]['s'];
	}

	/**
	 * Internal get() method, specialized on prototypes
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @param array $arguments Arguments which will be passed to the build method
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getPrototype($objectName, array $arguments = array()) {
		return call_user_func(array($this, 'c' . $this->objects[$objectName]['m']), $arguments);
	}

	/**
	 * Internal get() method, specialized on scopes singleton and session
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getSingleton($objectName) {
		if (isset($this->objects[$objectName]['i']) === FALSE) {
			$this->objects[$objectName]['i'] = call_user_func(array($this, 'c' . $this->objects[$objectName]['m']));
		}
		return $this->objects[$objectName]['i'];
	}

}
?>