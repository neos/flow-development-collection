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
 * Contract for a repository
 *
 * @api
 */
interface RepositoryInterface
{
    /**
     * Returns the object type this repository is managing.
     *
     * @return string
     * @api
     */
    public function getEntityClassName();

    /**
     * Adds an object to this repository.
     *
     * @param object $object The object to add
     * @return void
     * @api
     */
    public function add($object);

    /**
     * Removes an object from this repository.
     *
     * @param object $object The object to remove
     * @return void
     * @api
     */
    public function remove($object);

    /**
     * Returns all objects of this repository.
     *
     * @return QueryResultInterface The query result
     * @api
     */
    public function findAll();

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return object The matching object if found, otherwise NULL
     * @api
     */
    public function findByIdentifier($identifier);

    /**
     * Returns a query for objects of this repository
     *
     * @return QueryInterface
     * @api
     */
    public function createQuery();

    /**
     * Counts all objects of this repository
     *
     * @return integer
     * @api
     */
    public function countAll();

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     *
     * @return void
     * @api
     */
    public function removeAll();

    /**
     * Sets the property names to order results by. Expected like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array $defaultOrderings The property names to order by by default
     * @return void
     * @api
     */
    public function setDefaultOrderings(array $defaultOrderings);

    /**
     * Schedules a modified object for persistence.
     *
     * @param object $object The modified object
     * @return void
     * @api
     */
    public function update($object);

    /**
     * Magic call method for repository methods.
     *
     * Provides three methods
     *  - findBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE, $cacheResult = FALSE)
     *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
     *
     * @param string $method Name of the method
     * @param array $arguments The arguments
     * @return mixed The result of the repository method
     * @api
     */
    public function __call($method, $arguments);
}
