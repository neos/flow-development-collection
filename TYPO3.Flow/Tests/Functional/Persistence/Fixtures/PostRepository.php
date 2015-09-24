<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A repository for posts
 * @TYPO3\Flow\Annotations\Scope("singleton")
 */
class PostRepository extends \TYPO3\Flow\Persistence\Doctrine\Repository
{
    /**
     * @var string
     */
    const ENTITY_CLASSNAME = \TYPO3\Flow\Tests\Functional\Persistence\Fixtures\Post::class;
}
