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
 * @template T of object
 * @api
 */
interface RepositoryInterface
{
    /**
     * Returns the object type this repository is managing.
     *
     * @return class-string<T>
     * @api
     */
    public function getEntityClassName(): string;

    /**
     * Adds an object to this repository.
     *
     * @param T $object The object to add
     * @return void
     * @api
     */
    public function add($object): void;

    /**
     * Removes an object from this repository.
     *
     * @param T $object The object to remove
     * @return void
     * @api
     */
    public function remove($object): void;

    /**
     * Returns all objects of this repository.
     *
     * @return QueryResultInterface<T> The query result
     * @api
     */
    public function findAll(): QueryResultInterface;

    /**
     * Finds an object matching the given identifier.
     *
     * @param mixed $identifier The identifier of the object to find
     * @return T|null The matching object if found, otherwise NULL
     * @api
     */
    public function findByIdentifier($identifier);

    /**
     * Returns a query for objects of this repository
     *
     * @return QueryInterface<T>
     * @api
     */
    public function createQuery(): QueryInterface;

    /**
     * Counts all objects of this repository
     *
     * @return integer
     * @api
     */
    public function countAll(): int;

    /**
     * Removes all objects of this repository as if remove() was called for
     * all of them.
     *
     * @return void
     * @api
     */
    public function removeAll(): void;

    /**
     * Sets the property names to order results by. Expected like this:
     * array(
     *  'foo' => \Neos\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
     *  'bar' => \Neos\Flow\Persistence\QueryInterface::ORDER_DESCENDING
     * )
     *
     * @param array<string,QueryInterface::ORDER_ASCENDING|QueryInterface::ORDER_DESCENDING> $defaultOrderings The property names to "order by" by default
     * @return void
     * @api
     */
    public function setDefaultOrderings(array $defaultOrderings): void;

    /**
     * Schedules a modified object for persistence.
     *
     * @param T $object The modified object
     * @return void
     * @api
     */
    public function update($object): void;

    /**
     * Magic call method for repository methods.
     *
     * Provides three methods
     *  - findBy<PropertyName>($value, $caseSensitive = true, $cacheResult = false)
     *  - findOneBy<PropertyName>($value, $caseSensitive = true, $cacheResult = false)
     *  - countBy<PropertyName>($value, $caseSensitive = true)
     *
     * @param string $method Name of the method
     * @param array<mixed> $arguments The arguments
     * @return mixed The result of the repository method
     * @api
     */
    public function __call($method, $arguments);
}
