<?php
namespace TYPO3\Flow\Tests\Object\Fixture;

/*                                                                        *
 * This script belongs to the Flow framework.                             *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the MIT license.                                          *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 */
class ClassWithSetterAndPropertyInjection
{
    /**
     * @var \TYPO3\Foo\Bar
     * @Flow\Inject
     */
    protected $firstDependency;

    /**
     * @var \TYPO3\Coffee\Bar
     * @Flow\Inject
     */
    protected $secondDependency;

    /**
     * @param \TYPO3\Flow\Object\ObjectManagerInterface
     */
    public function injectFirstDependency(\TYPO3\Flow\Object\ObjectManagerInterface $firstDependency)
    {
        $this->firstDependency = $firstDependency;
    }
}
