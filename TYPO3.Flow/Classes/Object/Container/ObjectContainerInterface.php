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
 * Contract for the Object Container
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface ObjectContainerInterface {

	const SCOPE_PROTOTYPE = 1;
	const SCOPE_SINGLETON = 2;
	const SCOPE_SESSION = 3;

	/**
	 * Injects the global settings array, indexed by package key.
	 *
	 * @param array $settings The global settings
	 * @return void
	 */
	public function injectSettings(array $settings);

	/**
	 * Creates a fresh instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to create
	 * @return object The new object instance
	 * @throws \InvalidArgumentException if the object name starts with a backslash or is otherwise invalid
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 * @throws \F3\FLOW3\Object\Exception\WrongScopeException if the specified object is not configured as Prototype
	 */
	public function create($objectName);

	/**
	 * Returns a fresh or existing instance of the object specified by $objectName.
	 *
	 * @param string $objectName The name of the object to return an instance of
	 * @return object The object instance
	 * @throws \F3\FLOW3\Object\Exception\UnknownObjectException if an object with the given name does not exist
	 */
	public function get($objectName);

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
	 */
	public function recreate($objectName);

	/**
	 * Sets the instance of the given object
	 *
	 * @param string $objectName The object name
	 * @param object $instance A prebuilt instance
	 */
	public function setInstance($objectName, $instance);

	/**
	 * Returns TRUE if an object with the given name has already
	 * been registered.
	 *
	 * @param  string $objectName Name of the object
	 * @return boolean TRUE if the object has been registered, otherwise FALSE
	 */
	public function isRegistered($objectName);

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
	 */
	public function getCaseSensitiveObjectName($caseInsensitiveObjectName);

	/**
	 * Returns the object name corresponding to a given class name.
	 *
	 * @param string $className The class name
	 * @return string The object name corresponding to the given class name or FALSE if no object is configured to use that class
	 */
	public function getObjectNameByClassName($className);

	/**
	 * Returns the implementation class name for the specified object
	 *
	 * @param string $objectName The object name
	 * @return string The class name corresponding to the given object name or FALSE if no such object is registered
	 */
	public function getClassNameByObjectName($objectName);

	/**
	 * Returns the scope of the specified object.
	 *
	 * @param string $objectName The object name
	 * @return integer One of the Configuration::SCOPE_ constants
	 */
	public function getScope($objectName);
}
?>