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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A sample entity that has a property with an indexed relation
 */
#[Flow\Scope('prototype')]
#[Flow\Entity]
class EntityWithIndexedRelation
{
    #[ORM\ManyToMany(targetEntity: AnnotatedIdentitiesEntity::class, indexBy: 'author')]
    protected Collection $annotatedIdentitiesEntities;

    #[ORM\OneToMany(targetEntity: RelatedIndexEntity::class, indexBy: 'sorting', mappedBy: 'entityWithIndexedRelation')]
    protected Collection $relatedIndexEntities;

    public function __construct()
    {
        $this->annotatedIdentitiesEntities = new ArrayCollection();
        $this->relatedIndexEntities = new ArrayCollection();
    }

    public function setAnnotatedIdentitiesEntities(Collection $annotatedIdentitiesEntities)
    {
        $this->annotatedIdentitiesEntities = $annotatedIdentitiesEntities;
    }

    public function getAnnotatedIdentitiesEntities(): Collection
    {
        return $this->annotatedIdentitiesEntities;
    }

    public function setRelatedIndexEntities(Collection $relatedIndexEntities)
    {
        $this->relatedIndexEntities = $relatedIndexEntities;
    }

    public function getRelatedIndexEntities(): Collection
    {
        return $this->relatedIndexEntities;
    }

    public function setRelatedIndexEntity(string $sorting, RelatedIndexEntity $relatedIndexEntity)
    {
        $relatedIndexEntity->setSorting($sorting);
        $relatedIndexEntity->setEntityWithIndexedRelation($this);
        $this->relatedIndexEntities->set($sorting, $relatedIndexEntity);
    }
}
