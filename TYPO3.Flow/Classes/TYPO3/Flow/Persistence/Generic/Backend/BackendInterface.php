<?php
namespace TYPO3\Flow\Persistence\Generic\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A persistence backend interface
 *
 * @api
 */
interface BackendInterface {

	/**
	 * Set a PersistenceManager instance.
	 *
	 * @param \TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function setPersistenceManager(\TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager);

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
	 * Sets the changed objects
	 *
	 * @param \SplObjectStorage $entities
	 * @return void
	 */
	public function setChangedEntities(\SplObjectStorage $entities);

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
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\Flow\Persistence\QueryInterface $query);

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\Flow\Persistence\QueryInterface $query);

	/**
	 * Returns the object data for the given identifier.
	 *
	 * @param string $identifier The UUID or Hash of the object
	 * @param string $objectType
	 * @return array
	 * @api
	 */
	public function getObjectDataByIdentifier($identifier, $objectType = NULL);

}
?>