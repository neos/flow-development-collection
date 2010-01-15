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
 * A session Object Object Cache which provides a session-based
 * registry of objects.
 *
 * @version $Id: TransientRegistry.php 2293 2009-05-20 18:14:45Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class SessionRegistry implements \F3\FLOW3\Object\RegistryInterface {

	/**
	 * Location where objects are stored in memory
	 * @var array
	 */
	protected $objects = array();

	/**
	 * The session
	 * @var F3\FLOW3\Session\SessionInterface
	 */
	protected $session;

	/**
	 * The object serializer
	 * @var F3\FLOW3\Object\ObjectSerializer
	 */
	protected $objectSerializer;

	/**
	 * TRUE if the registry is initialized
	 * @var boolean
	 */
	protected $isInitialized = FALSE;

	/**
	 * Injects the session
	 *
	 * @param F3\FLOW3\Session\SessionInterface $session The session implementation
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectSession(\F3\FLOW3\Session\SessionInterface $session) {
		$this->session = $session;
	}

	/**
	 * Injects the object serializer
	 *
	 * @param F3\FLOW3\Object\ObjectSerializer $objectSerializer The object serializer
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function injectObjectSerializer(\F3\FLOW3\Object\ObjectSerializer $objectSerializer) {
		$this->objectSerializer = $objectSerializer;
	}

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getObject($objectName) {
		if (!$this->objectExists($objectName)) throw new \F3\FLOW3\Object\Exception\InvalidObjectNameException('Object "' . $objectName . '" does not exist in the session object registry.', 1246574394);

		return $this->objects[$objectName];
	}

	/**
	 * Put an object into the registry.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function putObject($objectName, $object) {
		if (!is_string($objectName) || strlen($objectName) === 0) throw new \F3\FLOW3\Object\Exception\InvalidObjectNameException('No valid object name specified.', 1246543470);
		if (!is_object($object)) throw new \F3\FLOW3\Object\Exception\InvalidObjectException('$object must be of type Object', 1246544555);

		$this->objects[$objectName] = $object;
	}

	/**
	 * Remove an object from the registry.
	 *
	 * @param string objectName Name of the object to remove the object for
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function removeObject($objectName) {
		if (!$this->objectExists($objectName)) throw new \F3\FLOW3\Object\Exception\InvalidObjectNameException('Object "' . $objectName . '" does not exist in the session object registry.', 1246572692);
		unset ($this->objects[$objectName]);
	}

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param string $objectName Name of the object to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function objectExists($objectName) {
		return isset($this->objects[$objectName]);
	}

	/**
	 * Initializes the registry: loads all objects from the session and reconstitutes them in memory.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function initialize() {
		if ($this->session->hasKey('F3_FLOW3_Object_SessionRegistry') === TRUE) {
			$objectsAsArray = $this->session->getData('F3_FLOW3_Object_SessionRegistry');
			if (is_array($objectsAsArray)) $this->objects = $this->objectSerializer->deserializeObjectsArray($objectsAsArray);
		}
	}

	/**
	 * Stores all registered objects to the session.
	 *
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function writeDataToSession() {
		$objectsAsArray = array();
		$this->objectSerializer->clearState();

		foreach($this->objects as $objectName => $object) {
			$objectsAsArray = array_merge($objectsAsArray, $this->objectSerializer->serializeObjectAsPropertyArray($objectName, $object));
		}

		$this->session->putData('F3_FLOW3_Object_SessionRegistry', $objectsAsArray);
	}
}
?>