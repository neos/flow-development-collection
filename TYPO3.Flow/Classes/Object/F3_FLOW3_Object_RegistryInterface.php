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
 * @version $Id:F3::FLOW3::Object::RegistryInterface.php 201 2007-03-30 11:18:30Z robert $
 */

/**
 * Object Object Cache Interface
 *
 * @package FLOW3
 * @subpackage Object
 * @version $Id:F3::FLOW3::Object::RegistryInterface.php 201 2007-03-30 11:18:30Z robert $
 * @author Robert Lemke <robert@typo3.org>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
interface RegistryInterface {

	/**
	 * Returns an object from the registry. If an instance of the required
	 * object does not exist yet, an exception is thrown.
	 *
	 * @param  string		$objectName: Name of the object to return an object of
	 * @return object		The object
	 */
	public function getObject($objectName);

	/**
	 * Put an object into the registry.
	 *
	 * @param  string		$objectName: Name of the object the object is made for
	 * @param  object		$object: The object to store in the registry
	 * @return void
	 */
	public function putObject($objectName, $object);

	/**
	 * Remove an object from the registry.
	 *
	 * @param  string		$objectName: Name of the object to remove the object for
	 * @return void
	 */
	public function removeObject($objectName);

	/**
	 * Checks if an object of the given object already exists in the object registry.
	 *
	 * @param  string		$objectName: Name of the object to check for an object
	 * @return boolean		TRUE if an object exists, otherwise FALSE
	 */
	public function objectExists($objectName);
}

?>