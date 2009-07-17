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
 * A persistence backend interface
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface BackendInterface {

	/**
	 * Initializes the backend
	 *
	 * @param array $classSchemata the class schemata the backend will be handling
	 * @return void
	 */
	public function initialize(array $classSchemata);

	/**
	 * Sets the aggregate root objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 */
	public function setAggregateRootObjects(\SplObjectStorage $objects);

	/**
	 * Sets the deleted objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 */
	public function setDeletedObjects(\SplObjectStorage $objects);

	/**
	 * Commits the current persistence session
	 *
	 * @return void
	 */
	public function commit();

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted, in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return string The identifier for the object if it is known, or NULL
	 */
	public function getIdentifierByObject($object);

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 */
	public function isNewObject($object);

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
	 */
	public function replaceObject($existingObject, $newObject);
}
?>