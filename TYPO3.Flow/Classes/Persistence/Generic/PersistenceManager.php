<?php
namespace TYPO3\FLOW3\Persistence\Generic;

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
 * The generic FLOW3 Persistence Manager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class PersistenceManager extends \TYPO3\FLOW3\Persistence\AbstractPersistenceManager {

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Backend\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * Create new instance
	 */
	public function __construct() {
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQueryFactory(\TYPO3\FLOW3\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Injects the data mapper
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(\TYPO3\FLOW3\Persistence\Generic\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
		$this->dataMapper->setPersistenceManager($this);
	}

	/**
	 * Injects the backend to use
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\Backend\BackendInterface $backend the backend to use for persistence
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectBackend(\TYPO3\FLOW3\Persistence\Generic\Backend\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\Session $persistenceSession The persistence session
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceSession(\TYPO3\FLOW3\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof \TYPO3\FLOW3\Persistence\Generic\Backend\BackendInterface) throw new \TYPO3\FLOW3\Persistence\Generic\Exception\MissingBackendException('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$this->backend->setPersistenceManager($this);
		$this->backend->initialize($this->settings['backendOptions']);
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \TYPO3\FLOW3\Persistence\QueryInterface $query
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\FLOW3\Persistence\QueryInterface $query) {
		return $this->backend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\FLOW3\Persistence\QueryInterface $query
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\FLOW3\Persistence\QueryInterface $query) {
		return $this->backend->getObjectDataByQuery($query);
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function persistAll() {
			// hand in only aggregate roots, leaving handling of subobjects to
			// the underlying storage layer
			// reconstituted entities must be fetched from the session and checked
			// for changes by the underlying backend as well!
		$this->backend->setAggregateRootObjects($this->addedObjects);
		$this->backend->setDeletedEntities($this->removedObjects);
		$this->backend->commit();

		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the persistence session
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->persistenceSession->hasObject($object) === FALSE);
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return mixed The identifier for the object if it is known, or NULL
	 * @api
	 */
	public function getIdentifierByObject($object) {
		return $this->persistenceSession->getIdentifierByObject($object);
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @return object The object for the identifier if it is known, or NULL
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL) {
		if ($this->persistenceSession->hasIdentifier($identifier)) {
			return $this->persistenceSession->getObjectByIdentifier($identifier);
		} else {
			$objectData = $this->backend->getObjectDataByIdentifier($identifier);
			if ($objectData !== FALSE) {
				return $this->dataMapper->mapToObject($objectData);
			} else {
				return NULL;
			}
		}
	}

	/**
	 * Returns the object data for the (internal) identifier, if it is known to
	 * the backend. Otherwise FALSE is returned.
	 *
	 * @param string $identifier
	 * @param string $objectType
	 * @return object The object data for the identifier if it is known, or FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getObjectDataByIdentifier($identifier, $objectType = NULL) {
		return $this->backend->getObjectDataByIdentifier($identifier, $objectType);
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\FLOW3\Persistence\QueryInterface
	 */
	public function createQueryForType($type) {
		return $this->queryFactory->create($type);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->addedObjects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Update an object in the persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1249479819);
		}
		$this->changedObjects->attach($object);
	}

}
?>