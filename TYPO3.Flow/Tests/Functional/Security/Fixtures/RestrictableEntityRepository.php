<?php
namespace TYPO3\Flow\Tests\Functional\Security\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A repository for comments
 * @TYPO3\Flow\Annotations\Scope("singleton")
 */
class RestrictableEntityRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = 'TYPO3\Flow\Tests\Functional\Security\Fixtures\RestrictableEntity';
}
