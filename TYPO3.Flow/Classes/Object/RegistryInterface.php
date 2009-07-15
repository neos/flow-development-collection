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
 * @version $Id$
 */

/**
 * Object Object Cache Interface
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id$
 * @author Robert Lemke <robert@typo3.org>
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface RegistryInterface {

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param string $objectName Name of the object to return an object of
	 * @return object The object
	 */
	public function getObject($objectName);

	/**
	 * Put an object into the registry.
	 *
	 * @param string $objectName Name of the object the object is made for
	 * @param object $object The object to store in the registry
	 * @return void
	 */
	public function putObject($objectName, $object);

	/**
	 * Remove an object from the registry.
	 *
	 * @param string $objectName Name of the object to remove the object for
	 * @return void
	 */
	public function removeObject($objectName);

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param string $objectName Name of the object to check for an object
	 * @return boolean TRUE if an object exists, otherwise FALSE
	 */
	public function objectExists($objectName);
}

?>