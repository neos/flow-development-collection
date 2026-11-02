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

use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Neos\Flow\Annotations as Flow;

/**
 * A SQL generator to create a condition matching nothing.
 */
class FalseConditionGenerator implements SqlGeneratorInterface
{
    /**
     * Returns an SQL query part that is basically a no-op in order to match no entity
     *
     * @param DoctrineSqlFilter $sqlFilter
     * @param ClassMetadata<object> $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias)
    {
        return ' (1=0) ';
    }
}
