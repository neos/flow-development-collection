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
 * The base repository - will usually be extended by a more concrete repository.
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Repository implements \F3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * Objects of this repository
	 *
	 * @var \SplObjectStorage
	 */
	protected $addedObjects;

	/**
	 * Objects removed but not found in $this->addedObjects at removal time
	 *
	 * @var \SplObjectStorage
	 */
	protected $removedObjects;

	/**
	 * @var \F3\FLOW3\Persistence\QueryFactoryInterface
	 */
	protected $queryFactory;

	/**
	 * @var \F3\FLOW3\Persistence\ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var string
	 */
	protected $objectType;

	/**
	 * Constructs a new Repository
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function __construct() {
		$this->addedObjects = new \SplObjectStorage();
		$this->removedObjects = new \SplObjectStorage();
		$this->objectType = str_replace(array('\\Repository\\', 'Repository'), array('\\Model\\', ''), $this->FLOW3_AOP_Proxy_getProxyTargetClassName());
	}

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
	 * Injects the persistence manager
	 *
	 * @param \F3\FLOW3\Persistence\ManagerInterface $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Adds an object to this repository
	 *
	 * @param object $object The object to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function add($object) {
		if (!($object instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectType('The object given to add() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
		}

		$this->addedObjects->attach($object);
		$this->removedObjects->detach($object);
	}

	/**
	 * Removes an object from this repository. If it is contained in $this->addedObjects
	 * we just remove it there, since this means it has never been persisted yet.
	 *
	 * Else we keep the object around to check if we need to remove it from the
	 * storage layer.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function remove($object) {
		if (!($object instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectType('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363426);
		}

		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Replaces an object by another.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		if (!($existingObject instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectType('The existing object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363434);
		}
		if (!($newObject instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectType('The new object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363439);
		}

		$backend = $this->persistenceManager->getBackend();
		$session = $this->persistenceManager->getSession();
		$uuid = $backend->getIdentifierByObject($existingObject);
		if ($uuid !== NULL) {
			$backend->replaceObject($existingObject, $newObject);
			$session->unregisterReconstitutedObject($existingObject);
			$session->registerReconstitutedObject($newObject);

			if ($this->removedObjects->contains($existingObject)) {
				$this->removedObjects->detach($existingObject);
				$this->removedObjects->attach($newObject);
			}
		} elseif ($this->addedObjects->contains($existingObject)) {
			$this->addedObjects->detach($existingObject);
			$this->addedObjects->attach($newObject);
		} else {
			throw new \F3\FLOW3\Persistence\Exception\UnknownObject('The "existing object" is unknown to the persistence backend.', 1238068475);
		}
	}

	/**
	 * Returns all addedObjects that have been added to this repository with add().
	 *
	 * This is a service method for the persistence manager to get all addedObjects
	 * added to the repository. Those are only objects *added*, not objects
	 * fetched from the underlying storage.
	 *
	 * @return \SplObjectStorage the objects
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAddedObjects() {
		return $this->addedObjects;
	}

	/**
	 * Returns an \SplObjectStorage with objects remove()d from the repository
	 * that had been persisted to the storage layer before.
	 *
	 * @return \SplObjectStorage the objects
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getRemovedObjects() {
		return $this->removedObjects;
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return array An array of objects, empty if no objects found
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findAll() {
		return $this->createQuery()->execute();
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param string $uuid The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findByUUID($uuid) {
		$query = $this->createQuery();
		$result = $query->matching($query->withUUID($uuid))->execute();
		return current($result);
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \F3\FLOW3\Persistence\QueryInterface
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function createQuery() {
		return $this->queryFactory->create($this->objectType);
	}

	/**
	 * Magic call method for finder methods.
	 *
	 * Currently provides two methods
	 *  - findBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE)
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments The arguments
	 * @return mixed The result of the find method
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function __call($methodName, array $arguments) {
		if (substr($methodName, 0, 6) === 'findBy' && strlen($methodName) > 7) {
			$propertyName = strtolower(substr($methodName, 6, 1)) . substr($methodName, 7);
			$query = $this->createQuery();
			if (isset($arguments[1])) {
				return $query->matching($query->equals($propertyName, $arguments[0], (boolean)$arguments[1]))->execute();
			} else {
				return $query->matching($query->equals($propertyName, $arguments[0]))->execute();
			}
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr($methodName, 9, 1)) . substr($methodName, 10);
			$query = $this->createQuery();
			if (isset($arguments[1])) {
				$result = $query->matching($query->equals($propertyName, $arguments[0], (boolean)$arguments[1]))->execute();
			} else {
				$result = $query->matching($query->equals($propertyName, $arguments[0]))->execute();
			}
			return current($result);
		}
		trigger_error('Call to undefined method ' . get_class($this) . '::' . $methodName, E_USER_ERROR);
	}

	/**
	 * Returns the class name of this class. Seems useless until you think about
	 * the possibility of $this *not* being an AOP proxy. If $this is an AOP proxy
	 * this method will be overridden.
	 *
	 * @return string Class name of the repository. If it is proxied, it's still the (target) class name.
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function FLOW3_AOP_Proxy_getProxyTargetClassName() {
		return get_class($this);
	}

}
?>