<?php
namespace TYPO3\Flow\Persistence;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * The Flow default Repository
 *
 * @api
 */
abstract class Repository implements \TYPO3\Flow\Persistence\RepositoryInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Warning: if you think you want to set this,
	 * look at RepositoryInterface::ENTITY_CLASSNAME first!
	 *
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
		if (defined('static::ENTITY_CLASSNAME') === FALSE) {
			$this->entityClassName = preg_replace(array('/\\\Repository\\\/', '/Repository$/'), array('\\Model\\', ''), get_class($this));
		} else {
			$this->entityClassName = static::ENTITY_CLASSNAME;
		}
	}

	/**
	 * Returns the classname of the entities this repository is managing.
	 *
	 * Note that anything that is an "instanceof" this class is accepted
	 * by the repository.
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
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function add($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to add() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1298403438);
		}
		$this->persistenceManager->add($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function remove($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to remove() was ' . $type . ' , however the ' . get_class($this) . ' can only handle ' . $this->entityClassName . ' instances.', 1298403442);
		}
		$this->persistenceManager->remove($object);
	}

	/**
	 * Returns all objects of this repository
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 * @api
	 * @see \TYPO3\Flow\Persistence\QueryInterface::execute()
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
	 * @return \TYPO3\Flow\Persistence\QueryInterface
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
	 *  'foo' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_DESCENDING
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
	 * Schedules a modified object for persistence.
	 *
	 * @param object $object The modified object
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function update($object) {
		if (!is_object($object) || !($object instanceof $this->entityClassName)) {
			$type = (is_object($object) ? get_class($object) : gettype($object));
			throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The value given to update() was ' . $type . ' , however the ' . get_class($this) . ' can only store ' . $this->entityClassName . ' instances.', 1249479625);
		}

		$this->persistenceManager->update($object);
	}

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
	public function __call($method, $arguments) {
		$query = $this->createQuery();
		$caseSensitive = isset($arguments[1]) ? (boolean)$arguments[1] : TRUE;
		$cacheResult = isset($arguments[2]) ? (boolean)$arguments[2] : FALSE;

		if (substr($method, 0, 6) === 'findBy' && strlen($method) > 7) {
			$propertyName = lcfirst(substr($method, 6));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult);
		} elseif (substr($method, 0, 7) === 'countBy' && strlen($method) > 8) {
			$propertyName = lcfirst(substr($method, 7));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
		} elseif (substr($method, 0, 9) === 'findOneBy' && strlen($method) > 10) {
			$propertyName = lcfirst(substr($method, 9));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute($cacheResult)->getFirst();
		}

		trigger_error('Call to undefined method ' . get_class($this) . '::' . $method, E_USER_ERROR);
	}

}
