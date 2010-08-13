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
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Repository implements \F3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * Objects added to this repository during the current session
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
	 * @var \F3\FLOW3\Persistence\PersistenceManagerInterface
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
		if ($this->objectType === NULL) {
			$this->objectType = str_replace(array('\\Repository\\', 'Repository'), array('\\Model\\', ''), $this->FLOW3_AOP_Proxy_getProxyTargetClassName());
		}
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
	 * @param \F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(\F3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
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
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The object given to add() was not of the type (' . $this->objectType . ') this repository manages.', 1248363335);
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
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The object given to remove() was not of the type (' . $this->objectType . ') this repository manages.', 1248363426);
		}

		if ($this->addedObjects->contains($object)) {
			$this->addedObjects->detach($object);
		} else {
			$this->removedObjects->attach($object);
		}
	}

	/**
	 * Replaces an object by another after checking that existing and new
	 * objects have the right types
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		if (!($existingObject instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The existing object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363434);
		}
		if (!($newObject instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The new object given to replace was not of the type (' . $this->objectType . ') this repository manages.', 1248363439);
		}

		$this->replaceObject($existingObject, $newObject);

	}

	/**
	 * Replaces an object by another without any further checks. Instead of
	 * calling this method, always call replace().
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function replaceObject($existingObject, $newObject) {
		if ($this->persistenceManager->getIdentifierByObject($existingObject) !== NULL) {
			$this->persistenceManager->replaceObject($existingObject, $newObject);

			if ($this->removedObjects->contains($existingObject)) {
				$this->removedObjects->detach($existingObject);
				$this->removedObjects->attach($newObject);
			}
		} elseif ($this->addedObjects->contains($existingObject)) {
			$this->addedObjects->detach($existingObject);
			$this->addedObjects->attach($newObject);
		} else {
			throw new \F3\FLOW3\Persistence\Exception\UnknownObjectException('The "existing object" is unknown to the persistence backend.', 1238068475);
		}

		$this->updateRecursively($newObject);
	}

	/**
	 * Loop through all gettable properties of the $newObject and call update()
	 * on them if they are entities/valueobjects.
	 * This makes sure that changes to subobjects of a given object are
	 * persisted as well.
	 *
	 * @param object $newObject The new object to loop over
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function updateRecursively($newObject) {
		$propertiesOfNewObject = \F3\FLOW3\Reflection\ObjectAccess::getGettableProperties($newObject);

		foreach ($propertiesOfNewObject as $subObject) {
			if ($subObject instanceof \F3\FLOW3\Persistence\Aspect\PersistenceMagicInterface && $subObject->FLOW3_Persistence_isClone()) {
				$this->updateObject($subObject);
				$this->updateRecursively($subObject);
			}
		}
	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 * after checking the type of the object fits to the repositories type
	 *
	 * @param object $modifiedObject The modified object
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function update($modifiedObject) {
		if (!($modifiedObject instanceof $this->objectType)) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}

		if ($modifiedObject->FLOW3_Persistence_isClone() !== TRUE) {
			throw new \F3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The object given to update() was not a clone of a persistent object.', 1253631553);
		}

		$this->updateObject($modifiedObject);
	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 * without any further checks.
	 * Never use this method directly, always use update().
	 *
	 * Note:
	 * The code may look funny, but the two calls to the $persistenceManager
	 * yield different results - getIdentifierByObject() in this case returns the
	 * identifier stored inside the $modifiedObject, whereas getObjectByIdentifier()
	 * returns the existing object from the object map in the session.
	 *
	 * @param object $modifiedObject The modified object
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function updateObject($modifiedObject) {
		$uuid = $this->persistenceManager->getIdentifierByObject($modifiedObject);
		if ($uuid !== NULL) {
			$existingObject = $this->persistenceManager->getObjectByIdentifier($uuid);
			$this->replaceObject($existingObject, $modifiedObject);
		} else {
			throw new \F3\FLOW3\Persistence\Exception\UnknownObjectException('The "modified object" does not have an existing counterpart in this repository.', 1249479819);
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
	 * Finds an object matching the given UUID.
	 *
	 * @param string $uuid The UUID of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findByUuid($uuid) {
		$object = $this->persistenceManager->getObjectByIdentifier($uuid);
		if ($object instanceof $this->objectType) {
			return $object;
		} else {
			return NULL;
		}
	}

	/**
	 * Counts all objects of this repository
	 *
	 * @return integer
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function countAll() {
		return $this->createQuery()->count();
	}

	/**
	 * Removes all objects of this repository as if remove() was called for
	 * all of them.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function removeAll() {
		$this->addedObjects = new \SplObjectStorage();
		foreach ($this->findAll() as $object) {
			$this->remove($object);
		}
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
	 * Currently provides three methods
	 *  - findBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - findOneBy<PropertyName>($value, $caseSensitive = TRUE)
	 *  - countBy<PropertyName>($value, $caseSensitive = TRUE)
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments The arguments
	 * @return mixed The result of the find method
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
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
		} elseif (substr($methodName, 0, 7) === 'countBy' && strlen($methodName) > 8) {
			$propertyName = strtolower(substr($methodName, 7, 1)) . substr($methodName, 8);
			$query = $this->createQuery();
			if (isset($arguments[1])) {
				return $query->matching($query->equals($propertyName, $arguments[0], (boolean)$arguments[1]))->count();
			} else {
				return $query->matching($query->equals($propertyName, $arguments[0]))->count();
			}
		} elseif (substr($methodName, 0, 9) === 'findOneBy' && strlen($methodName) > 10) {
			$propertyName = strtolower(substr($methodName, 9, 1)) . substr($methodName, 10);
			$query = $this->createQuery()->setLimit(1);
			if (isset($arguments[1])) {
				$query->matching($query->equals($propertyName, $arguments[0], (boolean)$arguments[1]));
			} else {
				$query->matching($query->equals($propertyName, $arguments[0]));
			}
			$result = $query->execute();
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