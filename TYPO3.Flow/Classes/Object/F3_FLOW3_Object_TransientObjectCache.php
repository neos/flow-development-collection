<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Object;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::TransientObjectCache.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * A transient Object Object Cache which provides a transient memory-based
 * registry of object objects.
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::TransientObjectCache.php 201 2007-03-30 11:18:30Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class TransientObjectCache implements F3::FLOW3::Object::ObjectCacheInterface {

	/**
	 * @var array Location where object objects are stored
	 */
	protected $objects = array();

	/**
	 * Returns an object object from the cache. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObject($objectName) {
		if (!$this->objectExists($objectName)) throw new RuntimeException('Object "' . $objectName . '" does not exist in the object object cache.', 1167917198);
		return $this->objects[$objectName];
	}

	/**
	 * Put an object object into the cache.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object object to store in the cache
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function putObject($objectName, $object) {
		if (!is_string($objectName) || strlen($objectName) == 0) throw new RuntimeException('No valid object name specified.', 1167919564);
		if (!is_object($object)) throw new RuntimeException('$object must be of type Object', 1167917199);
		$this->objects[$objectName] = $object;
	}

	/**
	 * Remove an object object from the cache.
	 *
	 * @param string objectName Name of the object to remove the object for
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function removeObject($objectName) {
		if (!$this->objectExists($objectName)) throw new RuntimeException('Object "' . $objectName . '" does not exist in the object object cache.', 1167917200);
		unset ($this->objects[$objectName]);
	}

	/**
	 * Checks if an object of the given object already exists in the object cache.
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