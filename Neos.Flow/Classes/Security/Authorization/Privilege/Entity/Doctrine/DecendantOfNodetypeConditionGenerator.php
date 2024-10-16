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

use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter as DoctrineSqlFilter;
use Neos\Flow\Annotations as Flow;

/**
 * A SQL generator to create a condition matching anything.
 */
class DecendantOfNodetypeConditionGenerator implements SqlGeneratorInterface
{
    private array $nodetypes;

    /**
     * @param array $nodetypes
     */
    public function __construct(array $nodetypes)
    {
        $this->nodetypes = $nodetypes;
    }

    /**
     * Returns an SQL query part that is basically a no-op in order to match any entity
     *
     * @param DoctrineSqlFilter $sqlFilter
     * @param ClassMetadata $targetEntity
     * @param string $targetTableAlias
     * @return string
     */
    public function getSql(DoctrineSqlFilter $sqlFilter, ClassMetadata $targetEntity, $targetTableAlias)
    {
        $nodetypeList = implode("','", $this->nodetypes);

        return "select * from public.neos_contentrepository_domain_model_nodedata n1
        JOIN public.neos_contentrepository_domain_model_nodedata n2 ON n1.path LIKE CONCAT('%', n2.path, '%')
        WHERE n2.nodetype in ('" . $nodetypeList . "')";
    }
}
