<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

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
class Repository implements F3::FLOW3::Persistence::RepositoryInterface {

	/**
	 * Objects of this repository
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * @var F3::FLOW3::Persistence::QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param F3::FLOW3::Persistence::QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQueryFactory(F3::FLOW3::Persistence::QueryFactoryInterface $queryFactory) {
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
		if (!isset($this->objects[$objectHash])) return;
		unset ($this->objects[$objectHash]);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findAll() {
		return $this->createQuery()->execute();
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return F3::FLOW3:Persistence::QueryInterface
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function createQuery() {
		$type = str_replace('Repository', '', $this->AOPProxyGetProxyTargetClassName());
		return $this->queryFactory->create($type);
	}

	/**
	 * Returns the class name of this class. Seems useless until you think about
	 * the possibility of $this *not* being an AOP proxy. If $this is an AOP proxy
	 * this method will be overridden.
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function AOPProxyGetProxyTargetClassName() {
		return get_class($this);
	}

	/**
	 * Returns all loaded objects of this repository
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