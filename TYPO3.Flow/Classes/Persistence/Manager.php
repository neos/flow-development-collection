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
 * The FLOW3 Persistence Manager
 *
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Manager implements \F3\FLOW3\Persistence\ManagerInterface {

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \F3\FLOW3\Persistence\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $persistenceSession;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * Injects the reflection service
	 *
	 * @param \F3\FLOW3\Reflection\Service $reflectionService The reflection service
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(\F3\FLOW3\Reflection\Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the data mapper
	 *
	 * @param \F3\FLOW3\Persistence\DataMapper $dataMapper
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectDataMapper(\F3\FLOW3\Persistence\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the backend to use
	 *
	 * @param \F3\FLOW3\Persistence\BackendInterface $backend the backend to use for persistence
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectBackend(\F3\FLOW3\Persistence\BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $persistenceSession The persistence session
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceSession(\F3\FLOW3\Persistence\Session $persistenceSession) {
		$this->persistenceSession = $persistenceSession;
	}

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Set settings for the persistence layer
	 *
	 * @param array $settings
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof \F3\FLOW3\Persistence\BackendInterface) throw new \F3\FLOW3\Persistence\Exception\MissingBackend('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$this->backend->initialize($this->settings['backendOptions']);
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return \F3\FLOW3\Persistence\Session
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSession() {
		return $this->persistenceSession;
	}

	/**
	 * Returns the persistence backend
	 *
	 * @return \F3\FLOW3\Persistence\BackendInterface
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Commits new objects and changes to objects in the current persistence
	 * session into the backend
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function persistAll() {
		$aggregateRootObjects = new \SplObjectStorage();
		$deletedEntities = new \SplObjectStorage();

			// fetch and inspect objects from all known repositories
		$repositoryClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Persistence\RepositoryInterface');
		foreach ($repositoryClassNames as $repositoryClassName) {
			$repository = $this->objectManager->getObject($repositoryClassName);
			$aggregateRootObjects->addAll($repository->getAddedObjects());
			$deletedEntities->addAll($repository->getRemovedObjects());
		}

		$aggregateRootObjects->addAll($this->persistenceSession->getReconstitutedObjects());

			// hand in only aggregate roots, leaving handling of subobjects to
			// the underlying storage layer
		$this->backend->setAggregateRootObjects($aggregateRootObjects);
		$this->backend->setDeletedEntities($deletedEntities);
		$this->backend->commit();

			// this needs to unregister more than just those, as at least some of
			// the subobjects are supposed to go away as well...
			// OTOH those do no harm, changes to the unused ones should not happen,
			// so all they do is eat some memory.
		foreach($deletedEntities as $deletedEntity) {
			$this->persistenceSession->unregisterReconstitutedObject($deletedEntity);
		}
	}

	/**
	 * Checks if the given object has ever been persisted.
	 *
	 * @param object $object The object to check
	 * @return boolean TRUE if the object is new, FALSE if the object exists in the persistence session
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function isNewObject($object) {
		return ($this->persistenceSession->hasObject($object) === FALSE);
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
	 * @return string The identifier for the object
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getIdentifierByObject($object) {
		if ($this->persistenceSession->hasObject($object)) {
			return $this->persistenceSession->getIdentifierByObject($object);
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_Entity_UUID')) {
				// entities created get an UUID set through AOP
			return $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_Entity_UUID');
		} elseif ($object instanceof \F3\FLOW3\AOP\ProxyInterface && $object->FLOW3_AOP_Proxy_hasProperty('FLOW3_Persistence_ValueObject_Hash')) {
				// valueobjects created get a hash set through AOP
			return $object->FLOW3_AOP_Proxy_getProperty('FLOW3_Persistence_ValueObject_Hash');
		} else {
			return NULL;
		}
	}

	/**
	 * Returns the object with the (internal) identifier, if it is known to the
	 * backend. Otherwise NULL is returned.
	 *
	 * @param string $identifier
	 * @return object The object for the identifier if it is known, or NULL
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function getObjectByIdentifier($identifier) {
		if ($this->persistenceSession->hasIdentifier($identifier)) {
			return $this->persistenceSession->getObjectByIdentifier($identifier);
		} else {
			$objectRecord = $this->backend->getObjectRecord($identifier);
			if ($objectRecord !== FALSE) {
				return $this->dataMapper->mapSingleObject($objectRecord);
			} else {
				return NULL;
			}
		}
	}
}
?>