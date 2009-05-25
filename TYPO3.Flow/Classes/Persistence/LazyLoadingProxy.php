<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

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
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class LazyLoadingProxy {

	/**
	 * The object this property is contained in.
	 *
	 * @var object
	 */
	private $F3_FLOW3_Persistence_LazyLoadingProxy_parent;

	/**
	 * The name of the property represented by this proxy.
	 *
	 * @var string
	 */
	private $F3_FLOW3_Persistence_LazyLoadingProxy_propertyName;

	/**
	 * The closure to invoke in case the object represented by this proxy is
	 * really needed.
	 *
	 * @var \Closure
	 */
	private $F3_FLOW3_Persistence_LazyLoadingProxy_population;

	/**
	 * Constructs this proxy instance.
	 *
	 * @param object $parent The object instance this proxy is part of
	 * @param string $propertyName The name of the proxied property in it's parent
	 * @param \Closure $population The closure to invoke in case the object represented by this proxy is really needed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __construct($parent, $propertyName, \Closure $population) {
		$this->F3_FLOW3_Persistence_LazyLoadingProxy_parent = $parent;
		$this->F3_FLOW3_Persistence_LazyLoadingProxy_propertyName = $propertyName;
		$this->F3_FLOW3_Persistence_LazyLoadingProxy_population = $population;
	}

	/**
	 * Populate this proxy by asking the $population closure.
	 *
	 * @return object The instance (hopefully) returned by the $population closure
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function _loadRealInstance() {
		$realInstance = $this->F3_FLOW3_Persistence_LazyLoadingProxy_population->__invoke();
		$this->F3_FLOW3_Persistence_LazyLoadingProxy_parent->FLOW3_AOP_Proxy_setProperty($this->F3_FLOW3_Persistence_LazyLoadingProxy_propertyName, $realInstance);
		return $realInstance;
	}

	/**
	 * Magic method call implementation.
	 *
	 * @param string $methodName The name of the property to get
	 * @param array $arguments The arguments given to the call
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __call($methodName, $arguments) {
		$realInstance = $this->_loadRealInstance();
		return call_user_func_array(array($realInstance, $methodName), $arguments);
	}

	/**
	 * Magic get call implementation.
	 *
	 * @param string $propertyName The name of the property to get
	 * @return mixed
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __get($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return $realInstance->$propertyName;
	}

	/**
	 * Magic set call implementation.
	 *
	 * @param string $propertyName The name of the property to set
	 * @param mixed $value The value for the property to set
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __set($propertyName, $value) {
		$realInstance = $this->_loadRealInstance();
		$realInstance->$propertyName = $value;
	}

	/**
	 * Magic isset call implementation.
	 *
	 * @param string $propertyName The name of the property to check
	 * @return boolean
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __isset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		return isset($realInstance->$propertyName);
	}

	/**
	 * Magic unset call implementation.
	 *
	 * @param string $propertyName The name of the property to unset
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @internal
	 */
	public function __unset($propertyName) {
		$realInstance = $this->_loadRealInstance();
		unset($realInstance->$propertyName);
	}
}
?>