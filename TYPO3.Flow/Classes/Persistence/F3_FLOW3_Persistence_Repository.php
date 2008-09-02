<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_FLOW3_Persistence_Repository implements F3_FLOW3_Persistence_RepositoryInterface {

	/**
	 * Objects of this repository
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * @var F3_FLOW3_Persistence_QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param F3_FLOW3_Persistence_QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQueryFactory(F3_FLOW3_Persistence_QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function add($object) {
		$this->objects[spl_object_hash($object)] = $object;
	}

	/**
	 * Removes an object from this repository
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove($object) {
		$objectHash = spl_object_hash($object);
		if (!key_exists($objectHash, $this->objects)) return;
		unset ($this->objects[$objectHash]);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findAll() {
		$type = str_replace('Repository', '', get_class($this));
		return $this->queryFactory->create($type)->execute();
	}

	/**
	 * Returns all objects of this repository
	 *
	 * This is a service method for the persistence manager to get all loaded objects from the
	 * repository without running a query.
	 *
	 * @return array An array of the objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjects() {
		return array_values($this->objects);
	}
}
?>