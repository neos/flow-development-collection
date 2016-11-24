<?php
namespace Neos\Flow\Persistence;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
     * @return QueryInterface
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
