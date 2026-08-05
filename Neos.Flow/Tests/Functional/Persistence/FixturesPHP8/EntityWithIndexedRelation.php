<?php
namespace Neos\Flow\Tests\Functional\Persistence\FixturesPHP8;

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
    /**
     * @var Collection<AnnotatedIdentitiesEntity>
     */
    #[ORM\ManyToMany(indexBy: 'author')]
    protected Collection $annotatedIdentitiesEntities;

    /**
     * @var Collection<RelatedIndexEntity>
     * @ORM\OneToMany(indexBy="sorting", mappedBy="entityWithIndexedRelation")
     */
    #[ORM\OneToMany(indexBy: 'sorting', mappedBy: 'entityWithIndexedRelation')]
    protected Collection $relatedIndexEntities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->annotatedIdentitiesEntities = new ArrayCollection();
        $this->relatedIndexEntities = new ArrayCollection();
    }

    /**
     * @param Collection<AnnotatedIdentitiesEntity> $annotatedIdentitiesEntities
     */
    public function setAnnotatedIdentitiesEntities(Collection $annotatedIdentitiesEntities): void
    {
        $this->annotatedIdentitiesEntities = $annotatedIdentitiesEntities;
    }

    /**
     * @return Collection<AnnotatedIdentitiesEntity>
     */
    public function getAnnotatedIdentitiesEntities(): Collection
    {
        return $this->annotatedIdentitiesEntities;
    }

    /**
     * @param Collection<RelatedIndexEntity> $relatedIndexEntities
     */
    public function setRelatedIndexEntities(Collection $relatedIndexEntities): void
    {
        $this->relatedIndexEntities = $relatedIndexEntities;
    }

    /**
     * @return Collection<RelatedIndexEntity>
     */
    public function getRelatedIndexEntities(): Collection
    {
        return $this->relatedIndexEntities;
    }

    public function setRelatedIndexEntity(string $sorting, RelatedIndexEntity $relatedIndexEntity): void
    {
        $relatedIndexEntity->setSorting($sorting);
        $relatedIndexEntity->setEntityWithIndexedRelation($this);
        $this->relatedIndexEntities->set($sorting, $relatedIndexEntity);
    }
}
