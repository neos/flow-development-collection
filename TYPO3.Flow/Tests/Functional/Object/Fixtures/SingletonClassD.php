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
class SingletonClassD
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassC
     */
    public $prototypeClassC;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\PrototypeClassAishInterface
     */
    public $prototypeClassA;
}
