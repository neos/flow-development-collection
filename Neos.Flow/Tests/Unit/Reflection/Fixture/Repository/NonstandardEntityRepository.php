<?php
namespace Neos\Flow\Tests\Reflection\Fixture\Repository;

/*
 * This file is part of the Neos.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Tests\Reflection\Fixture\Model\Entity;

/**
 * A repository claiming responsibility for a model that cannot be matched
 * to it via naming conventions.
 *
 * @Flow\Scope("singleton")
 */
class NonstandardEntityRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = Entity::class;
}
