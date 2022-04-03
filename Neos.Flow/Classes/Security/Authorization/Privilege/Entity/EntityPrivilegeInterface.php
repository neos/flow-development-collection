<?php
namespace Neos\Flow\Security\Authorization\Privilege\Entity;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Persistence\Mapping\ClassMetadata;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Authorization\Privilege\PrivilegeInterface;

/**
 * An entity privilege
 *
 * This privilege is capable of filtering entities retrieved from
 * the persistence layer and of blocking creation, update and delete
 * operations on them. Read access is usually controlled by rewriting
 * SQL queries, other operations are blocked by throwing an exception.
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
