<?php
namespace TYPO3\Flow\Tests\Functional\Persistence\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

/**
 * A repository for the test entities
 * @TYPO3\Flow\Annotations\Scope("singleton")
 */
class TestEntityRepository extends \TYPO3\Flow\Persistence\Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array('name' => \TYPO3\Flow\Persistence\QueryInterface::ORDER_ASCENDING);
}
