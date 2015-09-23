<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * A sample entity for tests
 *
 * @Flow\Entity
 */
class SubEntity extends SuperEntity
{
    /**
     * @var TestEntity
     * @ORM\OneToOne
     */
    protected $parentEntity;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $parentEntity
     * @return void
     */
    public function setParentEntity(\TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity $parentEntity)
    {
        $this->parentEntity = $parentEntity;
    }

    /**
     * @return \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\TestEntity
     */
    public function getParentEntity()
    {
        return $this->parentEntity;
    }
}
