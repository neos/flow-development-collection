<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture\Repository;

/*
 * This file is part of the TYPO3.Flow package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\Repository;
use TYPO3\Flow\Tests\Reflection\Fixture\Model\Entity;

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
