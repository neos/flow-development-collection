<?php
namespace TYPO3\Flow\Security\Authorization\Privilege\Entity;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * An entity privilege
 *
 * This privilege is capable of filtering entities retrieved from
 * the persistence layer. Usually by rewriting SQL queries.
 */
interface EntityPrivilegeInterface extends PrivilegeInterface
{
    /**
     * @param $entityType
     * @return bool
     */
    public function matchesEntityType($entityType);

    /**
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function getSqlConstraint(ClassMetadata $targetEntity, $targetTableAlias);
}
