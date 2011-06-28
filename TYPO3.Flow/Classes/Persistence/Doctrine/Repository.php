<?php
namespace TYPO3\FLOW3\Persistence\Doctrine;

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
 * The FLOW3 default Repository, based on Doctrine 2
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Repository extends \Doctrine\ORM\EntityRepository implements \TYPO3\FLOW3\Persistence\RepositoryInterface {

	/**
	 * @var \TYPO3\FLOW3\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
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
	public function __construct(\Doctrine\Common\Persistence\ObjectManager $entityManager, \Doctrine\Common\Persistence\Mapping\ClassMetadata $class = NULL) {
		if ($class === NULL) {
			if ($this->objectType === NULL) {
				$this->objectType = str_replace(array('\\Repository\\', 'Repository'), array('\\Model\\', ''), get_class($this));
			}
			$class = $entityManager->getClassMetadata($this->objectType);
		}
		parent::__construct($entityManager, $class);
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
	 * Replaces an object by another after checking that existing and new
	 * objects have the right types
	 *
	 * This method will unregister the existing object at the identity map and
	 * register the new object instead. The existing object must therefore
	 * already be registered at the identity map which is the case for all
	 * reconstituted objects.
	 *
	 * The new object will be identified by the uuid which formerly belonged
	 * to the existing object. The existing object looses its uuid.
	 *
	 * @param object $existingObject The existing object
	 * @param object $newObject The new object
	 * @throws \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException
	 * @throws \RuntimeException
	 * @api
	 */
	public function replace($existingObject, $newObject) {
		if (!($existingObject instanceof $this->objectType)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}
		if (!($newObject instanceof $this->objectType)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}

		$this->entityManager->merge($newObject);
	}

	/**
	 * Replaces an existing object with the same identifier by the given object
	 * after checking the type of the object fits to the repositories type
	 *
	 * This is a convenience method that replaces a find/replace chain.
	 *
	 * @param object $modifiedObject The modified object
	 * @api
	 */
	public function update($modifiedObject) {
		if (!($modifiedObject instanceof $this->objectType)) {
			throw new \TYPO3\FLOW3\Persistence\Exception\IllegalObjectTypeException('The modified object given to update() was not of the type (' . $this->objectType . ') this repository manages.', 1249479625);
		}

		$class = get_class($modifiedObject);
		$identifier = $this->entityManager->getClassMetadata($class)->getIdentifierValues($modifiedObject);
		$existingObject = $this->entityManager->find($class, $identifier);

		$this->replace($existingObject, $modifiedObject);
	}

}

?>