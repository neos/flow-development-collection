<?php
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

use Neos\Flow\Persistence\Repository;
use Neos\Flow\Annotations as Flow;

/**
 * A repository for SuperEntity and SubEntity
 * @Flow\Scope("singleton")
 */
class SuperEntityRepository extends Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = SuperEntity::class;
}
