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
 * A simple entity for persistence tests of OneToOne relations.
 */
#[Flow\Entity]
#[ORM\Table(name: 'persistence_php8_onetooneentity')]
class OneToOneEntity
{
    /**
     * Self-referencing
     */
    #[ORM\OneToOne]
    protected OneToOneEntity $selfReferencing;

    /**
     * Bidirectional relation owning side
     */
    #[ORM\OneToOne(inversedBy: 'bidirectionalRelation')]
    protected OneToOneEntity2 $bidirectionalRelation;

    /**
     * Unidirectional relation
     */
    #[ORM\OneToOne]
    protected OneToOneEntity2 $unidirectionalRelation;
}
