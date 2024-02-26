<?php
namespace Neos\Flow\Tests\Functional\Persistence\Fixtures\Attributes;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A test entity used by Entity with IndexedRelation
 */
#[Flow\Scope('prototype')]
#[Flow\Entity]
class RelatedIndexEntity
{
    protected string $sorting;

    #[ORM\ManyToOne(targetEntity: EntityWithIndexedRelation::class)]
    protected EntityWithIndexedRelation $entityWithIndexedRelation;

    public function getSorting(): string
    {
        return $this->sorting;
    }

    public function setSorting(string $sorting): void
    {
        $this->sorting = $sorting;
    }

    public function getEntityWithIndexedRelation(): EntityWithIndexedRelation
    {
        return $this->entityWithIndexedRelation;
    }

    public function setEntityWithIndexedRelation(EntityWithIndexedRelation $entityWithIndexedRelation): void
    {
        $this->entityWithIndexedRelation = $entityWithIndexedRelation;
    }
}
