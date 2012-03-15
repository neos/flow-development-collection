<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

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
 * The FLOW3 default Repository, based on Doctrine 2
 *
 * @api
 */
class Repository extends \Doctrine\ORM\EntityRepository implements \TYPO3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
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
	 * @return array The entities.
	 */
	public function findAll() {
		return parent::findAll();
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
	 * @return \TYPO3\FLOW3\Persistence\Doctrine\Query
	 * @api
	 */
	public function createQuery() {
		$query = new \TYPO3\FLOW3\Persistence\Doctrine\Query($this->objectType);
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
	 * Schedules a modified object for persistence.
	 *
	 * @param object $object The modified object
	 * @return void
	 * @throws \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @api
	 */
	public function update($object) {
		if (!($object instanceof $this->objectType)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}
		$this->persistenceManager->update($object);
	}

}

?>