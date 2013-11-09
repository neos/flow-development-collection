<?php
namespace TYPO3\Flow\Persistence\Generic;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The generic Flow Persistence Manager
 *
 * @Flow\Scope("singleton")
 * @api
 */
class PersistenceManager extends \TYPO3\Flow\Persistence\AbstractPersistenceManager {

	/**
	 * @var \SplObjectStorage
	 */
	protected $changedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $addedObjects;

	/**
	 * @var \SplObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Session
	 */
	protected $persistenceSession;

	/**
	 * Create new instance
	 */
	public function __construct() {
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();
	}

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\QueryFactoryInterface $queryFactory
	 * @return void
	 */
	public function injectQueryFactory(\TYPO3\Flow\Persistence\Generic\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Injects the data mapper
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\Flow\Persistence\Generic\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
		$this->dataMapper->setPersistenceManager($this);
	}

	/**
	 * Injects the backend to use
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface $backend the backend to use for persistence
	 * @return void
	 * @Flow\Autowiring(false)
	 */
	public function injectBackend(\TYPO3\Flow\Persistence\Generic\Backend\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \TYPO3\Flow\Persistence\Generic\Session $persistenceSession The persistence session
	 * @return void
	 */
	public function injectPersistenceSession(\TYPO3\Flow\Persistence\Generic\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Generic\Exception\MissingBackendException
	 */
	public function initialize() {
		if (!$this->backend instanceof \TYPO3\Flow\Persistence\Generic\Backend\BackendInterface) {
			throw new \TYPO3\Flow\Persistence\Generic\Exception\MissingBackendException('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		}
		$this->backend->setPersistenceManager($this);
		$this->backend->initialize($this->settings['backendOptions']);
	}

	/**
	 * Returns the number of records matching the query.
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return integer
	 * @api
	 */
	public function getObjectCountByQuery(\TYPO3\Flow\Persistence\QueryInterface $query) {
		return $this->backend->getObjectCountByQuery($query);
	}

	/**
	 * Returns the object data matching the $query.
	 *
	 * @param \TYPO3\Flow\Persistence\QueryInterface $query
	 * @return array
	 * @api
	 */
	public function getObjectDataByQuery(\TYPO3\Flow\Persistence\QueryInterface $query) {
		return $this->backend->getObjectDataByQuery($query);
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		// hand in only aggregate roots, leaving handling of subobjects to
		// the underlying storage layer
		// reconstituted entities must be fetched from the session and checked
		// for changes by the underlying backend as well!
		$this->backend->setAggregateRootObjects($this->addedObjects);
		$this->backend->setChangedEntities($this->changedObjects);
		$this->backend->setDeletedEntities($this->removedObjects);
		$this->backend->commit();

		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();

		$this->emitAllObjectsPersisted();
		$this->hasUnpersistedChanges = FALSE;
	}

	/**
	 * Clears the in-memory state of the persistence.
	 *
	 * Managed instances become detached, any fetches will
	 * return data directly from the persistence "backend".
	 * It will also forget about new objects.
	 *
	 * @return void
	 */
	public function clearState() {
		parent::clearState();
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->changedObjects = new \SplObjectStorage();
		$this->persistenceSession->destroy();
		$this->hasUnpersistedChanges = FALSE;
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the persistence session
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
	 * @param boolean $useLazyLoading This option is ignored in this persistence manager
	 * @return object The object for the identifier if it is known, or NULL
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL, $useLazyLoading = FALSE) {
		if (isset($this->newObjects[$identifier])) {
			return $this->newObjects[$identifier];
		}
		if ($this->persistenceSession->hasIdentifier($identifier)) {
			return $this->persistenceSession->getObjectByIdentifier($identifier);
		} else {
			$objectData = $this->backend->getObjectDataByIdentifier($identifier, $objectType);
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
	 */
	public function getObjectDataByIdentifier($identifier, $objectType = NULL) {
		return $this->backend->getObjectDataByIdentifier($identifier, $objectType);
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \TYPO3\Flow\Persistence\QueryInterface
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
		$this->hasUnpersistedChanges = TRUE;
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
		$this->hasUnpersistedChanges = TRUE;
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
	 * @throws \TYPO3\Flow\Persistence\Exception\UnknownObjectException
	 * @api
	 */
	public function update($object) {
		if ($this->isNewObject($object)) {
			throw new \TYPO3\Flow\Persistence\Exception\UnknownObjectException('The object of type "' . get_class($object) . '" given to update must be persisted already, but is new.', 1249479819);
		}
		$this->hasUnpersistedChanges = TRUE;
		$this->changedObjects->attach($object);
	}

	/**
	 * Returns TRUE, if an active connection to the persistence
	 * backend has been established, e.g. entities can be persisted.
	 *
	 * @return boolean TRUE, if an connection has been established, FALSE if add object will not be persisted by the backend
	 * @api
	 */
	public function isConnected() {
		return $this->backend->isConnected();
	}

	/**
	 * Signals that all persistAll() has been executed successfully.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitAllObjectsPersisted() {
	}

	/**
	 * Tear down the persistence
	 *
	 * This method is called in functional tests to reset the storage between tests.
	 * The implementation is optional and depends on the underlying persistence backend.
	 *
	 * @return void
	 */
	public function tearDown() {
		if (method_exists($this->backend, 'tearDown')) {
			$this->backend->tearDown();
		}
	}

}
