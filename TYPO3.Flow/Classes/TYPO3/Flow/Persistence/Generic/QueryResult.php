<?php
namespace TYPO3\FLOW3\Persistence\Generic;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A lazy result list that is returned by Query::execute()
 *
 * @api
 */
class QueryResult implements \TYPO3\FLOW3\Persistence\QueryResultInterface {

	/**
	 * @var \TYPO3\FLOW3\Persistence\Generic\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\FLOW3\Persistence\QueryInterface
	 */
	protected $query;

	/**
	 * @var array
	 * @FLOW3\Transient
	 */
	protected $queryResult;

	/**
	 * Constructor
	 *
	 * @param \TYPO3\FLOW3\Persistence\QueryInterface $query
	 */
	public function __construct(\TYPO3\FLOW3\Persistence\QueryInterface $query) {
		$this->query = $query;
	}

	/**
	 * Injects the DataMapper to map records to objects
	 *
	 * @param \TYPO3\FLOW3\Persistence\Generic\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\FLOW3\Persistence\Generic\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Loads the objects this QueryResult is supposed to hold
	 *
	 * @return void
	 */
	protected function initialize() {
		if (!is_array($this->queryResult)) {
			$this->queryResult = $this->dataMapper->mapToObjects($this->persistenceManager->getObjectDataByQuery($this->query));
		}
	}

	/**
	 * Returns a clone of the query object
	 *
	 * @return \TYPO3\FLOW3\Persistence\QueryInterface
	 * @api
	 */
	public function getQuery() {
		return clone $this->query;
	}

	/**
	 * Returns the first object in the result set, if any.
	 *
	 * @return mixed The first object of the result set or NULL if the result set was empty
	 * @api
	 */
	public function getFirst() {
		if (is_array($this->queryResult)) {
			$queryResult = &$this->queryResult;
		} else {
			$query = clone $this->query;
			$query->setLimit(1);
			$queryResult = $this->dataMapper->mapToObjects($this->persistenceManager->getObjectDataByQuery($query));
		}
		return (isset($queryResult[0])) ? $queryResult[0] : NULL;
	}

	/**
	 * Returns the number of objects in the result
	 *
	 * @return integer The number of matching objects
	 * @api
	 */
	public function count() {
		if (is_array($this->queryResult)) {
			return count($this->queryResult);
		} else {
			return $this->persistenceManager->getObjectCountByQuery($this->query);
		}
	}

	/**
	 * Returns an array with the objects in the result set
	 *
	 * @return array
	 * @api
	 */
	public function toArray() {
		$this->initialize();
		return iterator_to_array($this);
	}

	/**
	 * This method is needed to implement the \ArrayAccess interface,
	 * but it isn't very useful as the offset has to be an integer
	 *
	 * @param mixed $offset
	 * @return boolean
	 * @see \ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return mixed
	 * @see \ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		$this->initialize();
		return isset($this->queryResult[$offset]) ? $this->queryResult[$offset] : NULL;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 * @see \ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		$this->initialize();
		$this->queryResult[$offset] = $value;
	}

	/**
	 * This method has no effect on the persisted objects but only on the result set
	 *
	 * @param mixed $offset
	 * @return void
	 * @see \ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		$this->initialize();
		unset($this->queryResult[$offset]);
	}

	/**
	 * @return mixed
	 * @see \Iterator::current()
	 */
	public function current() {
		$this->initialize();
		return current($this->queryResult);
	}

	/**
	 * @return mixed
	 * @see \Iterator::key()
	 */
	public function key() {
		$this->initialize();
		return key($this->queryResult);
	}

	/**
	 * @return void
	 * @see \Iterator::next()
	 */
	public function next() {
		$this->initialize();
		next($this->queryResult);
	}

	/**
	 * @return void
	 * @see \Iterator::rewind()
	 */
	public function rewind() {
		$this->initialize();
		reset($this->queryResult);
	}

	/**
	 * @return boolean
	 * @see \Iterator::valid()
	 */
	public function valid() {
		$this->initialize();
		return current($this->queryResult) !== FALSE;
	}
}

?>