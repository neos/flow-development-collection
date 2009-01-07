<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Object;

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
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\TransientRegistry.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * A transient Object Object Cache which provides a transient memory-based
 * registry of objects.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:\F3\FLOW3\Object\TransientRegistry.php 201 2007-03-30 11:18:30Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class TransientRegistry implements \F3\FLOW3\Object\RegistryInterface {

	/**
	 * @var array Location where objects are stored
	 */
	protected $objects = array();

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObject($objectName) {
		if (!$this->objectExists($objectName)) throw new \RuntimeException('Object "' . $objectName . '" does not exist in the object registry.', 1167917198);
		return $this->objects[$objectName];
	}

	/**
	 * Put an object into the registry.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function putObject($objectName, $object) {
		if (!is_string($objectName) || strlen($objectName) == 0) throw new \RuntimeException('No valid object name specified.', 1167919564);
		if (!is_object($object)) throw new \RuntimeException('$object must be of type Object', 1167917199);
		$this->objects[$objectName] = $object;
	}

	/**
	 * Remove an object from the registry.
	 *
	 * @param string objectName Name of the object to remove the object for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeObject($objectName) {
		if (!$this->objectExists($objectName)) throw new \RuntimeException('Object "' . $objectName . '" does not exist in the object registry.', 1167917200);
		unset ($this->objects[$objectName]);
	}

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param string $objectName Name of the object to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function objectExists($objectName) {
		return isset($this->objects[$objectName]);
	}

}

?>