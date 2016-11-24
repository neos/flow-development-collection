<?php
namespace Neos\Flow\Persistence\Doctrine;

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
use Neos\Flow\Persistence\QueryResultInterface;

/**
 * A lazy result list that is returned by Query::execute()
 *
 * @api
 */
class QueryResult implements QueryResultInterface
{
    /**
     * @var array
     * @Flow\Transient
     */
    protected $rows;

    /**
     * @var integer
     * @Flow\Transient
     */
    protected $numberOfRows;

    /**
     * @var Query
     */
    protected $query;

    /**
     * @param Query $query
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Loads the objects this QueryResult is supposed to hold
     *
     * @return void
     */
    protected function initialize()
    {
        if (!is_array($this->rows)) {
            $this->rows = $this->query->getResult();
        }
    }

    /**
     * Returns a clone of the query object
     *
     * @return Query
     * @api
     */
    public function getQuery()
    {
        return clone $this->query;
    }

    /**
     * Returns the first object in the result set
     *
     * @return object
     * @api
     */
    public function getFirst()
    {
        if (is_array($this->rows)) {
            $rows = &$this->rows;
        } else {
            $query = clone $this->query;
            $query->setLimit(1);
            $rows = $query->getResult();
        }

        return (isset($rows[0])) ? $rows[0] : null;
    }

    /**
     * Returns the number of objects in the result
     *
     * @return integer The number of matching objects
     * @api
     */
    public function count()
    {
        if ($this->numberOfRows === null) {
            if (is_array($this->rows)) {
                $this->numberOfRows = count($this->rows);
            } else {
                $this->numberOfRows = $this->query->count();
            }
        }
        return $this->numberOfRows;
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
        return $this->rows;
    }

    /**
     * This method is needed to implement the \ArrayAccess interface,
     * but it isn't very useful as the offset has to be an integer
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        $this->initialize();
        return isset($this->rows[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->initialize();
        return isset($this->rows[$offset]) ? $this->rows[$offset] : null;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->rows[$offset] = $value;
    }

    /**
     * This method has no effect on the persisted objects but only on the result set
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->rows[$offset]);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->initialize();
        return current($this->rows);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->initialize();
        return key($this->rows);
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->initialize();
        next($this->rows);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->rows);
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        $this->initialize();
        return current($this->rows) !== false;
    }
}
