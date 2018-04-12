<?php
namespace Neos\Flow\Tests\Functional\Security\Fixtures;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Query;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\Repository;
use Neos\Flow\Tests\Functional\Security\Fixtures;

/**
 * @Flow\Scope("singleton")
 */
class TestEntityCDoctrineRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = TestEntityC::class;

    /**
     * Returns all TestEntityC objects from persistence
     * @return array
     */
    public function findAllWithDql()
    {
        $query = $this->createDqlQuery('SELECT n FROM Neos\Flow\Tests\Functional\Security\Fixtures\TestEntityC n');
        return $query->getResult();
    }
}
