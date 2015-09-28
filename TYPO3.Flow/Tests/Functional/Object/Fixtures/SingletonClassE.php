<?php
namespace TYPO3\Flow\Tests\Functional\Object\Fixtures;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * A class of scope singleton
 *
 * @Flow\Scope("singleton")
 */
class SingletonClassE
{
    /**
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
     */
    protected $objectB;

    /**
     * @param \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB
     */
    public function __construct(\TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB $objectB)
    {
        $this->objectB = $objectB;
    }
}
