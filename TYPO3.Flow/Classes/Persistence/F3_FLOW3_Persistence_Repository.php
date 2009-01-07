<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser Public License, version 3 or later
 */
class Repository implements \F3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * Objects of this repository
	 *
	 * @var array
	 */
	protected $objects = array();

	/**
	 * Objects removed but not found in $this->objects at removal time
	 *
	 * @var array
	 */
	protected $removedObjects = array();

	/**
	 * @var \F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * Injects a QueryFactory instance
	 *
	 * @param \F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectQueryFactory(\F3\FLOW3\Persistence\QueryFactoryInterface $queryFactory) {
		$this->queryFactory = $queryFactory;
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function add($object) {
		$objectHash = spl_object_hash($object);
		$this->objects[$objectHash] = $object;
		if (array_key_exists($objectHash, $this->removedObjects)) {
			unset ($this->removedObjects[$objectHash]);
		}
	}

	/**
	 * Removes an object from this repository. If it is contained in $this->objects
	 * we just remove it there, since this means it has never been persisted yet.
	 *
	 * Else we keep the object around to check if we need to remove it from the
	 * storage layer.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove($object) {
		$objectHash = spl_object_hash($object);
		if (array_key_exists($objectHash, $this->objects)) {
			unset ($this->objects[$objectHash]);
		} else {
			$this->removedObjects[$objectHash] = $object;
		}
	}

	/**
	 * Returns all objects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all objects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return array An array of the objects, the spl_object_hash is the key
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getObjects() {
		return $this->objects;
	}

	/**
	 * Returns an array with objects remove()d from the repository that
	 * had been persisted to the storage layer before.
	 *
	 * @return array An array of the objects, the spl_object_hash is the key
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
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
	 * @return \F3\FLOW3:Persistence\QueryInterface
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

}
?>