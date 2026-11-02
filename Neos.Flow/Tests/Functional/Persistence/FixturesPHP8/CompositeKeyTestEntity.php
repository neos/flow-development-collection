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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A simple entity for persistence tests
 */
#[Flow\Entity]
#[ORM\Table(name: 'persistence_php8_compsitekeytestentity')]
class CompositeKeyTestEntity
{
    #[ORM\Id]
    #[ORM\Column(length: 20)]
    protected string $name = '';

    #[ORM\Id]
    #[ORM\ManyToOne]
    protected TestEntity $relatedEntity;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRelatedEntity(TestEntity $relatedEntity): void
    {
        $this->relatedEntity = $relatedEntity;
    }

    public function getRelatedEntity(): TestEntity
    {
        return $this->relatedEntity;
    }
}
