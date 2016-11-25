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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Neos\Flow\Tests\Functional\Security\Fixtures;

/**
 * A repository for restrictable entities
 * @Flow\Scope("singleton")
 */
class RestrictableEntityRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = RestrictableEntity::class;
}
