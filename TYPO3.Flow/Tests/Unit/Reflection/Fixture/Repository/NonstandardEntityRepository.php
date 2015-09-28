<?php
namespace TYPO3\Flow\Tests\Reflection\Fixture\Repository;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A repository claiming responsibility for a model that cannot be matched
 * to it via naming conventions.
 *
 * @Flow\Scope("singleton")
 */
class NonstandardEntityRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = 'TYPO3\Flow\Tests\Reflection\Fixture\Model\Entity';
}
