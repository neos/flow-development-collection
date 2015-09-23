<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

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
    public function getSorting()
    {
        return $this->sorting;
    }

    /**
     * @param string $sorting
     */
    public function setSorting($sorting)
    {
        $this->sorting = $sorting;
    }

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EntityWithIndexedRelation $entityWithIndexedRelation
     */
    public function setEntityWithIndexedRelation($entityWithIndexedRelation)
    {
        $this->entityWithIndexedRelation = $entityWithIndexedRelation;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\EntityWithIndexedRelation
     */
    public function getEntityWithIndexedRelation()
    {
        return $this->entityWithIndexedRelation;
    }
}
