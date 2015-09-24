<?php
namespace TYPO3\Flow\Persistence;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A lazy result list that is returned by Query::execute()
 *
 * @api
 */
interface QueryResultInterface extends \Countable, \Iterator, \ArrayAccess
{
    /**
     * Returns a clone of the query object
     *
     * @return \TYPO3\Flow\Persistence\QueryInterface
     * @api
     */
    public function getQuery();

    /**
     * Returns the first object in the result set
     *
     * @return object
     * @api
     */
    public function getFirst();

    /**
     * Returns an array with the objects in the result set
     *
     * @return array
     * @api
     */
    public function toArray();
}
