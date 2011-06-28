<?php
namespace TYPO3\FLOW3\Persistence;

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
 * The FLOW3 default Repository
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Repository implements \TYPO3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var string
	 */
	protected $entityClassName;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array();

	/**
	 * Initializes a new Repository.
	 */
	public function __construct() {
		if (static::ENTITY_CLASSNAME === NULL) {
			$this->entityClassName = str_replace(array('\\Repository\\', 'Repository'), array('\\Model\\', ''), get_class($this));
		} else {
			$this->entityClassName = static::ENTITY_CLASSNAME;
		}
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\FLOW3\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Returns the classname of the entities this repository is managing.
	 *
	 * @return string
	 * @api
	 */
	public function getEntityClassName() {
		return $this->entityClassName;
	}

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		if (!($object instanceof $this->entityClassName)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The object given to add() was not of the type (' . $this->entityClassName . ') this repository manages.', 1298403438);
		}
		$this->persistenceManager->add($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		if (!($object instanceof $this->entityClassName)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The object given to remove() was not of the type (' . $this->entityClassName . ') this repository manages.', 1298403442);
		}
		$this->persistenceManager->remove($object);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface The query result
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 * @see \TYPO3\FLOW3\Persistence\QueryInterface::execute()
	 */
	public function findAll() {
		return $this->createQuery()->execute();
	}

	/**
	 * Finds an object matching the given identifier.
	 *
	 * @param mixed $identifier The identifier of the object to find
	 * @return object The matching object if found, otherwise NULL
	 * @api
	 */
	public function findByIdentifier($identifier) {
		return $this->persistenceManager->getObjectByIdentifier($identifier, $this->entityClassName);
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\FLOW3\Persistence\Doctrine\Query
	 * @api
	 */
	public function createQuery() {
		$query = $this->persistenceManager->createQueryForType($this->entityClassName);
		if ($this->defaultOrderings !== array()) {
			$query->setOrderings($this->defaultOrderings);
		}
		return $query;
	}

	/**
	 * Counts all objects of this repository
	 *
	 * @return integer
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
	 * @api
	 * @todo use DQL here, would be much more performant
	 */
	public function removeAll() {
		foreach ($this->findAll() AS $object) {
			$this->remove($object);
		}
	}

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
	public function setDefaultOrderings(array $defaultOrderings) {
		$this->defaultOrderings = $defaultOrderings;
	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 * after checking the type of the object fits to the repositories type
	 *
	 * @param object $modifiedObject The modified object
	 * @api
	 */
	public function update($modifiedObject) {
		if (!($modifiedObject instanceof $this->entityClassName)) {
			$type = (is_object($modifiedObject) ? get_class($modifiedObject) : gettype($modifiedObject));
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . '.', 1249479625);
		}

		$this->persistenceManager->merge($modifiedObject);
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
			$query = $this->createQuery();
			if (isset($arguments[1])) {
				$query->matching($query->equals($propertyName, $arguments[0], (boolean)$arguments[1]));
			} else {
				$query->matching($query->equals($propertyName, $arguments[0]));
			}
			return $query->execute()->getFirst();
		}
		trigger_error('Call to undefined method ' . get_class($this) . '::' . $methodName, E_USER_ERROR);
	}

}

?>