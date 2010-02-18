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
 * The FLOW3 Persistence Manager interface
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
interface PersistenceManagerInterface {

	/**
	 * Set settings for the persistence layer
	 *
	 * @param array $settings
	 */
	public function setSettings(array $settings);

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 */
	public function initialize();

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\F3\FLOW3\Persistence\QueryInterface $query);

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \F3\FLOW3\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\F3\FLOW3\Persistence\QueryInterface $query);

	/**
	 * Replaces the given object by the second object.
	 *
	 * This method will unregister the existing object at the identity map and
	 * register the new object instead. The existing object must therefore
	 * already be registered at the identity map which is the case for all
	 * reconstituted objects.
	 *
	 * The new object will be identified by the uuid which formerly belonged
	 * to the existing object. The existing object looses its uuid.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @return void
	 * @api
	 */
	public function replaceObject($existingObject, $newObject);

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll();

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object);

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return string The identifier for the object if it is known, or NULL
	 * @api
	 */
	public function getIdentifierByObject($object);

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param string $identifier
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier);

}
?>