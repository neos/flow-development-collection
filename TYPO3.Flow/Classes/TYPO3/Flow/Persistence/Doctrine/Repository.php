<?php
namespace TYPO3\Flow\Persistence\Doctrine;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * The Flow default Repository, based on Doctrine 2
 *
 * @api
 */
abstract class Repository extends \Doctrine\ORM\EntityRepository implements \TYPO3\Flow\Persistence\RepositoryInterface {

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Warning: if you think you want to set this,
	 * look at RepositoryInterface::ENTITY_CLASSNAME first!
	 *
	 * @var string
	 */
	protected $objectType;

	/**
	 * @var array
	 */
	protected $defaultOrderings = array();

	/**
	 * Initializes a new Repository.
	 *
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager The EntityManager to use.
	 * @param \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata The class descriptor.
	 */
	public function __construct(\Doctrine\Common\Persistence\ObjectManager $entityManager, \Doctrine\Common\Persistence\Mapping\ClassMetadata $classMetadata = NULL) {
		if ($classMetadata === NULL) {
			if (static::ENTITY_CLASSNAME === NULL) {
				$this->objectType = str_replace(array('\\Repository\\', 'Repository'), array('\\Model\\', ''), get_class($this));
			} else {
				$this->objectType = static::ENTITY_CLASSNAME;
			}
			$classMetadata = $entityManager->getClassMetadata($this->objectType);
		}
		parent::__construct($entityManager, $classMetadata);
		$this->entityManager = $this->_em;
	}

	/**
	 * Injects the persistence manager
	 *
	 * @param \TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\Flow\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Returns the classname of the entities this repository is managing.
	 *
	 * @return string
	 * @api
	 */
	public function getEntityClassName() {
		return $this->objectType;
	}

	/**
	 * Adds an object to this repository.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->entityManager->persist($object);
	}

	/**
	 * Removes an object from this repository.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		$this->entityManager->remove($object);
	}

	/**
	 * Finds all entities in the repository.
	 *
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface The query result
	 * @api
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
		return $this->entityManager->find($this->objectType, $identifier);
	}

	/**
	 * Returns a query for objects of this repository
	 *
	 * @return \TYPO3\Flow\Persistence\Doctrine\Query
	 * @api
	 */
	public function createQuery() {
		$query = new \TYPO3\Flow\Persistence\Doctrine\Query($this->objectType);
		if ($this->defaultOrderings) {
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
	 * @todo maybe use DQL here, would be much more performant
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
	 * @return void
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function update($object) {
		if (!($object instanceof $this->objectType)) {
			throw new \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}
		$this->persistenceManager->update($object);
	}

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
	public function __call($method, $arguments) {
		$query = $this->createQuery();
		$caseSensitive = isset($arguments[1]) ? (boolean)$arguments[1] : TRUE;

		if (substr($method, 0, 6) === 'findBy' && strlen($method) > 7) {
			$propertyName = lcfirst(substr($method, 6));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute();
		} elseif (substr($method, 0, 7) === 'countBy' && strlen($method) > 8) {
			$propertyName = lcfirst(substr($method, 7));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->count();
		} elseif (substr($method, 0, 9) === 'findOneBy' && strlen($method) > 10) {
			$propertyName = lcfirst(substr($method, 9));
			return $query->matching($query->equals($propertyName, $arguments[0], $caseSensitive))->execute()->getFirst();
		}

		trigger_error('Call to undefined method ' . get_class($this) . '::' . $method, E_USER_ERROR);
	}

}

?>