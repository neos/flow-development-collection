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
 */
class Manager implements \F3\FLOW3\Persistence\ManagerInterface {

	/**
	 * The reflection service
	 *
	 * @var \F3\FLOW3\Reflection\Service
	 */
	protected $reflectionService;

	/**
	 * @var \F3\FLOW3\Persistence\BackendInterface
	 */
	protected $backend;

	/**
	 * @var \F3\FLOW3\Persistence\Session
	 */
	protected $session;

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * Constructor
	 *
	 * @param \F3\FLOW3\Persistence\BackendInterface $backend the backend to use for persistence
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(\F3\FLOW3\Persistence\BackendInterface $backend) {
		$this->backend = $backend;
	}

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
	 * Injects the persistence session
	 *
	 * @param \F3\FLOW3\Persistence\Session $session The persistence session
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSession(\F3\FLOW3\Persistence\Session $session) {
		$this->session = $session;
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
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof \F3\FLOW3\Persistence\BackendInterface) throw new \F3\FLOW3\Persistence\Exception\MissingBackend('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$this->backend->initialize($this->reflectionService->getClassSchemata());
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return \F3\FLOW3\Persistence\Session
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSession() {
		return $this->session;
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
		$removedObjects = new \SplObjectStorage();

			// fetch and inspect objects from all known repositories
		$repositoryClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('F3\FLOW3\Persistence\RepositoryInterface');
		foreach ($repositoryClassNames as $repositoryClassName) {
			$repository = $this->objectManager->getObject($repositoryClassName);
			$aggregateRootObjects->addAll($repository->getAddedObjects());
			$removedObjects->addAll($repository->getRemovedObjects());
		}

		$aggregateRootObjects->addAll($this->session->getReconstitutedObjects());

			// hand in only aggregate roots, leaving handling of subobjects to
			// the underlying storage layer
		$this->backend->setAggregateRootObjects($aggregateRootObjects);
		$this->backend->setDeletedObjects($removedObjects);
		$this->backend->commit();

			// this needs to unregister more than just those, as at least some of
			// the subobjects are supposed to go away as well...
			// OTOH those do no harm, changes to the unused ones should not happen,
			// so all they do is eat some memory.
		foreach($removedObjects as $removedObject) {
			$this->session->unregisterReconstitutedObject($removedObject);
		}
	}
}
?>