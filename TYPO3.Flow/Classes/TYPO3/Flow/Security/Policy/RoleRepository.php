<?php
namespace TYPO3\Flow\Security\Policy;

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
 * The repository for roles
 *
 * @Flow\Scope("singleton")
 */
class RoleRepository extends \TYPO3\Flow\Persistence\Repository {

	/**
	 * @var string
	 */
	const ENTITY_CLASSNAME = 'TYPO3\Flow\Security\Policy\Role';

	/**
	 * Doctrine's Entity Manager. Note that "ObjectManager" is the name of the related
	 * interface ...
	 *
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 * @todo needed for persistEntities() - should be framework functionality
	 */
	protected $entityManager;

	/**
	 * Holds added roles - getIdentifierByObject otherwise fails because the builtin
	 * support for "new" objects only works for AOP'd UUID identifiers for now.
	 *
	 * @todo remove this workaround and clean up FunctionalTestCase then #43192
	 * @var array
	 */
	protected $newRoles = array();

	/**
	 * Adds a role to this repository.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role The role to add
	 * @return void
	 */
	public function add($role) {
		if (!isset($this->newRoles[$role->getIdentifier()])) {
			$this->newRoles[$role->getIdentifier()] = $role;
		}
		parent::add($role);
	}

	/**
	 * Removes a role from this repository.
	 *
	 * @param \TYPO3\Flow\Security\Policy\Role $role The role to remove
	 * @return void
	 */
	public function remove($role) {
		if (isset($this->newRoles[$role->getIdentifier()])) {
			unset($this->newRoles[$role->getIdentifier()]);
		}
		parent::remove($role);
	}

	/**
	 * Finds a role matching the given identifier.
	 *
	 * @param mixed $identifier The identifier of the role to find
	 * @return \TYPO3\Flow\Security\Policy\Role The matching role object if found, otherwise NULL
	 */
	public function findByIdentifier($identifier) {
		if (isset($this->newRoles[$identifier])) {
			return $this->newRoles[$identifier];
		}

		return parent::findByIdentifier($identifier);
	}

	/**
	 * Persists all entities managed by the repository and all cascading dependencies
	 *
	 * @return void
	 * @todo should be framework functionality and independent of the Doctrine EM
	 */
	public function persistEntities() {
		foreach ($this->entityManager->getUnitOfWork()->getIdentityMap() as $className => $entities) {
			if ($className === $this->entityClassName) {
				foreach ($entities as $entityToPersist) {
					$this->entityManager->flush($entityToPersist);
				}
				$this->emitRepositoryObjectsPersisted();
				break;
			}
		}
	}

	/**
	 * Signals that persistEntities() in this repository finished correctly.
	 *
	 * @Flow\Signal
	 * @return void
	 */
	protected function emitRepositoryObjectsPersisted() {}

}
