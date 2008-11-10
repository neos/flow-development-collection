<?php
declare(ENCODING = 'utf-8');
namespace F3::FLOW3::Persistence;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 */

/**
 * The FLOW3 Persistence Manager
 *
 * @package FLOW3
 * @subpackage Persistence
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Manager {

	/**
	 * The reflection service
	 *
	 * @var F3::FLOW3::Reflection::Service
	 */
	protected $reflectionService;

	/**
	 * The class schema builder
	 *
	 * @var F3::FLOW3::Persistence::ClassSchemataBuilder
	 */
	protected $classSchemataBuilder;

	/**
	 * @var F3::FLOW3::Persistence::BackendInterface
	 */
	protected $backend;

	/**
	 * @var F3::FLOW3::Persistence::Session
	 */
	protected $session;

	/**
	 * @var F3::FLOW3::Component::ManagerInterface
	 */
	protected $componentManager;

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array of F3::FLOW3::Persistence::ClassSchema
	 */
	protected $classSchemata = array();

	/**
	 * Constructor
	 *
	 * @param F3::FLOW3::Persistence::BackendInterface $backend the backend to use for persistence
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function __construct(F3::FLOW3::Persistence::BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Injects the reflection service
	 *
	 * @param F3::FLOW3::Reflection::Service $reflectionService The reflection service
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectReflectionService(F3::FLOW3::Reflection::Service $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * Injects the class schemata builder
	 *
	 * @param F3::FLOW3::Persistence::ClassSchemataBuilder $classSchemataBuilder The class schemata builder
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function injectClassSchemataBuilder(F3::FLOW3::Persistence::ClassSchemataBuilder $classSchemataBuilder) {
		$this->classSchemataBuilder = $classSchemataBuilder;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param F3::FLOW3::Persistence::Session $session The persistence session
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSession(F3::FLOW3::Persistence::Session $session) {
		$this->session = $session;
	}

	/**
	 * Injects the component manager
	 *
	 * @param F3::FLOW3::Component::ManagerInterface $componentManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentManager(F3::FLOW3::Component::ManagerInterface $componentManager) {
		$this->componentManager = $componentManager;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof F3::FLOW3::Persistence::BackendInterface) throw new F3::FLOW3::Persistence::Exception::MissingBackend('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$classNames = array_merge($this->reflectionService->getClassNamesByTag('entity'),
			$this->reflectionService->getClassNamesByTag('valueobject'));

		$this->classSchemata = $this->classSchemataBuilder->build($classNames);
		$this->backend->initialize($this->classSchemata);
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return F3::FLOW3::Persistence::Session
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the persistence backend
	 *
	 * @return F3::FLOW3::Persistence::Backend
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getBackend() {
		return $this->backend;
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param string $className
	 * @return F3::FLOW3::Persistence::ClassSchema
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getClassSchema($className) {
		return $this->classSchemata[$className];
	}

	/**
	 * Commits changes of the current persistence session into the backend
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function persistAll() {
		$newObjects = array();
		$dirtyObjects = array();
		$deletedObjects = array();
		$allObjects = array();

		$repositoryClassNames = $this->reflectionService->getAllImplementationClassNamesForInterface('F3::FLOW3::Persistence::RepositoryInterface');
		foreach ($repositoryClassNames as $repositoryClassName) {
			$aggregateRootObjects = $this->componentManager->getComponent($repositoryClassName)->getObjects();
			$this->traverseAndInspectReferenceObjects($aggregateRootObjects, $newObjects, $dirtyObjects, $allObjects);
		}

		$this->traverseAndInspectReferenceObjects(array_values($this->session->getReconstitutedObjects()), $newObjects, $dirtyObjects, $allObjects);

		$this->backend->setNewObjects($newObjects);
		$this->backend->setUpdatedObjects($dirtyObjects);
		$this->backend->setDeletedObjects($deletedObjects);
		$this->backend->commit();

		$this->session->unregisterAllNewObjects();
		foreach($dirtyObjects as $dirtyObject) {
			$dirtyObject->memorizeCleanState();
		}
	}

	/**
	 * Traverses the given object references and collects information about all, new and dirty objects.
	 *
	 * @param array $referenceObjects The reference objects to analyze
	 * @param array $newObjects Pass an empty array - will contain all new objects which were found in the aggregate
	 * @param array $dirtyObjects Pass an empty array - will contain all dirty objects which were found in the aggregate
	 * @param array $allObjects Pass an empty array - will contain all objects which were found in the aggregate
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function traverseAndInspectReferenceObjects(array $referenceObjects, array &$newObjects, array &$dirtyObjects, array &$allObjects) {
		foreach ($referenceObjects as $referenceObject) {
			if (!($referenceObject instanceof F3::FLOW3::AOP::ProxyInterface)) continue;

			$referenceClassName = $referenceObject->AOPProxyGetProxyTargetClassName();
			$referencePropertyNames = $this->reflectionService->getPropertyNamesByTag($referenceClassName, 'reference');

			$objectHash = spl_object_hash($referenceObject);
			$allObjects[$objectHash] = $referenceObject;
			if ($this->session->isNew($referenceObject)) {
				$newObjects[$objectHash] = $referenceObject;
			} elseif ($referenceObject->isDirty()) {
				$dirtyObjects[$objectHash] = $referenceObject;
			}
			foreach ($referencePropertyNames as $propertyName) {
				$propertyValue = $referenceObject->AOPProxyGetProperty($propertyName);
				$subReferenceObjects = is_array($propertyValue) ? $propertyValue : array($propertyValue);
				$this->traverseAndInspectReferenceObjects($subReferenceObjects, $newObjects, $dirtyObjects, $allObjects);
			}
		}
	}
}
?>