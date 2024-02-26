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

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;

/**
 * A simple entity for persistence tests of OneToOne relations.
 */
#[ORM\Table(name: 'persistence_attributes_onetooneentity2')]
#[Flow\Entity]
class OneToOneEntity2
{
    /**
     * Bidirectional relation inverse side
     */
    #[ORM\OneToOne(targetEntity: OneToOneEntity::class, mappedBy: 'bidirectionalRelation')]
    protected OneToOneEntity $bidirectionalRelation;
}
