<?php
declare(ENCODING = 'utf-8');

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
class F3_FLOW3_Persistence_Manager {

	/**
	 * The reflection service
	 *
	 * @var F3_FLOW3_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * The class schema builder
	 *
	 * @var F3_FLOW3_Persistence_ClassSchemataBuilder
	 */
	protected $classSchemataBuilder;

	/**
	 * @var F3_FLOW3_Persistence_BackendInterface
	 */
	protected $backend;

	/**
	 * @var F3_FLOW3_Persistence_Session
	 */
	protected $session;

	/**
	 * @var F3_FLOW3_Component_FactoryInterface
	 */
	protected $componentFactory;

	/**
	 * Schemata of all classes which need to be persisted
	 *
	 * @var array of F3_FLOW3_Persistence_ClassSchema
	 */
	protected $classSchemata = array();

	/**
	 * Constructor
	 *
	 * @param F3_FLOW3_Reflection_Service $reflectionService
	 * @param F3_FLOW3_Persistence_ClassSchemataBuilder $ClassSchemataBuilder
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Reflection_Service $reflectionService, F3_FLOW3_Persistence_ClassSchemataBuilder $classSchemataBuilder) {
		$this->reflectionService = $reflectionService;
		$this->classSchemataBuilder = $classSchemataBuilder;
	}

	/**
	 * Injects the persistence session
	 *
	 * @param F3_FLOW3_Persistence_Session $session The persistence session
	 * @return void
	 * @required
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSession(F3_FLOW3_Persistence_Session $session) {
		$this->session = $session;
	}

	/**
	 * Injects the component factory
	 *
	 * @param F3_FLOW3_Component_FactoryInterface $componentFactory
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectComponentFactory(F3_FLOW3_Component_FactoryInterface $componentFactory) {
		$this->componentFactory = $componentFactory;
	}

	/**
	 * Injects the backend to use for persistence
	 *
	 * @param F3_FLOW3_Persistence_BackendInterface $backend
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectBackend(F3_FLOW3_Persistence_BackendInterface $backend) {
		$this->backend = $backend;
	}

	/**
	 * Initializes the persistence manager
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function initialize() {
		if (!$this->backend instanceof F3_FLOW3_Persistence_BackendInterface) throw new F3_FLOW3_Persistence_Exception_MissingBackend('A persistence backend must be set prior to initializing the persistence manager.', 1215508456);
		$classNames = array_merge($this->reflectionService->getClassNamesByTag('entity'),
			$this->reflectionService->getClassNamesByTag('valueobject'));

		$this->classSchemata = $this->classSchemataBuilder->build($classNames);
		$this->backend->initialize($this->classSchemata);
	}

	/**
	 * Returns the current persistence session
	 *
	 * @return F3_FLOW3_Persistence_Session
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSession() {
		return $this->session;
	}

	/**
	 * Returns the class schema for the given class
	 *
	 * @param string $className
	 * @return F3_FLOW3_Persistence_ClassSchema
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
		$allObjects = array();

		$repositoryClassNames = $this->reflectionService->getClassNamesByTag('repository');
		foreach ($repositoryClassNames as $repositoryClassName) {
			$aggregateRootObjects = $this->componentFactory->getComponent($repositoryClassName)->findAll();
			$this->traverseAndInspectReferenceObjects($aggregateRootObjects, $newObjects, $dirtyObjects, $allObjects);
		}

		$this->backend->setNewObjects($newObjects);
		$this->backend->setUpdatedObjects($dirtyObjects);
#		$this->backend->setDeletedObjects($deletedObjects);
		$this->backend->commit();
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
		if (count($referenceObjects) == 0 || !$referenceObjects[0] instanceof F3_FLOW3_AOP_ProxyInterface) return;

		$referenceClassName = $referenceObjects[0]->AOPProxyGetProxyTargetClassName();
		$referencePropertyNames = $this->reflectionService->getPropertyNamesByTag($referenceClassName, 'reference');

		foreach($referenceObjects as $referenceObject) {
			$objectHash = spl_object_hash($referenceObject);
			$allObjects[$objectHash] = $referenceObject;
			if ($this->session->isNew($referenceObject)) {
				$newObjects[$objectHash] = $referenceObject;
			} elseif ($referenceObject->isDirty()) {
				$dirtyObjects[$objectHash] = $referenceObject;
			}
		}
		foreach ($referencePropertyNames as $propertyName) {
			$subReferenceObject = $referenceObject->AOPProxyGetProperty($propertyName);
			$this->traverseAndInspectReferenceObjects($subReferenceObject, $newObjects, $dirtyObjects, $allObjects);
		}
	}
}
?>