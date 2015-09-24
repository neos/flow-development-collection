<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Doctrine\Repository;

/**
 * @Flow\Scope("singleton")
 */
class RestrictableEntityDoctrineRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = \TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity::class;

    /**
     * Returns all RestrictableEntity objects from persistence
     * @return array
     */
    public function findAllWithDql()
    {
        $query = $this->createDqlQuery('SELECT n FROM TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity n WHERE n.name != \'Andi\'');
        return $query->getResult();
    }
}
