<?php
namespace Neos\Flow\Persistence\Generic;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;

/**
 * A lazy result list that is returned by Query::execute()
 *
 * @api
 */
class QueryResult implements QueryResultInterface
{
    /**
     * @var DataMapper
     */
    protected $dataMapper;

    /**
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var QueryInterface
     */
    protected $query;

    /**
     * @var array
     * @Flow\Transient
     */
    protected $queryResult;

    /**
     * @var array
     * @Flow\Transient
     */
    protected $numberOfResults;

    /**
     * Constructor
     *
     * @param QueryInterface $query
     */
    public function __construct(QueryInterface $query)
    {
        $this->query = $query;
    }

    /**
     * Injects the DataMapper to map records to objects
     *
     * @param DataMapper $dataMapper
     * @return void
     */
    public function injectDataMapper(DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * Injects the persistence manager
     *
     * @param PersistenceManagerInterface $persistenceManager
     * @return void
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Loads the objects this QueryResult is supposed to hold
     *
     * @return void
     */
    protected function initialize()
    {
        if (!is_array($this->queryResult)) {
            $this->queryResult = $this->dataMapper->mapToObjects($this->persistenceManager->getObjectDataByQuery($this->query));
        }
    }

    /**
     * Returns a clone of the query object
     *
     * @return QueryInterface
     * @api
     */
    public function getQuery()
    {
        return clone $this->query;
    }

    /**
     * Returns the first object in the result set, if any.
     *
     * @return mixed The first object of the result set or NULL if the result set was empty
     * @api
     */
    public function getFirst()
    {
        if (is_array($this->queryResult)) {
            $queryResult = &$this->queryResult;
        } else {
            $query = clone $this->query;
            $query->setLimit(1);
            $queryResult = $this->dataMapper->mapToObjects($this->persistenceManager->getObjectDataByQuery($query));
        }
        return (isset($queryResult[0])) ? $queryResult[0] : null;
    }

    /**
     * Returns the number of objects in the result
     *
     * @return integer The number of matching objects
     * @api
     */
    public function count()
    {
        if ($this->numberOfResults === null) {
            if (is_array($this->queryResult)) {
                $this->numberOfResults = count($this->queryResult);
            } else {
                $this->numberOfResults = $this->persistenceManager->getObjectCountByQuery($this->query);
            }
        }
        return $this->numberOfResults;
    }

    /**
     * Returns an array with the objects in the result set
     *
     * @return array
     * @api
     */
    public function toArray()
    {
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
    public function offsetExists($offset)
    {
        $this->initialize();
        return isset($this->queryResult[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @see \ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        $this->initialize();
        return isset($this->queryResult[$offset]) ? $this->queryResult[$offset] : null;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @see \ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
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
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->queryResult[$offset]);
    }

    /**
     * @return mixed
     * @see \Iterator::current()
     */
    public function current()
    {
        $this->initialize();
        return current($this->queryResult);
    }

    /**
     * @return mixed
     * @see \Iterator::key()
     */
    public function key()
    {
        $this->initialize();
        return key($this->queryResult);
    }

    /**
     * @return void
     * @see \Iterator::next()
     */
    public function next()
    {
        $this->initialize();
        next($this->queryResult);
    }

    /**
     * @return void
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->queryResult);
    }

    /**
     * @return boolean
     * @see \Iterator::valid()
     */
    public function valid()
    {
        $this->initialize();
        return current($this->queryResult) !== false;
    }
}
