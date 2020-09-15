<?php
namespace Neos\Flow\Security\Authorization\Privilege\Entity\Doctrine;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use Neos\Flow\Annotations as Flow;

/**
 * Contract for a SQL condition generator.
 */
interface SqlGeneratorInterface
{
    /**
     * @param DoctrineSqlFilter $sqlFilter
     * @param ClassMetadata $targetEntity Metadata object for the target entity to create the constraint for
     * @param string $targetTableAlias The target table alias used in the current query
     * @return string
     */
    public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias);
}
