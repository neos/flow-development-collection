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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A test entity used by Entity with IndexedRelation
 *
 * @Flow\Scope("prototype")
 * @Flow\Entity
 */
class RelatedIndexEntity
{
    /**
     * @var string
     */
    protected $sorting;

    /**
     * @var EntityWithIndexedRelation
     * @ORM\ManyToOne
     */
    protected $entityWithIndexedRelation;

    /**
     * @return string
     */
    public function getSorting(): string
    {
        return $this->sorting;
    }

    /**
     * @param string $sorting
     */
    public function setSorting($sorting): void
    {
        $this->sorting = $sorting;
    }

    /**
     * @param EntityWithIndexedRelation $entityWithIndexedRelation
     */
    public function setEntityWithIndexedRelation($entityWithIndexedRelation): void
    {
        $this->entityWithIndexedRelation = $entityWithIndexedRelation;
    }

    /**
     * @return EntityWithIndexedRelation
     */
    public function getEntityWithIndexedRelation(): EntityWithIndexedRelation
    {
        return $this->entityWithIndexedRelation;
    }
}
