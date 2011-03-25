<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Persistence\Doctrine;

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
 * FLOW3's Doctrine PersistenceManager
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope singleton
 * @api
 */
class PersistenceManager extends \F3\FLOW3\Persistence\AbstractPersistenceManager {

	/**
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @param \Doctrine\Common\Persistence\ObjectManager $entityManager
	 * @return void
	 */
	public function injectEntityManager(\Doctrine\Common\Persistence\ObjectManager $entityManager) {
		$this->entityManager = $entityManager;
	}

	/**
	 * Initializes the persistence manager, called by FLOW3.
	 *
	 * @return void
	 */
	public function initialize() {}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @api
	 */
	public function persistAll() {
		$this->entityManager->flush();
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the repository
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->entityManager->getUnitOfWork()->getEntityState($object, \Doctrine\ORM\UnitOfWork::STATE_NEW) === \Doctrine\ORM\UnitOfWork::STATE_NEW);
	}

	/**
	 * Returns the (internal) identifier for the object, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * Note: this returns an identifier even if the object has not been
	 * persisted in case of AOP-managed entities. Use isNewObject() if you need
	 * to distinguish those cases.
	 *
	 * @param object $object
	 * @return mixed The identifier for the object if it is known, or NULL
	 * @api
	 * @todo improve try/catch block
	 */
	public function getIdentifierByObject($object) {
		if ($this->entityManager->contains($object)) {
			try {
				return current($this->entityManager->getUnitOfWork()->getEntityIdentifier($object));
			} catch (\Doctrine\ORM\ORMException $e) {
				return NULL;
			}
		} else {
			return NULL;
		}
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param mixed $identifier
	 * @param string $objectType
	 * @return object The object for the identifier if it is known, or NULL
	 * @throws \RuntimeException
	 * @api
	 */
	public function getObjectByIdentifier($identifier, $objectType = NULL) {
		if ($objectType === NULL) {
			throw new \RuntimeException('Using only the identifier is not supported by Doctrine 2. Give classname as well or use repository to query identifier.', 1296646103);
		}
		return $this->entityManager->find($objectType, $identifier);
	}

	/**
	 * Return a query object for the given type.
	 *
	 * @param string $type
	 * @return \F3\FLOW3\Persistence\Doctrine\Query
	 */
	public function createQueryForType($type) {
		return new \F3\FLOW3\Persistence\Doctrine\Query($type, $this->entityManager);
	}

	/**
	 * Adds an object to the persistence.
	 *
	 * @param object $object The object to add
	 * @return void
	 * @api
	 */
	public function add($object) {
		$this->entityManager->persist($object);
	}

	/**
	 * Removes an object to the persistence.
	 *
	 * @param object $object The object to remove
	 * @return void
	 * @api
	 */
	public function remove($object) {
		$this->entityManager->remove($object);
	}

	/**
	 * Merge an object into the persistence.
	 *
	 * @param object $modifiedObject The modified object
	 * @return void
	 * @api
	 */
	public function merge($modifiedObject) {
		try {
			$this->entityManager->merge($modifiedObject);
		} catch (\Exception $exception) {
			throw new \F3\FLOW3\Persistence\Exception('Could not merge objects of type "' . get_class($modifiedObject) . '"', 1297778180, $exception);
		}
	}

	/**
	 * Called after a compile in FLOW3, validates the mapping and creates/updated
	 * database tables accordingly.
	 *
	 * @return void
	 */
	public function compile() {
			// "driver" is used only for Doctrine, thus we (mis-)use it here
			// additionally, when no path is set, skip this step, assuming no DB is needed
		if ($this->settings['backendOptions']['driver'] !== NULL && $this->settings['backendOptions']['path'] !== NULL) {
			$validator = new \Doctrine\ORM\Tools\SchemaValidator($this->entityManager);
			$errors = $validator->validateMapping();

			if (count($errors) > 0) {
				$this->systemLogger->log('Doctrine 2 schema validation failed.', LOG_CRIT, $errors);
			}

			$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
			$schemaTool->updateSchema($this->entityManager->getMetadataFactory()->getAllMetadata());

			$proxyFactory = $this->entityManager->getProxyFactory();
			$proxyFactory->generateProxyClasses($this->entityManager->getMetadataFactory()->getAllMetadata());
			$this->systemLogger->log('Doctrine 2 setup finished');
		}
	}

}

?>