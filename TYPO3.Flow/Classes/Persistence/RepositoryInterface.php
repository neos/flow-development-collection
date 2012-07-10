<?php
namespace TYPO3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for a repository
 *
 * @api
 */
interface RepositoryInterface {

	/**
	 * If set in an implementation overrides automatic detection of the
	 * entity class name being managed by the repository.
	 *
	 * @var string
	 * @api
	 */
	const ENTITY_CLASSNAME = NULL;

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
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface The query result
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
	 * @return \TYPO3\FLOW3\Persistence\QueryInterface
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
	 *  'foo' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
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
	 *  - findBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
	 *
	 * @param string $method Name of the method
	 * @param array $arguments The arguments
	 * @return mixed The result of the repository method
	 * @api
	 */
	public function __call($method, $arguments);

}
?>