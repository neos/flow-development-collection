<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Backend;

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
 * @api
 */
interface BackendInterface {

	/**
	 * Set a PersistenceManager instance.
	 *
	 * @param \F3\FLOW3\Persistence\PersistenceManager $persistenceManager
	 * @return void
	 */
	public function setPersistenceManager($persistenceManager);

	/**
	 * Initializes the backend
	 *
	 * @param array $options
	 * @return void
	 * @api
	 */
	public function initialize(array $options);

	/**
	 * Sets the aggregate root objects
	 *
	 * @param \SplObjectStorage $objects
	 * @return void
	 * @api
	 */
	public function setAggregateRootObjects(\SplObjectStorage $objects);

	/**
	 * Sets the deleted entities
	 *
	 * @param \SplObjectStorage $entities
	 * @return void
	 * @api
	 */
	public function setDeletedEntities(\SplObjectStorage $entities);

	/**
	 * Commits the current persistence session
	 *
	 * @return void
	 * @api
	 */
	public function commit();

	/**
	 * Returns the number of items matching the query.
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
	 * Returns the object data for the given identifier.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @return array
	 * @api
	 */
	public function getObjectDataByIdentifier($identifier);

}
?>