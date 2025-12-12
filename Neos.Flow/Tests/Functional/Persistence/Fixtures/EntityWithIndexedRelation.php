<?php
declare(strict_types=1);

namespace Neos\Flow\Tests\Functional\Persistence\Fixtures;

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
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class EntityWithIndexedRelation
{
    /**
     * @var Collection<AnnotatedIdentitiesEntity>
     * @ORM\ManyToMany(indexBy="author")
     */
    protected $annotatedIdentitiesEntities;

    /**
     * @var Collection<RelatedIndexEntity>
     * @ORM\OneToMany(indexBy="sorting", mappedBy="entityWithIndexedRelation")
     */
    protected $relatedIndexEntities;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->annotatedIdentitiesEntities = new ArrayCollection();
        $this->relatedIndexEntities = new ArrayCollection();
    }

    /**
     * @param Collection $annotatedIdentitiesEntities
     */
    public function setAnnotatedIdentitiesEntities($annotatedIdentitiesEntities): void
    {
        $this->annotatedIdentitiesEntities = $annotatedIdentitiesEntities;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getAnnotatedIdentitiesEntities(): ArrayCollection|Collection
    {
        return $this->annotatedIdentitiesEntities;
    }

    /**
     * @param Collection $relatedIndexEntities
     */
    public function setRelatedIndexEntities($relatedIndexEntities): void
    {
        $this->relatedIndexEntities = $relatedIndexEntities;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getRelatedIndexEntities(): ArrayCollection|Collection
    {
        return $this->relatedIndexEntities;
    }

    /**
     * @param string $sorting
     * @param RelatedIndexEntity $relatedIndexEntity
     */
    public function setRelatedIndexEntity($sorting, RelatedIndexEntity $relatedIndexEntity): void
    {
        $relatedIndexEntity->setSorting($sorting);
        $relatedIndexEntity->setEntityWithIndexedRelation($this);
        $this->relatedIndexEntities->set($sorting, $relatedIndexEntity);
    }
}
