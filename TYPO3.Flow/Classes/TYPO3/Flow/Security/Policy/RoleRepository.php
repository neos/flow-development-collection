<?php
namespace TYPO3\Flow\Security\Policy;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * The repository for roles
 *
 * @Flow\Scope("singleton")
 */
class RoleRepository extends \TYPO3\Flow\Persistence\Repository
{
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
    public function add($role)
    {
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
    public function remove($role)
    {
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
    public function findByIdentifier($identifier)
    {
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
    public function persistEntities()
    {
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
    protected function emitRepositoryObjectsPersisted()
    {
    }
}
