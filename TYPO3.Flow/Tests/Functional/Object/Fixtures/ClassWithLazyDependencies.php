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
 * A class which has lazy dependencies
 */
class ClassWithLazyDependencies
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassA
     */
    public $lazyA;

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassB
     */
    public $lazyB;

    /**
     * @Flow\Inject(lazy = FALSE)
     * @var \TYPO3\Flow\Tests\Functional\Object\Fixtures\SingletonClassC
     */
    public $eagerC;
}
